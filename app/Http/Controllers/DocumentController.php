<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Models\Employee;
use App\Models\Project;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;

class DocumentController extends Controller
{
    public function index(Request $request)
    {
        $category = $request->input('category');
        $search = $request->input('search');

        $query = Document::with(['employee', 'project', 'uploader']);

        if ($category) {
            $query->where('category', $category);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('document_number', 'like', "%{$search}%");
            });
        }

        $documents = $query->latest()->paginate(12)->withQueryString();
        $employees = Employee::where('status', 'active')->select('id', 'full_name', 'employee_code')->get();
        $projects = Project::select('id', 'name', 'code')->get();

        $stats = [
            'total' => Document::count(),
            'categories' => Document::query()
                ->selectRaw('category, COUNT(*) as total')
                ->groupBy('category')
                ->pluck('total', 'category'),
        ];

        return Inertia::render('Documents/Index', [
            'documents' => $documents,
            'employees' => $employees,
            'projects' => $projects,
            'stats' => $stats,
            'categories' => collect(config('document_categories', []))
                ->map(fn ($description, $category) => [
                    'value' => $category,
                    'label' => $category,
                    'description' => $description,
                ])
                ->values(),
            'filters' => $request->only(['category', 'search']),
        ]);
    }

    public function store(Request $request)
    {
        if ($this->requestExceedsPostMaxSize($request)) {
            throw ValidationException::withMessages([
                'file' => 'PHP rejected the request because it exceeds post_max_size. In cPanel PHP settings for this domain, set post_max_size to 24M and upload_max_filesize to 20M, then retry.',
            ]);
        }

        $file = $request->file('file');
        if ($file instanceof UploadedFile && ! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => $this->uploadErrorMessage($file->getError()),
            ]);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(array_keys(config('document_categories', [])))],
            'employee_id' => ['nullable', 'exists:employees,id'],
            'project_id' => ['nullable', 'exists:projects,id'],
            'issue_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,xls,xlsx,csv,jpg,jpeg,png,gif,webp', 'max:10240'],
        ], [
            'file.uploaded' => 'The upload was interrupted or exceeded the server upload limit. Please retry; if it continues, contact the administrator.',
            'file.max' => 'The file must not be larger than 10 MB.',
        ]);

        $file = $request->file('file');
        $fileName = $file->getClientOriginalName();
        $fileType = $file->getClientOriginalExtension();
        $fileSizeKb = round($file->getSize() / 1024);

        $path = $file->store("documents/{$validated['category']}", 'public');
        if ($path === false) {
            Log::error('Failed to store uploaded document.', [
                'category' => $validated['category'],
                'user_id' => auth()->id(),
            ]);

            throw ValidationException::withMessages([
                'file' => 'The server could not save this file. Please retry or contact the administrator.',
            ]);
        }

        $lastId = Document::max('id') ?? 0;
        $docNumber = 'GSK-DOC-' . date('Y') . '-' . str_pad($lastId + 1, 3, '0', STR_PAD_LEFT);

        $document = Document::create([
            'document_number' => $docNumber,
            'title' => $validated['title'],
            'category' => $validated['category'],
            'employee_id' => $validated['employee_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'file_path' => $path,
            'file_name' => $fileName,
            'file_type' => $fileType,
            'file_size_kb' => $fileSizeKb,
            'issue_date' => $validated['issue_date'] ?? null,
            'expiry_date' => $validated['expiry_date'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'uploaded_by' => auth()->id(),
        ]);

        AuditLog::log('create', 'documents', $document->id, "Uploaded document: {$document->title} ({$docNumber})");

        return back()->with('success', 'Document archived successfully.');
    }

    private function uploadErrorMessage(int $error): string
    {
        return match ($error) {
            UPLOAD_ERR_INI_SIZE => 'PHP rejected this file because it exceeds upload_max_filesize. Set upload_max_filesize to 20M and post_max_size to 24M in cPanel PHP settings for this domain, then retry.',
            UPLOAD_ERR_FORM_SIZE => 'The selected file exceeds the 10 MB application limit.',
            UPLOAD_ERR_PARTIAL => 'The upload was interrupted before it completed. Check your connection and retry.',
            UPLOAD_ERR_NO_TMP_DIR, UPLOAD_ERR_CANT_WRITE => 'The server could not save the temporary upload. Contact your hosting provider to check PHP temporary-directory permissions.',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload. Contact your hosting provider.',
            default => 'The file could not be uploaded. Please select it again and retry.',
        };
    }

    private function requestExceedsPostMaxSize(Request $request): bool
    {
        $postMaxBytes = $this->iniSizeToBytes(ini_get('post_max_size') ?: '0');
        $contentLength = (int) $request->server('CONTENT_LENGTH', 0);

        return $postMaxBytes > 0 && $contentLength > $postMaxBytes && $request->all() === [];
    }

    private function iniSizeToBytes(string $size): int
    {
        $value = (int) $size;
        $unit = strtolower(substr(trim($size), -1));

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }

    public function download(Document $document)
    {
        AuditLog::log('export', 'documents', $document->id, "Downloaded document: {$document->title}");
        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function preview(Document $document)
    {
        $disk = Storage::disk('public');

        if (!$disk->exists($document->file_path)) {
            abort(404, 'The document file could not be found.');
        }

        $mimeType = $disk->mimeType($document->file_path);
        $previewableMimeTypes = [
            'application/pdf',
            'image/jpeg',
            'image/png',
            'image/gif',
            'image/webp',
        ];

        if (!in_array($mimeType, $previewableMimeTypes, true)) {
            abort(415, 'Inline preview is available only for PDFs and images.');
        }

        return $disk->response(
            $document->file_path,
            $document->file_name,
            [
                'Content-Type' => $mimeType,
                'X-Content-Type-Options' => 'nosniff',
            ],
            'inline',
        );
    }

    public function destroy(Document $document)
    {
        $title = $document->title;
        Storage::disk('public')->delete($document->file_path);
        $document->delete();

        AuditLog::log('delete', 'documents', null, "Deleted document: {$title}");

        return back()->with('success', 'Document deleted.');
    }
}

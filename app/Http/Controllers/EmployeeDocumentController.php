<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use App\Models\GeneratedEmployeeDocument;
use App\Models\AuditLog;
use App\Services\DocumentGenerationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class EmployeeDocumentController extends Controller
{
    protected DocumentGenerationService $docService;

    public function __construct(DocumentGenerationService $docService)
    {
        $this->docService = $docService;
    }

    /**
     * Download the generated PDF document.
     */
    public function download(GeneratedEmployeeDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            // Re-generate if missing on disk
            $employee = $document->employee;
            if ($document->document_type === 'employment_bond') {
                $document = $this->docService->generateEmploymentBondPdf($employee, $document->template);
            } else {
                $document = $this->docService->generateRegistrationFormPdf($employee, $document->template);
            }
        }

        AuditLog::log('download', 'documents', $document->id, "Downloaded PDF: {$document->document_number} for {$document->employee->full_name}");

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    /**
     * Stream PDF inline for browser viewing / print.
     */
    public function preview(GeneratedEmployeeDocument $document)
    {
        if (!Storage::disk('public')->exists($document->file_path)) {
            $employee = $document->employee;
            if ($document->document_type === 'employment_bond') {
                $document = $this->docService->generateEmploymentBondPdf($employee, $document->template);
            } else {
                $document = $this->docService->generateRegistrationFormPdf($employee, $document->template);
            }
        }

        $filePath = Storage::disk('public')->path($document->file_path);

        AuditLog::log('preview', 'documents', $document->id, "Previewed PDF: {$document->document_number} for {$document->employee->full_name}");

        return response()->file($filePath, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $document->file_name . '"',
        ]);
    }

    /**
     * Regenerate document using latest active template.
     */
    public function regenerate(Request $request, Employee $employee, string $type)
    {
        $customData = $request->only([
            'probation_period',
            'reporting_manager',
            'work_location',
            'working_schedule',
            'payment_method',
            'highest_qualification',
            'institution',
            'technical_skills',
            'previous_experience',
            'previous_organization',
            'certifications',
            'portfolio',
            'primary_responsibilities',
            'secondary_responsibilities',
            'assigned_projects',
            'performance_targets',
            'reporting_requirements',
            'authorized_system_access',
            'agreement_date',
        ]);

        if ($type === 'employment_bond') {
            $doc = $this->docService->generateEmploymentBondPdf($employee, null, $customData);
            $msg = 'Employment Bond Agreement regenerated successfully.';
        } elseif ($type === 'registration_form') {
            $doc = $this->docService->generateRegistrationFormPdf($employee, null, $customData);
            $msg = 'Employee Registration Form regenerated successfully.';
        } else {
            $this->docService->generateAllDocumentsForEmployee($employee, $customData);
            $msg = 'All official documents regenerated successfully.';
        }

        return back()->with('success', $msg);
    }

    /**
     * Update physical document handling status (printed, signed, archived).
     */
    public function updateStatus(Request $request, GeneratedEmployeeDocument $document)
    {
        $validated = $request->validate([
            'status' => ['required', 'string', 'in:generated,printed,signed,archived'],
            'physical_location' => ['nullable', 'string', 'max:255'],
            'received_by_hr' => ['nullable', 'string', 'max:150'],
            'hr_remarks' => ['nullable', 'string'],
            'printed_at' => ['nullable', 'date'],
            'signed_at' => ['nullable', 'date'],
            'archived_at' => ['nullable', 'date'],
        ]);

        // Auto-stamp timestamps if status changes and timestamp was not provided
        if ($validated['status'] === 'printed' && empty($validated['printed_at'])) {
            $validated['printed_at'] = now();
        }
        if ($validated['status'] === 'signed' && empty($validated['signed_at'])) {
            $validated['signed_at'] = now();
        }
        if ($validated['status'] === 'archived' && empty($validated['archived_at'])) {
            $validated['archived_at'] = now();
        }

        $document->update($validated);

        AuditLog::log(
            'update_status',
            'documents',
            $document->id,
            "Updated document status to '" . strtoupper($document->status) . "' for {$document->document_number} ({$document->employee->full_name})"
        );

        return back()->with('success', "Document status updated to " . strtoupper($document->status) . ".");
    }

    /**
     * Update any document detail, text, or physical filing status, and immediately regenerate the PDF.
     */
    public function updateDetails(Request $request, GeneratedEmployeeDocument $document)
    {
        $validated = $request->validate([
            // Physical filing fields
            'status' => ['nullable', 'string', 'in:generated,printed,signed,archived'],
            'physical_location' => ['nullable', 'string', 'max:255'],
            'received_by_hr' => ['nullable', 'string', 'max:150'],
            'hr_remarks' => ['nullable', 'string'],
            'printed_at' => ['nullable', 'date'],
            'signed_at' => ['nullable', 'date'],
            'archived_at' => ['nullable', 'date'],

            // Document text and details
            'full_name' => ['nullable', 'string', 'max:150'],
            'employee_id' => ['nullable', 'string', 'max:50'],
            'date_of_birth' => ['nullable', 'string', 'max:50'],
            'gender' => ['nullable', 'string', 'max:30'],
            'citizenship_number' => ['nullable', 'string', 'max:100'],
            'permanent_address' => ['nullable', 'string', 'max:255'],
            'current_address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'string', 'max:100'],
            'emergency_contact_name' => ['nullable', 'string', 'max:100'],
            'emergency_contact_phone' => ['nullable', 'string', 'max:50'],
            'emergency_contact_relation' => ['nullable', 'string', 'max:50'],
            'highest_qualification' => ['nullable', 'string', 'max:150'],
            'institution' => ['nullable', 'string', 'max:150'],
            'technical_skills' => ['nullable', 'string', 'max:255'],
            'previous_experience' => ['nullable', 'string', 'max:100'],
            'previous_organization' => ['nullable', 'string', 'max:150'],
            'certifications' => ['nullable', 'string', 'max:255'],
            'portfolio' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:100'],
            'designation' => ['nullable', 'string', 'max:100'],
            'employment_type' => ['nullable', 'string', 'max:50'],
            'joining_date' => ['nullable', 'string', 'max:50'],
            'probation_period' => ['nullable', 'string', 'max:50'],
            'reporting_manager' => ['nullable', 'string', 'max:100'],
            'work_location' => ['nullable', 'string', 'max:100'],
            'working_schedule' => ['nullable', 'string', 'max:100'],
            'salary' => ['nullable', 'string', 'max:100'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'primary_responsibilities' => ['nullable', 'string'],
            'secondary_responsibilities' => ['nullable', 'string'],
            'assigned_projects' => ['nullable', 'string'],
            'performance_targets' => ['nullable', 'string'],
            'reporting_requirements' => ['nullable', 'string'],
            'authorized_system_access' => ['nullable', 'string'],
            'department_head' => ['nullable', 'string', 'max:100'],
            'hr_representative_name' => ['nullable', 'string', 'max:100'],
            'registration_date' => ['nullable', 'string', 'max:50'],
            'agreement_number' => ['nullable', 'string', 'max:50'],
            'agreement_date' => ['nullable', 'string', 'max:50'],
            'authorized_representative' => ['nullable', 'string', 'max:100'],
            'company_address' => ['nullable', 'string', 'max:255'],
        ]);

        $employee = $document->employee;

        // Custom data array to pass to generation service
        $customData = array_filter($validated, function ($val) {
            return $val !== null && $val !== '';
        });

        // Determine updated status
        $newStatus = $validated['status'] ?? $document->status;
        $printedAt = $validated['printed_at'] ?? $document->printed_at;
        $signedAt = $validated['signed_at'] ?? $document->signed_at;
        $archivedAt = $validated['archived_at'] ?? $document->archived_at;

        if ($newStatus === 'printed' && empty($printedAt)) {
            $printedAt = now();
        }
        if ($newStatus === 'signed' && empty($signedAt)) {
            $signedAt = now();
        }
        if ($newStatus === 'archived' && empty($archivedAt)) {
            $archivedAt = now();
        }

        // Regenerate PDF with custom details
        if ($document->document_type === 'employment_bond') {
            $updatedDoc = $this->docService->generateEmploymentBondPdf($employee, $document->template, $customData);
        } else {
            $updatedDoc = $this->docService->generateRegistrationFormPdf($employee, $document->template, $customData);
        }

        // Apply physical location and status metadata to the regenerated document
        $updatedDoc->update([
            'status' => $newStatus,
            'physical_location' => $validated['physical_location'] ?? $document->physical_location,
            'received_by_hr' => $validated['received_by_hr'] ?? $document->received_by_hr,
            'hr_remarks' => $validated['hr_remarks'] ?? $document->hr_remarks,
            'printed_at' => $printedAt,
            'signed_at' => $signedAt,
            'archived_at' => $archivedAt,
        ]);

        AuditLog::log(
            'update_details',
            'documents',
            $updatedDoc->id,
            "Updated document details and regenerated PDF for {$updatedDoc->document_number} ({$employee->full_name})"
        );

        return back()->with('success', 'Document details and physical filing information updated, and PDF successfully regenerated!');
    }

    /**
     * Upload scanned copy of physically signed document for digital backup.
     */
    public function uploadScannedCopy(Request $request, GeneratedEmployeeDocument $document)
    {
        $request->validate([
            'scanned_copy' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'], // up to 10MB
        ]);

        if ($request->hasFile('scanned_copy')) {
            if ($document->scanned_copy_path && Storage::disk('public')->exists($document->scanned_copy_path)) {
                Storage::disk('public')->delete($document->scanned_copy_path);
            }

            $path = $request->file('scanned_copy')->store('scanned_documents/' . $document->employee_id, 'public');
            $document->update([
                'scanned_copy_path' => $path,
                'status' => 'signed',
                'signed_at' => $document->signed_at ?: now(),
            ]);

            AuditLog::log('upload', 'documents', $document->id, "Uploaded signed physical copy for document {$document->document_number}");

            return back()->with('success', 'Scanned signed copy uploaded successfully. Status updated to SIGNED.');
        }

        return back()->with('error', 'No file uploaded.');
    }
}

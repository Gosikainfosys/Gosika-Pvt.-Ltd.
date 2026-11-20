<?php

namespace App\Http\Controllers;

use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\Employee;
use App\Models\CompanySetting;
use App\Models\AuditLog;
use App\Services\DocumentGenerationService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class DocumentTemplateController extends Controller
{
    protected DocumentGenerationService $docService;

    public function __construct(DocumentGenerationService $docService)
    {
        $this->docService = $docService;
    }

    /**
     * List all document templates.
     */
    public function index()
    {
        $this->docService->ensureDefaultTemplates();

        $templates = DocumentTemplate::withCount(['versions', 'generatedDocuments'])
            ->with(['updater:id,name', 'creator:id,name'])
            ->orderBy('id', 'asc')
            ->get();

        return Inertia::render('Documents/Templates/Index', [
            'templates' => $templates,
        ]);
    }

    /**
     * Create a new document template.
     */
    public function create()
    {
        $variables = $this->docService->getAvailableVariables();
        $defaultDesign = DocumentTemplate::defaultDesignConfig();

        return Inertia::render('Documents/Templates/Create', [
            'availableVariables' => $variables,
            'defaultDesign' => $defaultDesign,
        ]);
    }

    /**
     * Store new document template.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'string', 'in:registration_form,employment_bond,general'],
            'description' => ['nullable', 'string'],
            'design_config' => ['nullable', 'array'],
            'sections_data' => ['required', 'array'],
        ]);

        $code = Str::slug($validated['name'], '_');
        $baseCode = $code;
        $counter = 1;
        while (DocumentTemplate::where('code', $code)->exists()) {
            $code = $baseCode . '_' . $counter++;
        }

        $template = DocumentTemplate::create([
            'code' => $code,
            'name' => $validated['name'],
            'type' => $validated['type'],
            'description' => $validated['description'] ?? null,
            'is_active' => true,
            'current_version' => 1,
            'design_config' => $validated['design_config'] ?? DocumentTemplate::defaultDesignConfig(),
            'sections_data' => $validated['sections_data'],
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        DocumentTemplateVersion::create([
            'document_template_id' => $template->id,
            'version_number' => 1,
            'design_config' => $template->design_config,
            'sections_data' => $template->sections_data,
            'change_summary' => 'Initial template creation',
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('create', 'document_templates', $template->id, "Created new document template: {$template->name}");

        return redirect()->route('document-templates.edit', $template->id)->with('success', 'Document template created successfully.');
    }

    /**
     * Edit template: text, fields, clauses, branding, and live A4 preview.
     */
    public function edit(DocumentTemplate $document_template)
    {
        $document_template->load(['versions.creator:id,name']);

        $availableVariables = $this->docService->getAvailableVariables();
        $employees = Employee::select('id', 'employee_code', 'full_name', 'email')->latest('id')->take(20)->get();

        return Inertia::render('Documents/Templates/Edit', [
            'template' => $document_template,
            'availableVariables' => $availableVariables,
            'employees' => $employees,
            'company' => CompanySetting::current(),
        ]);
    }

    /**
     * Update template and create a new version record.
     */
    public function update(Request $request, DocumentTemplate $document_template)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
            'design_config' => ['required', 'array'],
            'sections_data' => ['required', 'array'],
            'change_summary' => ['nullable', 'string', 'max:500'],
        ]);

        $newVersionNumber = $document_template->current_version + 1;

        $document_template->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? $document_template->description,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : $document_template->is_active,
            'current_version' => $newVersionNumber,
            'design_config' => $validated['design_config'],
            'sections_data' => $validated['sections_data'],
            'updated_by' => auth()->id(),
        ]);

        DocumentTemplateVersion::create([
            'document_template_id' => $document_template->id,
            'version_number' => $newVersionNumber,
            'design_config' => $validated['design_config'],
            'sections_data' => $validated['sections_data'],
            'change_summary' => $validated['change_summary'] ?: "Updated template configuration to version {$newVersionNumber}",
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('update', 'document_templates', $document_template->id, "Published new version {$newVersionNumber} for template: {$document_template->name}");

        return back()->with('success', "Template successfully updated and published as Version {$newVersionNumber}.");
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(DocumentTemplate $document_template)
    {
        $newName = $document_template->name . ' (Copy)';
        $newCode = Str::slug($newName, '_');
        $baseCode = $newCode;
        $counter = 1;
        while (DocumentTemplate::where('code', $newCode)->exists()) {
            $newCode = $baseCode . '_' . $counter++;
        }

        $newTemplate = DocumentTemplate::create([
            'code' => $newCode,
            'name' => $newName,
            'type' => $document_template->type,
            'description' => $document_template->description,
            'is_active' => false,
            'current_version' => 1,
            'design_config' => $document_template->design_config,
            'sections_data' => $document_template->sections_data,
            'created_by' => auth()->id(),
            'updated_by' => auth()->id(),
        ]);

        DocumentTemplateVersion::create([
            'document_template_id' => $newTemplate->id,
            'version_number' => 1,
            'design_config' => $newTemplate->design_config,
            'sections_data' => $newTemplate->sections_data,
            'change_summary' => "Duplicated from {$document_template->name} v{$document_template->current_version}",
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('duplicate', 'document_templates', $newTemplate->id, "Duplicated template {$document_template->name} to {$newName}");

        return redirect()->route('document-templates.edit', $newTemplate->id)->with('success', 'Template duplicated successfully.');
    }

    /**
     * Toggle active/inactive status.
     */
    public function toggleStatus(DocumentTemplate $document_template)
    {
        $document_template->update([
            'is_active' => !$document_template->is_active,
            'updated_by' => auth()->id(),
        ]);

        $statusStr = $document_template->is_active ? 'Activated' : 'Deactivated';
        AuditLog::log('status_change', 'document_templates', $document_template->id, "{$statusStr} template {$document_template->name}");

        return back()->with('success', "Template has been {$statusStr}.");
    }

    /**
     * Show version history.
     */
    public function versions(DocumentTemplate $document_template)
    {
        $versions = $document_template->versions()
            ->with('creator:id,name')
            ->orderBy('version_number', 'desc')
            ->get();

        return Inertia::render('Documents/Templates/Versions', [
            'template' => $document_template,
            'versions' => $versions,
        ]);
    }

    /**
     * Restore previous version.
     */
    public function restoreVersion(DocumentTemplate $document_template, DocumentTemplateVersion $version)
    {
        $newVersionNumber = $document_template->current_version + 1;

        $document_template->update([
            'current_version' => $newVersionNumber,
            'design_config' => $version->design_config,
            'sections_data' => $version->sections_data,
            'updated_by' => auth()->id(),
        ]);

        DocumentTemplateVersion::create([
            'document_template_id' => $document_template->id,
            'version_number' => $newVersionNumber,
            'design_config' => $version->design_config,
            'sections_data' => $version->sections_data,
            'change_summary' => "Restored content from Version {$version->version_number}",
            'created_by' => auth()->id(),
        ]);

        AuditLog::log('restore_version', 'document_templates', $document_template->id, "Restored template {$document_template->name} to version {$version->version_number} (published as v{$newVersionNumber})");

        return redirect()->route('document-templates.edit', $document_template->id)->with('success', "Version {$version->version_number} restored and published as Version {$newVersionNumber}.");
    }

    /**
     * Live A4 preview generator for editor testing.
     */
    public function renderLivePdf(Request $request, DocumentTemplate $document_template)
    {
        return $this->renderPdfPreview($request, $document_template);
    }

    public function renderDraftPdf(Request $request, DocumentTemplate $document_template)
    {
        $draft = $request->validate([
            'design_config' => ['required', 'array'],
            'sections_data' => ['required', 'array'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
        ]);

        return $this->renderPdfPreview($request, $document_template, $draft);
    }

    private function renderPdfPreview(Request $request, DocumentTemplate $document_template, array $draft = [])
    {
        $employeeId = $draft['employee_id'] ?? $request->query('employee_id');
        $employee = $employeeId ? Employee::find($employeeId) : Employee::first();

        if (!$employee) {
            // Virtual mock employee for preview if none in database
            $employee = new Employee([
                'id' => 1,
                'employee_code' => 'GOS-EMP-0001',
                'first_name' => 'Sital',
                'last_name' => 'Mahato',
                'full_name' => 'Sital Mahato',
                'email' => 'sitalmahato00@gmail.com',
                'phone' => '9841234567',
                'gender' => 'male',
                'date_of_birth' => '2000-05-15',
                'permanent_address' => 'Kathmandu, Nepal',
                'current_address' => 'Kathmandu, Nepal',
                'employment_type' => 'full_time',
                'joining_date' => '2026-11-01',
                'basic_salary' => 60000,
                'emergency_contact_name' => 'Ramesh Mahato',
                'emergency_contact_phone' => '9812345678',
                'emergency_contact_relation' => 'Brother',
                'status' => 'active',
                'notes' => 'Citizenship: 1234567890123',
            ]);
        }

        $variables = $this->docService->compileVariables($employee);
        $designConfig = $draft['design_config'] ?? $document_template->design_config;
        $sectionsData = $draft['sections_data'] ?? $document_template->sections_data;
        $design = array_merge(DocumentTemplate::defaultDesignConfig(), $designConfig ?: []);
        $sections = $this->docService->interpolateArray($sectionsData ?: [], $variables);

        $cleanVars = [];
        foreach ($variables as $k => $val) {
            $trimmed = trim($k, '{}');
            $cleanVars[$trimmed] = $val;
            $cleanVars[str_replace('.', '_', $trimmed)] = $val;
        }

        $codeNum = '0001';
        $docNumber = ($document_template->type === 'employment_bond' ? 'GOS-BOND-' : 'GOS-REG-') . $codeNum . '-V' . $document_template->current_version;

        $viewData = [
            'employee' => $employee,
            'template' => $document_template,
            'design' => $design,
            'sections' => $sections,
            'v' => $cleanVars,
            'docNumber' => $docNumber,
            'agreementNumber' => 'GOS-BOND-' . $codeNum,
            'company' => CompanySetting::current(),
            'logoBase64' => $this->docService->getLogoBase64(),
        ];

        $viewName = $document_template->type === 'employment_bond'
            ? 'pdf.documents.employment_bond'
            : 'pdf.documents.registration_form';

        $pdf = app(\App\Services\PdfDocumentService::class)->render($viewName, $viewData, 'a4', 'portrait');

        return response($pdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="preview_' . $document_template->code . '.pdf"',
        ]);
    }

    /**
     * Delete template.
     */
    public function destroy(DocumentTemplate $document_template)
    {
        $name = $document_template->name;
        $document_template->delete();

        AuditLog::log('delete', 'document_templates', $document_template->id, "Archived document template: {$name}");

        return redirect()->route('document-templates.index')->with('success', "Template '{$name}' archived.");
    }
}

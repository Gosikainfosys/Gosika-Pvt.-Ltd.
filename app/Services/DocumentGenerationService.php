<?php

namespace App\Services;

use App\Models\Employee;
use App\Models\DocumentTemplate;
use App\Models\DocumentTemplateVersion;
use App\Models\GeneratedEmployeeDocument;
use App\Models\AuditLog;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Carbon\Carbon;

class DocumentGenerationService
{
    protected PdfDocumentService $pdfService;
    protected CorporateReportSettingsService $corporateSettings;

    public function __construct(
        PdfDocumentService $pdfService,
        CorporateReportSettingsService $corporateSettings
    ) {
        $this->pdfService = $pdfService;
        $this->corporateSettings = $corporateSettings;
    }

    /**
     * Get list of all available dynamic variables with categories and descriptions.
     */
    public function getAvailableVariables(): array
    {
        return [
            'Employee Variables' => [
                ['var' => '{{employee.id}}', 'desc' => 'Employee ID (e.g. GSK-EMP-0001)'],
                ['var' => '{{employee.full_name}}', 'desc' => 'Full legal name of employee'],
                ['var' => '{{employee.first_name}}', 'desc' => 'First name'],
                ['var' => '{{employee.last_name}}', 'desc' => 'Last name'],
                ['var' => '{{employee.email}}', 'desc' => 'Work or official email'],
                ['var' => '{{employee.phone}}', 'desc' => 'Primary contact phone number'],
                ['var' => '{{employee.gender}}', 'desc' => 'Gender (Male, Female, Other)'],
                ['var' => '{{employee.date_of_birth}}', 'desc' => 'Date of birth (DD-MM-YYYY)'],
                ['var' => '{{employee.citizenship_number}}', 'desc' => 'Citizenship or national ID number'],
                ['var' => '{{employee.permanent_address}}', 'desc' => 'Permanent residential address'],
                ['var' => '{{employee.current_address}}', 'desc' => 'Current residential address'],
                ['var' => '{{employee.department}}', 'desc' => 'Department name'],
                ['var' => '{{employee.designation}}', 'desc' => 'Job title / designation'],
                ['var' => '{{employee.employment_type}}', 'desc' => 'Employment type (Full-time, etc.)'],
                ['var' => '{{employee.joining_date}}', 'desc' => 'Employment start date (DD-MM-YYYY)'],
                ['var' => '{{employee.probation_period}}', 'desc' => 'Probation period duration'],
                ['var' => '{{employee.salary}}', 'desc' => 'Monthly basic salary in the configured currency'],
                ['var' => '{{employee.reporting_manager}}', 'desc' => 'Supervisor / reporting manager name'],
                ['var' => '{{employee.work_location}}', 'desc' => 'Assigned office work location'],
                ['var' => '{{employee.working_schedule}}', 'desc' => 'Standard working hours (e.g. 9 AM - 6 PM)'],
                ['var' => '{{employee.payment_method}}', 'desc' => 'Salary payment method (Bank Transfer)'],
                ['var' => '{{employee.bank_name}}', 'desc' => 'Bank name for payroll'],
                ['var' => '{{employee.bank_account_no}}', 'desc' => 'Bank account number'],
                ['var' => '{{employee.bank_branch}}', 'desc' => 'Bank branch location'],
                ['var' => '{{employee.emergency_contact_name}}', 'desc' => 'Emergency contact person name'],
                ['var' => '{{employee.emergency_contact_phone}}', 'desc' => 'Emergency contact phone number'],
                ['var' => '{{employee.emergency_contact_relation}}', 'desc' => 'Relationship with emergency contact'],
                ['var' => '{{employee.highest_qualification}}', 'desc' => 'Highest educational qualification'],
                ['var' => '{{employee.institution}}', 'desc' => 'Educational institution / university'],
                ['var' => '{{employee.technical_skills}}', 'desc' => 'Core technical skills and expertise'],
                ['var' => '{{employee.previous_experience}}', 'desc' => 'Previous years of experience'],
                ['var' => '{{employee.previous_organization}}', 'desc' => 'Previous company / employer'],
                ['var' => '{{employee.certifications}}', 'desc' => 'Relevant professional certifications'],
                ['var' => '{{employee.portfolio}}', 'desc' => 'Portfolio, GitHub or LinkedIn link'],
                ['var' => '{{employee.primary_responsibilities}}', 'desc' => 'Core assigned job duties'],
                ['var' => '{{employee.secondary_responsibilities}}', 'desc' => 'Secondary roles & support duties'],
                ['var' => '{{employee.assigned_projects}}', 'desc' => 'Initial assigned projects'],
                ['var' => '{{employee.performance_targets}}', 'desc' => 'Initial performance benchmarks'],
                ['var' => '{{employee.reporting_requirements}}', 'desc' => 'Progress reporting cadence'],
                ['var' => '{{employee.authorized_system_access}}', 'desc' => 'Authorized systems and accounts'],
                ['var' => '{{employee.department_head}}', 'desc' => 'Department head executive name'],
                ['var' => '{{employee.registration_date}}', 'desc' => 'System registration date'],
            ],
            'Company Variables' => [
                ['var' => '{{company.name}}', 'desc' => 'Company legal name from Settings'],
                ['var' => '{{company.tagline}}', 'desc' => 'Optional template tagline'],
                ['var' => '{{company.address}}', 'desc' => 'Corporate office street address'],
                ['var' => '{{company.city}}', 'desc' => 'City from company Settings'],
                ['var' => '{{company.country}}', 'desc' => 'Country from company Settings'],
                ['var' => '{{company.phone}}', 'desc' => 'Official phone number'],
                ['var' => '{{company.email}}', 'desc' => 'Official email address'],
                ['var' => '{{company.website}}', 'desc' => 'Official website URL'],
                ['var' => '{{company.registration_no}}', 'desc' => 'Government company registration number'],
                ['var' => '{{company.pan_vat_no}}', 'desc' => 'Tax identification number (PAN/VAT)'],
                ['var' => '{{company.authorized_representative}}', 'desc' => 'Authorized signatory executive name'],
            ],
            'Agreement & Document Variables' => [
                ['var' => '{{agreement.number}}', 'desc' => 'Unique agreement / bond reference code'],
                ['var' => '{{agreement.date}}', 'desc' => 'Agreement issuance date (DD-MM-YYYY)'],
                ['var' => '{{agreement.effective_date}}', 'desc' => 'Agreement effective start date'],
                ['var' => '{{hr.representative_name}}', 'desc' => 'HR verification officer name'],
            ],
        ];
    }

    /**
     * Compile existing employee and company settings into a key-value dictionary.
     * Never invents data: missing fields default to clean empty or "Not Provided".
     */
    public function compileVariables(
        Employee $employee,
        array $customData = [],
        ?array $reportSettings = null
    ): array
    {
        $reportSettings ??= $this->corporateSettings->forReport();
        $company = $reportSettings['company'];
        $employee->loadMissing(['department', 'designation']);

        $regDate = $customData['registration_date'] ?? ($employee->created_at ? $employee->created_at->format('d-m-Y') : date('d-m-Y'));
        $joiningDate = $customData['joining_date'] ?? ($employee->joining_date ? $employee->joining_date->format('d-m-Y') : date('d-m-Y'));
        $dob = $customData['date_of_birth'] ?? ($employee->date_of_birth ? $employee->date_of_birth->format('d-m-Y') : '');

        // Extract citizenship from customData, notes or leave blank
        $citizenship = $customData['citizenship_number'] ?? '';
        if (empty($citizenship) && !empty($employee->notes) && preg_match('/citizenship[:\s]+([A-Za-z0-9\/\-]+)/i', $employee->notes, $m)) {
            $citizenship = trim($m[1]);
        }

        $currency = $reportSettings['currency_symbol'] ?: $reportSettings['currency'];
        $salaryFormatted = $customData['salary'] ?? (
            $employee->basic_salary !== null && $employee->basic_salary > 0
                ? trim(($currency ? $currency.' ' : '').number_format((float) $employee->basic_salary, 2))
                : 'As per contract'
        );

        $codeNum = preg_replace('/[^0-9]/', '', $employee->employee_code);
        if (empty($codeNum)) {
            $codeNum = str_pad((string)$employee->id, 4, '0', STR_PAD_LEFT);
        } else {
            $codeNum = str_pad(substr($codeNum, -4), 4, '0', STR_PAD_LEFT);
        }

        $bondNumber = $customData['agreement_number'] ?? ('GOS-BOND-' . $codeNum);
        $agreementDate = $customData['agreement_date'] ?? date('d-m-Y');

        // Department head name
        $deptHead = $customData['department_head'] ?? null;
        if (empty($deptHead)) {
            if ($employee->department && $employee->department->head_employee_id) {
                $headEmp = Employee::find($employee->department->head_employee_id);
                if ($headEmp) {
                    $deptHead = $headEmp->full_name;
                }
            }
            if (empty($deptHead)) {
                $deptHead = 'Not Assigned';
            }
        }

        $reportingManager = $customData['reporting_manager'] ?? $deptHead;

        return [
            // Employee variables
            '{{employee.id}}' => $customData['employee_id'] ?? ($employee->employee_code ?: ('GOS-EMP-' . $codeNum)),
            '{{employee.full_name}}' => $customData['full_name'] ?? ($employee->full_name ?: trim($employee->first_name . ' ' . $employee->last_name)),
            '{{employee.first_name}}' => $customData['first_name'] ?? ($employee->first_name ?: ''),
            '{{employee.last_name}}' => $customData['last_name'] ?? ($employee->last_name ?: ''),
            '{{employee.email}}' => $customData['email'] ?? ($employee->email ?: 'Not Provided'),
            '{{employee.phone}}' => $customData['phone'] ?? ($employee->phone ?: 'Not Provided'),
            '{{employee.gender}}' => $customData['gender'] ?? ucfirst($employee->gender ?: 'Not Specified'),
            '{{employee.date_of_birth}}' => $dob ?: 'Not Provided',
            '{{employee.citizenship_number}}' => $citizenship ?: 'Not Provided',
            '{{employee.permanent_address}}' => $customData['permanent_address'] ?? ($employee->permanent_address ?: 'Not Provided'),
            '{{employee.current_address}}' => $customData['current_address'] ?? ($employee->current_address ?: ($employee->permanent_address ?: 'Not Provided')),
            '{{employee.department}}' => $customData['department'] ?? ($employee->department?->name ?: 'General'),
            '{{employee.designation}}' => $customData['designation'] ?? ($employee->designation?->title ?: 'Not Assigned'),
            '{{employee.employment_type}}' => $customData['employment_type'] ?? ucwords(str_replace('_', ' ', $employee->employment_type ?: 'full_time')),
            '{{employee.joining_date}}' => $joiningDate,
            '{{employee.probation_period}}' => $customData['probation_period'] ?? '3 Months',
            '{{employee.salary}}' => $salaryFormatted,
            '{{employee.reporting_manager}}' => $reportingManager,
            '{{employee.work_location}}' => $customData['work_location'] ?? ($company?->city ? $company->city . ' Office' : ''),
            '{{employee.working_schedule}}' => $customData['working_schedule'] ?? '9 AM – 6 PM',
            '{{employee.payment_method}}' => $customData['payment_method'] ?? 'Bank Transfer',
            '{{employee.bank_name}}' => $customData['bank_name'] ?? ($employee->bank_name ?: 'Not Provided'),
            '{{employee.bank_account_no}}' => $customData['bank_account_no'] ?? ($employee->bank_account_no ?: 'Not Provided'),
            '{{employee.bank_branch}}' => $customData['bank_branch'] ?? ($employee->bank_branch ?: 'Not Provided'),
            '{{employee.emergency_contact_name}}' => $customData['emergency_contact_name'] ?? ($employee->emergency_contact_name ?: 'Not Provided'),
            '{{employee.emergency_contact_phone}}' => $customData['emergency_contact_phone'] ?? ($employee->emergency_contact_phone ?: 'Not Provided'),
            '{{employee.emergency_contact_relation}}' => $customData['emergency_contact_relation'] ?? ($employee->emergency_contact_relation ?: 'Not Provided'),
            '{{employee.highest_qualification}}' => $customData['highest_qualification'] ?? 'Not Provided',
            '{{employee.institution}}' => $customData['institution'] ?? 'Not Provided',
            '{{employee.technical_skills}}' => $customData['technical_skills'] ?? 'Not Provided',
            '{{employee.previous_experience}}' => $customData['previous_experience'] ?? 'Not Provided',
            '{{employee.previous_organization}}' => $customData['previous_organization'] ?? 'Not Provided',
            '{{employee.certifications}}' => $customData['certifications'] ?? 'Not Provided',
            '{{employee.portfolio}}' => $customData['portfolio'] ?? 'Not Provided',
            '{{employee.primary_responsibilities}}' => $customData['primary_responsibilities'] ?? 'As assigned by Department Head and Employment Agreement',
            '{{employee.secondary_responsibilities}}' => $customData['secondary_responsibilities'] ?? 'Adhere to company policies and assist team deliverables',
            '{{employee.assigned_projects}}' => $customData['assigned_projects'] ?? 'Internal & Client Systems',
            '{{employee.performance_targets}}' => $customData['performance_targets'] ?? 'Meet deliverables according to project timelines',
            '{{employee.reporting_requirements}}' => $customData['reporting_requirements'] ?? 'Regular progress reports to supervisor',
            '{{employee.authorized_system_access}}' => $customData['authorized_system_access'] ?? 'Assigned company tools & workspace access',
            '{{employee.department_head}}' => $deptHead,
            '{{employee.registration_date}}' => $regDate,

            // Company variables
            '{{company.name}}' => $company?->company_name ?? '',
            '{{company.tagline}}' => '',
            '{{company.address}}' => $company?->address ?? '',
            '{{company.city}}' => $company?->city ?? '',
            '{{company.country}}' => $company?->country ?? '',
            '{{company.phone}}' => $company?->phone ?? '',
            '{{company.email}}' => $company?->email ?? '',
            '{{company.website}}' => $company?->website ?? '',
            '{{company.registration_no}}' => $company?->registration_no ?? '',
            '{{company.pan_vat_no}}' => $company?->pan_vat_no ?? '',
            '{{company.authorized_representative}}' => $customData['authorized_representative'] ?? '',

            // Agreement variables
            '{{agreement.number}}' => $bondNumber,
            '{{agreement.date}}' => $agreementDate,
            '{{agreement.effective_date}}' => $joiningDate,
            '{{hr.representative_name}}' => $customData['hr_representative_name'] ?? auth()->user()?->name ?? '',
        ];
    }

    /**
     * Replace template variable placeholders in any text/data string.
     */
    public function interpolate(string $text, array $variables): string
    {
        return str_replace(array_keys($variables), array_values($variables), $text);
    }

    /**
     * Recursively interpolate an array structure containing strings.
     */
    public function interpolateArray(array $data, array $variables): array
    {
        $result = [];
        foreach ($data as $key => $val) {
            if (is_array($val)) {
                $result[$key] = $this->interpolateArray($val, $variables);
            } elseif (is_string($val)) {
                $result[$key] = $this->interpolate($val, $variables);
            } else {
                $result[$key] = $val;
            }
        }
        return $result;
    }

    /**
     * Get or seed default template for Employee Registration Form.
     */
    public function getRegistrationFormTemplate(): DocumentTemplate
    {
        $template = DocumentTemplate::where('code', 'employee_registration_form')->first();
        if (!$template) {
            $template = $this->createDefaultRegistrationFormTemplate();
        }
        return $template;
    }

    /**
     * Get or seed default template for Employment Bond Agreement.
     */
    public function getEmploymentBondTemplate(): DocumentTemplate
    {
        $template = DocumentTemplate::where('code', 'employment_bond')->first();
        if (!$template) {
            $template = $this->createDefaultEmploymentBondTemplate();
        }
        return $template;
    }

    /**
     * Ensure both default templates exist in database.
     */
    public function ensureDefaultTemplates(): void
    {
        $this->getRegistrationFormTemplate();
        $this->getEmploymentBondTemplate();
    }

    /**
     * Create default Registration Form template in database.
     */
    public function createDefaultRegistrationFormTemplate(): DocumentTemplate
    {
        $design = DocumentTemplate::defaultDesignConfig();

        $sections = [
            'header' => [
                'company_name' => '{{company.name}}',
                'tagline' => '',
                'top_right_text' => '',
                'title' => 'EMPLOYEE REGISTRATION FORM',
                'subtitle' => 'Confidential – Human Resources Department',
                'badge_id_label' => 'Employee ID',
                'badge_date_label' => 'Registration Date',
            ],
            'section_a' => [
                'code' => 'section_a',
                'letter' => 'A',
                'title' => 'Personal Information',
                'fields' => [
                    ['label' => 'Full Legal Name', 'var' => '{{employee.full_name}}'],
                    ['label' => 'Date of Birth', 'var' => '{{employee.date_of_birth}}', 'inline_label' => 'Gender', 'inline_var' => '{{employee.gender}}'],
                    ['label' => 'Citizenship Number', 'var' => '{{employee.citizenship_number}}'],
                    ['label' => 'Permanent Address', 'var' => '{{employee.permanent_address}}'],
                    ['label' => 'Current Address', 'var' => '{{employee.current_address}}'],
                    ['label' => 'Contact Number', 'var' => '{{employee.phone}}', 'inline_label' => 'Email Address', 'inline_var' => '{{employee.email}}'],
                    ['label' => 'Emergency Contact Name', 'var' => '{{employee.emergency_contact_name}}'],
                    ['label' => 'Emergency Contact Number', 'var' => '{{employee.emergency_contact_phone}}', 'inline_label' => 'Relationship', 'inline_var' => '{{employee.emergency_contact_relation}}'],
                ],
                'photo_box' => true,
            ],
            'section_b' => [
                'code' => 'section_b',
                'letter' => 'B',
                'title' => 'Educational & Professional Information',
                'fields' => [
                    ['label' => 'Highest Qualification', 'var' => '{{employee.highest_qualification}}'],
                    ['label' => 'Institution', 'var' => '{{employee.institution}}'],
                    ['label' => 'Technical Skills', 'var' => '{{employee.technical_skills}}'],
                    ['label' => 'Previous Work Experience', 'var' => '{{employee.previous_experience}}'],
                    ['label' => 'Previous Organization', 'var' => '{{employee.previous_organization}}'],
                    ['label' => 'Relevant Certifications', 'var' => '{{employee.certifications}}'],
                    ['label' => 'Portfolio / GitHub', 'var' => '{{employee.portfolio}}'],
                ],
            ],
            'section_c' => [
                'code' => 'section_c',
                'letter' => 'C',
                'title' => 'Employment Information',
                'columns' => [
                    'left' => [
                        ['label' => 'Department', 'var' => '{{employee.department}}'],
                        ['label' => 'Designation', 'var' => '{{employee.designation}}'],
                        ['label' => 'Employee Type', 'var' => '{{employee.employment_type}}'],
                        ['label' => 'Employment Start Date', 'var' => '{{employee.joining_date}}'],
                        ['label' => 'Probation Period', 'var' => '{{employee.probation_period}}'],
                    ],
                    'right' => [
                        ['label' => 'Reporting Manager', 'var' => '{{employee.reporting_manager}}'],
                        ['label' => 'Work Location', 'var' => '{{employee.work_location}}'],
                        ['label' => 'Working Schedule', 'var' => '{{employee.working_schedule}}'],
                        ['label' => 'Monthly Salary', 'var' => '{{employee.salary}}'],
                        ['label' => 'Payment Method', 'var' => '{{employee.payment_method}}'],
                    ],
                ],
            ],
            'section_d' => [
                'code' => 'section_d',
                'letter' => 'D',
                'title' => 'Assigned Company Role & Responsibilities',
                'fields' => [
                    ['label' => 'Primary Responsibilities', 'var' => '{{employee.primary_responsibilities}}'],
                    ['label' => 'Secondary Responsibilities', 'var' => '{{employee.secondary_responsibilities}}'],
                    ['label' => 'Assigned Projects', 'var' => '{{employee.assigned_projects}}'],
                    ['label' => 'Performance Targets', 'var' => '{{employee.performance_targets}}'],
                    ['label' => 'Reporting Requirements', 'var' => '{{employee.reporting_requirements}}'],
                    ['label' => 'Authorized System Access', 'var' => '{{employee.authorized_system_access}}'],
                    ['label' => 'Department Head', 'var' => '{{employee.department_head}}'],
                ],
            ],
            'section_e' => [
                'code' => 'section_e',
                'letter' => 'E',
                'title' => 'Documents Checklist',
                'checklist' => [
                    'Citizenship / Valid Identification Copy',
                    'Passport-size Photograph',
                    'CV / Resume',
                    'Educational Certificates',
                    'Previous Experience Certificate (if applicable)',
                    'Signed Employment Agreement',
                    'Signed Confidentiality Agreement',
                    'Signed Employment Bond',
                    'Other Relevant Documents',
                ],
            ],
            'section_f' => [
                'code' => 'section_f',
                'letter' => 'F',
                'title' => 'Employee Declaration',
                'declaration_text' => 'I hereby declare that the information provided above is true and accurate. I agree to comply with company policies, professional standards, assigned responsibilities, confidentiality requirements and lawful instructions.',
                'employee_name_label' => 'Employee Name',
                'signature_label' => 'Signature',
                'date_label' => 'Date',
            ],
            'section_g' => [
                'code' => 'section_g',
                'letter' => 'G',
                'title' => 'HR Verification',
                'verification_checklist' => [
                    'Documents Verified',
                    'Employee ID Generated',
                    'Employment Agreement Signed',
                    'Bond Signed',
                    'Company ID Issued',
                    'System Access Approved',
                ],
                'hr_representative_label' => 'HR Representative',
                'signature_label' => 'Signature',
                'authorized_signature_label' => 'Authorized Signature',
            ],
            'footer' => [
                'address' => '{{company.address}}, {{company.city}}, {{company.country}}',
                'website' => '{{company.website}}',
                'email' => '{{company.email}}',
                'brand_text' => '{{company.name}}',
                'tagline' => '',
            ],
        ];

        $template = DocumentTemplate::create([
            'code' => 'employee_registration_form',
            'name' => 'Official Employee Registration Form',
            'type' => 'registration_form',
            'description' => 'Official physical registration document for newly registered employees.',
            'is_active' => true,
            'current_version' => 1,
            'design_config' => $design,
            'sections_data' => $sections,
        ]);

        DocumentTemplateVersion::create([
            'document_template_id' => $template->id,
            'version_number' => 1,
            'design_config' => $design,
            'sections_data' => $sections,
            'change_summary' => 'Initial approved employee registration template design.',
        ]);

        return $template;
    }

    /**
     * Create default Employment Bond template in database.
     */
    public function createDefaultEmploymentBondTemplate(): DocumentTemplate
    {
        $design = DocumentTemplate::defaultDesignConfig();

        $sections = [
            'header' => [
                'company_name' => '{{company.name}}',
                'tagline' => '',
                'top_right_text' => '',
                'title' => 'EMPLOYMENT BOND',
                'subtitle' => 'Confidential – Human Resources Department',
                'badge_bond_label' => 'Bond Number',
                'badge_date_label' => 'Agreement Date',
            ],
            'parties_intro' => 'This Employment Bond is made between:',
            'parties' => [
                'company' => [
                    'letter' => 'A',
                    'title' => 'Company',
                    'name' => '{{company.name}}',
                    'address' => '{{company.address}}, {{company.city}}, {{company.country}}',
                    'representative' => '{{company.authorized_representative}}',
                ],
                'employee' => [
                    'letter' => 'B',
                    'title' => 'Employee',
                    'name' => '{{employee.full_name}}',
                    'employee_id' => '{{employee.id}}',
                    'address' => '{{employee.permanent_address}}',
                    'position' => '{{employee.designation}}',
                    'department' => '{{employee.department}}',
                ],
            ],
            'clauses' => [
                [
                    'num' => 1,
                    'title' => 'Employment Commitment',
                    'body' => 'The employee agrees to perform assigned responsibilities professionally, honestly and responsibly according to the employment agreement and company policies.',
                ],
                [
                    'num' => 2,
                    'title' => 'Employment Duration',
                    'body' => '• Employment Start Date: {{employee.joining_date}}
• Employment Type: {{employee.employment_type}}
• Probation Period: {{employee.probation_period}}
• Agreement Duration: As per company policy',
                ],
                [
                    'num' => 3,
                    'title' => 'Roles and Responsibilities',
                    'body' => 'The employee agrees to:
1. Complete assigned tasks within agreed deadlines.
2. Maintain professional conduct.
3. Follow approved company procedures.
4. Report project progress to the supervisor.
5. Protect company property and resources.
6. Avoid unauthorized access to company systems.
7. Maintain quality and integrity of assigned work.',
                ],
                [
                    'num' => 4,
                    'title' => 'Confidentiality Agreement',
                    'body' => 'The employee shall not disclose, misuse, copy or transfer confidential company information without authorization.',
                ],
                [
                    'num' => 5,
                    'title' => 'Intellectual Property & Ownership',
                    'body' => 'Work created by the employee within their duties shall belong to the company, including software, designs, documentation and other deliverables.',
                ],
                [
                    'num' => 6,
                    'title' => 'Company Property & Digital Assets',
                    'body' => 'The employee shall take reasonable care of all company assets including computers, software, and digital resources.',
                ],
                [
                    'num' => 7,
                    'title' => 'Conflict of Interest',
                    'body' => 'The employee must disclose any actual or potential conflict and must not use company resources for unauthorized personal or competing activities.',
                ],
                [
                    'num' => 8,
                    'title' => 'Training & Professional Development',
                    'body' => 'Any company-sponsored training must be documented separately with proper terms.',
                ],
                [
                    'num' => 9,
                    'title' => 'Resignation & Exit Procedure',
                    'body' => 'The employee shall follow applicable notice requirements and complete proper handover, return company assets, and clear all accesses.',
                ],
                [
                    'num' => 10,
                    'title' => 'Breach of Agreement',
                    'body' => 'Any breach may result in appropriate disciplinary or legal action as per applicable law.',
                ],
                [
                    'num' => 11,
                    'title' => 'Applicable Law',
                    'body' => 'This agreement shall be governed by the laws of Nepal, including the Labour Act, 2074.',
                ],
                [
                    'num' => 12,
                    'title' => 'Employee Declaration',
                    'body' => 'I confirm that I have read and understood this agreement. I voluntarily accept the responsibilities and confidentiality obligations described herein.',
                ],
            ],
            'signatures' => [
                'employee' => [
                    'box_title' => 'EMPLOYEE',
                    'full_name' => '{{employee.full_name}}',
                    'employee_id' => '{{employee.id}}',
                ],
                'company' => [
                    'box_title' => 'FOR {{company.name}}',
                    'representative' => '{{company.authorized_representative}}',
                    'designation' => 'Authorized Representative',
                    'has_stamp_box' => true,
                ],
            ],
            'footer' => [
                'address' => '{{company.address}}, {{company.city}}, {{company.country}}',
                'website' => '{{company.website}}',
                'email' => '{{company.email}}',
                'brand_text' => '{{company.name}}',
                'tagline' => '',
            ],
        ];

        $template = DocumentTemplate::create([
            'code' => 'employment_bond',
            'name' => 'Official Employment Bond Agreement',
            'type' => 'employment_bond',
            'description' => 'Formal employment bond agreement for newly registered employees governed by applicable labor law.',
            'is_active' => true,
            'current_version' => 1,
            'design_config' => $design,
            'sections_data' => $sections,
        ]);

        DocumentTemplateVersion::create([
            'document_template_id' => $template->id,
            'version_number' => 1,
            'design_config' => $design,
            'sections_data' => $sections,
            'change_summary' => 'Initial approved employment bond template.',
        ]);

        return $template;
    }

    /**
     * Generate Document A: Employee Registration Form PDF.
     */
    public function generateRegistrationFormPdf(Employee $employee, ?DocumentTemplate $template = null, array $customData = []): GeneratedEmployeeDocument
    {
        $template = $template ?: $this->getRegistrationFormTemplate();
        $reportSettings = $this->corporateSettings->forReport();
        $variables = $this->compileVariables($employee, $customData, $reportSettings);

        // Interpolate all text in template
        $design = array_merge(DocumentTemplate::defaultDesignConfig(), $template->design_config ?: []);
        $sections = $this->interpolateArray($template->sections_data ?: [], $variables);

        $codeNum = preg_replace('/[^0-9]/', '', $employee->employee_code) ?: str_pad((string)$employee->id, 4, '0', STR_PAD_LEFT);
        $codeNum = str_pad(substr($codeNum, -4), 4, '0', STR_PAD_LEFT);
        $docNumber = 'GOS-REG-' . $codeNum . '-V' . $template->current_version;

        $cleanVars = [];
        foreach ($variables as $k => $val) {
            $trimmed = trim($k, '{}');
            $cleanVars[$trimmed] = $val;
            $cleanVars[str_replace('.', '_', $trimmed)] = $val;
        }

        $viewData = [
            'employee' => $employee,
            'template' => $template,
            'design' => $design,
            'sections' => $sections,
            'v' => $cleanVars,
            'docNumber' => $docNumber,
            'company' => $reportSettings['company'],
            'reportSettings' => $reportSettings,
            'logoBase64' => $this->getLogoBase64($reportSettings),
            'iconBase64' => $this->getIconBase64($reportSettings),
            'watermarkBase64' => $this->getWatermarkBase64($reportSettings),
            'stampBase64' => $this->getStampBase64($reportSettings),
        ];

        $pdf = $this->pdfService->render('pdf.documents.registration_form', $viewData, 'a4', 'portrait');
        $pdfOutput = $pdf->output();

        $storageDir = 'employee_documents/' . $employee->id;
        if (!Storage::disk('public')->exists($storageDir)) {
            Storage::disk('public')->makeDirectory($storageDir);
        }

        $fileName = 'Employee_Registration_Form_' . $docNumber . '.pdf';
        $filePath = $storageDir . '/' . $fileName;
        Storage::disk('public')->put($filePath, $pdfOutput);

        $fileSizeKb = max(1, (int) round(strlen($pdfOutput) / 1024));

        // Create or update generated document record
        $doc = GeneratedEmployeeDocument::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'document_type' => 'registration_form',
            ],
            [
                'document_template_id' => $template->id,
                'document_number' => $docNumber,
                'title' => 'Employee Registration Form',
                'version' => $template->current_version,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size_kb' => $fileSizeKb,
                'status' => 'generated',
                'snapshot_data' => $variables,
                'generated_by' => auth()->id(),
            ]
        );

        AuditLog::log('generate', 'documents', $doc->id, "Generated Employee Registration Form: {$docNumber} for {$employee->full_name}");

        return $doc;
    }

    /**
     * Generate Document B: Employment Bond Agreement PDF.
     */
    public function generateEmploymentBondPdf(Employee $employee, ?DocumentTemplate $template = null, array $customData = []): GeneratedEmployeeDocument
    {
        $template = $template ?: $this->getEmploymentBondTemplate();
        $reportSettings = $this->corporateSettings->forReport();
        $variables = $this->compileVariables($employee, $customData, $reportSettings);

        $design = array_merge(DocumentTemplate::defaultDesignConfig(), $template->design_config ?: []);
        $sections = $this->interpolateArray($template->sections_data ?: [], $variables);

        $codeNum = preg_replace('/[^0-9]/', '', $employee->employee_code) ?: str_pad((string)$employee->id, 4, '0', STR_PAD_LEFT);
        $codeNum = str_pad(substr($codeNum, -4), 4, '0', STR_PAD_LEFT);
        $docNumber = 'GOS-BOND-' . $codeNum . '-V' . $template->current_version;
        $agreementNumber = 'GOS-BOND-' . $codeNum;

        $cleanVars = [];
        foreach ($variables as $k => $val) {
            $trimmed = trim($k, '{}');
            $cleanVars[$trimmed] = $val;
            $cleanVars[str_replace('.', '_', $trimmed)] = $val;
        }

        $viewData = [
            'employee' => $employee,
            'template' => $template,
            'design' => $design,
            'sections' => $sections,
            'v' => $cleanVars,
            'docNumber' => $docNumber,
            'agreementNumber' => $agreementNumber,
            'company' => $reportSettings['company'],
            'reportSettings' => $reportSettings,
            'logoBase64' => $this->getLogoBase64($reportSettings),
            'iconBase64' => $this->getIconBase64($reportSettings),
            'watermarkBase64' => $this->getWatermarkBase64($reportSettings),
            'stampBase64' => $this->getStampBase64($reportSettings),
        ];

        $pdf = $this->pdfService->render('pdf.documents.employment_bond', $viewData, 'a4', 'portrait');
        $pdfOutput = $pdf->output();

        $storageDir = 'employee_documents/' . $employee->id;
        if (!Storage::disk('public')->exists($storageDir)) {
            Storage::disk('public')->makeDirectory($storageDir);
        }

        $fileName = 'Employment_Bond_' . $docNumber . '.pdf';
        $filePath = $storageDir . '/' . $fileName;
        Storage::disk('public')->put($filePath, $pdfOutput);

        $fileSizeKb = max(1, (int) round(strlen($pdfOutput) / 1024));

        $doc = GeneratedEmployeeDocument::updateOrCreate(
            [
                'employee_id' => $employee->id,
                'document_type' => 'employment_bond',
            ],
            [
                'document_template_id' => $template->id,
                'document_number' => $docNumber,
                'agreement_number' => $agreementNumber,
                'title' => 'Employment Bond Agreement',
                'version' => $template->current_version,
                'file_path' => $filePath,
                'file_name' => $fileName,
                'file_size_kb' => $fileSizeKb,
                'status' => 'generated',
                'snapshot_data' => $variables,
                'generated_by' => auth()->id(),
            ]
        );

        AuditLog::log('generate', 'documents', $doc->id, "Generated Employment Bond Agreement: {$docNumber} for {$employee->full_name}");

        return $doc;
    }

    /**
     * Generate both official documents for an employee.
     */
    public function generateAllDocumentsForEmployee(Employee $employee, array $customData = []): array
    {
        $regDoc = $this->generateRegistrationFormPdf($employee, null, $customData);
        $bondDoc = $this->generateEmploymentBondPdf($employee, null, $customData);

        return [
            'registration_form' => $regDoc,
            'employment_bond' => $bondDoc,
        ];
    }

    /**
     * Get logo as base64 string for robust DomPDF rendering.
     */
    public function getLogoBase64(?array $reportSettings = null): string
    {
        $logoPath = ($reportSettings ?? $this->corporateSettings->forReport())['logo_path'];
        if (! $logoPath) {
            return '';
        }

        $content = file_get_contents($logoPath);
        if ($content === false) {
            throw new \RuntimeException('Unable to read the configured company logo.');
        }

        $mime = mime_content_type($logoPath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }

    /**
     * Get standalone brand icon mark as base64.
     */
    public function getIconBase64(?array $reportSettings = null): string
    {
        return $this->getLogoBase64($reportSettings);
    }

    /**
     * Get watermark emblem with baked faint opacity as base64.
     */
    public function getWatermarkBase64(?array $reportSettings = null): string
    {
        return $this->getLogoBase64($reportSettings);
    }

    /**
     * Get official circular company stamp as base64.
     */
    public function getStampBase64(?array $reportSettings = null): string
    {
        $stampPath = ($reportSettings ?? $this->corporateSettings->forReport())['stamp_signature_path'];
        if (! $stampPath) {
            return '';
        }

        $content = file_get_contents($stampPath);
        if ($content === false) {
            throw new \RuntimeException('Unable to read the configured company signature.');
        }

        $mime = mime_content_type($stampPath) ?: 'image/png';

        return 'data:'.$mime.';base64,'.base64_encode($content);
    }
}

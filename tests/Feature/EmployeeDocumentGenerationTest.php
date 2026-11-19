<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Employee;
use App\Models\Department;
use App\Models\Designation;
use App\Models\DocumentTemplate;
use App\Models\GeneratedEmployeeDocument;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;

class EmployeeDocumentGenerationTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected Department $department;
    protected Designation $designation;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->admin = User::factory()->create([
            'role' => 'admin',
        ]);

        $this->department = Department::create([
            'name' => 'Engineering',
            'code' => 'ENG',
            'description' => 'Software engineering',
        ]);

        $this->designation = Designation::create([
            'department_id' => $this->department->id,
            'title' => 'Full Stack Developer',
            'code' => 'FSD',
        ]);
    }

    public function test_registering_employee_automatically_generates_both_official_documents(): void
    {
        $payload = [
            'first_name' => 'Sital',
            'last_name' => 'Mahato',
            'email' => 'sital.mahato@gosika.com',
            'phone' => '9841234567',
            'gender' => 'male',
            'date_of_birth' => '2000-05-15',
            'marital_status' => 'Single',
            'blood_group' => 'O+',
            'permanent_address' => 'Kathmandu, Nepal',
            'current_address' => 'Kathmandu, Nepal',
            'department_id' => $this->department->id,
            'designation_id' => $this->designation->id,
            'employment_type' => 'full_time',
            'joining_date' => '2026-11-01',
            'basic_salary' => 60000,
            'emergency_contact_name' => 'Ramesh Mahato',
            'emergency_contact_phone' => '9812345678',
            'emergency_contact_relation' => 'Brother',
            'status' => 'active',
            'notes' => 'Citizenship: 1234567890123',
        ];

        $response = $this->actingAs($this->admin)
            ->post('/employees', $payload);

        $employee = Employee::where('email', 'sital.mahato@gosika.com')->first();
        $this->assertNotNull($employee);

        $response->assertRedirect(route('employees.show', [
            'employee' => $employee->id,
            'tab' => 'employee_documents',
        ]));

        // Verify Document A (Registration Form) was automatically generated
        $regDoc = GeneratedEmployeeDocument::where('employee_id', $employee->id)
            ->where('document_type', 'registration_form')
            ->first();

        $this->assertNotNull($regDoc);
        $this->assertEquals('generated', $regDoc->status);
        $this->assertStringContainsString('GOS-REG-', $regDoc->document_number);
        Storage::disk('public')->assertExists($regDoc->file_path);

        // Verify Document B (Employment Bond) was automatically generated
        $bondDoc = GeneratedEmployeeDocument::where('employee_id', $employee->id)
            ->where('document_type', 'employment_bond')
            ->first();

        $this->assertNotNull($bondDoc);
        $this->assertEquals('generated', $bondDoc->status);
        $this->assertStringContainsString('GOS-BOND-', $bondDoc->document_number);
        Storage::disk('public')->assertExists($bondDoc->file_path);
    }

    public function test_document_status_and_physical_filing_location_can_be_updated(): void
    {
        $employee = Employee::create([
            'employee_code' => 'GOS-EMP-0010',
            'first_name' => 'Test',
            'last_name' => 'User',
            'full_name' => 'Test User',
            'email' => 'test.user@gosika.com',
            'phone' => '9800000000',
            'gender' => 'female',
            'joining_date' => '2026-11-01',
            'status' => 'active',
        ]);

        app(\App\Services\DocumentGenerationService::class)->generateAllDocumentsForEmployee($employee);

        $doc = GeneratedEmployeeDocument::where('employee_id', $employee->id)->first();

        $response = $this->actingAs($this->admin)
            ->patch("/employee-documents/{$doc->id}/status", [
                'status' => 'signed',
                'physical_location' => 'Cabinet A, Shelf 3, Folder 10',
                'received_by_hr' => 'Sabin Shrestha',
                'hr_remarks' => 'Signed by employee and 2 witnesses.',
            ]);

        $response->assertSessionHas('success');

        $doc->refresh();
        $this->assertEquals('signed', $doc->status);
        $this->assertEquals('Cabinet A, Shelf 3, Folder 10', $doc->physical_location);
        $this->assertEquals('Sabin Shrestha', $doc->received_by_hr);
        $this->assertNotNull($doc->signed_at);
    }

    public function test_document_templates_management_routes(): void
    {
        $this->actingAs($this->admin);

        // Index
        $response = $this->get('/document-templates');
        $response->assertStatus(200);

        // Ensure default templates seeded
        $bondTemplate = DocumentTemplate::where('code', 'employment_bond')->first();
        $this->assertNotNull($bondTemplate);

        // Edit page
        $response = $this->get("/document-templates/{$bondTemplate->id}/edit");
        $response->assertStatus(200);

        // Update creates new version
        $oldVersion = $bondTemplate->current_version;
        $response = $this->put("/document-templates/{$bondTemplate->id}", [
            'name' => 'Official Employment Bond Agreement (Updated)',
            'description' => $bondTemplate->description,
            'is_active' => true,
            'design_config' => $bondTemplate->design_config,
            'sections_data' => $bondTemplate->sections_data,
            'change_summary' => 'Updated legal clause 3 wording',
        ]);

        $response->assertSessionHas('success');
        $bondTemplate->refresh();
        $this->assertEquals($oldVersion + 1, $bondTemplate->current_version);
        $this->assertEquals('Official Employment Bond Agreement (Updated)', $bondTemplate->name);
    }

    public function test_document_details_and_filing_can_be_edited_and_regenerated(): void
    {
        $employee = Employee::create([
            'employee_code' => 'GOS-EMP-0012',
            'first_name' => 'Custom',
            'last_name' => 'Employee',
            'full_name' => 'Custom Employee',
            'email' => 'custom.employee@gosika.com',
            'phone' => '9801112233',
            'gender' => 'male',
            'joining_date' => '2026-11-01',
            'status' => 'active',
        ]);

        app(\App\Services\DocumentGenerationService::class)->generateAllDocumentsForEmployee($employee);

        $doc = GeneratedEmployeeDocument::where('employee_id', $employee->id)
            ->where('document_type', 'registration_form')
            ->first();

        $response = $this->actingAs($this->admin)
            ->post("/employee-documents/{$doc->id}/update-details", [
                'status' => 'printed',
                'physical_location' => 'Cabinet C, Shelf 1, Folder 12',
                'received_by_hr' => 'Bikash HR Officer',
                'hr_remarks' => 'Custom edited qualifications and terms verified.',
                'highest_qualification' => 'Master of Computer Science',
                'institution' => 'Pulchowk Campus, IOE',
                'technical_skills' => 'Laravel, React, PostgreSQL',
                'monthly_salary' => 'NPR 85,000',
                'work_location' => 'Kathmandu Tinkune Office',
                'citizenship_number' => '9988-7766-5544',
            ]);

        $response->assertSessionHas('success');

        $doc->refresh();
        $this->assertEquals('printed', $doc->status);
        $this->assertEquals('Cabinet C, Shelf 1, Folder 12', $doc->physical_location);
        $this->assertEquals('Bikash HR Officer', $doc->received_by_hr);
        $this->assertNotNull($doc->printed_at);

        // Verify updated snapshot contains custom edited data
        $this->assertEquals('Master of Computer Science', $doc->snapshot_data['{{employee.highest_qualification}}']);
        $this->assertEquals('Pulchowk Campus, IOE', $doc->snapshot_data['{{employee.institution}}']);
        $this->assertEquals('9988-7766-5544', $doc->snapshot_data['{{employee.citizenship_number}}']);
        Storage::disk('public')->assertExists($doc->file_path);
    }
}

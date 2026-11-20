<?php

namespace Tests\Feature;

use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\User;
use App\Services\CorporateReportSettingsService;
use App\Services\CorporateExcelReportService;
use App\Services\DocumentGenerationService;
use App\Services\ReportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class CorporateReportSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_report_settings_are_read_fresh_and_resolve_the_configured_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company/first.png', base64_decode($this->transparentPng()));
        Storage::disk('public')->put('company/second.png', base64_decode($this->transparentPng()));
        $company = $this->createCompany([
            'logo_path' => 'company/first.png',
            'report_footer_text' => 'First configured footer',
        ]);
        $settingsService = app(CorporateReportSettingsService::class);

        $first = $settingsService->forReport();
        $this->assertSame('Configured Company', $first['header']['company_name']);
        $this->assertSame('First configured footer', $first['footer']['text']);
        $this->assertSame(Storage::disk('public')->path('company/first.png'), $first['logo_path']);
        $this->assertSame(Storage::disk('public')->url('company/first.png'), $first['logo_url']);

        $company->update([
            'company_name' => 'Updated Company',
            'address' => 'New office address',
            'report_footer_text' => 'Updated footer',
            'logo_path' => 'company/second.png',
        ]);

        $updated = $settingsService->forReport();
        $this->assertSame('Updated Company', $updated['header']['company_name']);
        $this->assertSame('New office address, Test City, Test Country', $updated['header']['address']);
        $this->assertSame('Updated footer', $updated['footer']['text']);
        $this->assertSame(Storage::disk('public')->path('company/second.png'), $updated['logo_path']);

        $clientSettings = $settingsService->forInertia();
        $this->assertSame('Updated Company', $clientSettings['company_name']);
        $this->assertSame(Storage::disk('public')->url('company/second.png'), $clientSettings['logo_url']);
        $this->assertSame('Updated footer', $clientSettings['report_footer_text']);
    }

    public function test_report_preview_and_pdf_use_current_database_company_settings(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $company = $this->createCompany([
            'company_name' => 'Current Report Company',
            'address' => 'Current Report Address',
            'report_footer_text' => 'Current Report Footer',
        ]);

        $this->actingAs($admin)
            ->get(route('reports.show', 'hr_directory'))
            ->assertOk()
            ->assertSee('Current Report Company')
            ->assertSee('Current Report Address')
            ->assertSee('Current Report Footer');

        $company->update([
            'company_name' => 'Changed Report Company',
            'address' => 'Changed Report Address',
            'report_footer_text' => 'Changed Report Footer',
        ]);

        $this->get(route('reports.show', 'hr_directory'))
            ->assertOk()
            ->assertSee('Changed Report Company')
            ->assertSee('Changed Report Address')
            ->assertSee('Changed Report Footer')
            ->assertDontSee('Current Report Company');

        $this->get(route('reports.pdf', 'hr_directory'))
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf')
            ->assertDownload('employee_directory.pdf');
    }

    public function test_company_settings_form_saves_footer_text_and_replaces_the_report_logo(): void
    {
        Storage::fake('public');
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);
        $company = $this->createCompany();

        $this->actingAs($admin)
            ->post(route('settings.update'), [
                'company_name' => 'Settings Form Company',
                'trading_name' => 'Settings Form Trading',
                'registration_no' => 'REG-NEW',
                'pan_vat_no' => 'PAN-NEW',
                'report_footer_text' => 'Footer from Settings',
                'email' => 'settings@example.test',
                'phone' => '555-0199',
                'website' => 'https://example.test',
                'address' => 'Updated Address',
                'city' => 'Updated City',
                'country' => 'Updated Country',
                'default_currency' => 'USD',
                'currency_symbol' => '$',
                'fiscal_year' => '2026/2027',
                'id_card_template' => 'corporate_modern',
                'attendance_grace_minutes' => 15,
                'annual_leave_quota' => 18,
                'default_tax_rate' => 1,
                'default_provident_fund_rate' => 10,
                'logo' => UploadedFile::fake()->image('updated-logo.png'),
            ])
            ->assertRedirect();

        $company->refresh();
        $this->assertSame('Settings Form Company', $company->company_name);
        $this->assertSame('Footer from Settings', $company->report_footer_text);
        $this->assertNotNull($company->logo_path);
        Storage::disk('public')->assertExists($company->logo_path);

        $reportSettings = app(CorporateReportSettingsService::class)->forReport();
        $this->assertSame('Settings Form Company', $reportSettings['header']['company_name']);
        $this->assertSame('Footer from Settings', $reportSettings['footer']['text']);
        $this->assertNotNull($reportSettings['logo_path']);
    }

    public function test_employee_document_variables_use_fresh_company_settings_without_identity_fallbacks(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company/document-logo.png', base64_decode($this->transparentPng()));
        $company = $this->createCompany([
            'company_name' => 'Document Settings Company',
            'address' => 'Document Settings Address',
            'email' => 'documents@example.test',
            'phone' => '555-0110',
            'website' => 'https://documents.example.test',
            'registration_no' => 'REG-DOCS',
            'pan_vat_no' => 'PAN-DOCS',
            'logo_path' => 'company/document-logo.png',
        ]);
        $employee = Employee::create([
            'employee_code' => 'EMP-100',
            'first_name' => 'Test',
            'last_name' => 'Employee',
            'email' => 'employee@example.test',
            'phone' => '555-0120',
            'gender' => 'other',
            'joining_date' => '2025-01-01',
            'status' => 'active',
        ]);
        $documentService = app(DocumentGenerationService::class);

        $variables = $documentService->compileVariables($employee);
        $this->assertSame('Document Settings Company', $variables['{{company.name}}']);
        $this->assertSame('Document Settings Address', $variables['{{company.address}}']);
        $this->assertSame('REG-DOCS', $variables['{{company.registration_no}}']);
        $this->assertSame('PAN-DOCS', $variables['{{company.pan_vat_no}}']);
        $this->assertSame('', $variables['{{company.tagline}}']);
        $this->assertStringStartsWith(
            'data:image/png;base64,',
            $documentService->getLogoBase64(app(CorporateReportSettingsService::class)->forReport())
        );

        $company->update(['company_name' => 'Updated Document Settings Company']);
        $updatedVariables = $documentService->compileVariables($employee);
        $this->assertSame('Updated Document Settings Company', $updatedVariables['{{company.name}}']);
    }

    public function test_xlsx_export_contains_dynamic_company_details_footer_and_logo(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company/report-logo.png', base64_decode($this->transparentPng()));
        Storage::disk('public')->put('company/updated-report-logo.png', base64_decode($this->transparentPng()));
        $company = $this->createCompany([
            'company_name' => 'Spreadsheet Company',
            'trading_name' => 'Spreadsheet Trading',
            'report_footer_text' => 'Spreadsheet footer',
            'logo_path' => 'company/report-logo.png',
        ]);

        $definition = [
            'filename' => 'test.xlsx',
            'title' => 'Test Report',
            'metadata' => ['Period' => 'November 2026'],
            'columns' => [
                ['label' => 'Name', 'width' => 24],
                ['label' => 'Total', 'format' => 'currency', 'width' => 18],
            ],
            'rows' => [['Example', 42]],
            'summary' => [
                ['label' => 'Records', 'value' => 1, 'format' => 'number'],
                ['label' => 'Total Value', 'value' => 42, 'format' => 'currency'],
            ],
            'totals' => ['label' => 'TOTAL', 'formulas' => [1 => 'sum']],
        ];
        $response = app(CorporateExcelReportService::class)->download($definition);

        ob_start();
        $response->sendContent();
        $xlsxContents = ob_get_clean();
        $this->assertIsString($xlsxContents);
        $this->assertStringStartsWith('PK', $xlsxContents);

        $temporaryFile = tempnam(sys_get_temp_dir(), 'report-');
        try {
            file_put_contents($temporaryFile, $xlsxContents);
            $workbook = IOFactory::load($temporaryFile);
            $sheet = $workbook->getActiveSheet();
            $values = array_merge(...$sheet->toArray(null, true, true, false));
            $content = implode(' ', array_filter($values, 'is_string'));

            $this->assertStringContainsString('Spreadsheet Company', $content);
            $this->assertStringContainsString('Spreadsheet Trading', $content);
            $this->assertStringContainsString('Spreadsheet footer', $content);
            $this->assertStringContainsString('Example', $content);
            $this->assertCount(1, $sheet->getDrawingCollection());
            $this->assertNotEmpty($sheet->getMergeCells());
            $this->assertSame('A16:B17', $sheet->getAutoFilter()->getRange());
            $this->assertSame('A17', $sheet->getFreezePane());
            $this->assertSame('A1:F21', $sheet->getPageSetup()->getPrintArea());

            $workbook->disconnectWorksheets();

            $company->update([
                'company_name' => 'Updated Database Company',
                'address' => 'Updated Database Office',
                'report_footer_text' => 'Updated database footer',
                'logo_path' => 'company/updated-report-logo.png',
            ]);
            $updatedResponse = app(CorporateExcelReportService::class)->download($definition);
            ob_start();
            $updatedResponse->sendContent();
            $updatedContents = ob_get_clean();
            file_put_contents($temporaryFile, $updatedContents);
            $updatedWorkbook = IOFactory::load($temporaryFile);
            $updatedSheet = $updatedWorkbook->getActiveSheet();
            $updatedValues = array_merge(...$updatedSheet->toArray(null, true, true, false));
            $updatedContent = implode(' ', array_filter($updatedValues, 'is_string'));

            $this->assertStringContainsString('Updated Database Company', $updatedContent);
            $this->assertStringContainsString('Updated Database Office', $updatedContent);
            $this->assertStringContainsString('Updated database footer', $updatedContent);
            $this->assertStringNotContainsString('Spreadsheet Company', $updatedContent);
            $this->assertCount(1, $updatedSheet->getDrawingCollection());

            $updatedWorkbook->disconnectWorksheets();
        } finally {
            unlink($temporaryFile);
        }
    }

    public function test_svg_company_logo_is_embedded_as_a_vector_asset_in_xlsx(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put(
            'company/report-logo.svg',
            '<svg xmlns="http://www.w3.org/2000/svg" width="200" height="100" viewBox="0 0 200 100"><rect width="200" height="100" fill="#123456"/></svg>'
        );
        $this->createCompany(['logo_path' => 'company/report-logo.svg']);

        $response = app(CorporateExcelReportService::class)->download([
            'filename' => 'test.xlsx',
            'title' => 'Test Report',
            'columns' => [['label' => 'Name']],
            'rows' => [['Example']],
        ]);
        ob_start();
        $response->sendContent();
        $xlsxContents = ob_get_clean();
        $temporaryFile = tempnam(sys_get_temp_dir(), 'report-');

        try {
            file_put_contents($temporaryFile, $xlsxContents);
            $archive = new \ZipArchive;
            $this->assertSame(true, $archive->open($temporaryFile));
            $this->assertStringContainsString('<svg', $archive->getFromName('xl/media/company-logo.svg'));
            $this->assertStringContainsString('asvg:svgBlip', $archive->getFromName('xl/drawings/drawing1.xml'));
            $archive->close();
        } finally {
            unlink($temporaryFile);
        }
    }

    private function createCompany(array $attributes = []): CompanySetting
    {
        return CompanySetting::create(array_merge([
            'company_name' => 'Configured Company',
            'trading_name' => 'Configured Trading',
            'email' => 'reports@example.test',
            'phone' => '555-0100',
            'address' => 'Test Address',
            'city' => 'Test City',
            'country' => 'Test Country',
            'default_currency' => 'USD',
            'currency_symbol' => '$',
            'fiscal_year' => '2026/2027',
        ], $attributes));
    }

    private function transparentPng(): string
    {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/YQAAAABJRU5ErkJggg==';
    }
}

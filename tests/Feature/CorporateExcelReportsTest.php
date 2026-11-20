<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\CompanySetting;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\Income;
use App\Models\IncomeCategory;
use App\Models\InventoryItem;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use Tests\TestCase;

class CorporateExcelReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_all_required_report_types_export_actual_filtered_records_with_the_shared_workbook_design(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('company/excel-logo.png', base64_decode($this->transparentPng()));
        CompanySetting::create([
            'company_name' => 'Current Settings Company',
            'trading_name' => 'Settings Trading Name',
            'registration_no' => 'REG-EXCEL-1',
            'pan_vat_no' => 'PAN-EXCEL-1',
            'report_footer_text' => 'Configured report footer',
            'email' => 'reports@example.test',
            'phone' => '555-0199',
            'website' => 'https://reports.example.test',
            'address' => 'Settings Office',
            'city' => 'Settings City',
            'country' => 'Settings Country',
            'default_currency' => 'NPR',
            'currency_symbol' => 'रू',
            'fiscal_year' => '2082/2083',
            'logo_path' => 'company/excel-logo.png',
        ]);
        $admin = User::factory()->create(['role' => 'admin', 'status' => 'active']);

        $employee = Employee::create([
            'first_name' => 'Excel',
            'last_name' => 'Employee',
            'email' => 'excel.employee@example.test',
            'phone' => '555-0101',
            'gender' => 'other',
            'joining_date' => '2026-01-15',
            'basic_salary' => 65000,
            'status' => 'active',
        ]);
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-11-05',
            'status' => 'present',
            'total_hours' => 8.5,
        ]);
        Attendance::create([
            'employee_id' => $employee->id,
            'date' => '2026-12-05',
            'status' => 'present',
            'total_hours' => 8,
        ]);

        $expenseCategory = ExpenseCategory::create(['name' => 'Office Operations', 'code' => 'OFFICE']);
        Expense::create([
            'expense_number' => 'EXP-EXCEL-1',
            'title' => 'November Hosting Audit',
            'category_id' => $expenseCategory->id,
            'amount' => 1250.50,
            'date' => '2026-11-05',
            'payee' => 'Hosting Provider',
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
        ]);
        Expense::create([
            'expense_number' => 'EXP-EXCEL-2',
            'title' => 'Earlier Hosting Charge',
            'category_id' => $expenseCategory->id,
            'amount' => 900,
            'date' => '2026-10-05',
            'payee' => 'Hosting Provider',
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
        ]);

        $incomeCategory = IncomeCategory::create(['name' => 'Support Services', 'code' => 'SUPPORT']);
        Income::create([
            'income_number' => 'INC-EXCEL-1',
            'title' => 'Annual Support Contract',
            'category_id' => $incomeCategory->id,
            'amount' => 4500,
            'date' => '2026-11-08',
            'payment_method' => 'bank_transfer',
            'payer' => 'Client One',
            'status' => 'completed',
        ]);
        Income::create([
            'income_number' => 'INC-EXCEL-2',
            'title' => 'Unrelated Donation',
            'category_id' => $incomeCategory->id,
            'amount' => 100,
            'date' => '2026-11-10',
            'payment_method' => 'cash',
            'payer' => 'Other Source',
            'status' => 'completed',
        ]);
        Income::create([
            'income_number' => 'INC-EXCEL-CURRENT',
            'title' => 'Current Period Revenue',
            'category_id' => $incomeCategory->id,
            'amount' => 650,
            'date' => now()->toDateString(),
            'payment_method' => 'bank_transfer',
            'payer' => 'Current Client',
            'status' => 'completed',
        ]);
        $currentExpenseCategory = ExpenseCategory::create(['name' => 'Utilities', 'code' => 'UTILITY']);
        Expense::create([
            'expense_number' => 'EXP-EXCEL-CURRENT',
            'title' => 'Current Period Utility',
            'category_id' => $currentExpenseCategory->id,
            'amount' => 150,
            'date' => now()->toDateString(),
            'payee' => 'Utility Provider',
            'payment_method' => 'bank_transfer',
            'status' => 'paid',
        ]);
        Salary::create([
            'employee_id' => $employee->id,
            'month' => 11,
            'year' => 2026,
            'salary_period' => 'November 2026',
            'slip_number' => 'SLIP-EXCEL-1',
            'basic_salary' => 65000,
            'allowances' => 5000,
            'bonuses' => 0,
            'gross_salary' => 70000,
            'tax_deduction' => 2500,
            'provident_fund' => 2500,
            'total_deductions' => 5000,
            'net_salary' => 65000,
            'payment_date' => '2026-11-30',
            'status' => 'paid',
            'generated_by' => $admin->id,
        ]);

        InventoryItem::create([
            'item_code' => 'INV-EXCEL-1',
            'name' => 'Printer Paper',
            'category' => 'Stationery',
            'quantity' => 3,
            'unit' => 'ream',
            'min_stock_threshold' => 5,
            'unit_price' => 750,
            'location' => 'Storage Room A',
        ]);
        InventoryItem::create([
            'item_code' => 'INV-EXCEL-2',
            'name' => 'Wireless Mouse',
            'category' => 'Electronics',
            'quantity' => 20,
            'unit' => 'pcs',
            'min_stock_threshold' => 5,
            'unit_price' => 1200,
            'location' => 'IT Cabinet',
        ]);

        $this->actingAs($admin);
        $statementStart = now()->copy()->startOfMonth();
        $statementEnd = now()->copy()->endOfMonth();
        $statementIncome = (float) Income::whereBetween('date', [$statementStart, $statementEnd])->sum('amount');
        $statementExpense = (float) Expense::whereBetween('date', [$statementStart, $statementEnd])->sum('amount');
        $reports = [
            [
                $this->get(route('finance.expenses.report.xlsx', [
                    'start_date' => '2026-11-01',
                    'end_date' => '2026-11-30',
                    'category_id' => $expenseCategory->id,
                ])),
                'November Hosting Audit',
                [1250.5],
                ['CUMULATIVE EXPENSES AUDITED' => 1250.5],
                true,
            ],
            [
                $this->get(route('employees.export.xlsx')),
                $employee->employee_code,
                [65000],
                ['TOTAL EMPLOYEES' => 1, 'BASIC SALARY TOTAL' => 65000],
                false,
            ],
            [
                $this->get(route('attendance.export.xlsx', ['month' => 11, 'year' => 2026])),
                'Excel Employee',
                [],
                ['EMPLOYEES REPORTED' => 1, 'RECORDED HOURS' => 8.5],
                false,
            ],
            [
                $this->get(route('payroll.export.xlsx', ['month' => 11, 'year' => 2026])),
                'SLIP-EXCEL-1',
                [65000, 5000, 70000, 2500, 2500, 5000, 65000],
                ['GROSS PAYROLL' => 70000, 'NET SALARY PAYABLE' => 65000],
                true,
            ],
            [
                $this->get(route('finance.income.report.xlsx', [
                    'search' => 'Annual Support',
                    'category_id' => $incomeCategory->id,
                ])),
                'Annual Support Contract',
                [4500],
                ['INCOME ENTRIES' => 1, 'COMPLETED INCOME' => 4500],
                true,
            ],
            [
                $this->get(route('inventory.export.xlsx', [
                    'search' => 'Paper',
                    'category' => 'Stationery',
                    'low_stock' => 1,
                ])),
                'Printer Paper',
                [2250],
                ['INVENTORY ITEMS' => 1, 'STOCK VALUATION' => 2250],
                false,
            ],
            [
                $this->get(route('finance.statements.xlsx', ['period' => 'monthly'])),
                'Current Period Revenue',
                [],
                [
                    'TOTAL INCOME' => $statementIncome,
                    'TOTAL EXPENSES' => $statementExpense,
                    'NET CASH FLOW' => $statementIncome - $statementExpense,
                ],
                true,
            ],
            [
                $this->get(route('reports.xlsx', ['type' => 'hr_directory'])),
                $employee->employee_code,
                [65000],
                ['TOTAL EMPLOYEES' => 1, 'ACTIVE EMPLOYEES' => 1],
                false,
            ],
            [
                $this->get(route('reports.xlsx', ['type' => 'attendance_summary', 'month' => 11, 'year' => 2026])),
                'Excel Employee',
                [],
                ['EMPLOYEES REPORTED' => 1, 'RECORDED HOURS' => 8.5],
                false,
            ],
            [
                $this->get(route('reports.xlsx', ['type' => 'expense_audit'])),
                'November Hosting Audit',
                [2300.5],
                ['EXPENSE ENTRIES' => 3, 'CUMULATIVE EXPENSES AUDITED' => 2300.5],
                true,
            ],
        ];

        foreach ($reports as [$response, $recordLabel, $expectedFormulaValues, $expectedSummaryValues, $hasSignatures]) {
            $response->assertOk()->assertHeader(
                'content-type',
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
            );
            $workbook = $this->loadWorkbook($response->streamedContent());
            try {
                $this->assertCorporateWorkbook(
                    $workbook,
                    $recordLabel,
                    $expectedFormulaValues,
                    $expectedSummaryValues,
                    $hasSignatures
                );
            } finally {
                $workbook->disconnectWorksheets();
            }
        }
    }

    private function assertCorporateWorkbook(
        Spreadsheet $workbook,
        string $recordLabel,
        array $expectedFormulaValues,
        array $expectedSummaryValues,
        bool $hasSignatures
    ): void
    {
        $sheet = $workbook->getActiveSheet();
        $values = [];
        $formulaValues = [];
        foreach ($sheet->getCoordinates() as $coordinate) {
            $cell = $sheet->getCell($coordinate);
            $values[] = (string) $cell->getValue();
            if ($cell->getDataType() === DataType::TYPE_FORMULA) {
                $formulaValues[] = (float) $cell->getCalculatedValue();
            }
        }

        $content = implode(' ', $values);
        $this->assertStringContainsString('Current Settings Company', $content);
        $this->assertStringContainsString('Settings Office', $content);
        $this->assertStringContainsString('REG-EXCEL-1', $content);
        $this->assertStringContainsString('PAN-EXCEL-1', $content);
        $this->assertStringContainsString('Configured report footer', $content);
        $this->assertStringContainsString($recordLabel, $content);
        $this->assertCount(1, $sheet->getDrawingCollection());
        $logo = $sheet->getDrawingCollection()[0];
        $this->assertGreaterThan(0, $logo->getWidth());
        $this->assertGreaterThan(0, $logo->getHeight());
        $this->assertLessThanOrEqual(170, $logo->getWidth());
        $this->assertLessThanOrEqual(76, $logo->getHeight());
        $this->assertNotEmpty($sheet->getMergeCells());
        $filterRange = $sheet->getAutoFilter()->getRange();
        $this->assertNotSame('', $filterRange);
        $this->assertSame(1, preg_match('/^[A-Z]+(\d+):[A-Z]+(\d+)$/', $filterRange, $rangeParts));
        $headerRow = (int) $rangeParts[1];
        $lastFilteredRow = (int) $rangeParts[2];
        $headerStyle = $sheet->getStyle("A{$headerRow}");
        $this->assertSame('202124', strtoupper($headerStyle->getFill()->getStartColor()->getRGB()));
        $this->assertSame('FFFFFF', strtoupper($headerStyle->getFont()->getColor()->getRGB()));
        $this->assertSame('FF5500', strtoupper($headerStyle->getBorders()->getBottom()->getColor()->getRGB()));
        $this->assertSame('FFFFFF', strtoupper($sheet->getStyle('A'.($headerRow + 1))->getFill()->getStartColor()->getRGB()));
        if ($lastFilteredRow >= $headerRow + 2) {
            $this->assertSame('F7F8FA', strtoupper($sheet->getStyle('A'.($headerRow + 2))->getFill()->getStartColor()->getRGB()));
        }
        $this->assertNotNull($sheet->getFreezePane());
        $this->assertSame(PageSetup::PAPERSIZE_A4, $sheet->getPageSetup()->getPaperSize());
        $this->assertSame(PageSetup::ORIENTATION_LANDSCAPE, $sheet->getPageSetup()->getOrientation());
        $this->assertNotSame('', $sheet->getPageSetup()->getPrintArea());
        $this->assertStringContainsString('Page &P of &N', $sheet->getHeaderFooter()->getOddFooter());
        $this->assertStringContainsString('Current Settings Company', $sheet->getHeaderFooter()->getOddFooter());
        $this->assertSame(
            $sheet->getPageSetup()->getRowsToRepeatAtTop()[0],
            $sheet->getPageSetup()->getRowsToRepeatAtTop()[1]
        );
        sort($formulaValues);
        sort($expectedFormulaValues);
        $this->assertCount(count($expectedFormulaValues), $formulaValues);
        foreach ($expectedFormulaValues as $index => $expectedValue) {
            $this->assertEqualsWithDelta($expectedValue, $formulaValues[$index], 0.01);
        }
        foreach ($expectedSummaryValues as $label => $expectedValue) {
            $this->assertSummaryCardValue($sheet, $label, $expectedValue);
        }
        foreach (['PREPARED BY', 'VERIFIED / AUDITED BY', 'AUTHORIZED BY'] as $signatureLabel) {
            $this->assertSame(
                $hasSignatures,
                in_array($signatureLabel, $values, true),
                "Unexpected signature block {$signatureLabel}."
            );
        }
    }

    private function assertSummaryCardValue($sheet, string $label, float|int $expectedValue): void
    {
        foreach ($sheet->getCoordinates() as $coordinate) {
            $cell = $sheet->getCell($coordinate);
            if (mb_strtoupper((string) $cell->getValue()) !== $label) {
                continue;
            }

            $value = $sheet->getCell($cell->getColumn().($cell->getRow() + 1))->getValue();
            $this->assertEqualsWithDelta($expectedValue, (float) $value, 0.01, "Incorrect {$label} summary card.");
            $this->assertSame(
                'FFF0E8',
                strtoupper($sheet->getStyle($coordinate)->getFill()->getStartColor()->getRGB())
            );

            return;
        }

        $this->fail("The {$label} summary card is missing.");
    }

    private function loadWorkbook(string $contents): Spreadsheet
    {
        $temporaryFile = tempnam(sys_get_temp_dir(), 'corporate-xlsx-test-');
        if ($temporaryFile === false) {
            $this->fail('Unable to allocate a temporary workbook for inspection.');
        }

        try {
            file_put_contents($temporaryFile, $contents);

            return IOFactory::load($temporaryFile);
        } finally {
            @unlink($temporaryFile);
        }
    }

    private function transparentPng(): string
    {
        return 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAwMCAO+/YQAAAABJRU5ErkJggg==';
    }
}

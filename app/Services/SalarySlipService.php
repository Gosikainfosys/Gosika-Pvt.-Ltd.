<?php

namespace App\Services;

use App\Models\Salary;

class SalarySlipService
{
    public function __construct(
        private PdfDocumentService $documents,
        private CorporateReportSettingsService $corporateSettings
    ) {}

    public function generateSlipPdf(Salary $salary)
    {
        $salary->load(['employee.department', 'employee.designation']);
        $reportSettings = $this->corporateSettings->forReport();

        return $this->documents->render('pdf.salary_slip', [
            'salary' => $salary,
            'reportSettings' => $reportSettings,
            'generatedAt' => now(),
        ], 'a4', 'landscape');
    }

    public function generateBulkPayrollPdf($salaries, string $period)
    {
        $reportSettings = $this->corporateSettings->forReport();

        return $this->documents->render('pdf.bulk_payroll_slips', [
            'salaries' => $salaries,
            'reportSettings' => $reportSettings,
            'generatedAt' => now(),
            'period' => $period,
        ], 'a4', 'landscape');
    }
}

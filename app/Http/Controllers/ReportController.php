<?php

namespace App\Http\Controllers;

use App\Models\Asset;
use App\Models\Attendance;
use App\Models\AuditLog;
use App\Models\Employee;
use App\Models\Expense;
use App\Models\Income;
use App\Models\InventoryItem;
use App\Models\Salary;
use App\Services\CorporateExcelReportService;
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportController extends Controller
{
    public function index()
    {
        return Inertia::render('Reports/Index', [
            'metrics' => [
                'employees_count' => Employee::count(),
                'monthly_payroll' => (float) Salary::where('month', Carbon::now()->month)->where('year', Carbon::now()->year)->sum('net_salary'),
                'total_income' => (float) Income::where('status', 'completed')->sum('amount'),
                'total_expense' => (float) Expense::where('status', 'paid')->sum('amount'),
                'assets_valuation' => (float) Asset::sum('purchase_cost'),
                'inventory_valuation' => (float) InventoryItem::selectRaw('SUM(quantity * unit_price) as total')->value('total'),
            ],
        ]);
    }

    public function show(string $type, Request $request, ReportService $reportService)
    {
        $report = $this->buildReport($type, $request);
        if ($report === null) {
            return back()->with('error', 'Unknown report type requested.');
        }

        return view(
            "pdf.reports.{$report['view']}",
            $reportService->prepareReportData($report['data'])
        );
    }

    public function downloadPdf(string $type, Request $request, ReportService $reportService)
    {
        $report = $this->buildReport($type, $request);
        if ($report === null) {
            return back()->with('error', 'Unknown report type requested.');
        }

        $pdf = $reportService->generateReportPdf(
            $report['view'],
            $report['data'],
            $report['orientation']
        );

        AuditLog::log('export', 'reports', null, "Exported PDF Report: {$type}");

        return $pdf->download($report['pdf_filename']);
    }

    public function downloadXlsx(string $type, Request $request, CorporateExcelReportService $reportService)
    {
        $report = $this->buildReport($type, $request);
        if ($report === null) {
            return back()->with('error', 'Unknown report type requested.');
        }

        $definition = $this->spreadsheetDefinition($type, $report['data']);
        $response = $reportService->download([
            'filename' => $report['xlsx_filename'],
            'title' => $report['title'],
            'metadata' => $report['metadata'],
            ...$definition,
        ]);

        AuditLog::log('export', 'reports', null, "Exported XLSX Report: {$type}");

        return $response;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function buildReport(string $type, Request $request): ?array
    {
        switch ($type) {
            case 'hr_directory':
                $employees = Employee::with(['department', 'designation'])->get();

                return [
                    'view' => 'hr_directory',
                    'title' => 'Employee Directory Report',
                    'orientation' => 'landscape',
                    'data' => ['employees' => $employees],
                    'metadata' => ['Period' => 'All Records'],
                    'pdf_filename' => 'employee_directory.pdf',
                    'xlsx_filename' => 'employee_directory.xlsx',
                ];

            case 'attendance_summary':
                $month = (int) $request->input('month', Carbon::now()->month);
                $year = (int) $request->input('year', Carbon::now()->year);
                $startDate = Carbon::createFromDate($year, $month, 1)->startOfMonth();
                $endDate = $startDate->copy()->endOfMonth();

                $employees = Employee::with('department')->where('status', 'active')->get();
                $attendances = Attendance::whereBetween('date', [
                    $startDate->format('Y-m-d'),
                    $endDate->format('Y-m-d'),
                ])->get();

                $summary = [];
                foreach ($employees as $employee) {
                    $records = $attendances->where('employee_id', $employee->id);
                    $summary[] = [
                        'employee' => $employee,
                        'present' => $records->where('status', 'present')->count(),
                        'late' => $records->where('status', 'late')->count(),
                        'half_day' => $records->where('status', 'half_day')->count(),
                        'absent_leave' => $records->whereIn('status', ['absent', 'on_leave'])->count(),
                        'total_hours' => $records->sum('total_hours'),
                    ];
                }

                return [
                    'view' => 'attendance_summary',
                    'title' => 'Monthly Attendance Audit Ledger',
                    'orientation' => 'landscape',
                    'data' => [
                        'summary' => $summary,
                        'monthName' => $startDate->format('F Y'),
                    ],
                    'metadata' => ['Period' => $startDate->format('F Y')],
                    'pdf_filename' => "attendance_summary_{$year}_{$month}.pdf",
                    'xlsx_filename' => "attendance_summary_{$year}_{$month}.xlsx",
                ];

            case 'expense_audit':
                $expenses = Expense::with('category')->latest('date')->get();

                return [
                    'view' => 'expense_report',
                    'title' => 'Corporate Expense Audit Statement',
                    'orientation' => 'portrait',
                    'data' => [
                        'expenses' => $expenses,
                        'totalAmount' => $expenses->sum('amount'),
                        'dateRange' => 'Full Historical Log',
                        'categorySummary' => $expenses->groupBy('category.name'),
                    ],
                    'metadata' => ['Date Range' => 'Full Historical Log'],
                    'pdf_filename' => 'expense_audit.pdf',
                    'xlsx_filename' => 'expense_audit.xlsx',
                ];
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    private function spreadsheetDefinition(string $type, array $data): array
    {
        if ($type === 'hr_directory') {
            $rows = [];
            foreach ($data['employees'] as $employee) {
                $rows[] = [
                    $employee->employee_code,
                    $employee->full_name,
                    $employee->department->name ?? null,
                    $employee->designation->title ?? null,
                    $employee->email,
                    $employee->phone,
                    $employee->joining_date,
                    $employee->basic_salary === null ? null : (float) $employee->basic_salary,
                    strtoupper($employee->status),
                ];
            }

            return [
                'columns' => [
                    ['label' => 'Employee ID', 'width' => 17],
                    ['label' => 'Full Name', 'width' => 24],
                    ['label' => 'Department', 'width' => 20],
                    ['label' => 'Designation', 'width' => 20],
                    ['label' => 'Official Email', 'width' => 28],
                    ['label' => 'Phone', 'width' => 17],
                    ['label' => 'Joined', 'format' => 'date', 'width' => 15],
                    ['label' => 'Basic Salary', 'format' => 'currency', 'width' => 17],
                    ['label' => 'Status', 'width' => 14],
                ],
                'rows' => $rows,
                'summary' => [
                    ['label' => 'Total Employees', 'value' => count($rows), 'format' => 'integer'],
                    ['label' => 'Active Employees', 'value' => $data['employees']->where('status', 'active')->count(), 'format' => 'integer'],
                    ['label' => 'Departments', 'value' => $data['employees']->pluck('department.name')->filter()->unique()->count(), 'format' => 'integer'],
                ],
                'totals' => ['label' => 'TOTAL BASIC SALARY', 'formulas' => [7 => 'sum']],
                'orientation' => 'landscape',
            ];
        }

        if ($type === 'attendance_summary') {
            $rows = [];
            foreach ($data['summary'] as $summary) {
                $rows[] = [
                    $summary['employee']->employee_code,
                    $summary['employee']->full_name,
                    $summary['employee']->department->name ?? null,
                    $summary['present'],
                    $summary['late'],
                    $summary['half_day'],
                    $summary['absent_leave'],
                    $summary['total_hours'],
                ];
            }

            return [
                'columns' => [
                    ['label' => 'Employee Code', 'width' => 17],
                    ['label' => 'Full Name', 'width' => 24],
                    ['label' => 'Department', 'width' => 22],
                    ['label' => 'Present Days', 'format' => 'integer', 'width' => 15],
                    ['label' => 'Late Days', 'format' => 'integer', 'width' => 14],
                    ['label' => 'Half Days', 'format' => 'integer', 'width' => 14],
                    ['label' => 'Absent / Leave', 'format' => 'integer', 'width' => 17],
                    ['label' => 'Total Hours', 'format' => 'decimal', 'width' => 15],
                ],
                'rows' => $rows,
                'summary' => [
                    ['label' => 'Employees Reported', 'value' => count($rows), 'format' => 'integer'],
                    ['label' => 'Present Days', 'value' => array_sum(array_column($rows, 3)), 'format' => 'integer'],
                    ['label' => 'Recorded Hours', 'value' => array_sum(array_column($rows, 7)), 'format' => 'decimal'],
                ],
                'orientation' => 'landscape',
            ];
        }

        $rows = [];
        foreach ($data['expenses'] as $expense) {
            $rows[] = [
                $expense->expense_number,
                $expense->date,
                $expense->title,
                $expense->description,
                $expense->category->name ?? null,
                $expense->payee,
                str_replace('_', ' ', $expense->payment_method),
                strtoupper($expense->status),
                (float) $expense->amount,
            ];
        }

        return [
            'columns' => [
                ['label' => 'Expense Voucher', 'width' => 20],
                ['label' => 'Date', 'format' => 'date', 'width' => 15],
                ['label' => 'Title', 'width' => 25],
                ['label' => 'Description', 'width' => 34],
                ['label' => 'Category', 'width' => 20],
                ['label' => 'Payee', 'width' => 22],
                ['label' => 'Payment Method', 'width' => 18],
                ['label' => 'Status', 'width' => 14],
                ['label' => 'Amount', 'format' => 'currency', 'width' => 17],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Expense Entries', 'value' => $data['expenses']->count(), 'format' => 'integer'],
                ['label' => 'Cumulative Expenses Audited', 'value' => (float) $data['totalAmount'], 'format' => 'currency'],
                ['label' => 'Expense Categories', 'value' => $data['expenses']->pluck('category.name')->filter()->unique()->count(), 'format' => 'integer'],
                ['label' => 'Paid Amount', 'value' => (float) $data['expenses']->where('status', 'paid')->sum('amount'), 'format' => 'currency'],
                ['label' => 'Outstanding Amount', 'value' => (float) $data['expenses']->where('status', 'pending')->sum('amount'), 'format' => 'currency'],
            ],
            'totals' => ['label' => 'TOTAL EXPENSES', 'formulas' => [8 => 'sum']],
            'signatures' => true,
            'orientation' => 'landscape',
        ];
    }
}

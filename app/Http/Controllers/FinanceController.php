<?php

namespace App\Http\Controllers;

use App\Models\Income;
use App\Models\Expense;
use App\Models\IncomeCategory;
use App\Models\ExpenseCategory;
use App\Models\CompanySetting;
use App\Models\AuditLog;
use App\Services\CorporateExcelReportService;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage;

class FinanceController extends Controller
{
    public function dashboard(Request $request)
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        $allIncome = (float) Income::where('status', 'completed')->sum('amount');
        $allExpenses = (float) Expense::where('status', 'paid')->sum('amount');
        $currentBalance = $allIncome - $allExpenses;

        $monthlyIncome = (float) Income::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'completed')
            ->sum('amount');

        $monthlyExpenses = (float) Expense::whereBetween('date', [$startOfMonth, $endOfMonth])
            ->where('status', 'paid')
            ->sum('amount');

        // Monthly cashflow trend (last 6 months)
        $chartData = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $mStart = $month->copy()->startOfMonth();
            $mEnd = $month->copy()->endOfMonth();

            $inc = (float) Income::whereBetween('date', [$mStart, $mEnd])->where('status', 'completed')->sum('amount');
            $exp = (float) Expense::whereBetween('date', [$mStart, $mEnd])->where('status', 'paid')->sum('amount');

            $chartData[] = [
                'month' => $month->format('M Y'),
                'income' => $inc,
                'expense' => $exp,
                'net' => $inc - $exp,
            ];
        }

        // Category breakdown
        $expenseCategoryBreakdown = ExpenseCategory::withSum('expenses', 'amount')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'name' => $c->name,
                'budget' => (float) $c->monthly_budget,
                'spent' => (float) ($c->expenses_sum_amount ?? 0),
            ]);

        // Recent transactions
        $recentIncomes = Income::with('category')->latest('date')->limit(4)->get();
        $recentExpenses = Expense::with('category')->latest('date')->limit(4)->get();

        return Inertia::render('Finance/Dashboard', [
            'stats' => [
                'all_income' => $allIncome,
                'all_expenses' => $allExpenses,
                'current_balance' => $currentBalance,
                'monthly_income' => $monthlyIncome,
                'monthly_expenses' => $monthlyExpenses,
                'monthly_net' => $monthlyIncome - $monthlyExpenses,
            ],
            'chartData' => $chartData,
            'categoryBreakdown' => $expenseCategoryBreakdown,
            'recentIncomes' => $recentIncomes,
            'recentExpenses' => $recentExpenses,
        ]);
    }

    public function incomeIndex(Request $request)
    {
        $incomes = $this->incomeQuery($request)->latest('date')->paginate(12)->withQueryString();
        $categories = IncomeCategory::all();

        return Inertia::render('Finance/Income/Index', [
            'incomes' => $incomes,
            'categories' => $categories,
            'filters' => $request->only(['category_id', 'search']),
        ]);
    }

    public function downloadIncomeReportXlsx(Request $request, CorporateExcelReportService $reportService)
    {
        $incomes = $this->incomeQuery($request)->latest('date')->get();
        $rows = $incomes->map(fn (Income $income) => [
            $income->income_number,
            $income->title,
            $income->category->name ?? null,
            $income->date,
            str_replace('_', ' ', $income->payment_method),
            $income->payer,
            (float) $income->amount,
            strtoupper($income->status),
        ])->all();

        $metadata = [];
        if ($request->filled('category_id')) {
            $metadata['Category'] = IncomeCategory::whereKey($request->input('category_id'))->value('name');
        }
        if ($request->filled('search')) {
            $metadata['Search'] = $request->input('search');
        }

        AuditLog::log('export', 'incomes', null, 'Exported Corporate Income Report XLSX');

        return $reportService->download([
            'filename' => 'corporate_income_report.xlsx',
            'title' => 'Corporate Income Report',
            'metadata' => $metadata,
            'columns' => [
                ['label' => 'Income Reference', 'width' => 20],
                ['label' => 'Title', 'width' => 26],
                ['label' => 'Category', 'width' => 21],
                ['label' => 'Date', 'format' => 'date', 'width' => 15],
                ['label' => 'Payment Method', 'width' => 19],
                ['label' => 'Payer', 'width' => 24],
                ['label' => 'Amount', 'format' => 'currency', 'width' => 17],
                ['label' => 'Status', 'width' => 15],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Income Entries', 'value' => $incomes->count(), 'format' => 'number'],
                ['label' => 'Completed Income', 'value' => (float) $incomes->where('status', 'completed')->sum('amount'), 'format' => 'currency'],
                ['label' => 'Pending Income', 'value' => (float) $incomes->where('status', 'pending')->sum('amount'), 'format' => 'currency'],
            ],
            'totals' => ['label' => 'TOTAL INCOME', 'formulas' => [6 => 'sum']],
            'signatures' => true,
            'orientation' => 'landscape',
        ]);
    }

    private function incomeQuery(Request $request)
    {
        $query = Income::with(['category', 'creator']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->input('category_id'));
        }
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($query) use ($search) {
                $query->where('title', 'like', "%{$search}%")
                    ->orWhere('income_number', 'like', "%{$search}%")
                    ->orWhere('payer', 'like', "%{$search}%");
            });
        }

        return $query;
    }

    public function storeIncome(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:income_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payment_method' => ['required', 'string'],
            'payer' => ['required', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'receipt' => ['nullable', 'file', 'max:4096'],
        ]);

        $lastId = Income::max('id') ?? 0;
        $validated['income_number'] = 'GSK-INC-' . date('Y') . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        $validated['status'] = 'completed';
        $validated['created_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('finance/income_receipts', 'public');
        }

        $income = Income::create($validated);
        AuditLog::log('create', 'incomes', $income->id, "Recorded corporate income: {$income->title} (NPR {$income->amount})");

        return back()->with('success', 'Income transaction recorded successfully.');
    }

    public function expenseIndex(Request $request)
    {
        $query = Expense::with(['category', 'approver']);

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('title', 'like', "%{$s}%")
                  ->orWhere('expense_number', 'like', "%{$s}%")
                  ->orWhere('payee', 'like', "%{$s}%");
            });
        }
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
        }

        $expenses = $query->latest('date')->paginate(12)->withQueryString();
        $categories = ExpenseCategory::all();

        return Inertia::render('Finance/Expenses/Index', [
            'expenses' => $expenses,
            'categories' => $categories,
            'filters' => $request->only(['category_id', 'search', 'start_date', 'end_date']),
        ]);
    }

    public function storeExpense(Request $request)
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:expense_categories,id'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'date' => ['required', 'date'],
            'payee' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', 'string'],
            'payment_reference' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'receipt' => ['nullable', 'file', 'max:4096'],
        ]);

        $lastId = Expense::max('id') ?? 0;
        $validated['expense_number'] = 'GSK-EXP-' . date('Y') . '-' . str_pad($lastId + 1, 4, '0', STR_PAD_LEFT);
        $validated['status'] = 'paid';
        $validated['approved_by'] = auth()->id();

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('finance/expense_receipts', 'public');
        }

        $expense = Expense::create($validated);
        AuditLog::log('create', 'expenses', $expense->id, "Recorded corporate expense: {$expense->title} (NPR {$expense->amount})");

        return back()->with('success', 'Expense transaction logged successfully.');
    }

    public function downloadExpenseReportPdf(Request $request, ReportService $reportService)
    {
        $data = $this->expenseReportData($request);
        $pdf = $reportService->generateReportPdf('expense_report', $data, 'portrait');

        AuditLog::log('export', 'expenses', null, "Exported Expense Audit Report PDF ({$data['dateRange']})");

        return $pdf->download('expense_audit_report.pdf');
    }

    public function showExpenseReport(Request $request, ReportService $reportService)
    {
        return view(
            'pdf.reports.expense_report',
            $reportService->prepareReportData($this->expenseReportData($request))
        );
    }

    public function downloadExpenseReportXlsx(Request $request, CorporateExcelReportService $reportService)
    {
        $data = $this->expenseReportData($request);
        $rows = $data['expenses']->map(fn (Expense $expense) => [
                $expense->expense_number,
                $expense->date,
                $expense->title,
                $expense->description,
                $expense->category->name ?? null,
                $expense->payee,
                str_replace('_', ' ', $expense->payment_method),
                strtoupper($expense->status),
                (float) $expense->amount,
            ])->all();

        AuditLog::log('export', 'expenses', null, "Exported Expense Audit Report XLSX ({$data['dateRange']})");

        return $reportService->download([
            'filename' => 'expense_audit_report.xlsx',
            'title' => 'Corporate Expense Audit Statement',
            'metadata' => ['Date Range' => $data['dateRange']],
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
                ['label' => 'Expense Entries', 'value' => $data['expenses']->count(), 'format' => 'number'],
                ['label' => 'Cumulative Expenses Audited', 'value' => (float) $data['totalAmount'], 'format' => 'currency'],
                ['label' => 'Expense Categories', 'value' => $data['expenses']->pluck('category.name')->filter()->unique()->count(), 'format' => 'number'],
                ['label' => 'Paid Amount', 'value' => (float) $data['expenses']->where('status', 'paid')->sum('amount'), 'format' => 'currency'],
                ['label' => 'Outstanding Amount', 'value' => (float) $data['expenses']->where('status', 'pending')->sum('amount'), 'format' => 'currency'],
            ],
            'totals' => ['label' => 'TOTAL EXPENSES', 'formulas' => [8 => 'sum']],
            'signatures' => true,
            'orientation' => 'landscape',
        ]);
    }

    private function expenseReportData(Request $request): array
    {
        $query = Expense::with('category');
        $dateRangeText = 'All Historical Dates';

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('date', [$request->start_date, $request->end_date]);
            $dateRangeText = "{$request->start_date} to {$request->end_date}";
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $expenses = $query->latest('date')->get();

        return [
            'expenses' => $expenses,
            'totalAmount' => $expenses->sum('amount'),
            'dateRange' => $dateRangeText,
            'categorySummary' => $expenses->groupBy('category.name'),
        ];
    }

    public function statements(Request $request)
    {
        $period = $request->input('period', 'monthly'); // monthly, quarterly, yearly
        $now = Carbon::now();

        if ($period === 'quarterly') {
            $startDate = $now->copy()->firstOfQuarter();
            $endDate = $now->copy()->lastOfQuarter();
            $periodLabel = "Quarter {$now->quarter} - " . $now->year;
        } elseif ($period === 'yearly') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
            $periodLabel = "Year " . $now->year;
        } else {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
            $periodLabel = $now->format('F Y');
        }

        $incomes = Income::with('category')->whereBetween('date', [$startDate, $endDate])->get();
        $expenses = Expense::with('category')->whereBetween('date', [$startDate, $endDate])->get();

        $totalIncome = $incomes->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $netCashFlow = $totalIncome - $totalExpense;

        $incomeCategories = IncomeCategory::withCount(['incomes' => fn($q) => $q->whereBetween('date', [$startDate, $endDate])])
            ->withSum(['incomes' => fn($q) => $q->whereBetween('date', [$startDate, $endDate])], 'amount')
            ->get();

        $expenseCategories = ExpenseCategory::withCount(['expenses' => fn($q) => $q->whereBetween('date', [$startDate, $endDate])])
            ->withSum(['expenses' => fn($q) => $q->whereBetween('date', [$startDate, $endDate])], 'amount')
            ->get();

        return Inertia::render('Finance/Statements', [
            'period' => $period,
            'periodLabel' => $periodLabel,
            'totalIncome' => (float) $totalIncome,
            'totalExpense' => (float) $totalExpense,
            'netCashFlow' => (float) $netCashFlow,
            'incomeCategories' => $incomeCategories,
            'expenseCategories' => $expenseCategories,
            'recentIncomes' => $incomes->take(6),
            'recentExpenses' => $expenses->take(6),
        ]);
    }

    public function downloadStatementPdf(Request $request, ReportService $reportService)
    {
        $period = $request->input('period', 'monthly');
        $data = $this->financialStatementData($period);
        $pdf = $reportService->generateReportPdf('financial_statement', $data, 'portrait');

        AuditLog::log('export', 'finance', null, "Exported Financial Statement PDF ({$data['periodName']})");

        return $pdf->download("financial_statement_{$period}.pdf");
    }

    public function showStatement(Request $request, ReportService $reportService)
    {
        $data = $this->financialStatementData($request->input('period', 'monthly'));

        return view(
            'pdf.reports.financial_statement',
            $reportService->prepareReportData($data)
        );
    }

    public function downloadStatementXlsx(Request $request, CorporateExcelReportService $reportService)
    {
        $period = $request->input('period', 'monthly');
        $data = $this->financialStatementData($period);
        $rows = $data['transactions']->map(fn (array $transaction) => [
                strtoupper($transaction['type']),
                $transaction['ref'],
                $transaction['date'],
                $transaction['title'],
                $transaction['category'],
                (float) $transaction['amount'],
            ])->all();

        AuditLog::log('export', 'finance', null, "Exported Financial Statement XLSX ({$data['periodName']})");

        return $reportService->download([
            'filename' => "financial_statement_{$period}.xlsx",
            'title' => 'Statement of Income & Expenditure',
            'metadata' => ['Reporting Period' => $data['periodName']],
            'columns' => [
                ['label' => 'Type', 'width' => 16],
                ['label' => 'Transaction Reference', 'width' => 23],
                ['label' => 'Date', 'format' => 'date', 'width' => 15],
                ['label' => 'Account / Description', 'width' => 32],
                ['label' => 'Category', 'width' => 24],
                ['label' => 'Amount', 'format' => 'currency', 'width' => 18],
            ],
            'rows' => $rows,
            'summary' => [
                ['label' => 'Total Income', 'value' => (float) $data['totalIncome'], 'format' => 'currency'],
                ['label' => 'Total Expenses', 'value' => (float) $data['totalExpense'], 'format' => 'currency'],
                ['label' => 'Net Cash Flow', 'value' => (float) ($data['totalIncome'] - $data['totalExpense']), 'format' => 'currency'],
            ],
            'signatures' => true,
            'orientation' => 'landscape',
        ]);
    }

    private function financialStatementData(string $period): array
    {
        $now = Carbon::now();

        if ($period === 'quarterly') {
            $startDate = $now->copy()->firstOfQuarter();
            $endDate = $now->copy()->lastOfQuarter();
            $periodLabel = "Quarter {$now->quarter} - " . $now->year;
        } elseif ($period === 'yearly') {
            $startDate = $now->copy()->startOfYear();
            $endDate = $now->copy()->endOfYear();
            $periodLabel = "Year " . $now->year;
        } else {
            $startDate = $now->copy()->startOfMonth();
            $endDate = $now->copy()->endOfMonth();
            $periodLabel = $now->format('F Y');
        }

        $incomes = Income::with('category')->whereBetween('date', [$startDate, $endDate])->get();
        $expenses = Expense::with('category')->whereBetween('date', [$startDate, $endDate])->get();
        $totalIncome = $incomes->sum('amount');
        $totalExpense = $expenses->sum('amount');
        $transactions = $incomes->map(fn (Income $income) => [
            'type' => 'income',
            'ref' => $income->income_number,
            'date' => $income->date?->format('Y-m-d'),
            'title' => $income->title,
            'category' => $income->category->name ?? 'General',
            'amount' => $income->amount,
        ])->concat($expenses->map(fn (Expense $expense) => [
            'type' => 'expense',
            'ref' => $expense->expense_number,
            'date' => $expense->date?->format('Y-m-d'),
            'title' => $expense->title,
            'category' => $expense->category->name ?? 'General',
            'amount' => $expense->amount,
        ]))->sortBy('date')->values();

        $incomeCategories = IncomeCategory::withCount(['incomes' => fn($q) => $q->whereBetween('date', [$startDate, $endDate])])
            ->withSum(['incomes' => fn($q) => $q->whereBetween('date', [$startDate, $endDate])], 'amount')
            ->get();

        $expenseCategories = ExpenseCategory::withCount(['expenses' => fn($q) => $q->whereBetween('date', [$startDate, $endDate])])
            ->withSum(['expenses' => fn($q) => $q->whereBetween('date', [$startDate, $endDate])], 'amount')
            ->get();

        return [
            'periodName' => $periodLabel,
            'totalIncome' => $totalIncome,
            'totalExpense' => $totalExpense,
            'transactions' => $transactions,
            'incomeCategories' => $incomeCategories,
            'expenseCategories' => $expenseCategories,
        ];
    }

    public function categories()
    {
        $incomeCategories = IncomeCategory::withCount('incomes')->get();
        $expenseCategories = ExpenseCategory::withCount('expenses')->get();

        return Inertia::render('Finance/Categories', [
            'incomeCategories' => $incomeCategories,
            'expenseCategories' => $expenseCategories,
        ]);
    }

    public function storeCategory(Request $request)
    {
        $validated = $request->validate([
            'type' => ['required', 'in:income,expense'],
            'name' => ['required', 'string', 'max:255'],
            'code' => [
                'required',
                'string',
                'max:10',
                Rule::unique(
                    $request->input('type') === 'income' ? 'income_categories' : 'expense_categories',
                    'code'
                ),
            ],
            'monthly_budget' => ['nullable', 'numeric'],
            'description' => ['nullable', 'string'],
        ]);

        if ($validated['type'] === 'income') {
            IncomeCategory::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'description' => $validated['description'] ?? null,
            ]);
        } else {
            ExpenseCategory::create([
                'name' => $validated['name'],
                'code' => $validated['code'],
                'monthly_budget' => $validated['monthly_budget'] ?? 0,
                'description' => $validated['description'] ?? null,
            ]);
        }

        AuditLog::log('create', 'finance_categories', null, "Created {$validated['type']} category: {$validated['name']}");

        return back()->with('success', 'Category created successfully.');
    }
}

import React from 'react';
import { Head, router, usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import { FileSpreadsheet, Printer, TrendingUp, TrendingDown, Wallet } from 'lucide-react';

export default function Statements({
    period,
    periodLabel,
    totalIncome,
    totalExpense,
    netCashFlow,
    incomeCategories,
    expenseCategories,
    recentIncomes,
    recentExpenses
}) {
    const { company } = usePage().props;
    const currency = company?.currency || '';
    const footerContacts = [company?.phone, company?.email, company?.website].filter(Boolean);
    const companyDetails = [
        ['Registration No.', company?.registration_no],
        ['PAN/VAT', company?.pan_vat_no],
        ['Office', company?.address],
        ['Phone', company?.phone],
        ['Email', company?.email],
        ['Website', company?.website],
    ].filter(([, value]) => value);

    const handlePeriodChange = (newPeriod) => {
        router.get('/finance/statements', { period: newPeriod });
    };

    return (
        <AdminLayout title="Financial Statements">
            <Head title={`Financial Statement - ${periodLabel}`} />

            {/* Header */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">
                <div>
                    <h1 className="text-xl sm:text-2xl font-black text-[#202124] tracking-tight">
                        Audited Statement of Cash Flow & Earnings
                    </h1>
                    <p className="text-xs text-[#6B7280]">
                        Comprehensive corporate ledger breakdown for office accounting & auditing
                    </p>
                </div>
                <div className="flex items-center gap-2">
                    <a
                        href={`/finance/statements/preview?period=${period}`}
                        target="_blank"
                        rel="noreferrer"
                        className="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg shadow-xs transition"
                    >
                        <FileSpreadsheet className="h-4 w-4" />
                        <span>Preview</span>
                    </a>
                    <a
                        href={`/finance/statements/pdf?period=${period}`}
                        className="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-[#FF5500] hover:bg-[#E04B00] rounded-lg shadow-xs transition"
                    >
                        <Printer className="h-4 w-4" />
                        <span>Download Certified PDF Statement</span>
                    </a>
                    <a
                        href={`/finance/statements/xlsx?period=${period}`}
                        className="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-green-700 bg-green-50 border border-green-200 hover:bg-green-100 rounded-lg shadow-xs transition"
                    >
                        <FileSpreadsheet className="h-4 w-4" />
                        <span>Excel</span>
                    </a>
                </div>
            </div>

            {/* Period Switcher */}
            <div className="flex items-center gap-1.5 mb-6 bg-white p-1.5 rounded-xl border border-gray-200 w-fit">
                {['monthly', 'quarterly', 'yearly'].map((p) => (
                    <button
                        key={p}
                        onClick={() => handlePeriodChange(p)}
                        className={`px-4 py-1.5 text-xs font-bold rounded-lg capitalize transition ${
                            period === p
                                ? 'bg-[#FF5500] text-white shadow-2xs'
                                : 'text-gray-600 hover:bg-gray-100'
                        }`}
                    >
                        {p} Statement ({p === 'monthly' ? 'Current Month' : p === 'quarterly' ? 'Q4' : 'Fiscal Year'})
                    </button>
                ))}
            </div>

            {/* Statement Summary Card */}
            <div className="bg-white p-6 rounded-2xl border border-[#E5E7EB] shadow-xs mb-6">
                <div className="flex items-center justify-center gap-4 pb-4">
                    {company?.logo_url && (
                        <img src={company.logo_url} alt={company.name || 'Company logo'} className="max-h-14 max-w-36 object-contain" />
                    )}
                    <div className="text-center">
                        {company?.name && (
                            <span className="block text-[10px] font-bold text-gray-500 uppercase tracking-widest">
                                {company.name}
                            </span>
                        )}
                        {companyDetails.map(([label, detail]) => (
                            <span key={label} className="block text-[10px] text-gray-500">{label}: {detail}</span>
                        ))}
                    </div>
                </div>
                <div className="text-center pb-4 mb-5 border-b border-gray-100">
                    <span className="text-[10px] font-bold text-gray-400 uppercase tracking-widest">
                        Office Financial Statement
                    </span>
                    <h2 className="text-lg font-black text-gray-900 mt-0.5">{periodLabel}</h2>
                    {currency && <span className="text-xs font-mono text-[#FF5500] font-bold">Currency: {currency}</span>}
                </div>

                <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
                    <div className="p-4 rounded-xl bg-emerald-50/50 border border-emerald-100 text-center">
                        <span className="text-xs font-bold text-emerald-800 uppercase tracking-wider">Gross Office Inflow</span>
                        <p className="text-2xl font-black text-emerald-600 font-mono mt-1">
                            {currency} {totalIncome.toLocaleString()}
                        </p>
                        <p className="text-[10px] text-emerald-700 mt-1">Confirmed client & retainer billings</p>
                    </div>

                    <div className="p-4 rounded-xl bg-red-50/50 border border-red-100 text-center">
                        <span className="text-xs font-bold text-red-800 uppercase tracking-wider">Operating Outflows</span>
                        <p className="text-2xl font-black text-red-600 font-mono mt-1">
                            {currency} {totalExpense.toLocaleString()}
                        </p>
                        <p className="text-[10px] text-red-700 mt-1">Salaries, leases, cloud & utilities</p>
                    </div>

                    <div className="p-4 rounded-xl bg-[#FFF0E8] border border-[#FFD6C0] text-center">
                        <span className="text-xs font-bold text-[#662200] uppercase tracking-wider">Net Operating Surplus</span>
                        <p className="text-2xl font-black text-[#FF5500] font-mono mt-1">
                            {currency} {netCashFlow.toLocaleString()}
                        </p>
                        <p className="text-[10px] text-[#8C2E00] mt-1 font-semibold">Retained operating liquidity</p>
                    </div>
                </div>
            </div>

            {/* Income & Expense Breakdown Tables */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                {/* Income Categories Table */}
                <div className="bg-white rounded-2xl border border-[#E5E7EB] shadow-xs overflow-hidden">
                    <div className="p-4 border-b border-gray-100 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <TrendingUp className="h-4 w-4 text-emerald-600" />
                            <h3 className="text-xs font-bold text-gray-900 uppercase">Revenue Breakdown by Source</h3>
                        </div>
                    </div>
                    <table className="min-w-full divide-y divide-gray-200 text-xs">
                        <thead className="bg-[#F7F8FA]">
                            <tr>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Category</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Records</th>
                                <th className="px-5 py-2.5 text-right font-bold text-gray-500">Total ({currency})</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {incomeCategories.map((c) => (
                                <tr key={c.id}>
                                    <td className="px-5 py-2.5 font-bold text-gray-800">{c.name}</td>
                                    <td className="px-5 py-2.5 text-gray-500">{c.incomes_count} entries</td>
                                    <td className="px-5 py-2.5 text-right font-mono font-bold text-emerald-600">
                                        {currency} {Number(c.incomes_sum_amount || 0).toLocaleString()}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                {/* Expense Categories Table */}
                <div className="bg-white rounded-2xl border border-[#E5E7EB] shadow-xs overflow-hidden">
                    <div className="p-4 border-b border-gray-100 flex items-center justify-between">
                        <div className="flex items-center gap-2">
                            <TrendingDown className="h-4 w-4 text-red-600" />
                            <h3 className="text-xs font-bold text-gray-900 uppercase">Expenditure Breakdown by Division</h3>
                        </div>
                    </div>
                    <table className="min-w-full divide-y divide-gray-200 text-xs">
                        <thead className="bg-[#F7F8FA]">
                            <tr>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Category</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Budget</th>
                                <th className="px-5 py-2.5 text-right font-bold text-gray-500">Actual ({currency})</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {expenseCategories.map((c) => (
                                <tr key={c.id}>
                                    <td className="px-5 py-2.5 font-bold text-gray-800">{c.name}</td>
                                    <td className="px-5 py-2.5 font-mono text-gray-500">{Number(c.monthly_budget).toLocaleString()}</td>
                                    <td className="px-5 py-2.5 text-right font-mono font-bold text-red-600">
                                        {currency} {Number(c.expenses_sum_amount || 0).toLocaleString()}
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
            {(company?.report_footer_text || footerContacts.length > 0) && (
                <footer className="mt-6 border-t border-gray-200 pt-3 text-center text-xs text-gray-500">
                    {company?.report_footer_text && <div>{company.report_footer_text}</div>}
                    {footerContacts.length > 0 && <div className="mt-1">{footerContacts.join(' | ')}</div>}
                </footer>
            )}
        </AdminLayout>
    );
}

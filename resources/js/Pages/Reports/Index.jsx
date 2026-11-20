import React, { useState } from 'react';
import { usePage } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    BarChart3, Users, Wallet, TrendingUp, TrendingDown,
    Package, Boxes, Download, FileText, Calendar
} from 'lucide-react';

function fmt(val, currencySymbol) {
    return `${currencySymbol || ''} ${Number(val || 0).toLocaleString('en-IN', { minimumFractionDigits: 2 })}`;
}

const now = new Date();

export default function ReportsIndex({ metrics }) {
    const { company } = usePage().props;
    const [attMonth, setAttMonth] = useState(String(now.getMonth() + 1).padStart(2, '0'));
    const [attYear, setAttYear] = useState(String(now.getFullYear()));

    const years = [];
    for (let y = now.getFullYear(); y >= now.getFullYear() - 3; y--) years.push(y);

    function reportUrl(type, format = null, params = {}) {
        const qs = new URLSearchParams(params).toString();
        const path = `/reports/${type}${format ? `/${format}` : ''}`;
        return `${path}${qs ? '?' + qs : ''}`;
    }

    const statCards = [
        { label: 'Total Employees', value: metrics.employees_count, suffix: 'staff', icon: Users, color: 'text-[#FF5500] bg-[#FFF0E8]' },
        { label: 'Monthly Payroll', value: fmt(metrics.monthly_payroll, company?.currency_symbol || company?.currency), icon: Wallet, color: 'text-purple-600 bg-purple-50' },
        { label: 'Total Income', value: fmt(metrics.total_income, company?.currency_symbol || company?.currency), icon: TrendingUp, color: 'text-green-600 bg-green-50' },
        { label: 'Total Expenses', value: fmt(metrics.total_expense, company?.currency_symbol || company?.currency), icon: TrendingDown, color: 'text-red-500 bg-red-50' },
        { label: 'Assets Valuation', value: fmt(metrics.assets_valuation), icon: Package, color: 'text-blue-600 bg-blue-50' },
        { label: 'Inventory Valuation', value: fmt(metrics.inventory_valuation), icon: Boxes, color: 'text-amber-600 bg-amber-50' },
    ];

    return (
        <AdminLayout title="Reports">
            <div className="space-y-8">
                {/* Header */}
                <div>
                    <h1 className="text-2xl font-bold text-[#202124]">Reports & Analytics Hub</h1>
                    <p className="text-sm text-gray-500 mt-1">Preview and download current reports as PDF or Excel</p>
                </div>

                {/* Metric Cards */}
                <div className="grid grid-cols-2 md:grid-cols-3 xl:grid-cols-6 gap-4">
                    {statCards.map(s => (
                        <div key={s.label} className="bg-white border border-[#E5E7EB] rounded-xl p-4">
                            <div className={`w-9 h-9 rounded-lg flex items-center justify-center mb-3 ${s.color}`}>
                                <s.icon size={16} />
                            </div>
                            <div className="text-base font-bold text-[#202124] leading-tight">
                                {typeof s.value === 'number' ? s.value : s.value}
                            </div>
                            <div className="text-xs text-gray-500 mt-0.5">{s.label}</div>
                        </div>
                    ))}
                </div>

                {/* HR Reports */}
                <div>
                    <h2 className="text-base font-semibold text-[#202124] mb-4 flex items-center gap-2">
                        <Users size={18} className="text-[#FF5500]" /> Human Resources Reports
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        {/* Employee Directory */}
                        <div className="bg-white border border-[#E5E7EB] rounded-xl p-5 hover:shadow-md transition-shadow">
                            <div className="flex items-start justify-between mb-3">
                                <div>
                                    <h3 className="font-semibold text-[#202124]">Employee Directory</h3>
                                    <p className="text-xs text-gray-500 mt-1">Complete HR directory with all employee profiles, departments, and designations.</p>
                                </div>
                                <span className="text-xs font-semibold bg-red-50 text-red-600 px-2 py-0.5 rounded border border-red-100">PDF</span>
                            </div>
                            <div className="pt-3 border-t border-[#E5E7EB] grid grid-cols-3 gap-2">
                                <a href={reportUrl('hr_directory')} target="_blank" rel="noreferrer" className="py-2 rounded-lg border border-[#E5E7EB] text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Preview</a>
                                <a href={reportUrl('hr_directory', 'pdf')} className="py-2 rounded-lg bg-[#FF5500] text-center text-xs font-medium text-white hover:bg-orange-600">PDF</a>
                                <a href={reportUrl('hr_directory', 'xlsx')} className="py-2 rounded-lg border border-green-200 bg-green-50 text-center text-xs font-medium text-green-700 hover:bg-green-100">Excel</a>
                            </div>
                        </div>

                        {/* Attendance Summary */}
                        <div className="bg-white border border-[#E5E7EB] rounded-xl p-5 hover:shadow-md transition-shadow">
                            <div className="flex items-start justify-between mb-3">
                                <div>
                                    <h3 className="font-semibold text-[#202124]">Attendance Summary</h3>
                                    <p className="text-xs text-gray-500 mt-1">Monthly attendance summary with present, late, half-day and absent counts per employee.</p>
                                </div>
                                <span className="text-xs font-semibold bg-red-50 text-red-600 px-2 py-0.5 rounded border border-red-100">PDF</span>
                            </div>
                            <div className="flex gap-2 mb-3">
                                <select value={attMonth} onChange={e => setAttMonth(e.target.value)}
                                    className="flex-1 border border-[#E5E7EB] rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-[#FF5500]/20 focus:border-[#FF5500]">
                                    {['01','02','03','04','05','06','07','08','09','10','11','12'].map((m, i) => (
                                        <option key={m} value={m}>{new Date(2000, i).toLocaleString('en', {month: 'long'})}</option>
                                    ))}
                                </select>
                                <select value={attYear} onChange={e => setAttYear(e.target.value)}
                                    className="border border-[#E5E7EB] rounded-lg px-2 py-1.5 text-xs focus:outline-none focus:ring-2 focus:ring-[#FF5500]/20 focus:border-[#FF5500]">
                                    {years.map(y => <option key={y} value={y}>{y}</option>)}
                                </select>
                            </div>
                            <div className="grid grid-cols-3 gap-2">
                                <a href={reportUrl('attendance_summary', null, { month: parseInt(attMonth), year: attYear })} target="_blank" rel="noreferrer" className="py-2 rounded-lg border border-[#E5E7EB] text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Preview</a>
                                <a href={reportUrl('attendance_summary', 'pdf', { month: parseInt(attMonth), year: attYear })} className="py-2 rounded-lg bg-[#FF5500] text-center text-xs font-medium text-white hover:bg-orange-600">PDF</a>
                                <a href={reportUrl('attendance_summary', 'xlsx', { month: parseInt(attMonth), year: attYear })} className="py-2 rounded-lg border border-green-200 bg-green-50 text-center text-xs font-medium text-green-700 hover:bg-green-100">Excel</a>
                            </div>
                        </div>

                        {/* Coming Soon */}
                        <div className="bg-[#F7F8FA] border border-dashed border-[#E5E7EB] rounded-xl p-5 flex flex-col items-center justify-center text-center">
                            <BarChart3 size={28} className="text-gray-300 mb-2" />
                            <h3 className="font-medium text-gray-400 text-sm">Leave Balance Report</h3>
                            <p className="text-xs text-gray-400 mt-1">Coming soon</p>
                        </div>
                    </div>
                </div>

                {/* Finance Reports */}
                <div>
                    <h2 className="text-base font-semibold text-[#202124] mb-4 flex items-center gap-2">
                        <TrendingUp size={18} className="text-[#FF5500]" /> Finance Reports
                    </h2>
                    <div className="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
                        {/* Expense Audit */}
                        <div className="bg-white border border-[#E5E7EB] rounded-xl p-5 hover:shadow-md transition-shadow">
                            <div className="flex items-start justify-between mb-3">
                                <div>
                                    <h3 className="font-semibold text-[#202124]">Expense Audit Report</h3>
                                    <p className="text-xs text-gray-500 mt-1">Full historical log of all company expenses with category breakdowns and totals.</p>
                                </div>
                                <span className="text-xs font-semibold bg-red-50 text-red-600 px-2 py-0.5 rounded border border-red-100">PDF</span>
                            </div>
                            <div className="pt-3 border-t border-[#E5E7EB] grid grid-cols-3 gap-2">
                                <a href={reportUrl('expense_audit')} target="_blank" rel="noreferrer" className="py-2 rounded-lg border border-[#E5E7EB] text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Preview</a>
                                <a href={reportUrl('expense_audit', 'pdf')} className="py-2 rounded-lg bg-[#FF5500] text-center text-xs font-medium text-white hover:bg-orange-600">PDF</a>
                                <a href={reportUrl('expense_audit', 'xlsx')} className="py-2 rounded-lg border border-green-200 bg-green-50 text-center text-xs font-medium text-green-700 hover:bg-green-100">Excel</a>
                            </div>
                        </div>

                        {/* Expense PDF from Finance */}
                        <div className="bg-white border border-[#E5E7EB] rounded-xl p-5 hover:shadow-md transition-shadow">
                            <div className="flex items-start justify-between mb-3">
                                <div>
                                    <h3 className="font-semibold text-[#202124]">Expense Report (Finance)</h3>
                                    <p className="text-xs text-gray-500 mt-1">Detailed expense report with filters from the Finance module.</p>
                                </div>
                                <span className="text-xs font-semibold bg-red-50 text-red-600 px-2 py-0.5 rounded border border-red-100">PDF</span>
                            </div>
                            <div className="pt-3 border-t border-[#E5E7EB] grid grid-cols-3 gap-2">
                                <a href="/finance/expenses/report" target="_blank" rel="noreferrer" className="py-2 rounded-lg border border-[#E5E7EB] text-center text-xs font-medium text-gray-700 hover:bg-gray-50">Preview</a>
                                <a href="/finance/expenses/report-pdf" className="py-2 rounded-lg bg-[#FF5500] text-center text-xs font-medium text-white hover:bg-orange-600">
                                    <Download size={14} className="inline mr-1" /> PDF
                                </a>
                                <a href="/finance/expenses/report-xlsx" className="py-2 rounded-lg border border-green-200 bg-green-50 text-center text-xs font-medium text-green-700 hover:bg-green-100">Excel</a>
                            </div>
                        </div>

                        <div className="bg-white border border-[#E5E7EB] rounded-xl p-5 flex flex-col">
                            <h3 className="font-semibold text-[#202124]">Financial Statement</h3>
                            <p className="text-xs text-gray-500 mt-1 mb-4">Income and expenditure statement for the selected reporting period.</p>
                            <a href="/finance/statements" className="mt-auto w-full py-2 rounded-lg bg-[#FF5500] text-center text-sm font-medium text-white hover:bg-orange-600">
                                Open Statement Report
                            </a>
                        </div>
                    </div>
                </div>

                {/* Info Banner */}
                <div className="bg-[#FFF0E8] border border-[#FF5500]/20 rounded-xl p-4 flex items-start gap-3">
                    <FileText size={18} className="text-[#FF5500] mt-0.5 shrink-0" />
                    <div>
                        <p className="text-sm font-medium text-[#202124]">Reports are generated from live database records</p>
                        <p className="text-xs text-gray-600 mt-0.5">Reports use current database records and Settings when generated. Store downloaded files separately for archival.</p>
                    </div>
                </div>
            </div>
        </AdminLayout>
    );
}

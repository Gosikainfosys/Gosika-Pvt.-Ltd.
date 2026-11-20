import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import {
    FileText,
    Plus,
    Edit3,
    Copy,
    History,
    Eye,
    CheckCircle2,
    XCircle,
    Trash2,
    ExternalLink,
    ShieldCheck,
    Layers,
    FileCheck,
    Sparkles
} from 'lucide-react';

export default function Index({ templates = [] }) {
    const [previewTemplate, setPreviewTemplate] = useState(null);

    const handleDuplicate = (template) => {
        if (confirm(`Do you want to create an editable duplicate of "${template.name}"?`)) {
            router.post(`/document-templates/${template.id}/duplicate`);
        }
    };

    const handleToggleStatus = (template) => {
        router.patch(`/document-templates/${template.id}/toggle-status`);
    };

    const handleDelete = (template) => {
        if (confirm(`Are you sure you want to archive template "${template.name}"?`)) {
            router.delete(`/document-templates/${template.id}`);
        }
    };

    return (
        <AdminLayout title="Document Templates">
            <Head title="Document Templates Management" />

            {/* Header Banner */}
            <div className="bg-white p-6 rounded-2xl border border-[#E5E7EB] shadow-xs mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div className="flex items-center gap-2 mb-1">
                        <span className="p-1.5 bg-[#FFF3EB] text-[#F36B12] rounded-lg">
                            <Layers className="h-5 w-5" />
                        </span>
                        <h1 className="text-xl sm:text-2xl font-black text-[#202124] tracking-tight">
                            Document Template Management
                        </h1>
                    </div>
                    <p className="text-xs text-gray-500 max-w-2xl">
                        Manage employee document templates. Company identity and contact details are supplied dynamically from Settings.
                    </p>
                </div>

                <div className="flex items-center gap-2.5">
                    <Link
                        href="/document-templates/create"
                        className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#F36B12] hover:bg-[#E04B00] rounded-xl shadow-xs transition"
                    >
                        <Plus className="h-4 w-4" />
                        <span>Create New Template</span>
                    </Link>
                </div>
            </div>

            {/* Templates List */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
                {templates.map((tpl) => {
                    const isRegistration = tpl.type === 'registration_form' || tpl.code === 'employee_registration_form';
                    const isBond = tpl.type === 'employment_bond' || tpl.code === 'employment_bond';

                    return (
                        <div
                            key={tpl.id}
                            className="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col justify-between hover:border-[#F36B12]/50 transition"
                        >
                            <div className={`h-2 bg-gradient-to-r ${isRegistration ? 'from-[#F36B12] to-amber-500' : isBond ? 'from-[#E94B0B] to-[#F36B12]' : 'from-gray-400 to-gray-600'}`} />

                            <div className="p-6 flex-1">
                                {/* Title and Badge */}
                                <div className="flex items-start justify-between gap-3 mb-3">
                                    <div className="flex items-center gap-3">
                                        <div className={`h-11 w-11 rounded-xl flex items-center justify-center font-black shrink-0 ${
                                            isRegistration
                                                ? 'bg-orange-50 text-[#F36B12] border border-orange-200'
                                                : isBond
                                                ? 'bg-red-50 text-[#E94B0B] border border-red-200'
                                                : 'bg-gray-100 text-gray-700 border border-gray-200'
                                        }`}>
                                            {isRegistration ? <FileText className="h-6 w-6" /> : isBond ? <ShieldCheck className="h-6 w-6" /> : <Layers className="h-6 w-6" />}
                                        </div>
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <h2 className="text-base font-black text-gray-900 leading-tight">
                                                    {tpl.name}
                                                </h2>
                                                <span className="px-2 py-0.5 text-[10px] font-black uppercase font-mono bg-gray-100 text-gray-700 rounded-md">
                                                    v{tpl.current_version}
                                                </span>
                                            </div>
                                            <span className="text-[11px] font-mono text-gray-400 block mt-0.5">
                                                Code: {tpl.code}
                                            </span>
                                        </div>
                                    </div>

                                    <Badge variant={tpl.is_active ? 'success' : 'neutral'}>
                                        {tpl.is_active ? 'ACTIVE' : 'INACTIVE'}
                                    </Badge>
                                </div>

                                <p className="text-xs text-gray-600 mb-5 leading-relaxed">
                                    {tpl.description || 'Dynamic database-driven corporate A4 template.'}
                                </p>

                                {/* Meta Stats */}
                                <div className="bg-[#F9FAFB] rounded-xl p-3.5 border border-gray-100 grid grid-cols-2 gap-3 text-xs mb-4">
                                    <div>
                                        <span className="text-gray-400 text-[11px] block">Total Versions:</span>
                                        <span className="font-bold text-gray-800">{tpl.versions_count || 1} Published</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-400 text-[11px] block">Generated Records:</span>
                                        <span className="font-bold text-[#F36B12]">{tpl.generated_documents_count || 0} Employees</span>
                                    </div>
                                    <div>
                                        <span className="text-gray-400 text-[11px] block">Primary Accent:</span>
                                        <div className="flex items-center gap-1.5 mt-0.5">
                                            <span
                                                className="w-3.5 h-3.5 rounded-full border border-gray-300"
                                                style={{ backgroundColor: tpl.design_config?.primary_color || '#F36B12' }}
                                            />
                                            <span className="font-mono text-[11px] text-gray-700">
                                                {tpl.design_config?.primary_color || '#F36B12'}
                                            </span>
                                        </div>
                                    </div>
                                    <div>
                                        <span className="text-gray-400 text-[11px] block">Last Updated:</span>
                                        <span className="text-gray-700 font-medium">
                                            {tpl.updated_at ? new Date(tpl.updated_at).toLocaleDateString() : 'Initial'}
                                        </span>
                                    </div>
                                </div>
                            </div>

                            {/* Card Actions */}
                            <div className="p-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center justify-between gap-2">
                                <div className="flex items-center gap-2">
                                    <Link
                                        href={`/document-templates/${tpl.id}/edit`}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-white bg-[#F36B12] hover:bg-[#E04B00] rounded-lg shadow-2xs transition"
                                    >
                                        <Edit3 className="h-3.5 w-3.5" />
                                        <span>Edit Template</span>
                                    </Link>

                                    <button
                                        onClick={() => setPreviewTemplate(tpl)}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg shadow-2xs transition"
                                    >
                                        <Eye className="h-3.5 w-3.5 text-[#F36B12]" />
                                        <span>Preview</span>
                                    </button>

                                    <Link
                                        href={`/document-templates/${tpl.id}/versions`}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg shadow-2xs transition"
                                    >
                                        <History className="h-3.5 w-3.5 text-indigo-600" />
                                        <span>History</span>
                                    </Link>
                                </div>

                                <div className="flex items-center gap-1">
                                    <button
                                        onClick={() => handleDuplicate(tpl)}
                                        title="Duplicate Template"
                                        className="p-1.5 text-gray-500 hover:text-gray-800 hover:bg-gray-200 rounded-lg transition"
                                    >
                                        <Copy className="h-4 w-4" />
                                    </button>

                                    <button
                                        onClick={() => handleToggleStatus(tpl)}
                                        title={tpl.is_active ? 'Deactivate Template' : 'Activate Template'}
                                        className={`p-1.5 rounded-lg transition ${
                                            tpl.is_active
                                                ? 'text-emerald-600 hover:bg-emerald-50'
                                                : 'text-gray-400 hover:bg-gray-200'
                                        }`}
                                    >
                                        {tpl.is_active ? <CheckCircle2 className="h-4 w-4" /> : <XCircle className="h-4 w-4" />}
                                    </button>

                                    {!isRegistration && !isBond && (
                                        <button
                                            onClick={() => handleDelete(tpl)}
                                            title="Archive Template"
                                            className="p-1.5 text-red-500 hover:text-red-700 hover:bg-red-50 rounded-lg transition"
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </button>
                                    )}
                                </div>
                            </div>
                        </div>
                    );
                })}
            </div>

            {/* Preview Modal */}
            <Modal
                isOpen={!!previewTemplate}
                onClose={() => setPreviewTemplate(null)}
                title={`Live PDF Preview: ${previewTemplate?.name || ''}`}
                maxWidth="max-w-5xl"
            >
                {previewTemplate && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs">
                            <span className="text-gray-600">
                                Rendered dynamically with sample employee data using DomPDF A4 portrait standard.
                            </span>
                            <div className="flex items-center gap-2">
                                <a
                                    href={`/document-templates/${previewTemplate.id}/preview-pdf`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 px-3 py-1 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100"
                                >
                                    <ExternalLink className="h-3.5 w-3.5" />
                                    <span>Open Full Window</span>
                                </a>
                                <Link
                                    href={`/document-templates/${previewTemplate.id}/edit`}
                                    className="inline-flex items-center gap-1 px-3 py-1 bg-[#F36B12] text-white rounded-lg text-xs font-bold hover:bg-[#E04B00]"
                                >
                                    <Edit3 className="h-3.5 w-3.5" />
                                    <span>Edit Template</span>
                                </Link>
                            </div>
                        </div>

                        <div className="border border-gray-200 rounded-xl overflow-hidden bg-gray-100">
                            <iframe
                                src={`/document-templates/${previewTemplate.id}/preview-pdf`}
                                className="w-full h-[70vh]"
                                title="Live Template Preview"
                            />
                        </div>
                    </div>
                )}
            </Modal>
        </AdminLayout>
    );
}

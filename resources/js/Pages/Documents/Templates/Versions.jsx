import React from 'react';
import { Head, Link, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Badge from '@/Components/Badge';
import {
    ArrowLeft,
    RotateCcw,
    Calendar,
    User,
    CheckCircle2,
    Eye,
    Edit3,
    History,
    FileText
} from 'lucide-react';

export default function Versions({ template, versions = [] }) {
    const handleRestore = (version) => {
        if (confirm(`Are you sure you want to restore Version ${version.version_number}? This will publish it as a new version v${template.current_version + 1}.`)) {
            router.post(`/document-templates/${template.id}/versions/${version.id}/restore`);
        }
    };

    return (
        <AdminLayout title={`Version History: ${template.name}`}>
            <Head title={`Version History: ${template.name}`} />

            <div className="bg-white p-6 rounded-2xl border border-[#E5E7EB] shadow-xs mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div className="flex items-center gap-2 mb-1">
                        <Link
                            href={`/document-templates/${template.id}/edit`}
                            className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-[#F36B12] transition"
                        >
                            <ArrowLeft className="h-4 w-4" />
                            <span>Back to Template Editor</span>
                        </Link>
                    </div>
                    <div className="flex items-center gap-3">
                        <h1 className="text-xl sm:text-2xl font-black text-[#202124] tracking-tight">
                            Version History: {template.name}
                        </h1>
                        <Badge variant="neutral">Current v{template.current_version}</Badge>
                    </div>
                    <p className="text-xs text-gray-500 mt-1">
                        Audit trail of every published version. You can review change summaries and restore any previous version without altering existing generated documents.
                    </p>
                </div>
            </div>

            <div className="space-y-4 max-w-4xl">
                {versions.map((ver, idx) => {
                    const isCurrent = ver.version_number === template.current_version;

                    return (
                        <div
                            key={ver.id}
                            className={`p-5 rounded-2xl border transition ${
                                isCurrent
                                    ? 'bg-orange-50/40 border-orange-200 shadow-xs'
                                    : 'bg-white border-gray-200 hover:border-gray-300'
                            }`}
                        >
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-3">
                                <div className="flex items-center gap-3">
                                    <div className={`w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm shrink-0 ${
                                        isCurrent
                                            ? 'bg-[#F36B12] text-white shadow-2xs'
                                            : 'bg-gray-100 text-gray-700'
                                    }`}>
                                        v{ver.version_number}
                                    </div>
                                    <div>
                                        <div className="flex items-center gap-2">
                                            <h3 className="text-sm font-bold text-gray-900">
                                                Version {ver.version_number}
                                            </h3>
                                            {isCurrent && (
                                                <Badge variant="success">CURRENT ACTIVE VERSION</Badge>
                                            )}
                                        </div>
                                        <div className="flex items-center gap-3 text-[11px] text-gray-500 mt-0.5">
                                            <span className="flex items-center gap-1">
                                                <Calendar className="h-3 w-3" />
                                                {new Date(ver.created_at).toLocaleString()}
                                            </span>
                                            {ver.creator && (
                                                <span className="flex items-center gap-1">
                                                    <User className="h-3 w-3" />
                                                    {ver.creator.name}
                                                </span>
                                            )}
                                        </div>
                                    </div>
                                </div>

                                {!isCurrent && (
                                    <button
                                        onClick={() => handleRestore(ver)}
                                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 rounded-lg shadow-2xs transition shrink-0"
                                    >
                                        <RotateCcw className="h-3.5 w-3.5" />
                                        <span>Restore this Version</span>
                                    </button>
                                )}
                            </div>

                            <div className="bg-gray-50 p-3 rounded-xl border border-gray-100 text-xs text-gray-700">
                                <span className="font-semibold text-gray-500 block text-[10px] uppercase tracking-wider mb-0.5">
                                    Change Summary
                                </span>
                                <p>{ver.change_summary || 'No change summary recorded.'}</p>
                            </div>
                        </div>
                    );
                })}
            </div>
        </AdminLayout>
    );
}

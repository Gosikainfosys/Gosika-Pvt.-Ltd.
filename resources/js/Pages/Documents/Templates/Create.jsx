import React, { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import {
    ArrowLeft,
    Plus,
    FileText,
    ShieldCheck,
    Layers,
    Save
} from 'lucide-react';

export default function Create({ defaultDesign }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        type: 'registration_form',
        description: '',
        design_config: defaultDesign,
        sections_data: {
            header: {
                company_name: 'GOSIKA INFOSYS',
                tagline: 'Build • Innovate • Grow',
                title: 'OFFICIAL DOCUMENT',
                subtitle: 'Confidential – Human Resources Department',
            },
            clauses: [
                {
                    num: 1,
                    title: 'General Terms',
                    body: 'The employee agrees to comply with all standard company policies and lawful instructions.',
                }
            ],
            checklist: [
                'Citizenship / Valid Identification Copy',
                'Passport-size Photograph',
                'Signed Agreement'
            ]
        }
    });

    const handleSubmit = (e) => {
        e.preventDefault();
        post('/document-templates');
    };

    return (
        <AdminLayout title="Create Document Template">
            <Head title="Create Template" />

            <div className="max-w-3xl">
                <div className="flex items-center gap-2 mb-4">
                    <Link
                        href="/document-templates"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-[#F36B12] transition"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        <span>Back to All Templates</span>
                    </Link>
                </div>

                <div className="bg-white p-6 rounded-2xl border border-[#E5E7EB] shadow-xs mb-6">
                    <h1 className="text-xl font-black text-[#202124] mb-1">
                        Create New Document Template
                    </h1>
                    <p className="text-xs text-gray-500 mb-6">
                        Set up a new document template. All clauses, sections, and design elements can be customized and previewed in real-time.
                    </p>

                    <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <label className="block text-xs font-bold text-gray-700 mb-1">Template Name *</label>
                            <input
                                type="text"
                                placeholder="e.g. Non-Disclosure Agreement (NDA)"
                                value={data.name}
                                onChange={(e) => setData('name', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg p-2.5 text-xs font-medium focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                                required
                            />
                            {errors.name && <p className="text-red-500 text-xs mt-1">{errors.name}</p>}
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-gray-700 mb-1">Template Type / Base Model *</label>
                            <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                {[
                                    { id: 'registration_form', name: 'Registration Form Base', icon: FileText, desc: 'Structured grid with checklists, photo frame, and HR verification.' },
                                    { id: 'employment_bond', name: 'Employment Bond Base', icon: ShieldCheck, desc: 'Legal clauses with dual columns and witness blocks.' },
                                    { id: 'general', name: 'General Document Base', icon: Layers, desc: 'Clean corporate letterhead layout.' },
                                ].map((typeOpt) => {
                                    const Icon = typeOpt.icon;
                                    const isSelected = data.type === typeOpt.id;

                                    return (
                                        <button
                                            key={typeOpt.id}
                                            type="button"
                                            onClick={() => setData('type', typeOpt.id)}
                                            className={`p-4 rounded-xl border text-left transition flex flex-col justify-between ${
                                                isSelected
                                                    ? 'border-[#F36B12] bg-orange-50/50 shadow-xs'
                                                    : 'border-gray-200 hover:border-gray-300 bg-white'
                                            }`}
                                        >
                                            <div>
                                                <div className={`p-2 rounded-lg inline-block mb-2 ${isSelected ? 'bg-[#F36B12] text-white' : 'bg-gray-100 text-gray-600'}`}>
                                                    <Icon className="h-5 w-5" />
                                                </div>
                                                <div className="font-bold text-xs text-gray-900">{typeOpt.name}</div>
                                                <p className="text-[11px] text-gray-500 mt-1 leading-normal">{typeOpt.desc}</p>
                                            </div>
                                        </button>
                                    );
                                })}
                            </div>
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-gray-700 mb-1">Template Description</label>
                            <textarea
                                rows={3}
                                placeholder="Describe the purpose of this template and when it should be generated..."
                                value={data.description}
                                onChange={(e) => setData('description', e.target.value)}
                                className="w-full border border-gray-300 rounded-lg p-2.5 text-xs focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                            />
                        </div>

                        <div className="pt-4 border-t border-gray-100 flex items-center justify-end gap-2">
                            <Link
                                href="/document-templates"
                                className="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition"
                            >
                                Cancel
                            </Link>
                            <button
                                type="submit"
                                disabled={processing}
                                className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#F36B12] hover:bg-[#E04B00] rounded-lg shadow-xs transition disabled:opacity-50"
                            >
                                <Save className="h-4 w-4" />
                                <span>{processing ? 'Creating...' : 'Create & Open Editor'}</span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </AdminLayout>
    );
}

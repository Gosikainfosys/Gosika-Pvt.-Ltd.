import React, { useEffect, useState, useMemo } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import axios from 'axios';
import AdminLayout from '@/Layouts/AdminLayout';
import Badge from '@/Components/Badge';
import {
    ArrowLeft,
    Save,
    RotateCcw,
    Eye,
    Plus,
    Trash2,
    MoveUp,
    MoveDown,
    Palette,
    FileText,
    ShieldCheck,
    Type,
    Sliders,
    Layers,
    ZoomIn,
    ZoomOut,
    Printer,
    Sparkles,
    Copy,
    Check,
    ExternalLink
} from 'lucide-react';

export default function Edit({
    template,
    availableVariables = {},
    employees = [],
    company: companyProp = {}
}) {
    const company = companyProp || {};
    const [editorTab, setEditorTab] = useState('content'); // 'content' | 'design' | 'variables'
    const [previewZoom, setPreviewZoom] = useState(100);
    const [previewMode, setPreviewMode] = useState('layout');
    const [previewPdfUrl, setPreviewPdfUrl] = useState('');
    const [previewLoading, setPreviewLoading] = useState(false);
    const [previewError, setPreviewError] = useState('');
    const [selectedEmployeeId, setSelectedEmployeeId] = useState('');
    const [copiedVar, setCopiedVar] = useState(null);
    const [isSaving, setIsSaving] = useState(false);

    // Form states
    const [name, setName] = useState(template.name || '');
    const [description, setDescription] = useState(template.description || '');
    const [isActive, setIsActive] = useState(template.is_active ?? true);
    const [changeSummary, setChangeSummary] = useState('');
    const [design, setDesign] = useState(template.design_config || {
        primary_color: '#F36B12',
        secondary_color: '#E94B0B',
        text_color: '#202124',
        font_family: 'DejaVu Sans',
        font_size: '8pt',
        header_top_right: '',
        company_name: '',
        tagline: '',
        footer_text: '',
    });
    const [sections, setSections] = useState(template.sections_data || {});
    const companyName = company.company_name || '';
    const companyAddress = company.address || [company.city, company.country].filter(Boolean).join(', ');

    const isRegistration = template.type === 'registration_form' || template.code === 'employee_registration_form';
    const isBond = template.type === 'employment_bond' || template.code === 'employment_bond';

    // Copy variable helper
    const handleCopyVar = (variableStr) => {
        navigator.clipboard.writeText(variableStr);
        setCopiedVar(variableStr);
        setTimeout(() => setCopiedVar(null), 2000);
    };

    // Design state updater
    const updateDesign = (key, value) => {
        setDesign(prev => ({ ...prev, [key]: value }));
    };

    // Sections state updater
    const updateSectionValue = (path, value) => {
        setSections(prev => {
            const next = JSON.parse(JSON.stringify(prev));
            let curr = next;
            const parts = path.split('.');
            for (let i = 0; i < parts.length - 1; i++) {
                if (!curr[parts[i]]) curr[parts[i]] = {};
                curr = curr[parts[i]];
            }
            curr[parts[parts.length - 1]] = value;
            return next;
        });
    };

    // Clause operations for Employment Bond
    const handleAddClause = () => {
        const clauses = sections.clauses || [];
        const newNum = clauses.length + 1;
        const newClause = {
            num: newNum,
            title: `New Clause ${newNum}`,
            body: 'Enter clause legal terms and conditions here...',
        };
        updateSectionValue('clauses', [...clauses, newClause]);
    };

    const handleDeleteClause = (index) => {
        const clauses = (sections.clauses || []).filter((_, i) => i !== index).map((c, i) => ({
            ...c,
            num: i + 1,
        }));
        updateSectionValue('clauses', clauses);
    };

    const handleMoveClause = (index, direction) => {
        const clauses = [...(sections.clauses || [])];
        const targetIndex = index + direction;
        if (targetIndex < 0 || targetIndex >= clauses.length) return;
        const temp = clauses[index];
        clauses[index] = clauses[targetIndex];
        clauses[targetIndex] = temp;
        // renumber
        const renumbered = clauses.map((c, i) => ({ ...c, num: i + 1 }));
        updateSectionValue('clauses', renumbered);
    };

    // Checklist operations for Registration Form
    const handleAddChecklistItem = () => {
        const list = sections.section_e?.checklist || [];
        updateSectionValue('section_e.checklist', [...list, 'New Verification Document Item']);
    };

    const handleDeleteChecklistItem = (index) => {
        const list = (sections.section_e?.checklist || []).filter((_, i) => i !== index);
        updateSectionValue('section_e.checklist', list);
    };

    // Submit publish
    const handlePublish = (e) => {
        e.preventDefault();
        setIsSaving(true);

        router.put(`/document-templates/${template.id}`, {
            name,
            description,
            is_active: isActive,
            design_config: design,
            sections_data: sections,
            change_summary: changeSummary || `Published version ${template.current_version + 1}`,
        }, {
            onFinish: () => setIsSaving(false),
        });
    };

    // Sample data compiled for live preview
    const sampleEmployee = useMemo(() => {
        if (selectedEmployeeId) {
            const found = employees.find(e => String(e.id) === String(selectedEmployeeId));
            if (found) {
                return {
                    id: found.employee_code || 'GOS-EMP-0001',
                    full_name: found.full_name,
                    date_of_birth: '15-05-2000',
                    gender: 'Male',
                    citizenship_number: '1234567890123',
                    permanent_address: 'Kathmandu, Nepal',
                    current_address: 'Kathmandu, Nepal',
                    phone: '9841234567',
                    email: found.email,
                    department: 'Technology',
                    designation: 'Software Developer',
                    employment_type: 'Full-time',
                    joining_date: '01-11-2026',
                    probation_period: '3 Months',
                    salary: `${company.currency_symbol || company.currency || ''} 60,000`.trim(),
                    reporting_manager: 'Sunil Adhikari',
                    work_location: company.city ? `${company.city} Office` : '',
                    working_schedule: '9 AM – 6 PM',
                    payment_method: 'Bank Transfer',
                    emergency_contact_name: 'Ramesh Mahato',
                    emergency_contact_phone: '9812345678',
                    emergency_contact_relation: 'Brother',
                    highest_qualification: 'Bachelor’s Degree',
                    institution: 'Tribhuvan University',
                    technical_skills: 'React, Node.js, Python',
                    previous_experience: '1 Year',
                    previous_organization: 'Prior Employer',
                    certifications: 'AWS, React',
                    portfolio: 'https://github.com/sitalmahato00',
                    primary_responsibilities: 'Develop web applications and maintain systems.',
                    secondary_responsibilities: 'Improve performance and ensure quality.',
                    assigned_projects: 'Internal Platform',
                    performance_targets: 'Meet project deadlines.',
                    reporting_requirements: 'Weekly progress report.',
                    authorized_system_access: 'Development Tools, Admin Panel',
                    department_head: '',
                    registration_date: '01-11-2026',
                };
            }
        }

        return {
            id: 'GOS-EMP-0001',
            full_name: 'Sital Mahato',
            date_of_birth: '15-05-2000',
            gender: 'Male',
            citizenship_number: '1234567890123',
            permanent_address: 'Kathmandu, Nepal',
            current_address: 'Kathmandu, Nepal',
            phone: '9841234567',
            email: 'sitalmahato00@gmail.com',
            department: 'Technology',
            designation: 'Software Developer',
            employment_type: 'Full-time',
            joining_date: '01-11-2026',
            probation_period: '3 Months',
            salary: `${company.currency_symbol || company.currency || ''} 60,000`.trim(),
            reporting_manager: 'Sunil Adhikari',
            work_location: company.city ? `${company.city} Office` : '',
            working_schedule: '9 AM – 6 PM',
            payment_method: 'Bank Transfer',
            emergency_contact_name: 'Ramesh Mahato',
            emergency_contact_phone: '9812345678',
            emergency_contact_relation: 'Brother',
            highest_qualification: 'Bachelor’s Degree',
            institution: 'Tribhuvan University',
            technical_skills: 'React, Node.js, Python',
            previous_experience: '1 Year',
            previous_organization: 'Prior Employer',
            certifications: 'AWS, React',
            portfolio: 'https://github.com/sitalmahato00',
            primary_responsibilities: 'Develop web applications and maintain systems.',
            secondary_responsibilities: 'Improve performance and ensure quality.',
            assigned_projects: 'Internal Platform',
            performance_targets: 'Meet project deadlines.',
            reporting_requirements: 'Weekly progress report.',
            authorized_system_access: 'Development Tools, Admin Panel',
            department_head: '',
            registration_date: '01-11-2026',
        };
    }, [selectedEmployeeId, employees, company.city]);

    // Interpolate helper for live preview
    const replaceVars = (text) => {
        if (!text || typeof text !== 'string') return text;
        return text
            .replace(/\{\{employee\.id\}\}/g, sampleEmployee.id)
            .replace(/\{\{employee\.full_name\}\}/g, sampleEmployee.full_name)
            .replace(/\{\{employee\.date_of_birth\}\}/g, sampleEmployee.date_of_birth)
            .replace(/\{\{employee\.gender\}\}/g, sampleEmployee.gender)
            .replace(/\{\{employee\.citizenship_number\}\}/g, sampleEmployee.citizenship_number)
            .replace(/\{\{employee\.permanent_address\}\}/g, sampleEmployee.permanent_address)
            .replace(/\{\{employee\.current_address\}\}/g, sampleEmployee.current_address)
            .replace(/\{\{employee\.phone\}\}/g, sampleEmployee.phone)
            .replace(/\{\{employee\.email\}\}/g, sampleEmployee.email)
            .replace(/\{\{employee\.department\}\}/g, sampleEmployee.department)
            .replace(/\{\{employee\.designation\}\}/g, sampleEmployee.designation)
            .replace(/\{\{employee\.employment_type\}\}/g, sampleEmployee.employment_type)
            .replace(/\{\{employee\.joining_date\}\}/g, sampleEmployee.joining_date)
            .replace(/\{\{employee\.probation_period\}\}/g, sampleEmployee.probation_period)
            .replace(/\{\{employee\.salary\}\}/g, sampleEmployee.salary)
            .replace(/\{\{employee\.reporting_manager\}\}/g, sampleEmployee.reporting_manager)
            .replace(/\{\{employee\.work_location\}\}/g, sampleEmployee.work_location)
            .replace(/\{\{employee\.working_schedule\}\}/g, sampleEmployee.working_schedule)
            .replace(/\{\{employee\.payment_method\}\}/g, sampleEmployee.payment_method)
            .replace(/\{\{employee\.emergency_contact_name\}\}/g, sampleEmployee.emergency_contact_name)
            .replace(/\{\{employee\.emergency_contact_phone\}\}/g, sampleEmployee.emergency_contact_phone)
            .replace(/\{\{employee\.emergency_contact_relation\}\}/g, sampleEmployee.emergency_contact_relation)
            .replace(/\{\{employee\.highest_qualification\}\}/g, sampleEmployee.highest_qualification)
            .replace(/\{\{employee\.institution\}\}/g, sampleEmployee.institution)
            .replace(/\{\{employee\.technical_skills\}\}/g, sampleEmployee.technical_skills)
            .replace(/\{\{employee\.previous_experience\}\}/g, sampleEmployee.previous_experience)
            .replace(/\{\{employee\.previous_organization\}\}/g, sampleEmployee.previous_organization)
            .replace(/\{\{employee\.certifications\}\}/g, sampleEmployee.certifications)
            .replace(/\{\{employee\.portfolio\}\}/g, sampleEmployee.portfolio)
            .replace(/\{\{employee\.primary_responsibilities\}\}/g, sampleEmployee.primary_responsibilities)
            .replace(/\{\{employee\.secondary_responsibilities\}\}/g, sampleEmployee.secondary_responsibilities)
            .replace(/\{\{employee\.assigned_projects\}\}/g, sampleEmployee.assigned_projects)
            .replace(/\{\{employee\.performance_targets\}\}/g, sampleEmployee.performance_targets)
            .replace(/\{\{employee\.reporting_requirements\}\}/g, sampleEmployee.reporting_requirements)
            .replace(/\{\{employee\.authorized_system_access\}\}/g, sampleEmployee.authorized_system_access)
            .replace(/\{\{employee\.department_head\}\}/g, sampleEmployee.department_head)
            .replace(/\{\{employee\.registration_date\}\}/g, sampleEmployee.registration_date)
            .replace(/\{\{company\.name\}\}/g, companyName)
            .replace(/\{\{company\.tagline\}\}/g, '')
            .replace(/\{\{company\.address\}\}/g, company.address || '')
            .replace(/\{\{company\.city\}\}/g, company.city || '')
            .replace(/\{\{company\.country\}\}/g, company.country || '')
            .replace(/\{\{company\.phone\}\}/g, company.phone || '')
            .replace(/\{\{company\.email\}\}/g, company.email || '')
            .replace(/\{\{company\.website\}\}/g, company.website || '')
            .replace(/\{\{company\.registration_no\}\}/g, company.registration_no || '')
            .replace(/\{\{company\.pan_vat_no\}\}/g, company.pan_vat_no || '')
            .replace(/\{\{company\.authorized_representative\}\}/g, '')
            .replace(/\{\{agreement\.number\}\}/g, 'GOS-BOND-0001')
            .replace(/\{\{agreement\.date\}\}/g, '01-11-2026')
            .replace(/\{\{hr\.representative_name\}\}/g, 'Sabin Shrestha');
    };

    useEffect(() => {
        if (previewMode !== 'pdf') return undefined;

        const controller = new AbortController();
        const timeout = window.setTimeout(async () => {
            setPreviewLoading(true);
            setPreviewError('');

            try {
                const response = await axios.post(
                    `/document-templates/${template.id}/preview-pdf/live`,
                    {
                        design_config: design,
                        sections_data: sections,
                        employee_id: selectedEmployeeId || null,
                    },
                    { responseType: 'blob', signal: controller.signal },
                );

                if (!response.headers['content-type']?.includes('application/pdf')) {
                    throw new Error('The preview endpoint did not return a PDF.');
                }

                if (!controller.signal.aborted) {
                    setPreviewPdfUrl(URL.createObjectURL(response.data));
                }
            } catch (error) {
                if (!controller.signal.aborted) {
                    setPreviewError(error.message || 'The live PDF preview could not be generated.');
                }
            } finally {
                if (!controller.signal.aborted) setPreviewLoading(false);
            }
        }, 400);

        return () => {
            window.clearTimeout(timeout);
            controller.abort();
        };
    }, [previewMode, template.id, design, sections, selectedEmployeeId]);

    useEffect(() => () => {
        if (previewPdfUrl) URL.revokeObjectURL(previewPdfUrl);
    }, [previewPdfUrl]);

    return (
        <AdminLayout title={`Edit Template: ${template.name}`}>
            <Head title={`Edit: ${template.name}`} />

            {/* Top Bar with Actions */}
            <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                <div className="flex items-center gap-3">
                    <Link
                        href="/document-templates"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-[#F36B12] transition"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        <span>All Templates</span>
                    </Link>
                    <span className="text-gray-300">/</span>
                    <span className="text-xs font-bold text-gray-800">{template.name}</span>
                    <Badge variant="neutral">v{template.current_version}</Badge>
                </div>

                <div className="flex flex-wrap items-center gap-2.5">
                    <Link
                        href={`/document-templates/${template.id}/versions`}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg shadow-2xs transition"
                    >
                        <RotateCcw className="h-4 w-4 text-indigo-600" />
                        <span>Version History ({template.versions?.length || 1})</span>
                    </Link>

                    <a
                        href={`/document-templates/${template.id}/preview-pdf`}
                        target="_blank"
                        rel="noopener noreferrer"
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg shadow-2xs transition"
                    >
                        <Printer className="h-4 w-4" />
                        <span>Print Preview</span>
                    </a>

                    <button
                        onClick={handlePublish}
                        disabled={isSaving}
                        className="inline-flex items-center gap-1.5 px-4 py-1.5 text-xs font-bold text-white bg-[#F36B12] hover:bg-[#E04B00] rounded-lg shadow-xs transition disabled:opacity-50"
                    >
                        <Save className="h-4 w-4" />
                        <span>{isSaving ? 'Publishing...' : `Publish Version ${template.current_version + 1}`}</span>
                    </button>
                </div>
            </div>

            {/* Split Screen Container: Editor on Left, Live A4 Preview on Right */}
            <div className="grid grid-cols-1 xl:grid-cols-12 gap-6 items-start">
                {/* LEFT PANE: Editor Controls (5 cols) */}
                <div className="xl:col-span-5 space-y-4">
                    {/* Navigation Tabs */}
                    <div className="bg-white p-1.5 rounded-2xl border border-gray-200 shadow-xs flex flex-col sm:flex-row items-stretch gap-1">
                        <button
                            onClick={() => setEditorTab('content')}
                            className={`w-full sm:w-auto sm:flex-1 py-2 px-3 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 ${
                                editorTab === 'content'
                                    ? 'bg-[#F36B12] text-white shadow-2xs'
                                    : 'text-gray-600 hover:bg-gray-100'
                            }`}
                        >
                            <FileText className="h-4 w-4" />
                            <span>Document Content</span>
                        </button>

                        <button
                            onClick={() => setEditorTab('design')}
                            className={`w-full sm:w-auto sm:flex-1 py-2 px-3 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 ${
                                editorTab === 'design'
                                    ? 'bg-[#F36B12] text-white shadow-2xs'
                                    : 'text-gray-600 hover:bg-gray-100'
                            }`}
                        >
                            <Palette className="h-4 w-4" />
                            <span>Design & Colors</span>
                        </button>

                        <button
                            onClick={() => setEditorTab('variables')}
                            className={`w-full sm:w-auto sm:flex-1 py-2 px-3 text-xs font-bold rounded-xl transition flex items-center justify-center gap-1.5 ${
                                editorTab === 'variables'
                                    ? 'bg-[#F36B12] text-white shadow-2xs'
                                    : 'text-gray-600 hover:bg-gray-100'
                            }`}
                        >
                            <Sparkles className="h-4 w-4" />
                            <span>Variables System</span>
                        </button>
                    </div>

                    {/* Version Change Summary Box */}
                    <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs">
                        <label className="block text-xs font-bold text-gray-700 mb-1">
                            Version Change Summary (for Audit History)
                        </label>
                        <input
                            type="text"
                            placeholder="e.g. Updated confidentiality clause 4 and adjusted section headers"
                            value={changeSummary}
                            onChange={(e) => setChangeSummary(e.target.value)}
                            className="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                        />
                    </div>

                    {/* TAB 1: CONTENT EDITOR */}
                    {editorTab === 'content' && (
                        <div className="space-y-4 max-h-[calc(100vh-250px)] overflow-y-auto pr-1">
                            {/* General Template Meta */}
                            <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                                <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100">
                                    Header & Document Titles
                                </h3>

                                <div>
                                    <label className="block text-xs font-semibold text-gray-600 mb-1">Document Title</label>
                                    <input
                                        type="text"
                                        value={sections.header?.title || ''}
                                        onChange={(e) => updateSectionValue('header.title', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg p-2 text-xs font-bold"
                                    />
                                </div>

                                <div>
                                    <label className="block text-xs font-semibold text-gray-600 mb-1">Document Subtitle</label>
                                    <input
                                        type="text"
                                        value={sections.header?.subtitle || ''}
                                        onChange={(e) => updateSectionValue('header.subtitle', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg p-2 text-xs"
                                    />
                                </div>

                            </div>

                            {/* REGISTRATION FORM SPECIFIC SECTIONS */}
                            {isRegistration && (
                                <>
                                    {/* Section A: Personal Information */}
                                    <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                                        <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100">
                                            Section A: Personal Information
                                        </h3>
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-600 mb-1">Section Title</label>
                                            <input
                                                type="text"
                                                value={sections.section_a?.title || 'Personal Information'}
                                                onChange={(e) => updateSectionValue('section_a.title', e.target.value)}
                                                className="w-full border border-gray-300 rounded-lg p-2 text-xs font-bold"
                                            />
                                        </div>
                                    </div>

                                    {/* Section B: Educational */}
                                    <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                                        <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100">
                                            Section B: Educational & Professional
                                        </h3>
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-600 mb-1">Section Title</label>
                                            <input
                                                type="text"
                                                value={sections.section_b?.title || 'Educational & Professional Information'}
                                                onChange={(e) => updateSectionValue('section_b.title', e.target.value)}
                                                className="w-full border border-gray-300 rounded-lg p-2 text-xs font-bold"
                                            />
                                        </div>
                                    </div>

                                    {/* Section E: Documents Checklist */}
                                    <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                                        <div className="flex items-center justify-between pb-2 border-b border-gray-100">
                                            <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                                Section E: Checklist Items ({sections.section_e?.checklist?.length || 0})
                                            </h3>
                                            <button
                                                type="button"
                                                onClick={handleAddChecklistItem}
                                                className="inline-flex items-center gap-1 text-[11px] font-bold text-[#F36B12] hover:underline"
                                            >
                                                <Plus className="h-3 w-3" />
                                                <span>Add Checkpoint</span>
                                            </button>
                                        </div>

                                        <div className="space-y-2">
                                            {(sections.section_e?.checklist || []).map((item, idx) => (
                                                <div key={idx} className="flex items-center gap-2">
                                                    <input
                                                        type="text"
                                                        value={item}
                                                        onChange={(e) => {
                                                            const copy = [...sections.section_e.checklist];
                                                            copy[idx] = e.target.value;
                                                            updateSectionValue('section_e.checklist', copy);
                                                        }}
                                                        className="flex-1 border border-gray-300 rounded-lg p-1.5 text-xs"
                                                    />
                                                    <button
                                                        type="button"
                                                        onClick={() => handleDeleteChecklistItem(idx)}
                                                        className="p-1.5 text-gray-400 hover:text-red-500 rounded-md"
                                                    >
                                                        <Trash2 className="h-3.5 w-3.5" />
                                                    </button>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Section F: Employee Declaration */}
                                    <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                                        <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100">
                                            Section F: Employee Declaration
                                        </h3>
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-600 mb-1">Declaration Legal Wording</label>
                                            <textarea
                                                rows={3}
                                                value={sections.section_f?.declaration_text || ''}
                                                onChange={(e) => updateSectionValue('section_f.declaration_text', e.target.value)}
                                                className="w-full border border-gray-300 rounded-lg p-2 text-xs"
                                            />
                                        </div>
                                    </div>
                                </>
                            )}

                            {/* EMPLOYMENT BOND SPECIFIC SECTIONS */}
                            {isBond && (
                                <>
                                    {/* Parties Introduction */}
                                    <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                                        <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100">
                                            Parties to the Agreement
                                        </h3>
                                        <div>
                                            <label className="block text-xs font-semibold text-gray-600 mb-1">Preamble Text</label>
                                            <input
                                                type="text"
                                                value={sections.parties_intro || 'This Employment Bond is made between:'}
                                                onChange={(e) => updateSectionValue('parties_intro', e.target.value)}
                                                className="w-full border border-gray-300 rounded-lg p-2 text-xs"
                                            />
                                        </div>
                                    </div>

                                    {/* Legal Clauses Manager */}
                                    <div className="bg-white p-4 rounded-2xl border border-gray-200 shadow-xs space-y-3">
                                        <div className="flex items-center justify-between pb-2 border-b border-gray-100">
                                            <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                                Legal Clauses ({(sections.clauses || []).length})
                                            </h3>
                                            <button
                                                type="button"
                                                onClick={handleAddClause}
                                                className="inline-flex items-center gap-1 text-[11px] font-bold text-[#F36B12] hover:underline"
                                            >
                                                <Plus className="h-3.5 w-3.5" />
                                                <span>Add New Clause</span>
                                            </button>
                                        </div>

                                        <div className="space-y-4">
                                            {(sections.clauses || []).map((clause, idx) => (
                                                <div key={idx} className="p-3 bg-gray-50 border border-gray-200 rounded-xl space-y-2">
                                                    <div className="flex items-center justify-between gap-2">
                                                        <div className="flex items-center gap-2 flex-1">
                                                            <span className="w-5 h-5 rounded-full bg-[#F36B12] text-white flex items-center justify-center text-[10px] font-bold shrink-0">
                                                                {clause.num || idx + 1}
                                                            </span>
                                                            <input
                                                                type="text"
                                                                value={clause.title || ''}
                                                                onChange={(e) => {
                                                                    const copy = [...sections.clauses];
                                                                    copy[idx].title = e.target.value;
                                                                    updateSectionValue('clauses', copy);
                                                                }}
                                                                className="flex-1 border border-gray-300 rounded-lg p-1.5 text-xs font-bold bg-white"
                                                            />
                                                        </div>

                                                        <div className="flex items-center gap-1">
                                                            <button
                                                                type="button"
                                                                onClick={() => handleMoveClause(idx, -1)}
                                                                disabled={idx === 0}
                                                                className="p-1 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                                                            >
                                                                <MoveUp className="h-3.5 w-3.5" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleMoveClause(idx, 1)}
                                                                disabled={idx === (sections.clauses.length - 1)}
                                                                className="p-1 text-gray-400 hover:text-gray-700 disabled:opacity-30"
                                                            >
                                                                <MoveDown className="h-3.5 w-3.5" />
                                                            </button>
                                                            <button
                                                                type="button"
                                                                onClick={() => handleDeleteClause(idx)}
                                                                className="p-1 text-gray-400 hover:text-red-500"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <textarea
                                                        rows={3}
                                                        value={clause.body || ''}
                                                        onChange={(e) => {
                                                            const copy = [...sections.clauses];
                                                            copy[idx].body = e.target.value;
                                                            updateSectionValue('clauses', copy);
                                                        }}
                                                        className="w-full border border-gray-300 rounded-lg p-2 text-xs bg-white font-mono text-[11px]"
                                                    />
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>
                    )}

                    {/* TAB 2: DESIGN & BRANDING */}
                    {editorTab === 'design' && (
                        <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4">
                            <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider pb-2 border-b border-gray-100">
                                Corporate Branding & Document Styling
                            </h3>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">Primary Color</label>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value={design.primary_color || '#F36B12'}
                                            onChange={(e) => updateDesign('primary_color', e.target.value)}
                                            className="h-8 w-12 rounded cursor-pointer border border-gray-200"
                                        />
                                        <input
                                            type="text"
                                            value={design.primary_color || '#F36B12'}
                                            onChange={(e) => updateDesign('primary_color', e.target.value)}
                                            className="flex-1 border border-gray-300 rounded-lg p-1.5 text-xs font-mono"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">Secondary Color (Deep Orange)</label>
                                    <div className="flex items-center gap-2">
                                        <input
                                            type="color"
                                            value={design.secondary_color || '#E94B0B'}
                                            onChange={(e) => updateDesign('secondary_color', e.target.value)}
                                            className="h-8 w-12 rounded cursor-pointer border border-gray-200"
                                        />
                                        <input
                                            type="text"
                                            value={design.secondary_color || '#E94B0B'}
                                            onChange={(e) => updateDesign('secondary_color', e.target.value)}
                                            className="flex-1 border border-gray-300 rounded-lg p-1.5 text-xs font-mono"
                                        />
                                    </div>
                                </div>
                            </div>

                            <div className="grid grid-cols-2 gap-4">
                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">Font Family</label>
                                    <select
                                        value={design.font_family || 'DejaVu Sans'}
                                        onChange={(e) => updateDesign('font_family', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg p-2 text-xs"
                                    >
                                        <option value="DejaVu Sans">DejaVu Sans (Default)</option>
                                        <option value="Arial">Arial</option>
                                        <option value="Helvetica">Helvetica</option>
                                        <option value="Times New Roman">Times New Roman</option>
                                        <option value="Georgia">Georgia</option>
                                    </select>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">Body Font Size</label>
                                    <select
                                        value={design.font_size || '8pt'}
                                        onChange={(e) => updateDesign('font_size', e.target.value)}
                                        className="w-full border border-gray-300 rounded-lg p-2 text-xs"
                                    >
                                        <option value="7pt">Compact (7pt)</option>
                                        <option value="7.6pt">Standard Compact (7.6pt)</option>
                                        <option value="8pt">Normal (8pt)</option>
                                        <option value="8.5pt">Medium (8.5pt)</option>
                                        <option value="9pt">Large (9pt)</option>
                                    </select>
                                </div>
                            </div>

                        </div>
                    )}

                    {/* TAB 3: DYNAMIC VARIABLES DIRECTORY */}
                    {editorTab === 'variables' && (
                        <div className="bg-white p-5 rounded-2xl border border-gray-200 shadow-xs space-y-4 max-h-[calc(100vh-250px)] overflow-y-auto">
                            <div className="flex items-center justify-between pb-2 border-b border-gray-100">
                                <div>
                                    <h3 className="text-xs font-bold text-gray-900 uppercase tracking-wider">
                                        Dynamic Variables System
                                    </h3>
                                    <p className="text-[11px] text-gray-500">
                                        Click any variable to copy to clipboard and paste into any clause or heading.
                                    </p>
                                </div>
                            </div>

                            {Object.entries(availableVariables).map(([category, list]) => (
                                <div key={category} className="space-y-2">
                                    <span className="text-[11px] font-black uppercase text-[#F36B12] block tracking-wider">
                                        {category}
                                    </span>
                                    <div className="grid grid-cols-1 gap-1.5">
                                        {list.map((item) => (
                                            <button
                                                key={item.var}
                                                type="button"
                                                onClick={() => handleCopyVar(item.var)}
                                                className="w-full text-left p-2 rounded-lg bg-gray-50 hover:bg-orange-50 border border-gray-200 hover:border-orange-300 transition flex items-center justify-between group"
                                            >
                                                <div>
                                                    <span className="font-mono text-xs font-bold text-gray-900 group-hover:text-[#F36B12]">
                                                        {item.var}
                                                    </span>
                                                    <span className="text-[10px] text-gray-500 block">
                                                        {item.desc}
                                                    </span>
                                                </div>
                                                <span className="text-[10px] font-semibold text-gray-400 group-hover:text-[#F36B12] flex items-center gap-1">
                                                    {copiedVar === item.var ? (
                                                        <span className="text-emerald-600 flex items-center gap-0.5">
                                                            <Check className="h-3 w-3" /> Copied
                                                        </span>
                                                    ) : (
                                                        <Copy className="h-3 w-3 opacity-0 group-hover:opacity-100" />
                                                    )}
                                                </span>
                                            </button>
                                        ))}
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>

                {/* RIGHT PANE: LIVE A4 PREVIEW (7 cols) */}
                <div className="xl:col-span-7 bg-white p-5 rounded-2xl border border-gray-200 shadow-xs flex flex-col">
                    {/* Live Preview Controls */}
                    <div className="flex flex-wrap items-center justify-between gap-3 pb-3 mb-4 border-b border-gray-100 text-xs">
                        <div className="flex items-center gap-2">
                            <span className="font-bold text-gray-800 flex items-center gap-1.5">
                                <Eye className="h-4 w-4 text-[#F36B12]" />
                                <span>Live A4 Document Preview</span>
                            </span>
                            <span className={`px-2 py-0.5 rounded-full text-[10px] font-bold ${previewError ? 'bg-red-100 text-red-700' : previewLoading ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-800'}`}>
                                {previewError ? 'Preview Error' : previewLoading ? 'Updating PDF…' : previewMode === 'pdf' ? 'Live PDF' : 'Live Layout'}
                            </span>
                        </div>

                        <div className="flex items-center gap-2">
                            <div className="flex items-center rounded-lg border border-gray-200 bg-gray-50 p-0.5">
                                <button
                                    type="button"
                                    aria-pressed={previewMode === 'layout'}
                                    onClick={() => setPreviewMode('layout')}
                                    className={`rounded-md px-2 py-1 text-[11px] font-semibold ${previewMode === 'layout' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-800'}`}
                                >
                                    Layout
                                </button>
                                <button
                                    type="button"
                                    aria-pressed={previewMode === 'pdf'}
                                    onClick={() => setPreviewMode('pdf')}
                                    className={`rounded-md px-2 py-1 text-[11px] font-semibold ${previewMode === 'pdf' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-800'}`}
                                >
                                    Live PDF
                                </button>
                            </div>

                            {/* Employee Switcher */}
                            <select
                                value={selectedEmployeeId}
                                onChange={(e) => setSelectedEmployeeId(e.target.value)}
                                className="border border-gray-300 rounded-lg px-2 py-1 text-xs font-medium"
                            >
                                <option value="">Preview: Sample (Sital Mahato)</option>
                                {employees.map((emp) => (
                                    <option key={emp.id} value={emp.id}>
                                        {emp.full_name} ({emp.employee_code})
                                    </option>
                                ))}
                            </select>

                            {/* Zoom Controls */}
                            <div className="flex items-center border border-gray-200 rounded-lg overflow-hidden bg-gray-50">
                                <button
                                    type="button"
                                    onClick={() => setPreviewZoom(Math.max(60, previewZoom - 10))}
                                    className="p-1 hover:bg-gray-200 text-gray-600"
                                    title="Zoom Out"
                                >
                                    <ZoomOut className="h-3.5 w-3.5" />
                                </button>
                                <span className="px-2 font-mono text-[11px] text-gray-700 font-bold">
                                    {previewZoom}%
                                </span>
                                <button
                                    type="button"
                                    onClick={() => setPreviewZoom(Math.min(130, previewZoom + 10))}
                                    className="p-1 hover:bg-gray-200 text-gray-600"
                                    title="Zoom In"
                                >
                                    <ZoomIn className="h-3.5 w-3.5" />
                                </button>
                            </div>
                        </div>
                    </div>

                    {/* A4 Container Preview Canvas */}
                    {previewMode === 'layout' ? (
                    <div className="bg-gray-100 rounded-xl p-4 overflow-auto max-h-[calc(100vh-250px)] flex justify-center">
                        <div
                            style={{
                                transform: `scale(${previewZoom / 100})`,
                                transformOrigin: 'top center',
                                width: '210mm',
                                minHeight: '297mm',
                                backgroundColor: '#ffffff',
                                color: design.text_color || '#202124',
                                fontFamily: design.font_family || 'DejaVu Sans',
                                fontSize: design.font_size || '8pt',
                                boxShadow: '0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1)',
                                padding: '10mm 12mm',
                                position: 'relative',
                                boxSizing: 'border-box',
                            }}
                            className="transition-transform duration-150"
                        >
                            {/* Fluid Top Wave */}
                            <div
                                style={{
                                    height: '16px',
                                    background: `linear-gradient(135deg, ${design.primary_color || '#F36B12'}, ${design.secondary_color || '#E94B0B'})`,
                                    borderBottomLeftRadius: '50% 12px',
                                    borderBottomRightRadius: '50% 12px',
                                    marginBottom: '8px',
                                }}
                            />

                            {/* Header */}
                            <div className="flex items-center justify-between mb-2">
                                <div className="flex items-center gap-2">
                                    {company.logo_url && <img src={company.logo_url} alt={companyName || 'Company logo'} className="max-h-10 max-w-24 object-contain" />}
                                    <div>
                                        <div className="text-xl font-black text-gray-900 leading-none">{companyName}</div>
                                        {company.trading_name && <div className="text-[9px] font-bold tracking-widest mt-0.5">{company.trading_name}</div>}
                                    </div>
                                </div>
                                <div className="text-right text-[9px] font-medium text-gray-600">
                                    {[companyAddress, company.phone, company.email, company.website].filter(Boolean).map((value) => (
                                        <div key={value}>{value}</div>
                                    ))}
                                </div>
                            </div>

                            {/* Document Title Banner */}
                            <div className="text-center my-2">
                                <div
                                    style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                    className="inline-block text-white font-bold text-xs px-6 py-1 rounded-full uppercase tracking-wider"
                                >
                                    {sections.header?.title || (isRegistration ? 'EMPLOYEE REGISTRATION FORM' : 'EMPLOYMENT BOND')}
                                </div>
                                <div className="text-[10px] text-gray-500 font-semibold mt-0.5">
                                    {sections.header?.subtitle || 'Confidential – Human Resources Department'}
                                </div>
                            </div>

                            {/* Meta Badge Bar */}
                            <div
                                style={{
                                    backgroundColor: '#FFF3EB',
                                    borderColor: '#FFD6C0',
                                }}
                                className="border rounded-md px-3 py-1 flex items-center justify-between text-[10px] font-bold my-2"
                            >
                                <span style={{ color: design.primary_color || '#F36B12' }}>
                                    &#128196; <strong>{isRegistration ? 'Employee ID' : 'Bond Number'}:</strong>{' '}
                                    <span className="text-gray-900 font-mono font-bold">
                                        {isRegistration ? sampleEmployee.id : 'GOS-BOND-0001'}
                                    </span>
                                </span>
                                <span style={{ color: design.primary_color || '#F36B12' }}>
                                    &#128197; <strong>{isRegistration ? 'Registration Date' : 'Agreement Date'}:</strong>{' '}
                                    <span className="text-gray-900">01-11-2026</span>
                                </span>
                            </div>

                            {/* DOCUMENT A: REGISTRATION FORM LIVE PREVIEW */}
                            {isRegistration && (
                                <div className="space-y-2 text-[10px]">
                                    {/* Section A */}
                                    <div
                                        style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                        className="text-white font-bold px-2.5 py-0.5 rounded-full text-[10.5px]"
                                    >
                                        A. {sections.section_a?.title || 'Personal Information'}
                                    </div>
                                    <div className="flex gap-3">
                                        <div className="flex-1 space-y-1">
                                            <div className="grid grid-cols-4 gap-1">
                                                <span className="font-semibold text-gray-600">Full Legal Name:</span>
                                                <span className="col-span-3 font-bold text-gray-900">{sampleEmployee.full_name}</span>
                                            </div>
                                            <div className="grid grid-cols-4 gap-1">
                                                <span className="font-semibold text-gray-600">Date of Birth:</span>
                                                <span className="text-gray-800">{sampleEmployee.date_of_birth}</span>
                                                <span className="font-semibold text-gray-600">Gender:</span>
                                                <span className="text-gray-800">{sampleEmployee.gender}</span>
                                            </div>
                                            <div className="grid grid-cols-4 gap-1">
                                                <span className="font-semibold text-gray-600">Citizenship No:</span>
                                                <span className="col-span-3 text-gray-800">{sampleEmployee.citizenship_number}</span>
                                            </div>
                                            <div className="grid grid-cols-4 gap-1">
                                                <span className="font-semibold text-gray-600">Address:</span>
                                                <span className="col-span-3 text-gray-800">{sampleEmployee.permanent_address}</span>
                                            </div>
                                            <div className="grid grid-cols-4 gap-1">
                                                <span className="font-semibold text-gray-600">Contact / Email:</span>
                                                <span className="col-span-3 text-gray-800">{sampleEmployee.phone} | {sampleEmployee.email}</span>
                                            </div>
                                        </div>
                                        <div className="w-20 h-24 border border-dashed border-gray-300 rounded flex items-center justify-center text-[8px] text-gray-400 text-center p-1 bg-gray-50 shrink-0">
                                            Passport Photo
                                        </div>
                                    </div>

                                    {/* Section B */}
                                    <div
                                        style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                        className="text-white font-bold px-2.5 py-0.5 rounded-full text-[10.5px] mt-1"
                                    >
                                        B. {sections.section_b?.title || 'Educational & Professional Information'}
                                    </div>
                                    <div className="space-y-0.5">
                                        <div className="grid grid-cols-4 gap-1">
                                            <span className="font-semibold text-gray-600">Highest Qualification:</span>
                                            <span className="col-span-3 text-gray-900">{sampleEmployee.highest_qualification} ({sampleEmployee.institution})</span>
                                        </div>
                                        <div className="grid grid-cols-4 gap-1">
                                            <span className="font-semibold text-gray-600">Technical Skills:</span>
                                            <span className="col-span-3 text-gray-900">{sampleEmployee.technical_skills}</span>
                                        </div>
                                        <div className="grid grid-cols-4 gap-1">
                                            <span className="font-semibold text-gray-600">Previous Experience:</span>
                                            <span className="col-span-3 text-gray-900">{sampleEmployee.previous_experience} at {sampleEmployee.previous_organization}</span>
                                        </div>
                                    </div>

                                    {/* Section C: Employment */}
                                    <div
                                        style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                        className="text-white font-bold px-2.5 py-0.5 rounded-full text-[10.5px] mt-1"
                                    >
                                        C. Employment Information
                                    </div>
                                    <div className="grid grid-cols-2 gap-3">
                                        <div>
                                            <div className="grid grid-cols-2 gap-1">
                                                <span className="font-semibold text-gray-600">Department:</span>
                                                <span className="font-bold text-gray-900">{sampleEmployee.department}</span>
                                            </div>
                                            <div className="grid grid-cols-2 gap-1">
                                                <span className="font-semibold text-gray-600">Designation:</span>
                                                <span className="font-bold text-gray-900">{sampleEmployee.designation}</span>
                                            </div>
                                            <div className="grid grid-cols-2 gap-1">
                                                <span className="font-semibold text-gray-600">Employment Type:</span>
                                                <span className="text-gray-900">{sampleEmployee.employment_type}</span>
                                            </div>
                                        </div>
                                        <div>
                                            <div className="grid grid-cols-2 gap-1">
                                                <span className="font-semibold text-gray-600">Reporting Manager:</span>
                                                <span className="text-gray-900">{sampleEmployee.reporting_manager}</span>
                                            </div>
                                            <div className="grid grid-cols-2 gap-1">
                                                <span className="font-semibold text-gray-600">Monthly Salary:</span>
                                                <span className="font-bold text-gray-900">{sampleEmployee.salary}</span>
                                            </div>
                                            <div className="grid grid-cols-2 gap-1">
                                                <span className="font-semibold text-gray-600">Work Location:</span>
                                                <span className="text-gray-900">{sampleEmployee.work_location}</span>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Section E & F */}
                                    <div className="grid grid-cols-2 gap-3 mt-1">
                                        <div className="border border-gray-200 rounded p-2 bg-gray-50">
                                            <div className="font-bold text-gray-900 mb-1">E. Verification Checklist</div>
                                            <div className="space-y-0.5 text-[9px] text-gray-600">
                                                {(sections.section_e?.checklist || []).slice(0, 5).map((item, idx) => (
                                                    <div key={idx} className="flex items-center gap-1.5">
                                                        <span className="w-2.5 h-2.5 border border-gray-400 rounded-2xs inline-block" />
                                                        <span>{item}</span>
                                                    </div>
                                                ))}
                                            </div>
                                        </div>
                                        <div className="border border-gray-200 rounded p-2 bg-gray-50">
                                            <div className="font-bold text-gray-900 mb-1">F. Employee Declaration</div>
                                            <p className="text-[8.5px] italic text-gray-600 mb-2 leading-tight">
                                                "{sections.section_f?.declaration_text || 'I hereby declare that the information provided above is true and accurate.'}"
                                            </p>
                                            <div className="text-[9px] text-gray-800">
                                                Name: <strong>{sampleEmployee.full_name}</strong> | Sig: _________
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* DOCUMENT B: EMPLOYMENT BOND LIVE PREVIEW */}
                            {isBond && (
                                <div className="space-y-2 text-[9.5px]">
                                    <div className="font-bold text-gray-900 mb-1 text-[10px]">
                                        {sections.parties_intro || 'This Employment Bond is made between:'}
                                    </div>

                                    {/* Parties Grid */}
                                    <div className="grid grid-cols-11 gap-1.5 items-center mb-2">
                                        <div className="col-span-5 border border-orange-200 bg-orange-50/50 p-2 rounded text-[9px]">
                                            <div
                                                style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                                className="inline-block text-white font-bold text-[8px] px-1.5 py-0.2 rounded"
                                            >
                                                A. Company
                                            </div>
                                            <div className="font-bold text-gray-900 mt-1">{companyName}</div>
                                            <div className="text-gray-600 text-[8px]">{companyAddress}</div>
                                            {replaceVars(sections.parties?.company?.representative) && (
                                                <div className="text-gray-600 text-[8px]">Rep: {replaceVars(sections.parties.company.representative)}</div>
                                            )}
                                        </div>

                                        <div className="col-span-1 text-center font-bold text-[9px] text-[#F36B12]">
                                            AND
                                        </div>

                                        <div className="col-span-5 border border-orange-200 bg-orange-50/50 p-2 rounded text-[9px]">
                                            <div
                                                style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                                className="inline-block text-white font-bold text-[8px] px-1.5 py-0.2 rounded"
                                            >
                                                B. Employee
                                            </div>
                                            <div className="font-bold text-gray-900 mt-1">{sampleEmployee.full_name}</div>
                                            <div className="text-gray-600 text-[8px]">ID: {sampleEmployee.id}</div>
                                            <div className="text-gray-600 text-[8px]">{sampleEmployee.designation} &bull; {sampleEmployee.department}</div>
                                        </div>
                                    </div>

                                    {/* Clauses in 2 Columns */}
                                    <div className="grid grid-cols-2 gap-3 text-[8.5px]">
                                        <div className="space-y-1.5">
                                            {(sections.clauses || []).slice(0, 6).map((c, idx) => (
                                                <div key={idx} className="space-y-0.5">
                                                    <div
                                                        style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                                        className="inline-block text-white font-bold text-[8px] px-1.5 py-0.2 rounded"
                                                    >
                                                        {c.num || idx + 1}. {c.title}
                                                    </div>
                                                    <p className="text-gray-700 leading-tight">
                                                        {replaceVars(c.body)}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>

                                        <div className="space-y-1.5">
                                            {(sections.clauses || []).slice(6).map((c, idx) => (
                                                <div key={idx} className="space-y-0.5">
                                                    <div
                                                        style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                                        className="inline-block text-white font-bold text-[8px] px-1.5 py-0.2 rounded"
                                                    >
                                                        {c.num || idx + 7}. {c.title}
                                                    </div>
                                                    <p className="text-gray-700 leading-tight">
                                                        {replaceVars(c.body)}
                                                    </p>
                                                </div>
                                            ))}
                                        </div>
                                    </div>

                                    {/* Signatures */}
                                    <div className="grid grid-cols-2 gap-3 pt-2 border-t border-gray-200 mt-2 text-[8.5px]">
                                        <div className="border rounded p-1.5 bg-gray-50">
                                            <div className="font-bold text-gray-800 text-[8px] uppercase mb-1">EMPLOYEE & WITNESS</div>
                                            <div>{sampleEmployee.full_name} ({sampleEmployee.id})</div>
                                            <div className="mt-1">Signature: ____________ Date: 01-11-2026</div>
                                        </div>
                                        <div className="border rounded p-1.5 bg-gray-50">
                                            <div className="font-bold text-gray-800 text-[8px] uppercase mb-1">FOR {companyName}</div>
                                            {replaceVars(sections.signatures?.company?.representative) && (
                                                <div>Authorized Rep: {replaceVars(sections.signatures.company.representative)}</div>
                                            )}
                                            <div className="mt-1">Signature & Stamp: ____________ Date: 01-11-2026</div>
                                        </div>
                                    </div>
                                </div>
                            )}

                            {/* Footer */}
                            <div
                                style={{ backgroundColor: design.primary_color || '#F36B12' }}
                                className="text-white text-[8px] font-medium p-1.5 rounded-md flex items-center justify-between mt-3"
                            >
                                <span>{companyAddress}</span>
                                <span>{company.website}</span>
                                <span>{company.phone || company.email}</span>
                                <span className="font-bold">{company.report_footer_text}</span>
                            </div>
                        </div>
                        </div>
                        ) : (
                            <div className="relative min-h-[70vh] overflow-auto rounded-xl border border-gray-200 bg-gray-100">
                                {previewPdfUrl ? (
                                    <iframe
                                        key={previewPdfUrl}
                                        src={previewPdfUrl}
                                        title="Live PDF preview of the current draft"
                                        className="h-[70vh] min-h-[600px] w-full border-0 bg-white"
                                        style={{ zoom: `${previewZoom}%` }}
                                    />
                                ) : (
                                    <div role="status" className="flex min-h-[70vh] items-center justify-center p-8 text-center text-sm text-gray-500">
                                        {previewLoading ? 'Generating live PDF preview…' : previewError || 'Switch to Live PDF to preview the current draft.'}
                                    </div>
                                )}
                                {previewError && (
                                    <p role="alert" className="border-t border-red-200 bg-red-50 px-4 py-2 text-xs text-red-700">
                                        {previewError}
                                    </p>
                                )}
                            </div>
                        )}
                    </div>
                </div>
        </AdminLayout>
    );
}

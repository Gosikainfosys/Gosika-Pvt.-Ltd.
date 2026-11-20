import React, { useState } from 'react';
import { Head, Link, usePage, router } from '@inertiajs/react';
import AdminLayout from '@/Layouts/AdminLayout';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import {
    ArrowLeft,
    Edit3,
    CreditCard,
    Printer,
    Download,
    Mail,
    Phone,
    Building2,
    Calendar,
    Briefcase,
    HeartPulse,
    ShieldCheck,
    Clock,
    Banknote,
    FolderLock,
    History,
    FileText,
    Eye,
    RefreshCw,
    FolderCheck,
    Upload,
    CheckCircle2,
    Archive,
    ExternalLink
} from 'lucide-react';

export default function Show({
    employee,
    attendances,
    leaves,
    salaries,
    documents,
    generatedDocuments = [],
    idCards,
    auditLogs,
    qrCodeUri,
    tab = 'personal'
}) {
    const [activeTab, setActiveTab] = useState(tab);
    const { company } = usePage().props;
    const companyLocation = company?.address || [company?.city, company?.country].filter(Boolean).join(', ');
    const currencyLabel = company?.currency_symbol || company?.currency || '';
    const formatCurrency = (amount) => `${currencyLabel ? `${currencyLabel} ` : ''}${Number(amount).toLocaleString()}`;

    // Modals for Employee Documents handling
    const [previewDoc, setPreviewDoc] = useState(null);
    const [statusModalDoc, setStatusModalDoc] = useState(null);
    const [modalSubTab, setModalSubTab] = useState('filing'); // 'filing' | 'details'
    const [statusForm, setStatusForm] = useState({});
    const [isSavingDetails, setIsSavingDetails] = useState(false);
    const [uploadModalDoc, setUploadModalDoc] = useState(null);
    const [scannedFile, setScannedFile] = useState(null);
    const [isUploading, setIsUploading] = useState(false);
    const [regeneratingType, setRegeneratingType] = useState(null);

    const tabs = [
        { id: 'personal', name: 'Personal Profile' },
        { id: 'employment', name: 'Employment & Compensation' },
        { id: 'employee_documents', name: 'Official Documents (Registration & Bond)', count: generatedDocuments?.length || 2 },
        { id: 'idcard', name: 'CR80 ID Card' },
        { id: 'attendance', name: 'Attendance History' },
        { id: 'leave', name: 'Leave Record' },
        { id: 'payroll', name: 'Salary Slips' },
        { id: 'documents', name: 'Document Vault' },
        { id: 'history', name: 'Audit Trail' },
    ];

    const handlePrintProfile = () => {
        window.print();
    };

    const handleOpenStatusModal = (doc) => {
        setStatusModalDoc(doc);
        setModalSubTab('filing');
        const snap = doc.snapshot_data || {};
        setStatusForm({
            // Physical Filing
            status: doc.status || 'generated',
            physical_location: doc.physical_location || '',
            received_by_hr: doc.received_by_hr || '',
            hr_remarks: doc.hr_remarks || '',

            // Document Details & Text
            full_name: snap['{{employee.full_name}}'] || employee.full_name || '',
            employee_id: snap['{{employee.id}}'] || employee.employee_code || '',
            date_of_birth: snap['{{employee.date_of_birth}}'] || employee.date_of_birth || '',
            gender: snap['{{employee.gender}}'] || employee.gender || '',
            citizenship_number: snap['{{employee.citizenship_number}}'] || '',
            permanent_address: snap['{{employee.permanent_address}}'] || employee.permanent_address || '',
            current_address: snap['{{employee.current_address}}'] || employee.current_address || employee.permanent_address || '',
            phone: snap['{{employee.phone}}'] || employee.phone || '',
            email: snap['{{employee.email}}'] || employee.email || '',
            emergency_contact_name: snap['{{employee.emergency_contact_name}}'] || employee.emergency_contact_name || '',
            emergency_contact_phone: snap['{{employee.emergency_contact_phone}}'] || employee.emergency_contact_phone || '',
            emergency_contact_relation: snap['{{employee.emergency_contact_relation}}'] || employee.emergency_contact_relation || '',
            
            highest_qualification: snap['{{employee.highest_qualification}}'] || '',
            institution: snap['{{employee.institution}}'] || '',
            technical_skills: snap['{{employee.technical_skills}}'] || '',
            previous_experience: snap['{{employee.previous_experience}}'] || '',
            previous_organization: snap['{{employee.previous_organization}}'] || '',
            certifications: snap['{{employee.certifications}}'] || '',
            portfolio: snap['{{employee.portfolio}}'] || '',

            department: snap['{{employee.department}}'] || employee.department?.name || '',
            designation: snap['{{employee.designation}}'] || employee.designation?.title || '',
            employment_type: snap['{{employee.employment_type}}'] || employee.employment_type || '',
            joining_date: snap['{{employee.joining_date}}'] || employee.joining_date || '',
            probation_period: snap['{{employee.probation_period}}'] || '3 Months',
            reporting_manager: snap['{{employee.reporting_manager}}'] || '',
            work_location: snap['{{employee.work_location}}'] || (company?.city ? `${company.city} Office` : ''),
            working_schedule: snap['{{employee.working_schedule}}'] || '9 AM – 6 PM',
            salary: snap['{{employee.salary}}'] || (employee.basic_salary ? formatCurrency(employee.basic_salary) : ''),
            payment_method: snap['{{employee.payment_method}}'] || 'Bank Transfer',

            primary_responsibilities: snap['{{employee.primary_responsibilities}}'] || '',
            secondary_responsibilities: snap['{{employee.secondary_responsibilities}}'] || '',
            assigned_projects: snap['{{employee.assigned_projects}}'] || '',
            performance_targets: snap['{{employee.performance_targets}}'] || '',
            reporting_requirements: snap['{{employee.reporting_requirements}}'] || '',
            authorized_system_access: snap['{{employee.authorized_system_access}}'] || '',
            department_head: snap['{{employee.department_head}}'] || '',

            hr_representative_name: snap['{{hr.representative_name}}'] || '',
            registration_date: snap['{{employee.registration_date}}'] || '',
            agreement_number: snap['{{agreement.number}}'] || '',
            agreement_date: snap['{{agreement.date}}'] || '',
            authorized_representative: snap['{{company.authorized_representative}}'] || '',
            company_address: snap['{{company.address}}'] || '',
        });
    };

    const handleSaveStatus = (e) => {
        e.preventDefault();
        if (!statusModalDoc) return;

        setIsSavingDetails(true);
        router.post(`/employee-documents/${statusModalDoc.id}/update-details`, statusForm, {
            preserveScroll: true,
            onSuccess: () => {
                setStatusModalDoc(null);
                setIsSavingDetails(false);
            },
            onError: () => {
                setIsSavingDetails(false);
            },
        });
    };

    const handleUploadScannedCopy = (e) => {
        e.preventDefault();
        if (!uploadModalDoc || !scannedFile) return;

        const formData = new FormData();
        formData.append('scanned_copy', scannedFile);

        setIsUploading(true);
        router.post(`/employee-documents/${uploadModalDoc.id}/upload-signed`, formData, {
            preserveScroll: true,
            onSuccess: () => {
                setUploadModalDoc(null);
                setScannedFile(null);
                setIsUploading(false);
            },
            onError: () => {
                setIsUploading(false);
            },
        });
    };

    const handleRegenerate = (type) => {
        if (!confirm(`Are you sure you want to regenerate this document using the latest active template?`)) {
            return;
        }
        setRegeneratingType(type);
        router.post(`/employee-documents/${employee.id}/regenerate/${type}`, {}, {
            preserveScroll: true,
            onFinish: () => setRegeneratingType(null),
        });
    };

    return (
        <AdminLayout title={`Employee: ${employee.full_name}`}>
            <Head title={`${employee.full_name} (${employee.employee_code})`} />

            {/* Back link & actions */}
            <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6 no-print">
                <div className="flex items-center gap-2">
                    <Link
                        href="/employees"
                        className="inline-flex items-center gap-1.5 text-xs font-semibold text-gray-500 hover:text-[#FF5500] transition"
                    >
                        <ArrowLeft className="h-4 w-4" />
                        <span>Back to Directory</span>
                    </Link>
                </div>

                <div className="flex items-center gap-2.5">
                    <button
                        onClick={handlePrintProfile}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-50 rounded-lg shadow-2xs transition"
                    >
                        <Printer className="h-4 w-4" />
                        <span>Print Profile</span>
                    </button>
                    <button
                        onClick={() => setActiveTab('employee_documents')}
                        className={`inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold rounded-lg shadow-2xs transition ${
                            activeTab === 'employee_documents'
                                ? 'bg-[#F36B12] text-white shadow-xs'
                                : 'text-[#F36B12] bg-[#FFF3EB] border border-[#FFD6C0] hover:bg-orange-100'
                        }`}
                    >
                        <FileText className="h-4 w-4" />
                        <span>Official Documents ({generatedDocuments?.length || 2})</span>
                    </button>
                    <a
                        href={`/employee-id-cards/${employee.id}/pdf`}
                        className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-[#FF5500] bg-[#FFF0E8] border border-[#FFD6C0] hover:bg-orange-100 rounded-lg shadow-2xs transition"
                    >
                        <CreditCard className="h-4 w-4" />
                        <span>Download CR80 ID Card</span>
                    </a>
                    <Link
                        href={`/employees/${employee.id}/edit`}
                        className="inline-flex items-center gap-1.5 px-3.5 py-1.5 text-xs font-bold text-white bg-[#FF5500] hover:bg-[#E04B00] rounded-lg shadow-xs transition"
                    >
                        <Edit3 className="h-4 w-4" />
                        <span>Edit Employee</span>
                    </Link>
                </div>
            </div>

            {/* Top Employee Profile Card Banner */}
            <div className="bg-white rounded-2xl border border-[#E5E7EB] shadow-xs p-6 mb-6">
                <div className="flex flex-col sm:flex-row items-center sm:items-start gap-5">
                    {/* Photo */}
                    {employee.photo_path ? (
                        <img
                            src={`/storage/${employee.photo_path}`}
                            alt={employee.full_name}
                            className="h-24 w-24 rounded-2xl object-cover border-2 border-[#FF5500] shadow-sm shrink-0"
                        />
                    ) : (
                        <div className="h-24 w-24 rounded-2xl bg-[#FFF0E8] border-2 border-[#FFD6C0] text-[#FF5500] font-black flex items-center justify-center text-2xl shadow-xs shrink-0">
                            {employee.first_name.charAt(0)}{employee.last_name.charAt(0)}
                        </div>
                    )}

                    {/* Details */}
                    <div className="flex-1 text-center sm:text-left min-w-0">
                        <div className="flex flex-col sm:flex-row sm:items-center gap-2 mb-1">
                            <h1 className="text-xl font-black text-[#202124]">{employee.full_name}</h1>
                            <div className="flex items-center justify-center sm:justify-start gap-2">
                                <span className="font-mono text-xs font-bold text-gray-500 bg-gray-100 px-2 py-0.5 rounded">
                                    {employee.employee_code}
                                </span>
                                <Badge variant={employee.status === 'active' ? 'success' : 'danger'}>
                                    {employee.status?.toUpperCase()}
                                </Badge>
                            </div>
                        </div>

                        <p className="text-xs font-bold text-[#FF5500] mb-3">
                            {employee.designation?.title || 'Staff'} &bull; <span className="text-gray-600">{employee.department?.name || 'General Management'}</span>
                        </p>

                        <div className="flex flex-wrap items-center justify-center sm:justify-start gap-4 text-xs text-gray-600">
                            <div className="flex items-center gap-1.5">
                                <Mail className="h-3.5 w-3.5 text-gray-400" />
                                <span>{employee.email}</span>
                            </div>
                            <div className="flex items-center gap-1.5">
                                <Phone className="h-3.5 w-3.5 text-gray-400" />
                                <span>{employee.phone}</span>
                            </div>
                            <div className="flex items-center gap-1.5">
                                <Calendar className="h-3.5 w-3.5 text-gray-400" />
                                <span>Joined {employee.joining_date}</span>
                            </div>
                        </div>
                    </div>
                </div>

                {/* Tabs bar */}
                <div className="flex items-center gap-1 border-t border-gray-100 mt-6 pt-3 overflow-x-auto no-print">
                    {tabs.map((t) => (
                        <button
                            key={t.id}
                            onClick={() => setActiveTab(t.id)}
                            className={`px-3.5 py-1.5 text-xs font-bold rounded-lg transition whitespace-nowrap flex items-center gap-1.5 ${
                                activeTab === t.id
                                    ? 'bg-[#FF5500] text-white shadow-2xs'
                                    : 'text-gray-600 hover:bg-gray-100'
                            }`}
                        >
                            <span>{t.name}</span>
                            {t.count !== undefined && (
                                <span className={`px-1.5 py-0.5 rounded-full text-[10px] font-black leading-none ${
                                    activeTab === t.id ? 'bg-white text-[#FF5500]' : 'bg-orange-100 text-[#FF5500]'
                                }`}>
                                    {t.count}
                                </span>
                            )}
                        </button>
                    ))}
                </div>
            </div>

            {/* Tab Contents */}
            {activeTab === 'personal' && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white p-5 rounded-2xl border border-[#E5E7EB] shadow-xs space-y-3">
                        <h3 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                            Personal & Demographic Information
                        </h3>
                        <div className="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span className="text-gray-400">Gender:</span>
                                <p className="font-bold text-gray-800 capitalize">{employee.gender}</p>
                            </div>
                            <div>
                                <span className="text-gray-400">Date of Birth:</span>
                                <p className="font-bold text-gray-800">{employee.date_of_birth || 'Not recorded'}</p>
                            </div>
                            <div>
                                <span className="text-gray-400">Marital Status:</span>
                                <p className="font-bold text-gray-800">{employee.marital_status || 'Single'}</p>
                            </div>
                            <div>
                                <span className="text-gray-400">Blood Group:</span>
                                <p className="font-bold text-gray-800 font-mono">{employee.blood_group || 'N/A'}</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-[#E5E7EB] shadow-xs space-y-3">
                        <h3 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                            Residential & Emergency Contacts
                        </h3>
                        <div className="space-y-3 text-xs">
                            <div>
                                <span className="text-gray-400">Current Residence:</span>
                                <p className="font-semibold text-gray-800">{employee.current_address || 'Not provided'}</p>
                            </div>
                            <div>
                                <span className="text-gray-400">Permanent Address:</span>
                                <p className="font-semibold text-gray-800">{employee.permanent_address || 'N/A'}</p>
                            </div>
                            <div className="pt-2 border-t border-gray-100">
                                <span className="text-gray-400">Emergency Contact:</span>
                                <p className="font-bold text-gray-900">{employee.emergency_contact_name} ({employee.emergency_contact_relation})</p>
                                <p className="font-mono text-gray-600 mt-0.5">{employee.emergency_contact_phone}</p>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {activeTab === 'employment' && (
                <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div className="bg-white p-5 rounded-2xl border border-[#E5E7EB] shadow-xs space-y-3">
                        <h3 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                            Employment Structure
                        </h3>
                        <div className="grid grid-cols-2 gap-3 text-xs">
                            <div>
                                <span className="text-gray-400">Department:</span>
                                <p className="font-bold text-gray-800">{employee.department?.name}</p>
                            </div>
                            <div>
                                <span className="text-gray-400">Designation:</span>
                                <p className="font-bold text-gray-800">{employee.designation?.title}</p>
                            </div>
                            <div>
                                <span className="text-gray-400">Employment Type:</span>
                                <p className="font-bold text-gray-800 uppercase">{employee.employment_type?.replace('_', ' ')}</p>
                            </div>
                            <div>
                                <span className="text-gray-400">Date of Joining:</span>
                                <p className="font-bold text-gray-800">{employee.joining_date}</p>
                            </div>
                        </div>
                    </div>

                    <div className="bg-white p-5 rounded-2xl border border-[#E5E7EB] shadow-xs space-y-3">
                        <h3 className="text-xs font-bold text-gray-400 uppercase tracking-wider mb-2">
                            Salary & Banking Details
                        </h3>
                        <div className="space-y-3 text-xs">
                            <div>
                                <span className="text-gray-400">Basic Monthly Salary:</span>
                                <p className="font-black text-[#FF5500] text-base font-mono">
                                    {employee.basic_salary === null ? 'Not set' : formatCurrency(employee.basic_salary)}
                                </p>
                            </div>
                            <div className="grid grid-cols-2 gap-3 pt-2 border-t border-gray-100">
                                <div>
                                    <span className="text-gray-400">Bank Name:</span>
                                    <p className="font-bold text-gray-800">{employee.bank_name || 'N/A'}</p>
                                </div>
                                <div>
                                    <span className="text-gray-400">Account Number:</span>
                                    <p className="font-mono font-bold text-gray-800">{employee.bank_account_no || 'N/A'}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {/* CR80 ID Card Preview */}
            {activeTab === 'idcard' && (
                <div className="bg-white p-6 rounded-2xl border border-[#E5E7EB] shadow-xs">
                    <div className="flex items-center justify-between mb-6">
                        <div>
                            <h2 className="text-sm font-bold text-[#202124]">Official CR80 Identity Card</h2>
                            <p className="text-xs text-gray-500">Standard CR80 dimensions (85.60 mm x 53.98 mm) with Front & Back Live Preview</p>
                        </div>
                        <a
                            href={`/employee-id-cards/${employee.id}/pdf`}
                            className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#FF5500] hover:bg-[#E04B00] rounded-lg shadow-sm transition"
                        >
                            <Download className="h-4 w-4" />
                            <span>Download Individual Card (PDF)</span>
                        </a>
                    </div>

                    {/* Interactive CR80 Portrait Visual Preview */}
                    <div className="flex flex-col md:flex-row items-center justify-center gap-8 py-8 bg-[#F7F8FA] rounded-2xl border border-gray-200">
                        {/* Front Side */}
                        <div className="w-[220px] h-[348px] bg-white rounded-xl shadow-lg border border-gray-200 relative overflow-hidden flex flex-col justify-between p-3 select-none">
                            <div className="absolute top-0 left-0 right-0 h-1.5 bg-[#FF5500]" />

                            <div className="pt-2 text-center">
                                <div className="w-10 h-1 mx-auto bg-gray-200 rounded-full border border-dashed border-gray-400 mb-1.5" />
                                {company?.logo_url ? (
                                    <img src={company.logo_url} alt={company.name || 'Company logo'} className="h-6 max-w-[120px] mx-auto object-contain" />
                                ) : (
                                    company?.company_name && <span className="text-xs font-black text-gray-900 tracking-tight block">
                                        {company.company_name}
                                    </span>
                                )}
                                <p className="text-[6.5px] text-gray-400 uppercase font-bold tracking-wider">Employee Identity Badge</p>
                            </div>

                            <div className="text-center my-1">
                                {employee.photo_path ? (
                                    <img
                                        src={`/storage/${employee.photo_path}`}
                                        alt={employee.full_name}
                                        className="h-20 w-16 mx-auto rounded-lg object-cover border-2 border-[#FF5500] shadow-2xs"
                                    />
                                ) : (
                                    <div className="h-20 w-16 mx-auto rounded-lg bg-[#FFF0E8] border border-[#FF5500] text-[#FF5500] font-black flex items-center justify-center text-base">
                                        {employee.first_name.charAt(0)}{employee.last_name.charAt(0)}
                                    </div>
                                )}
                            </div>

                            <div className="text-center space-y-0.5">
                                <p className="text-xs font-black text-gray-900 truncate leading-tight">{employee.full_name}</p>
                                <p className="text-[10px] font-bold text-[#FF5500] truncate">{employee.designation?.title}</p>
                                <span className="inline-block text-[7.5px] font-bold text-gray-600 bg-gray-100 px-2 py-0.5 rounded-full uppercase">
                                    {employee.department?.name}
                                </span>
                            </div>

                            <div className="bg-gray-50 rounded p-1.5 text-[8px] space-y-0.5 border border-gray-100">
                                <div className="flex justify-between">
                                    <span className="text-gray-500 font-bold">ID:</span>
                                    <span className="font-mono font-bold text-gray-800">{employee.employee_code}</span>
                                </div>
                                {employee.blood_group && (
                                    <div className="flex justify-between">
                                        <span className="text-gray-500 font-bold">BLOOD:</span>
                                        <span className="font-bold text-gray-800">{employee.blood_group}</span>
                                    </div>
                                )}
                            </div>

                            {qrCodeUri && (
                                <div className="text-center">
                                    <img src={qrCodeUri} alt="QR" className="h-9 w-9 mx-auto border border-gray-200 rounded p-0.5 bg-white shadow-2xs" />
                                    <span className="text-[6px] text-gray-400 block mt-0.5 uppercase">Scan to Verify</span>
                                </div>
                            )}

                            <div className="bg-[#202124] -mx-3 -mb-3 px-2 py-1 flex items-center justify-between text-[6.5px] text-gray-300">
                                <span>{company?.website || company?.report_footer_text}</span>
                                <span className="text-[#FF5500] font-bold">AUTHORIZED</span>
                            </div>
                        </div>

                        {/* Back Side */}
                        <div className="w-[220px] h-[348px] bg-white rounded-xl shadow-lg border border-gray-200 relative overflow-hidden flex flex-col justify-between p-3 select-none">
                            <div className="absolute top-0 left-0 right-0 h-1.5 bg-[#FF5500]" />

                            <div className="pt-2 text-center">
                                <div className="w-10 h-1 mx-auto bg-gray-200 rounded-full border border-dashed border-gray-400 mb-1.5" />
                                {(company?.trading_name || company?.company_name) && (
                                    <span className="text-[8px] font-black text-gray-900 uppercase block">
                                        Property of {company.trading_name || company.company_name}
                                    </span>
                                )}
                                <span className="text-[6.5px] text-gray-500">Please return if found</span>
                            </div>

                            <div className="bg-gray-50 border border-gray-100 rounded p-2 text-[7px] text-gray-600 leading-tight space-y-1">
                                <p>&bull; Non-transferable company ID badge.</p>
                                <p>&bull; Wear visibly within corporate facilities.</p>
                                <p>&bull; Report lost or damaged cards to HR.</p>
                            </div>

                            <div className="text-center text-[7px] text-gray-600 leading-tight">
                                <p className="font-bold text-gray-800">{company?.name || 'Company'}</p>
                                {companyLocation && <p>{companyLocation}</p>}
                                {company?.phone && <p>Tel: <span className="font-bold">{company.phone}</span></p>}
                                <p>Emergency: <span className="font-bold text-[#FF5500]">{employee.emergency_contact_phone || company?.phone || '—'}</span></p>
                            </div>

                            <div className="text-center pt-1 border-t border-gray-100">
                                <div className="w-16 border-t border-gray-400 mx-auto mb-0.5" />
                                <span className="text-[6.5px] font-bold text-gray-500 uppercase block">Authorized Signatory</span>
                                <span className="text-[6px] text-gray-400 font-mono mt-0.5 block">CR80-PORTRAIT-V1</span>
                            </div>

                            <div className="bg-[#FF5500] -mx-3 -mb-3 px-2 py-1 text-center text-[6.5px] text-white font-bold uppercase tracking-wider">
                                {company?.report_footer_text}
                            </div>
                        </div>
                    </div>
                </div>
            )}

            {activeTab === 'employee_documents' && (
                <div className="space-y-6">
                    {/* Top Notice Banner */}
                    <div className="bg-gradient-to-r from-[#FFF3EB] via-orange-50 to-white p-5 rounded-2xl border border-[#FFD6C0] shadow-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                        <div className="flex items-start gap-3">
                            <div className="p-2.5 bg-[#F36B12] text-white rounded-xl shadow-2xs shrink-0">
                                <FileText className="h-6 w-6" />
                            </div>
                            <div>
                                <div className="flex items-center gap-2 mb-0.5">
                                    <h2 className="text-sm font-black text-gray-900 tracking-tight">Official Employee Physical Documents</h2>
                                    <span className="px-2 py-0.5 text-[10px] font-black uppercase tracking-wider bg-orange-100 text-[#F36B12] rounded-full">
                                        Corporate HR Records
                                    </span>
                                </div>
                                <p className="text-xs text-gray-600 leading-relaxed">
                                    Both documents are dynamically compiled from the employee’s database record into printable A4 PDFs.
                                    They are physically printed, signed by the employee, company authorized representative, and witnesses, then permanently archived.
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-2 shrink-0">
                            <button
                                onClick={() => handleRegenerate('all')}
                                disabled={regeneratingType === 'all'}
                                className="inline-flex items-center gap-1.5 px-3.5 py-2 text-xs font-bold text-white bg-[#F36B12] hover:bg-[#E04B00] rounded-xl shadow-xs transition disabled:opacity-50"
                            >
                                <RefreshCw className={`h-4 w-4 ${regeneratingType === 'all' ? 'animate-spin' : ''}`} />
                                <span>Regenerate Both Documents</span>
                            </button>
                        </div>
                    </div>

                    {/* The 2 Document Cards Grid */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
                        {/* DOCUMENT A: EMPLOYEE REGISTRATION FORM */}
                        {(() => {
                            const doc = generatedDocuments?.find(d => d.document_type === 'registration_form');
                            const statusVariant = {
                                generated: 'warning',
                                printed: 'info',
                                signed: 'success',
                                archived: 'neutral',
                            }[doc?.status || 'generated'] || 'warning';

                            return (
                                <div className="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col justify-between">
                                    <div className="h-2 bg-gradient-to-r from-[#F36B12] to-[#E94B0B]" />
                                    
                                    <div className="p-6 flex-1">
                                        {/* Card Header */}
                                        <div className="flex items-start justify-between gap-3 mb-4">
                                            <div className="flex items-center gap-3">
                                                <div className="h-10 w-10 rounded-xl bg-orange-50 border border-orange-200 flex items-center justify-center text-[#F36B12] font-black shrink-0">
                                                    A
                                                </div>
                                                <div>
                                                    <span className="text-[10px] font-black tracking-wider uppercase text-gray-400 block">DOCUMENT A</span>
                                                    <h3 className="text-base font-black text-gray-900 leading-tight">Employee Registration Form</h3>
                                                </div>
                                            </div>

                                            {doc ? (
                                                <Badge variant={statusVariant}>
                                                    {doc.status?.toUpperCase()?.replace('_', ' ')}
                                                </Badge>
                                            ) : (
                                                <Badge variant="danger">NOT GENERATED</Badge>
                                            )}
                                        </div>

                                        <p className="text-xs text-gray-600 mb-5 leading-relaxed">
                                            Official corporate dossier covering Personal Demographics, Education & Tech Skills, Employment Terms, Assigned Responsibilities, Documents Checklist, Employee Declaration, and HR Verification.
                                        </p>

                                        {doc ? (
                                            <div className="bg-[#F9FAFB] rounded-xl p-4 border border-gray-100 mb-5 space-y-2 text-xs">
                                                <div className="flex items-center justify-between">
                                                    <span className="text-gray-500 font-medium">Document Number:</span>
                                                    <span className="font-mono font-bold text-gray-800">{doc.document_number}</span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-gray-500 font-medium">Template Version:</span>
                                                    <span className="font-semibold text-gray-800">Version {doc.version}</span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-gray-500 font-medium">Generated On:</span>
                                                    <span className="font-semibold text-gray-700">{doc.created_at ? new Date(doc.created_at).toLocaleDateString() : 'Today'}</span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-gray-500 font-medium">PDF File Size:</span>
                                                    <span className="font-mono text-gray-600">{doc.file_size_kb} KB</span>
                                                </div>
                                                <div className="flex items-center justify-between pt-1 border-t border-gray-200">
                                                    <span className="text-gray-500 font-medium">Physical Location:</span>
                                                    <span className="font-semibold text-[#F36B12]">{doc.physical_location || 'Not Recorded Yet'}</span>
                                                </div>
                                                {doc.received_by_hr && (
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-gray-500 font-medium">Received By HR:</span>
                                                        <span className="font-semibold text-gray-800">{doc.received_by_hr}</span>
                                                    </div>
                                                )}
                                            </div>
                                        ) : (
                                            <div className="bg-amber-50 rounded-xl p-4 border border-amber-200 mb-5 text-xs text-amber-800">
                                                This document has not been generated for this employee yet.
                                            </div>
                                        )}

                                        {/* Scanned Copy Backup Alert */}
                                        {doc?.scanned_copy_path && (
                                            <div className="mb-5 bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center justify-between text-xs">
                                                <div className="flex items-center gap-2 text-emerald-800 font-semibold">
                                                    <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                                                    <span>Physical signed copy uploaded</span>
                                                </div>
                                                <a
                                                    href={`/storage/${doc.scanned_copy_path}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="font-bold text-emerald-700 hover:text-emerald-900 underline flex items-center gap-1"
                                                >
                                                    <span>View Copy</span>
                                                    <ExternalLink className="h-3 w-3" />
                                                </a>
                                            </div>
                                        )}
                                    </div>

                                    {/* Card Footer Actions */}
                                    <div className="p-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center gap-2">
                                        {doc ? (
                                            <>
                                                <button
                                                    onClick={() => setPreviewDoc(doc)}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-800 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg shadow-2xs transition"
                                                >
                                                    <Eye className="h-4 w-4 text-[#F36B12]" />
                                                    <span>Preview A4</span>
                                                </button>

                                                <a
                                                    href={`/employee-documents/${doc.id}/download`}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-[#F36B12] bg-[#FFF3EB] border border-[#FFD6C0] hover:bg-orange-100 rounded-lg shadow-2xs transition"
                                                >
                                                    <Download className="h-4 w-4" />
                                                    <span>Download PDF</span>
                                                </a>

                                                <a
                                                    href={`/employee-documents/${doc.id}/preview`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg shadow-2xs transition"
                                                >
                                                    <Printer className="h-4 w-4" />
                                                    <span>Print A4</span>
                                                </a>

                                                <button
                                                    onClick={() => handleOpenStatusModal(doc)}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 rounded-lg shadow-2xs transition"
                                                    title="Edit document report details, text, and physical filing status"
                                                >
                                                    <FolderCheck className="h-4 w-4" />
                                                    <span>Status & Edit Details</span>
                                                </button>

                                                <button
                                                    onClick={() => {
                                                        setUploadModalDoc(doc);
                                                        setScannedFile(null);
                                                    }}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-teal-700 bg-teal-50 border border-teal-200 hover:bg-teal-100 rounded-lg shadow-2xs transition"
                                                >
                                                    <Upload className="h-4 w-4" />
                                                    <span>Upload Signed</span>
                                                </button>

                                                <button
                                                    onClick={() => handleRegenerate('registration_form')}
                                                    disabled={regeneratingType === 'registration_form'}
                                                    title="Regenerate PDF using active template"
                                                    className="ml-auto inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:text-[#F36B12] transition"
                                                >
                                                    <RefreshCw className={`h-3.5 w-3.5 ${regeneratingType === 'registration_form' ? 'animate-spin' : ''}`} />
                                                    <span>Regenerate</span>
                                                </button>
                                            </>
                                        ) : (
                                            <button
                                                onClick={() => handleRegenerate('registration_form')}
                                                className="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#F36B12] hover:bg-[#E04B00] rounded-lg shadow-xs transition"
                                            >
                                                <FileText className="h-4 w-4" />
                                                <span>Generate Registration Form Now</span>
                                            </button>
                                        )}
                                    </div>
                                </div>
                            );
                        })()}

                        {/* DOCUMENT B: EMPLOYMENT BOND AGREEMENT */}
                        {(() => {
                            const doc = generatedDocuments?.find(d => d.document_type === 'employment_bond');
                            const statusVariant = {
                                generated: 'warning',
                                printed: 'info',
                                signed: 'success',
                                archived: 'neutral',
                            }[doc?.status || 'generated'] || 'warning';

                            return (
                                <div className="bg-white rounded-2xl border border-gray-200 shadow-xs overflow-hidden flex flex-col justify-between">
                                    <div className="h-2 bg-gradient-to-r from-[#E94B0B] to-[#F36B12]" />
                                    
                                    <div className="p-6 flex-1">
                                        {/* Card Header */}
                                        <div className="flex items-start justify-between gap-3 mb-4">
                                            <div className="flex items-center gap-3">
                                                <div className="h-10 w-10 rounded-xl bg-orange-50 border border-orange-200 flex items-center justify-center text-[#E94B0B] font-black shrink-0">
                                                    B
                                                </div>
                                                <div>
                                                    <span className="text-[10px] font-black tracking-wider uppercase text-gray-400 block">DOCUMENT B</span>
                                                    <h3 className="text-base font-black text-gray-900 leading-tight">Employment Bond Agreement</h3>
                                                </div>
                                            </div>

                                            {doc ? (
                                                <Badge variant={statusVariant}>
                                                    {doc.status?.toUpperCase()?.replace('_', ' ')}
                                                </Badge>
                                            ) : (
                                                <Badge variant="danger">NOT GENERATED</Badge>
                                            )}
                                        </div>

                                        <p className="text-xs text-gray-600 mb-5 leading-relaxed">
                                            Official binding agreement governed by Nepal Labour Act 2074. Features 12 clauses covering Confidentiality, IP Rights, Company Property, Conflict of Interest, Exit Notice, and Dual Witness Signatures.
                                        </p>

                                        {doc ? (
                                            <div className="bg-[#F9FAFB] rounded-xl p-4 border border-gray-100 mb-5 space-y-2 text-xs">
                                                <div className="flex items-center justify-between">
                                                    <span className="text-gray-500 font-medium">Bond Reference:</span>
                                                    <span className="font-mono font-bold text-gray-800">{doc.document_number}</span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-gray-500 font-medium">Template Version:</span>
                                                    <span className="font-semibold text-gray-800">Version {doc.version}</span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-gray-500 font-medium">Agreement Date:</span>
                                                    <span className="font-semibold text-gray-700">{doc.created_at ? new Date(doc.created_at).toLocaleDateString() : 'Today'}</span>
                                                </div>
                                                <div className="flex items-center justify-between">
                                                    <span className="text-gray-500 font-medium">PDF File Size:</span>
                                                    <span className="font-mono text-gray-600">{doc.file_size_kb} KB</span>
                                                </div>
                                                <div className="flex items-center justify-between pt-1 border-t border-gray-200">
                                                    <span className="text-gray-500 font-medium">Physical Location:</span>
                                                    <span className="font-semibold text-[#E94B0B]">{doc.physical_location || 'Not Recorded Yet'}</span>
                                                </div>
                                                {doc.received_by_hr && (
                                                    <div className="flex items-center justify-between">
                                                        <span className="text-gray-500 font-medium">Received By HR:</span>
                                                        <span className="font-semibold text-gray-800">{doc.received_by_hr}</span>
                                                    </div>
                                                )}
                                            </div>
                                        ) : (
                                            <div className="bg-amber-50 rounded-xl p-4 border border-amber-200 mb-5 text-xs text-amber-800">
                                                This document has not been generated for this employee yet.
                                            </div>
                                        )}

                                        {/* Scanned Copy Backup Alert */}
                                        {doc?.scanned_copy_path && (
                                            <div className="mb-5 bg-emerald-50 border border-emerald-200 rounded-xl p-3 flex items-center justify-between text-xs">
                                                <div className="flex items-center gap-2 text-emerald-800 font-semibold">
                                                    <CheckCircle2 className="h-4 w-4 text-emerald-600 shrink-0" />
                                                    <span>Physical signed copy uploaded</span>
                                                </div>
                                                <a
                                                    href={`/storage/${doc.scanned_copy_path}`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="font-bold text-emerald-700 hover:text-emerald-900 underline flex items-center gap-1"
                                                >
                                                    <span>View Copy</span>
                                                    <ExternalLink className="h-3 w-3" />
                                                </a>
                                            </div>
                                        )}
                                    </div>

                                    {/* Card Footer Actions */}
                                    <div className="p-4 bg-gray-50 border-t border-gray-100 flex flex-wrap items-center gap-2">
                                        {doc ? (
                                            <>
                                                <button
                                                    onClick={() => setPreviewDoc(doc)}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-800 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg shadow-2xs transition"
                                                >
                                                    <Eye className="h-4 w-4 text-[#E94B0B]" />
                                                    <span>Preview A4</span>
                                                </button>

                                                <a
                                                    href={`/employee-documents/${doc.id}/download`}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-[#E94B0B] bg-[#FFF3EB] border border-[#FFD6C0] hover:bg-orange-100 rounded-lg shadow-2xs transition"
                                                >
                                                    <Download className="h-4 w-4" />
                                                    <span>Download PDF</span>
                                                </a>

                                                <a
                                                    href={`/employee-documents/${doc.id}/preview`}
                                                    target="_blank"
                                                    rel="noopener noreferrer"
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-gray-700 bg-white border border-gray-200 hover:bg-gray-100 rounded-lg shadow-2xs transition"
                                                >
                                                    <Printer className="h-4 w-4" />
                                                    <span>Print A4</span>
                                                </a>

                                                <button
                                                    onClick={() => handleOpenStatusModal(doc)}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-indigo-700 bg-indigo-50 border border-indigo-200 hover:bg-indigo-100 rounded-lg shadow-2xs transition"
                                                    title="Edit document report details, text, and physical filing status"
                                                >
                                                    <FolderCheck className="h-4 w-4" />
                                                    <span>Status & Edit Details</span>
                                                </button>

                                                <button
                                                    onClick={() => {
                                                        setUploadModalDoc(doc);
                                                        setScannedFile(null);
                                                    }}
                                                    className="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-bold text-teal-700 bg-teal-50 border border-teal-200 hover:bg-teal-100 rounded-lg shadow-2xs transition"
                                                >
                                                    <Upload className="h-4 w-4" />
                                                    <span>Upload Signed</span>
                                                </button>

                                                <button
                                                    onClick={() => handleRegenerate('employment_bond')}
                                                    disabled={regeneratingType === 'employment_bond'}
                                                    title="Regenerate PDF using active template"
                                                    className="ml-auto inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-semibold text-gray-500 hover:text-[#E94B0B] transition"
                                                >
                                                    <RefreshCw className={`h-3.5 w-3.5 ${regeneratingType === 'employment_bond' ? 'animate-spin' : ''}`} />
                                                    <span>Regenerate</span>
                                                </button>
                                            </>
                                        ) : (
                                            <button
                                                onClick={() => handleRegenerate('employment_bond')}
                                                className="w-full inline-flex items-center justify-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#E94B0B] hover:bg-[#D44108] rounded-lg shadow-xs transition"
                                            >
                                                <FileText className="h-4 w-4" />
                                                <span>Generate Employment Bond Now</span>
                                            </button>
                                        )}
                                    </div>
                                </div>
                            );
                        })()}
                    </div>
                </div>
            )}

            {activeTab === 'attendance' && (
                <div className="bg-white rounded-2xl border border-[#E5E7EB] shadow-xs overflow-hidden">
                    <div className="p-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 className="text-xs font-bold text-gray-900">Recent Attendance Records (Last 30 Days)</h3>
                        <Link href="/attendance" className="text-xs font-semibold text-[#FF5500] hover:underline">
                            Full Attendance Ledger
                        </Link>
                    </div>
                    <table className="min-w-full divide-y divide-gray-200 text-xs">
                        <thead className="bg-[#F7F8FA]">
                            <tr>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Date</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Clock In</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Clock Out</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Hours</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Status</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {attendances.map((att) => (
                                <tr key={att.id}>
                                    <td className="px-5 py-2.5 font-semibold text-gray-900">{att.date}</td>
                                    <td className="px-5 py-2.5 text-gray-600">{att.clock_in || '--'}</td>
                                    <td className="px-5 py-2.5 text-gray-600">{att.clock_out || '--'}</td>
                                    <td className="px-5 py-2.5 font-mono">{att.total_hours}h</td>
                                    <td className="px-5 py-2.5">
                                        <Badge variant={att.status === 'present' ? 'success' : att.status === 'late' ? 'warning' : 'danger'}>
                                            {att.status?.toUpperCase()}
                                        </Badge>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {activeTab === 'payroll' && (
                <div className="bg-white rounded-2xl border border-[#E5E7EB] shadow-xs overflow-hidden">
                    <div className="p-4 border-b border-gray-100 flex items-center justify-between">
                        <h3 className="text-xs font-bold text-gray-900">Historical Salary Slips</h3>
                        <Link href="/payroll" className="text-xs font-semibold text-[#FF5500] hover:underline">
                            Payroll Dashboard
                        </Link>
                    </div>
                    <table className="min-w-full divide-y divide-gray-200 text-xs">
                        <thead className="bg-[#F7F8FA]">
                            <tr>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Slip Number</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Period</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">
                                    Gross{currencyLabel ? ` (${currencyLabel})` : ''}
                                </th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Deductions</th>
                                <th className="px-5 py-2.5 text-left font-bold text-gray-500">Net Paid</th>
                                <th className="px-5 py-2.5 text-right font-bold text-gray-500">Action</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {salaries.map((sal) => (
                                <tr key={sal.id}>
                                    <td className="px-5 py-2.5 font-mono font-bold text-gray-800">{sal.slip_number}</td>
                                    <td className="px-5 py-2.5 font-semibold text-gray-700">{sal.salary_period}</td>
                                    <td className="px-5 py-2.5 font-mono">{Number(sal.gross_salary).toLocaleString()}</td>
                                    <td className="px-5 py-2.5 font-mono text-red-600">- {Number(sal.total_deductions).toLocaleString()}</td>
                                    <td className="px-5 py-2.5 font-mono font-bold text-emerald-600">{formatCurrency(sal.net_salary)}</td>
                                    <td className="px-5 py-2.5 text-right">
                                        <a
                                            href={`/payroll/${sal.id}/pdf`}
                                            className="inline-flex items-center gap-1 text-xs font-bold text-[#FF5500] hover:underline"
                                        >
                                            <Download className="h-3.5 w-3.5" />
                                            <span>PDF Slip</span>
                                        </a>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {/* MODAL 1: Document Preview Modal */}
            <Modal
                isOpen={!!previewDoc}
                onClose={() => setPreviewDoc(null)}
                title={previewDoc?.title || 'Document Preview'}
                maxWidth="max-w-5xl"
            >
                {previewDoc && (
                    <div className="space-y-4">
                        <div className="flex items-center justify-between bg-gray-50 p-3 rounded-xl border border-gray-200 text-xs">
                            <div className="flex items-center gap-3">
                                <span className="font-mono font-bold text-gray-800">{previewDoc.document_number}</span>
                                <Badge variant={previewDoc.status === 'signed' ? 'success' : 'warning'}>
                                    {previewDoc.status?.toUpperCase()}
                                </Badge>
                            </div>
                            <div className="flex items-center gap-2">
                                <a
                                    href={`/employee-documents/${previewDoc.id}/preview`}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-1 px-3 py-1 bg-white border border-gray-200 rounded-lg text-xs font-bold text-gray-700 hover:bg-gray-100"
                                >
                                    <ExternalLink className="h-3.5 w-3.5" />
                                    <span>Open in New Tab</span>
                                </a>
                                <a
                                    href={`/employee-documents/${previewDoc.id}/download`}
                                    className="inline-flex items-center gap-1 px-3 py-1 bg-[#F36B12] text-white rounded-lg text-xs font-bold hover:bg-[#E04B00]"
                                >
                                    <Download className="h-3.5 w-3.5" />
                                    <span>Download PDF</span>
                                </a>
                            </div>
                        </div>

                        <div className="border border-gray-200 rounded-xl overflow-hidden bg-gray-100">
                            <iframe
                                src={`/employee-documents/${previewDoc.id}/preview`}
                                className="w-full h-[70vh]"
                                title="A4 Document Preview"
                            />
                        </div>
                    </div>
                )}
            </Modal>

            {/* MODAL 2: Physical Status & Filing + Edit Document Report Details */}
            <Modal
                isOpen={!!statusModalDoc}
                onClose={() => setStatusModalDoc(null)}
                title={`Edit Document Details & Filing Status: ${statusModalDoc?.title || ''}`}
                maxWidth="max-w-3xl"
            >
                {statusModalDoc && (
                    <form onSubmit={handleSaveStatus} className="space-y-4">
                        {/* Subtab Navigation */}
                        <div className="flex border-b border-gray-200 gap-4 mb-4">
                            <button
                                type="button"
                                onClick={() => setModalSubTab('filing')}
                                className={`pb-2 text-xs font-bold transition border-b-2 ${
                                    modalSubTab === 'filing'
                                        ? 'border-[#F36B12] text-[#F36B12]'
                                        : 'border-transparent text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                1. Physical Filing & Verification Status
                            </button>
                            <button
                                type="button"
                                onClick={() => setModalSubTab('details')}
                                className={`pb-2 text-xs font-bold transition border-b-2 ${
                                    modalSubTab === 'details'
                                        ? 'border-[#F36B12] text-[#F36B12]'
                                        : 'border-transparent text-gray-500 hover:text-gray-700'
                                }`}
                            >
                                2. Edit Report Content & Text Details
                            </button>
                        </div>

                        {/* SUBTAB 1: Physical Filing & Verification */}
                        {modalSubTab === 'filing' && (
                            <div className="space-y-4">
                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 mb-1">Document Status *</label>
                                        <select
                                            value={statusForm.status || 'generated'}
                                            onChange={(e) => setStatusForm({ ...statusForm, status: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg p-2 text-xs font-medium focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                                        >
                                            <option value="generated">Generated (Initial draft created)</option>
                                            <option value="printed">Printed (Physical A4 document printed for signing)</option>
                                            <option value="signed">Physically Signed (Signed by Employee & Company)</option>
                                            <option value="archived">Archived (Stored in physical office filing cabinet)</option>
                                        </select>
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 mb-1">
                                            Physical File / Cabinet Reference Location
                                        </label>
                                        <input
                                            type="text"
                                            placeholder="e.g. Cabinet A, Shelf 2, Folder GSK-0004"
                                            value={statusForm.physical_location || ''}
                                            onChange={(e) => setStatusForm({ ...statusForm, physical_location: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg p-2 text-xs font-medium focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 mb-1">
                                            Received / Verified by HR Officer
                                        </label>
                                        <input
                                            type="text"
                                            placeholder="e.g. Sabin Shrestha"
                                            value={statusForm.received_by_hr || ''}
                                            onChange={(e) => setStatusForm({ ...statusForm, received_by_hr: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg p-2 text-xs font-medium focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                                        />
                                    </div>

                                    <div>
                                        <label className="block text-xs font-bold text-gray-700 mb-1">
                                            HR Representative Signatory Name
                                        </label>
                                        <input
                                            type="text"
                                            placeholder="e.g. Sabin Shrestha"
                                            value={statusForm.hr_representative_name || ''}
                                            onChange={(e) => setStatusForm({ ...statusForm, hr_representative_name: e.target.value })}
                                            className="w-full border border-gray-300 rounded-lg p-2 text-xs font-medium focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <label className="block text-xs font-bold text-gray-700 mb-1">HR Verification & Filing Remarks</label>
                                    <textarea
                                        rows={3}
                                        placeholder="e.g. Verified citizenship copy and physical signatures in order. Stored in employee permanent file."
                                        value={statusForm.hr_remarks || ''}
                                        onChange={(e) => setStatusForm({ ...statusForm, hr_remarks: e.target.value })}
                                        className="w-full border border-gray-300 rounded-lg p-2 text-xs font-medium focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                                    />
                                </div>
                            </div>
                        )}

                        {/* SUBTAB 2: Edit Report Content & Text Details */}
                        {modalSubTab === 'details' && (
                            <div className="space-y-4 max-h-[60vh] overflow-y-auto pr-2">
                                <div className="bg-orange-50 border border-orange-200 rounded-lg p-2.5 text-[11px] text-[#C2410C]">
                                    <strong>Edit Report Content:</strong> You can edit any detail or text shown in this document. Saving will immediately update the database and regenerate the physical A4 PDF.
                                </div>

                                {/* Personal Information */}
                                <div>
                                    <h4 className="text-xs font-black uppercase text-[#F36B12] tracking-wider mb-2">A. Personal Information</h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Full Legal Name</label>
                                            <input
                                                type="text"
                                                value={statusForm.full_name || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, full_name: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Date of Birth</label>
                                            <input
                                                type="text"
                                                placeholder="DD-MM-YYYY"
                                                value={statusForm.date_of_birth || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, date_of_birth: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Gender</label>
                                            <input
                                                type="text"
                                                value={statusForm.gender || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, gender: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Citizenship / ID No.</label>
                                            <input
                                                type="text"
                                                value={statusForm.citizenship_number || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, citizenship_number: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Contact Phone</label>
                                            <input
                                                type="text"
                                                value={statusForm.phone || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, phone: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Email Address</label>
                                            <input
                                                type="email"
                                                value={statusForm.email || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, email: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Permanent Address</label>
                                            <input
                                                type="text"
                                                value={statusForm.permanent_address || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, permanent_address: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Current Address</label>
                                            <input
                                                type="text"
                                                value={statusForm.current_address || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, current_address: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>

                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Emergency Contact Name</label>
                                            <input
                                                type="text"
                                                value={statusForm.emergency_contact_name || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, emergency_contact_name: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Emergency Phone</label>
                                            <input
                                                type="text"
                                                value={statusForm.emergency_contact_phone || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, emergency_contact_phone: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Relationship</label>
                                            <input
                                                type="text"
                                                value={statusForm.emergency_contact_relation || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, emergency_contact_relation: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Educational & Professional */}
                                <div className="pt-3 border-t border-gray-100">
                                    <h4 className="text-xs font-black uppercase text-[#F36B12] tracking-wider mb-2">B. Educational & Professional</h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Highest Qualification</label>
                                            <input
                                                type="text"
                                                placeholder="e.g. Bachelor in Computer Engineering"
                                                value={statusForm.highest_qualification || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, highest_qualification: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Institution</label>
                                            <input
                                                type="text"
                                                placeholder="e.g. Kathmandu University"
                                                value={statusForm.institution || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, institution: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Technical Skills</label>
                                            <input
                                                type="text"
                                                placeholder="e.g. React, Laravel, Python, MySQL"
                                                value={statusForm.technical_skills || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, technical_skills: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Previous Experience</label>
                                            <input
                                                type="text"
                                                placeholder="e.g. 2 Years"
                                                value={statusForm.previous_experience || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, previous_experience: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Previous Organization</label>
                                            <input
                                                type="text"
                                                value={statusForm.previous_organization || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, previous_organization: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Certifications</label>
                                            <input
                                                type="text"
                                                value={statusForm.certifications || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, certifications: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Portfolio / GitHub</label>
                                            <input
                                                type="text"
                                                value={statusForm.portfolio || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, portfolio: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Employment Terms */}
                                <div className="pt-3 border-t border-gray-100">
                                    <h4 className="text-xs font-black uppercase text-[#F36B12] tracking-wider mb-2">C. Employment Terms & Compensation</h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Department</label>
                                            <input
                                                type="text"
                                                value={statusForm.department || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, department: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Designation</label>
                                            <input
                                                type="text"
                                                value={statusForm.designation || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, designation: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Employment Type</label>
                                            <input
                                                type="text"
                                                value={statusForm.employment_type || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, employment_type: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Probation Period</label>
                                            <input
                                                type="text"
                                                value={statusForm.probation_period || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, probation_period: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Reporting Manager</label>
                                            <input
                                                type="text"
                                                value={statusForm.reporting_manager || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, reporting_manager: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Work Location</label>
                                            <input
                                                type="text"
                                                value={statusForm.work_location || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, work_location: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Working Schedule</label>
                                            <input
                                                type="text"
                                                placeholder="9 AM – 6 PM"
                                                value={statusForm.working_schedule || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, working_schedule: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Monthly Salary</label>
                                            <input
                                                type="text"
                                                placeholder={`${currencyLabel} 50,000`.trim()}
                                                value={statusForm.salary || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, salary: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Payment Method</label>
                                            <input
                                                type="text"
                                                value={statusForm.payment_method || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, payment_method: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Roles & Responsibilities */}
                                <div className="pt-3 border-t border-gray-100">
                                    <h4 className="text-xs font-black uppercase text-[#F36B12] tracking-wider mb-2">D. Assigned Roles & Responsibilities</h4>
                                    <div>
                                        <label className="block text-[11px] font-bold text-gray-600 mb-1">Primary Responsibilities</label>
                                        <textarea
                                            rows={2}
                                            value={statusForm.primary_responsibilities || ''}
                                            onChange={(e) => setStatusForm({ ...statusForm, primary_responsibilities: e.target.value })}
                                            className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                        />
                                    </div>
                                    <div className="mt-2">
                                        <label className="block text-[11px] font-bold text-gray-600 mb-1">Secondary Responsibilities</label>
                                        <textarea
                                            rows={2}
                                            value={statusForm.secondary_responsibilities || ''}
                                            onChange={(e) => setStatusForm({ ...statusForm, secondary_responsibilities: e.target.value })}
                                            className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                        />
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Assigned Projects</label>
                                            <input
                                                type="text"
                                                value={statusForm.assigned_projects || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, assigned_projects: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Performance Targets</label>
                                            <input
                                                type="text"
                                                value={statusForm.performance_targets || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, performance_targets: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                    <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 mt-2">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Reporting Requirements</label>
                                            <input
                                                type="text"
                                                value={statusForm.reporting_requirements || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, reporting_requirements: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Authorized System Access</label>
                                            <input
                                                type="text"
                                                value={statusForm.authorized_system_access || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, authorized_system_access: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                </div>

                                {/* Bond / Agreement Specific Fields */}
                                <div className="pt-3 border-t border-gray-100">
                                    <h4 className="text-xs font-black uppercase text-[#F36B12] tracking-wider mb-2">E. Agreement & Signatory Representative</h4>
                                    <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Authorized Representative</label>
                                            <input
                                                type="text"
                                                value={statusForm.authorized_representative || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, authorized_representative: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Agreement / Bond Date</label>
                                            <input
                                                type="text"
                                                placeholder="DD-MM-YYYY"
                                                value={statusForm.agreement_date || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, agreement_date: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                        <div>
                                            <label className="block text-[11px] font-bold text-gray-600 mb-1">Registration Date</label>
                                            <input
                                                type="text"
                                                placeholder="DD-MM-YYYY"
                                                value={statusForm.registration_date || ''}
                                                onChange={(e) => setStatusForm({ ...statusForm, registration_date: e.target.value })}
                                                className="w-full border border-gray-300 rounded p-1.5 text-xs font-medium"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        )}

                        <div className="pt-3 border-t border-gray-100 flex items-center justify-between gap-2">
                            <div className="text-[11px] text-gray-400">
                                Changes will be saved to physical records and regenerate the official A4 PDF immediately.
                            </div>
                            <div className="flex items-center gap-2">
                                <button
                                    type="button"
                                    onClick={() => setStatusModalDoc(null)}
                                    className="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition"
                                >
                                    Cancel
                                </button>
                                <button
                                    type="submit"
                                    disabled={isSavingDetails}
                                    className="inline-flex items-center gap-1.5 px-4 py-2 text-xs font-bold text-white bg-[#F36B12] hover:bg-[#E04B00] rounded-lg shadow-xs transition disabled:opacity-50"
                                >
                                    {isSavingDetails && <RefreshCw className="h-3.5 w-3.5 animate-spin" />}
                                    <span>Save & Regenerate PDF</span>
                                </button>
                            </div>
                        </div>
                    </form>
                )}
            </Modal>

            {/* MODAL 3: Upload Scanned Physically Signed Copy Modal */}
            <Modal
                isOpen={!!uploadModalDoc}
                onClose={() => setUploadModalDoc(null)}
                title={`Upload Scanned Signed Copy: ${uploadModalDoc?.title || ''}`}
                maxWidth="max-w-md"
            >
                {uploadModalDoc && (
                    <form onSubmit={handleUploadScannedCopy} className="space-y-4">
                        <div className="p-3 bg-amber-50 rounded-xl border border-amber-200 text-xs text-amber-800 leading-relaxed">
                            Upload a scanned PDF or photograph of the physically signed original document for digital backup and audit compliance.
                        </div>

                        <div>
                            <label className="block text-xs font-bold text-gray-700 mb-1">Select Scanned File (PDF, JPG, PNG) *</label>
                            <input
                                type="file"
                                accept=".pdf,image/*"
                                onChange={(e) => setScannedFile(e.target.files[0])}
                                className="w-full border border-gray-300 rounded-lg p-2 text-xs focus:ring-2 focus:ring-[#F36B12] focus:border-[#F36B12]"
                                required
                            />
                            {scannedFile && (
                                <p className="text-[11px] text-gray-500 mt-1 font-mono">
                                    Selected: {scannedFile.name} ({(scannedFile.size / 1024).toFixed(1)} KB)
                                </p>
                            )}
                        </div>

                        <div className="pt-3 border-t border-gray-100 flex items-center justify-end gap-2">
                            <button
                                type="button"
                                onClick={() => setUploadModalDoc(null)}
                                className="px-4 py-2 text-xs font-semibold text-gray-600 hover:bg-gray-100 rounded-lg transition"
                            >
                                Cancel
                            </button>
                            <button
                                type="submit"
                                disabled={isUploading || !scannedFile}
                                className="px-4 py-2 text-xs font-bold text-white bg-teal-600 hover:bg-teal-700 rounded-lg shadow-xs transition disabled:opacity-50"
                            >
                                {isUploading ? 'Uploading...' : 'Upload Scanned Copy'}
                            </button>
                        </div>
                    </form>
                )}
            </Modal>
        </AdminLayout>
    );
}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $docNumber }}</title>
    <style>
        @page {
            size: A4 portrait;
            margin: 5mm 8mm 5mm 8mm;
        }
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 7pt;
            color: #1F2937;
            line-height: 1.18;
            background: #ffffff;
            position: relative;
        }

        /* ─── WATERMARK ─── */
        .wm {
            position: fixed;
            top: 230px; left: 100px;
            width: 370px;
            z-index: -1000;
        }

        /* ─── HEADER ─── */
        .hdr-tbl {
            width: 100%;
            border-collapse: collapse;
            border-spacing: 0;
            height: 72px;
        }
        .hdr-tbl td { border: 0; padding: 0; }
        .hdr-left {
            background: #ffffff;
            padding: 7px 10px;
            vertical-align: middle;
            height: 72px;
        }
        .hdr-tri {
            width: 30px;
            padding: 0;
            vertical-align: top;
            background: #F36B12;
            height: 72px;
        }
        .hdr-right {
            background: #F36B12;
            padding: 10px 14px 10px 3px;
            vertical-align: middle;
            text-align: right;
            height: 72px;
            width: 38%;
        }

        /* ─── BRAND ─── */
        .brand-tbl { border-collapse: collapse; border-spacing: 0; }
        .brand-tbl td { padding: 0; border: 0; vertical-align: middle; }
        .co-name {
            font-size: 17pt;
            font-weight: bold;
            color: #1A1A1A;
            line-height: 1.0;
            letter-spacing: 0.5px;
            display: block;
        }
        .co-tag {
            font-size: 6.5pt;
            color: #F36B12;
            font-weight: bold;
            letter-spacing: 1.2px;
            margin-top: 2px;
            display: block;
        }
        .slogan {
            color: #ffffff;
            font-style: italic;
            font-size: 8pt;
            line-height: 1.35;
        }
        .slogan-bold {
            font-weight: bold;
            font-size: 9.5pt;
            font-style: normal;
        }

        /* ─── TITLE ─── */
        .title-bar {
            background: #F36B12;
            text-align: center;
            padding: 5px 0 3px 0;
            margin-bottom: 1px;
        }
        .doc-title {
            font-size: 13pt;
            font-weight: bold;
            color: #ffffff;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .doc-sub {
            font-size: 6.8pt;
            color: rgba(255,255,255,0.92);
            font-style: italic;
            margin-top: 1px;
        }

        /* ─── ID BAR ─── */
        .id-bar {
            background: #FFF3E8;
            border: 0.8pt solid #FBCBA8;
            border-radius: 3px;
            margin-bottom: 2.5px;
        }
        .id-bar td {
            padding: 3px 8px;
            font-size: 7.5pt;
            font-weight: bold;
            vertical-align: middle;
            border: 0;
        }

        /* ─── SECTION HEADER ─── */
        .sec-hdr {
            background: #F36B12;
            color: #ffffff;
            font-size: 7.3pt;
            font-weight: bold;
            padding: 2.5px 8px;
            margin-top: 3px;
            margin-bottom: 1px;
            letter-spacing: 0.2px;
        }
        .sec-badge {
            display: inline-block;
            width: 13px;
            height: 13px;
            background: #ffffff;
            color: #F36B12;
            border-radius: 50%;
            text-align: center;
            line-height: 13px;
            font-size: 6.5pt;
            font-weight: bold;
            margin-right: 4px;
            vertical-align: middle;
        }

        /* ─── DATA TABLE ─── */
        .dt { width: 100%; border-collapse: collapse; border-spacing: 0; }
        .dt td {
            padding: 1.1px 2px;
            font-size: 6.9pt;
            vertical-align: top;
            border: 0;
        }
        .dt tr { border-bottom: 0.4pt solid #F3F4F6; }
        .lbl { font-weight: bold; color: #374151; white-space: nowrap; width: 128px; }
        .sep { width: 7px; color: #9CA3AF; text-align: center; }
        .val { color: #111827; }

        /* ─── PHOTO FRAME ─── */
        .photo-frame {
            width: 70px;
            height: 84px;
            border: 1pt solid #D1D5DB;
            border-radius: 3px;
            background: #F9FAFB;
            overflow: hidden;
            text-align: center;
            vertical-align: middle;
            margin: 0 auto;
        }
        .photo-ph {
            font-size: 6pt;
            color: #9CA3AF;
            line-height: 84px;
        }

        /* ─── CHECKBOX ─── */
        .chk {
            display: inline-block;
            width: 7px; height: 7px;
            border: 0.8pt solid #6B7280;
            margin-right: 3px;
            vertical-align: middle;
        }
        .ci { font-size: 6.5pt; color: #374151; line-height: 1.6; display: block; }

        /* ─── PANEL BOX ─── */
        .panel {
            border: 0.5pt solid #E5E7EB;
            border-radius: 3px;
            padding: 3px 5px;
            background: #FAFAFA;
        }
        .decl-text {
            font-size: 6.5pt;
            font-style: italic;
            color: #374151;
            line-height: 1.25;
            margin-bottom: 2.5px;
        }

        /* ─── SIG LINE ─── */
        .sig-line {
            display: inline-block;
            border-bottom: 0.8pt solid #374151;
            width: 100px; height: 9px;
            vertical-align: bottom;
        }

        /* ─── FOOTER ─── */
        .footer {
            background: #2B2B2B;
            color: #ffffff;
            padding: 4px 10px;
            border-radius: 3px;
            margin-top: 3px;
            font-size: 6.5pt;
        }
        .footer td { color: #ffffff; border: 0; padding: 0 2px; vertical-align: middle; }
    </style>
</head>
<body>
@php
    $header = $reportSettings['header'];
    $footer = $reportSettings['footer'];
    $footerContacts = array_filter([
        $footer['phone'],
        $footer['email'],
        $footer['website'],
    ], fn ($value) => filled($value));
@endphp

@if(!empty($watermarkBase64))
<div class="wm"><img src="{{ $watermarkBase64 }}" style="width:370px; opacity:0.08;" alt="Company watermark"></div>
@elseif(!empty($logoBase64))
<div class="wm"><img src="{{ $logoBase64 }}" style="width:370px; opacity:0.06;" alt="Company watermark"></div>
@endif

{{-- ═══════════ HEADER ═══════════ --}}
<table class="hdr-tbl">
    <tr>
        {{-- Left: Logo --}}
        <td class="hdr-left">
            <table class="brand-tbl">
                <tr>
            @if(!empty($logoBase64))
                    <td style="width:50px; padding-right:7px; vertical-align:middle;">
                <img src="{{ $logoBase64 }}" style="max-width:46px; max-height:46px; object-fit:contain; display:block;" alt="{{ $header['company_name'] ?: 'Company logo' }}">
                    </td>
                    @endif
                    <td style="vertical-align:middle;">
                @if($header['company_name'])
                    <span class="co-name">{{ $header['company_name'] }}</span>
                @endif
                @if($header['trading_name'])
                    <span class="co-tag">{{ $header['trading_name'] }}</span>
                @endif
                @if($header['registration_no'] || $header['pan_vat_no'])
                    <span class="co-tag">
                        @if($header['registration_no']){{ $header['registration_no'] }}@endif
                        @if($header['registration_no'] && $header['pan_vat_no']) &bull; @endif
                        @if($header['pan_vat_no'])PAN/VAT: {{ $header['pan_vat_no'] }}@endif
                    </span>
                @endif
                    </td>
                </tr>
            </table>
        </td>

        {{-- Triangle diagonal --}}
        <td class="hdr-tri">
            <div style="width:0;height:0;border-top:72px solid #ffffff;border-right:30px solid transparent;"></div>
        </td>

        {{-- Right: Orange + tagline --}}
        <td class="hdr-right">
            <div class="slogan">
                @if($header['address']){{ $header['address'] }}<br>@endif
                @if($header['phone']){{ $header['phone'] }}<br>@endif
                @if($header['email']){{ $header['email'] }}<br>@endif
                @if($header['website']){{ $header['website'] }}@endif
            </div>
        </td>
    </tr>
</table>

{{-- ═══════════ TITLE ═══════════ --}}
<div class="title-bar">
    <div class="doc-title">EMPLOYEE REGISTRATION FORM</div>
    <div class="doc-sub">Confidential &ndash; Human Resources Department</div>
</div>

{{-- ═══════════ ID BAR ═══════════ --}}
<table class="id-bar dt">
    <tr>
        <td style="width:50%; color:#E94B0B;">
            &#9998;&nbsp;<span style="color:#374151;">Employee ID:</span>&nbsp;
            <span style="color:#111827; font-size:7.5pt;">{{ $v['employee_id'] ?? $employee->employee_code }}</span>
        </td>
        <td style="width:50%; text-align:right; color:#E94B0B;">
            &#128197;&nbsp;<span style="color:#374151;">Registration Date:</span>&nbsp;
            <span style="color:#111827; font-size:7.5pt;">{{ $v['employee_registration_date'] ?? date('d-m-Y') }}</span>
        </td>
    </tr>
</table>

{{-- ═══════════ SECTION A: Personal Information ═══════════ --}}
<div class="sec-hdr"><span class="sec-badge">A</span> Personal Information</div>
<table class="dt">
    <tr>
        {{-- Fields --}}
        <td style="width:79%; vertical-align:top;">
            <table class="dt">
                <tr>
                    <td class="lbl">Full Legal Name</td><td class="sep">:</td>
                    <td class="val" colspan="4"><strong>{{ $v['employee_full_name'] ?? '' }}</strong></td>
                </tr>
                <tr>
                    <td class="lbl">Date of Birth</td><td class="sep">:</td>
                    <td class="val" style="width:105px;">{{ $v['employee_date_of_birth'] ?? '' }}</td>
                    <td class="lbl" style="width:48px; padding-left:4px;">Gender</td><td class="sep">:</td>
                    <td class="val">{{ $v['employee_gender'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Citizenship / ID No.</td><td class="sep">:</td>
                    <td class="val" colspan="4">{{ $v['employee_citizenship_number'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Permanent Address</td><td class="sep">:</td>
                    <td class="val" colspan="4">{{ $v['employee_permanent_address'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Current Address</td><td class="sep">:</td>
                    <td class="val" colspan="4">{{ $v['employee_current_address'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Contact Number</td><td class="sep">:</td>
                    <td class="val" style="width:105px;">{{ $v['employee_phone'] ?? '' }}</td>
                    <td class="lbl" style="padding-left:4px;">Email Address</td><td class="sep">:</td>
                    <td class="val">{{ $v['employee_email'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Emergency Contact Name</td><td class="sep">:</td>
                    <td class="val" colspan="4">{{ $v['employee_emergency_contact_name'] ?? '' }}</td>
                </tr>
                <tr>
                    <td class="lbl">Emergency Contact No.</td><td class="sep">:</td>
                    <td class="val" style="width:105px;">{{ $v['employee_emergency_contact_phone'] ?? '' }}</td>
                    <td class="lbl" style="padding-left:4px;">Relationship</td><td class="sep">:</td>
                    <td class="val">{{ $v['employee_emergency_contact_relation'] ?? '' }}</td>
                </tr>
            </table>
        </td>
        {{-- Photo --}}
        <td style="width:21%; vertical-align:middle; text-align:center; padding-left:4px;">
            <div class="photo-frame">
                @if($employee->photo_path && file_exists(storage_path('app/public/' . $employee->photo_path)))
                    <img src="{{ storage_path('app/public/' . $employee->photo_path) }}"
                         style="width:70px;height:84px;object-fit:cover;display:block;">
                @else
                    <div class="photo-ph">Affix<br>Photo</div>
                @endif
            </div>
        </td>
    </tr>
</table>

{{-- ═══════════ SECTION B: Education ═══════════ --}}
<div class="sec-hdr"><span class="sec-badge">B</span> Educational &amp; Professional Information</div>
<table class="dt">
    <tr><td class="lbl" style="width:138px;">Highest Qualification</td><td class="sep">:</td><td class="val">{{ $v['employee_highest_qualification'] ?? 'Not Provided' }}</td></tr>
    <tr><td class="lbl">Institution</td><td class="sep">:</td><td class="val">{{ $v['employee_institution'] ?? 'Not Provided' }}</td></tr>
    <tr><td class="lbl">Technical Skills</td><td class="sep">:</td><td class="val">{{ $v['employee_technical_skills'] ?? 'Not Provided' }}</td></tr>
    <tr><td class="lbl">Previous Work Experience</td><td class="sep">:</td><td class="val">{{ $v['employee_previous_experience'] ?? 'Not Provided' }}</td></tr>
    <tr><td class="lbl">Previous Organization</td><td class="sep">:</td><td class="val">{{ $v['employee_previous_organization'] ?? 'Not Provided' }}</td></tr>
    <tr><td class="lbl">Relevant Certifications</td><td class="sep">:</td><td class="val">{{ $v['employee_certifications'] ?? 'Not Provided' }}</td></tr>
    <tr><td class="lbl">Portfolio / GitHub / LinkedIn</td><td class="sep">:</td><td class="val" style="color:#2563EB;">{{ $v['employee_portfolio'] ?? 'Not Provided' }}</td></tr>
</table>

{{-- ═══════════ SECTION C: Employment Info ═══════════ --}}
<div class="sec-hdr"><span class="sec-badge">C</span> Employment Information</div>
<table class="dt">
    <tr>
        <td style="width:50%; vertical-align:top; padding-right:5px;">
            <table class="dt">
                <tr><td class="lbl" style="width:118px;">Department</td><td class="sep">:</td><td class="val">{{ $v['employee_department'] ?? 'General' }}</td></tr>
                <tr><td class="lbl">Designation</td><td class="sep">:</td><td class="val"><strong>{{ $v['employee_designation'] ?? 'Not Assigned' }}</strong></td></tr>
                <tr><td class="lbl">Employee Type</td><td class="sep">:</td><td class="val">{{ $v['employee_employment_type'] ?? 'Full-time' }}</td></tr>
                <tr><td class="lbl">Employment Start Date</td><td class="sep">:</td><td class="val">{{ $v['employee_joining_date'] ?? '' }}</td></tr>
                <tr><td class="lbl">Probation Period</td><td class="sep">:</td><td class="val">{{ $v['employee_probation_period'] ?? '3 Months' }}</td></tr>
            </table>
        </td>
        <td style="width:50%; vertical-align:top; padding-left:5px; border-left:0.5pt dashed #E5E7EB;">
            <table class="dt">
                <tr><td class="lbl" style="width:112px;">Reporting Manager</td><td class="sep">:</td><td class="val">{{ $v['employee_reporting_manager'] ?? '' }}</td></tr>
                <tr><td class="lbl">Work Location</td><td class="sep">:</td><td class="val">{{ $v['employee_work_location'] ?? '' }}</td></tr>
                <tr><td class="lbl">Working Schedule</td><td class="sep">:</td><td class="val">{{ $v['employee_working_schedule'] ?? '9 AM – 6 PM' }}</td></tr>
                <tr><td class="lbl">Monthly Salary</td><td class="sep">:</td><td class="val"><strong>{{ $v['employee_salary'] ?? '' }}</strong></td></tr>
                <tr><td class="lbl">Payment Method</td><td class="sep">:</td><td class="val">{{ $v['employee_payment_method'] ?? 'Bank Transfer' }}</td></tr>
            </table>
        </td>
    </tr>
</table>

{{-- ═══════════ SECTION D: Roles & Responsibilities ═══════════ --}}
<div class="sec-hdr"><span class="sec-badge">D</span> Assigned Company Role &amp; Responsibilities</div>
<table class="dt">
    <tr><td class="lbl" style="width:138px;">Primary Responsibilities</td><td class="sep">:</td><td class="val">{{ $v['employee_primary_responsibilities'] ?? 'As assigned by Department Head' }}</td></tr>
    <tr><td class="lbl">Secondary Responsibilities</td><td class="sep">:</td><td class="val">{{ $v['employee_secondary_responsibilities'] ?? 'Adhere to company policies and assist team deliverables' }}</td></tr>
    <tr><td class="lbl">Assigned Projects</td><td class="sep">:</td><td class="val">{{ $v['employee_assigned_projects'] ?? 'Internal &amp; Client Systems' }}</td></tr>
    <tr><td class="lbl">Performance Targets</td><td class="sep">:</td><td class="val">{{ $v['employee_performance_targets'] ?? 'Meet deliverables according to project timelines' }}</td></tr>
    <tr><td class="lbl">Reporting Requirements</td><td class="sep">:</td><td class="val">{{ $v['employee_reporting_requirements'] ?? 'Regular progress reports to supervisor' }}</td></tr>
    <tr><td class="lbl">Authorized System Access</td><td class="sep">:</td><td class="val">{{ $v['employee_authorized_system_access'] ?? 'Assigned company tools &amp; workspace access' }}</td></tr>
    <tr><td class="lbl">Department Head</td><td class="sep">:</td><td class="val">{{ $v['employee_department_head'] ?? '' }}</td></tr>
</table>

{{-- ═══════════ SECTION E + F ═══════════ --}}
<table style="width:100%; border-collapse:collapse; border-spacing:0; margin-top:3px;">
    <tr>
        {{-- E: Documents Checklist --}}
        <td style="width:44%; vertical-align:top; padding-right:3px;">
            <div class="sec-hdr" style="margin-top:0;"><span class="sec-badge">E</span> Documents Checklist</div>
            <div class="panel">
                <table style="width:100%; border-collapse:collapse; font-size:6.5pt;">
                    <tr>
                        <td style="width:50%; vertical-align:top;">
                            <span class="ci"><span class="chk"></span>Citizenship / Valid ID Copy</span>
                            <span class="ci"><span class="chk"></span>Passport-size Photograph</span>
                            <span class="ci"><span class="chk"></span>CV / Resume</span>
                            <span class="ci"><span class="chk"></span>Educational Certificates</span>
                            <span class="ci"><span class="chk"></span>Other Relevant Documents</span>
                        </td>
                        <td style="width:50%; vertical-align:top;">
                            <span class="ci"><span class="chk"></span>Experience Certificate</span>
                            <span class="ci"><span class="chk"></span>Signed Employment Agreement</span>
                            <span class="ci"><span class="chk"></span>Signed Confidentiality Agr.</span>
                            <span class="ci"><span class="chk"></span>Signed Employment Bond</span>
                        </td>
                    </tr>
                </table>
            </div>
        </td>

        {{-- F: Employee Declaration --}}
        <td style="width:56%; vertical-align:top; padding-left:3px;">
            <div class="sec-hdr" style="margin-top:0;"><span class="sec-badge">F</span> Employee Declaration</div>
            <div class="panel">
                <div class="decl-text">
                    &ldquo;I hereby declare that the information provided above is true and accurate. I agree to comply with company policies, professional standards, assigned responsibilities, confidentiality requirements and lawful instructions.&rdquo;
                </div>
                <table style="width:100%; border-collapse:collapse; font-size:7pt;">
                    <tr>
                        <td style="width:88px; font-weight:bold; color:#374151; padding:1px 0;">Employee Name:</td>
                        <td style="padding:1px 0;"><strong>{{ $v['employee_full_name'] ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color:#374151; padding:2px 0;">Signature:</td>
                        <td style="padding:2px 0;"><span class="sig-line"></span></td>
                    </tr>
                    <tr>
                        <td style="font-weight:bold; color:#374151; padding:1px 0;">Date:</td>
                        <td style="padding:1px 0;">{{ $v['employee_registration_date'] ?? date('d-m-Y') }}</td>
                    </tr>
                </table>
            </div>
        </td>
    </tr>
</table>

{{-- ═══════════ SECTION G: HR Verification ═══════════ --}}
<div class="sec-hdr" style="margin-top:3px;"><span class="sec-badge">G</span> HR Verification</div>
<div class="panel">
    <table style="width:100%; border-collapse:collapse; font-size:6.5pt;">
        <tr>
            <td style="width:25%; vertical-align:top;">
                <span class="ci"><span class="chk"></span>Documents Verified</span>
                <span class="ci"><span class="chk"></span>Employee ID Generated</span>
            </td>
            <td style="width:25%; vertical-align:top;">
                <span class="ci"><span class="chk"></span>Employment Agr. Signed</span>
                <span class="ci"><span class="chk"></span>Bond Signed</span>
            </td>
            <td style="width:22%; vertical-align:top;">
                <span class="ci"><span class="chk"></span>Company ID Issued</span>
                <span class="ci"><span class="chk"></span>System Access Approved</span>
            </td>
            <td style="width:28%; vertical-align:middle; padding-left:7px; border-left:0.5pt dashed #D1D5DB;">
                <table style="width:100%; border-collapse:collapse; font-size:6.5pt;">
                    <tr>
                        <td style="width:72px; color:#4B5563; padding:1px 0;">HR Representative:</td>
                        <td style="padding:1px 0;"><strong>{{ $v['hr_representative_name'] ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td style="color:#4B5563; padding:2px 0;">HR Signature:</td>
                        <td><span class="sig-line" style="width:62px;"></span></td>
                    </tr>
                    <tr>
                        <td style="color:#4B5563; padding:2px 0;">Auth. Signature:</td>
                        <td><span class="sig-line" style="width:62px;"></span></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

{{-- ═══════════ FOOTER ═══════════ --}}
<div class="footer">
    <table style="width:100%; border-collapse:collapse;">
        <tr>
            <td style="width:65%;">
                {{ $header['address'] }}
                @if($footerContacts)&bull; {{ implode(' &bull; ', $footerContacts) }}@endif
            </td>
            <td style="width:15%; text-align:right; font-weight:bold;">
                @if($footer['text']){{ $footer['text'] }}@endif
            </td>
        </tr>
    </table>
</div>

</body>
</html>

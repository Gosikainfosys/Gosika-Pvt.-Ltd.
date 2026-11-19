<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $docNumber }} - {{ $template->name }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 8mm 10mm 8mm 10mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 8.2pt;
            color: {{ $design['text_color'] ?? '#202124' }};
            line-height: 1.35;
            background-color: #ffffff;
            position: relative;
        }

        /* Fluid top wave effect */
        .top-wave {
            width: 100%;
            height: 18px;
            background: linear-gradient(135deg, {{ $design['primary_color'] ?? '#F36B12' }}, {{ $design['secondary_color'] ?? '#E94B0B' }});
            border-bottom-left-radius: 50% 12px;
            border-bottom-right-radius: 50% 12px;
            margin-bottom: 8px;
        }

        .header-table {
            width: 100%;
            margin-bottom: 6px;
            border-collapse: collapse;
        }
        .header-logo-td {
            width: 55%;
            vertical-align: middle;
        }
        .header-right-td {
            width: 45%;
            text-align: right;
            vertical-align: middle;
        }
        .logo-box {
            display: inline-block;
            vertical-align: middle;
        }
        .logo-box img {
            max-height: 42px;
            max-width: 160px;
        }
        .brand-text-container {
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px;
        }
        .company-name-heading {
            font-size: 15pt;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.5px;
            line-height: 1.1;
        }
        .company-tagline-text {
            font-size: 7.2pt;
            color: {{ $design['primary_color'] ?? '#F36B12' }};
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-top: 1px;
        }
        .top-right-slogan {
            font-size: 9.5pt;
            font-style: italic;
            color: #4B5563;
            font-weight: 500;
        }

        /* Title Box */
        .doc-title-container {
            text-align: center;
            margin: 4px 0 6px 0;
        }
        .doc-title-banner {
            display: inline-block;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 3px 28px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .doc-subtitle-text {
            font-size: 7.5pt;
            color: #4B5563;
            font-weight: 600;
            margin-top: 2px;
            letter-spacing: 0.3px;
        }

        /* Meta bar with Employee ID & Date */
        .meta-bar-table {
            width: 100%;
            background-color: #FFF3EB;
            border: 1px solid #FFD6C0;
            border-radius: 6px;
            padding: 3px 12px;
            margin-bottom: 7px;
            font-size: 8pt;
            font-weight: bold;
        }
        .meta-bar-table td {
            vertical-align: middle;
        }

        /* Section Styling */
        .section-header {
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 8.5pt;
            font-weight: bold;
            padding: 3.5px 10px;
            border-radius: 10px;
            margin-top: 5px;
            margin-bottom: 4px;
            letter-spacing: 0.3px;
        }
        .section-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.8pt;
        }
        .section-table td {
            padding: 2.5px 4px;
            vertical-align: top;
        }
        .label-cell {
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
        }
        .colon-cell {
            width: 8px;
            text-align: center;
            color: #6B7280;
            font-weight: bold;
        }
        .val-cell {
            color: #111827;
            font-weight: normal;
        }

        /* Photo Frame */
        .photo-box-cell {
            width: 115px;
            text-align: center;
            vertical-align: middle;
            padding-left: 10px;
        }
        .photo-frame {
            width: 100px;
            height: 115px;
            border: 1.5px solid #D1D5DB;
            border-radius: 6px;
            overflow: hidden;
            background-color: #F9FAFB;
            margin: 0 auto;
            text-align: center;
        }
        .photo-frame img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        .photo-placeholder {
            width: 100%;
            height: 100%;
            line-height: 115px;
            font-size: 7.5pt;
            color: #9CA3AF;
            text-align: center;
        }

        /* Checklist */
        .checklist-item {
            font-size: 7.4pt;
            color: #374151;
            line-height: 1.4;
            margin-bottom: 2px;
        }
        .checkbox-square {
            display: inline-block;
            width: 9px;
            height: 9px;
            border: 1.2px solid #6B7280;
            border-radius: 2px;
            margin-right: 5px;
            vertical-align: middle;
        }

        /* Declaration & Signature Boxes */
        .box-panel {
            border: 1px solid #E5E7EB;
            border-radius: 6px;
            padding: 6px 8px;
            background-color: #FAFAFA;
        }
        .declaration-text {
            font-size: 7.2pt;
            color: #374151;
            font-style: italic;
            line-height: 1.35;
            margin-bottom: 6px;
        }
        .sig-line {
            border-bottom: 1px solid #4B5563;
            display: inline-block;
            width: 120px;
            height: 12px;
            vertical-align: middle;
            margin-left: 4px;
        }

        /* Footer */
        .footer-banner {
            margin-top: 7px;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            border-radius: 6px;
            padding: 5px 12px;
            font-size: 7.5pt;
            font-weight: 500;
        }
        .footer-table {
            width: 100%;
            border-collapse: collapse;
        }
        .footer-table td {
            vertical-align: middle;
            color: #ffffff;
        }

        /* Watermark */
        .watermark-bg {
            position: fixed;
            top: 35%;
            left: 25%;
            width: 50%;
            opacity: 0.04;
            z-index: -100;
            text-align: center;
        }
        .watermark-bg img {
            max-width: 320px;
        }
    </style>
</head>
<body>

    @if(!empty($logoBase64))
        <div class="watermark-bg">
            <img src="{{ $logoBase64 }}" alt="Gosika Watermark">
        </div>
    @endif

    <!-- Top orange wave -->
    <div class="top-wave"></div>

    <!-- Header Table -->
    <table class="header-table">
        <tr>
            <td class="header-logo-td">
                @if(!empty($logoBase64))
                    <div class="logo-box">
                        <img src="{{ $logoBase64 }}" alt="Gosika Logo">
                    </div>
                @else
                    <div class="brand-text-container">
                        <div class="company-name-heading">{{ $sections['header']['company_name'] ?? 'GOSIKA INFOSYS' }}</div>
                        <div class="company-tagline-text">{{ $sections['header']['tagline'] ?? 'Build • Innovate • Grow' }}</div>
                    </div>
                @endif
            </td>
            <td class="header-right-td">
                <div class="top-right-slogan">{{ $sections['header']['top_right_text'] ?? 'Together for a Better Tomorrow' }}</div>
            </td>
        </tr>
    </table>

    <!-- Document Title -->
    <div class="doc-title-container">
        <div class="doc-title-banner">{{ $sections['header']['title'] ?? 'EMPLOYEE REGISTRATION FORM' }}</div>
        <div class="doc-subtitle-text">{{ $sections['header']['subtitle'] ?? 'Confidential – Human Resources Department' }}</div>
    </div>

    <!-- Meta Bar -->
    <table class="meta-bar-table">
        <tr>
            <td style="width: 50%; color: {{ $design['primary_color'] ?? '#F36B12' }};">
                &#9998; <strong>{{ $sections['header']['badge_id_label'] ?? 'Employee ID' }}:</strong>
                <span style="color: #111827; margin-left: 4px;">{{ $v['employee_id'] ?? $employee->employee_code }}</span>
            </td>
            <td style="width: 50%; text-align: right; color: {{ $design['primary_color'] ?? '#F36B12' }};">
                &#128197; <strong>{{ $sections['header']['badge_date_label'] ?? 'Registration Date' }}:</strong>
                <span style="color: #111827; margin-left: 4px;">{{ $v['employee_registration_date'] ?? date('d-m-Y') }}</span>
            </td>
        </tr>
    </table>

    <!-- SECTION A: Personal Information -->
    <div class="section-header">A. {{ $sections['section_a']['title'] ?? 'Personal Information' }}</div>
    <table class="section-table" style="margin-bottom: 4px;">
        <tr>
            <td style="vertical-align: top; width: 75%;">
                <table class="section-table">
                    <tr>
                        <td class="label-cell" style="width: 140px;">Full Legal Name</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" colspan="4"><strong>{{ $v['employee_full_name'] ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Date of Birth</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" style="width: 130px;">{{ $v['employee_date_of_birth'] ?? '' }}</td>
                        <td class="label-cell" style="width: 65px;">Gender</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_gender'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Citizenship Number</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" colspan="4">{{ $v['employee_citizenship_number'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Permanent Address</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" colspan="4">{{ $v['employee_permanent_address'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Current Address</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" colspan="4">{{ $v['employee_current_address'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Contact Number</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_phone'] ?? '' }}</td>
                        <td class="label-cell">Email Address</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_email'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Emergency Contact Name</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" colspan="4">{{ $v['employee_emergency_contact_name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Emergency Contact Number</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_emergency_contact_phone'] ?? '' }}</td>
                        <td class="label-cell">Relationship</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_emergency_contact_relation'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td class="photo-box-cell">
                <div class="photo-frame">
                    @if($employee->photo_path && file_exists(storage_path('app/public/' . $employee->photo_path)))
                        <img src="{{ storage_path('app/public/' . $employee->photo_path) }}" alt="Photo">
                    @else
                        <div class="photo-placeholder">Affix Passport Photo</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- SECTION B: Educational & Professional Information -->
    <div class="section-header">B. {{ $sections['section_b']['title'] ?? 'Educational & Professional Information' }}</div>
    <table class="section-table" style="margin-bottom: 4px;">
        <tr>
            <td class="label-cell" style="width: 170px;">Highest Qualification</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_highest_qualification'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Institution</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_institution'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Technical Skills</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_technical_skills'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Previous Work Experience</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_previous_experience'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Previous Organization</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_previous_organization'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Relevant Certifications</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_certifications'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Portfolio / GitHub</td>
            <td class="colon-cell">:</td>
            <td class="val-cell" style="color: #2563EB;">{{ $v['employee_portfolio'] ?? '' }}</td>
        </tr>
    </table>

    <!-- SECTION C: Employment Information -->
    <div class="section-header">C. {{ $sections['section_c']['title'] ?? 'Employment Information' }}</div>
    <table class="section-table" style="margin-bottom: 4px;">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 8px;">
                <table class="section-table">
                    <tr>
                        <td class="label-cell" style="width: 135px;">Department</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell"><strong>{{ $v['employee_department'] ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Designation</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell"><strong>{{ $v['employee_designation'] ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Employee Type</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_employment_type'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Employment Start Date</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_joining_date'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Probation Period</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_probation_period'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 8px; border-left: 1px dashed #E5E7EB;">
                <table class="section-table">
                    <tr>
                        <td class="label-cell" style="width: 125px;">Reporting Manager</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_reporting_manager'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Work Location</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_work_location'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Working Schedule</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_working_schedule'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Monthly Salary</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell"><strong>{{ $v['employee_salary'] ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Payment Method</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_payment_method'] ?? '' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- SECTION D: Assigned Company Role & Responsibilities -->
    <div class="section-header">D. {{ $sections['section_d']['title'] ?? 'Assigned Company Role & Responsibilities' }}</div>
    <table class="section-table" style="margin-bottom: 4px;">
        <tr>
            <td class="label-cell" style="width: 170px;">Primary Responsibilities</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_primary_responsibilities'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Secondary Responsibilities</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_secondary_responsibilities'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Assigned Projects</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_assigned_projects'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Performance Targets</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_performance_targets'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Reporting Requirements</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_reporting_requirements'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Authorized System Access</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_authorized_system_access'] ?? '' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Department Head</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_department_head'] ?? '' }}</td>
        </tr>
    </table>

    <!-- SECTION E & F: Checklist and Declaration in 2 Columns -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 4px;">
        <tr>
            <!-- SECTION E: Checklist -->
            <td style="width: 48%; vertical-align: top; padding-right: 6px;">
                <div class="section-header" style="font-size: 8pt; padding: 2.5px 8px;">E. {{ $sections['section_e']['title'] ?? 'Documents Checklist' }}</div>
                <div class="box-panel" style="min-height: 100px;">
                    @php
                        $checklist = $sections['section_e']['checklist'] ?? [
                            'Citizenship / Valid Identification Copy',
                            'Passport-size Photograph',
                            'CV / Resume',
                            'Educational Certificates',
                            'Previous Experience Certificate (if applicable)',
                            'Signed Employment Agreement',
                            'Signed Confidentiality Agreement',
                            'Signed Employment Bond',
                            'Other Relevant Documents',
                        ];
                    @endphp
                    @foreach($checklist as $item)
                        <div class="checklist-item">
                            <span class="checkbox-square"></span> {{ $item }}
                        </div>
                    @endforeach
                </div>
            </td>

            <!-- SECTION F: Employee Declaration -->
            <td style="width: 52%; vertical-align: top; padding-left: 6px;">
                <div class="section-header" style="font-size: 8pt; padding: 2.5px 8px;">F. {{ $sections['section_f']['title'] ?? 'Employee Declaration' }}</div>
                <div class="box-panel" style="min-height: 100px;">
                    <div class="declaration-text">
                        "{{ $sections['section_f']['declaration_text'] ?? 'I hereby declare that the information provided above is true and accurate. I agree to comply with Gosika Infosys company policies, professional standards, assigned responsibilities, confidentiality requirements and lawful instructions.' }}"
                    </div>
                    <table style="width: 100%; font-size: 7.5pt; margin-top: 6px;">
                        <tr>
                            <td style="width: 100px; font-weight: bold; color: #374151;">Employee Name:</td>
                            <td><strong>{{ $v['employee_full_name'] ?? '' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #374151; padding-top: 4px;">Signature:</td>
                            <td style="padding-top: 4px;"><span class="sig-line" style="width: 130px;"></span></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #374151; padding-top: 4px;">Date:</td>
                            <td style="padding-top: 4px;">{{ $v['employee_registration_date'] ?? date('d-m-Y') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- SECTION G: HR Verification -->
    <div class="section-header" style="font-size: 8pt; padding: 2.5px 8px; margin-top: 6px;">G. {{ $sections['section_g']['title'] ?? 'HR Verification' }}</div>
    <div class="box-panel" style="padding: 4px 8px;">
        <table style="width: 100%; font-size: 7.4pt; border-collapse: collapse;">
            <tr>
                <td style="width: 25%; vertical-align: top;">
                    <div class="checklist-item"><span class="checkbox-square"></span> Documents Verified</div>
                    <div class="checklist-item"><span class="checkbox-square"></span> Employee ID Generated</div>
                    <div class="checklist-item"><span class="checkbox-square"></span> Employment Agreement Signed</div>
                </td>
                <td style="width: 25%; vertical-align: top;">
                    <div class="checklist-item"><span class="checkbox-square"></span> Bond Signed</div>
                    <div class="checklist-item"><span class="checkbox-square"></span> Company ID Issued</div>
                    <div class="checklist-item"><span class="checkbox-square"></span> System Access Approved</div>
                </td>
                <td style="width: 50%; vertical-align: middle; padding-left: 12px; border-left: 1px dashed #D1D5DB;">
                    <table style="width: 100%; font-size: 7.4pt;">
                        <tr>
                            <td style="width: 110px; font-weight: bold; color: #374151;">HR Representative :</td>
                            <td>{{ $v['hr_representative_name'] ?? 'Sabin Shrestha' }}</td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #374151; padding-top: 3px;">Signature :</td>
                            <td style="padding-top: 3px;"><span class="sig-line" style="width: 100px;"></span></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #374151; padding-top: 3px;">Authorized Signature:</td>
                            <td style="padding-top: 3px;"><span class="sig-line" style="width: 100px;"></span></td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </div>

    <!-- Footer Banner -->
    <div class="footer-banner">
        <table class="footer-table">
            <tr>
                <td style="width: 28%;">&#128205; {{ $v['company_address'] ?? 'Corporate Tower, Tinkune' }}, {{ $v['company_city'] ?? 'Kathmandu' }}</td>
                <td style="width: 24%;">&#127760; {{ $v['company_website'] ?? 'www.gosika.com' }}</td>
                <td style="width: 24%;">&#9993; {{ $v['company_email'] ?? 'info@gosika.com' }}</td>
                <td style="width: 24%; text-align: right; font-weight: bold;">
                    {{ $sections['footer']['brand_text'] ?? 'Gosika Infosys' }} <span style="font-weight: normal; font-size: 6.8pt; display: block;">Growing Together</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $docNumber }} - {{ $template->name }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 4mm 8mm 4mm 8mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 6.9pt;
            color: {{ $design['text_color'] ?? '#202124' }};
            line-height: 1.15;
            background-color: #ffffff;
            position: relative;
        }

        /* Fixed Centered Background Watermark */
        .watermark-bg {
            position: fixed;
            top: 280px;
            left: 130px;
            width: 320px;
            text-align: center;
            z-index: -1000;
        }
        .watermark-bg img {
            width: 320px;
            height: auto;
        }

        /* Fluid top wave */
        .top-wave {
            width: 100%;
            height: 12px;
            background: linear-gradient(135deg, {{ $design['primary_color'] ?? '#F36B12' }}, {{ $design['secondary_color'] ?? '#E94B0B' }});
            border-bottom-left-radius: 50% 8px;
            border-bottom-right-radius: 50% 8px;
            margin-bottom: 2px;
        }

        .header-table {
            width: 100%;
            margin-bottom: 2px;
            border-collapse: collapse;
        }
        .header-logo-td {
            width: 60%;
            vertical-align: middle;
        }
        .header-right-td {
            width: 40%;
            text-align: right;
            vertical-align: middle;
        }
        .brand-table {
            border-collapse: collapse;
        }
        .brand-table td {
            vertical-align: middle;
            padding: 0;
        }
        .company-name-heading {
            font-size: 14pt;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.5px;
            line-height: 1.05;
        }
        .company-tagline-text {
            font-size: 6.5pt;
            color: {{ $design['primary_color'] ?? '#F36B12' }};
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-top: 1px;
        }
        .top-right-slogan {
            font-size: 8.5pt;
            font-style: italic;
            color: #4B5563;
            line-height: 1.15;
        }

        /* Title Box */
        .doc-title-container {
            text-align: center;
            margin: 1px 0 2px 0;
        }
        .doc-title-banner {
            display: inline-block;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 10pt;
            font-weight: bold;
            letter-spacing: 1.2px;
            padding: 2px 26px;
            border-radius: 10px;
            text-transform: uppercase;
        }
        .doc-subtitle-text {
            font-size: 6.8pt;
            color: #4B5563;
            font-weight: 600;
            margin-top: 1px;
            letter-spacing: 0.3px;
        }

        /* Meta bar with Employee ID & Date */
        .meta-bar-table {
            width: 100%;
            background-color: #FFF3EB;
            border: 1px solid #FFD6C0;
            border-radius: 4px;
            padding: 2px 8px;
            margin-bottom: 2.5px;
            font-size: 7.2pt;
            font-weight: bold;
        }

        /* Section Styling */
        .section-header {
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 7.4pt;
            font-weight: bold;
            padding: 1.8px 8px;
            border-radius: 7px;
            margin-top: 2.5px;
            margin-bottom: 1.5px;
            letter-spacing: 0.3px;
        }
        .section-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 6.8pt;
        }
        .section-table td {
            padding: 0.9px 2px;
            vertical-align: top;
        }
        .label-cell {
            font-weight: 600;
            color: #374151;
            white-space: nowrap;
        }
        .colon-cell {
            width: 6px;
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
            width: 84px;
            text-align: center;
            vertical-align: middle;
            padding-left: 6px;
        }
        .photo-frame {
            width: 76px;
            height: 90px;
            border: 1.2px solid #D1D5DB;
            border-radius: 5px;
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
            line-height: 90px;
            font-size: 6.2pt;
            color: #9CA3AF;
            text-align: center;
        }

        /* Checklist */
        .checklist-item {
            font-size: 6.5pt;
            color: #374151;
            line-height: 1.2;
            margin-bottom: 1.5px;
        }
        .checkbox-square {
            display: inline-block;
            width: 8px;
            height: 8px;
            border: 1px solid #6B7280;
            border-radius: 2px;
            margin-right: 4px;
            vertical-align: middle;
        }

        /* Declaration & Signature Boxes */
        .box-panel {
            border: 1px solid #E5E7EB;
            border-radius: 5px;
            padding: 3.5px 6px;
            background-color: #FAFAFA;
        }
        .declaration-text {
            font-size: 6.5pt;
            color: #374151;
            font-style: italic;
            line-height: 1.2;
            margin-bottom: 3px;
        }
        .sig-line {
            border-bottom: 1px solid #4B5563;
            display: inline-block;
            width: 105px;
            height: 8px;
            vertical-align: middle;
            margin-left: 3px;
        }

        /* Footer */
        .footer-banner {
            margin-top: 3px;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            border-radius: 4px;
            padding: 3px 10px;
            font-size: 6.6pt;
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
    </style>
</head>
<body>

    <!-- Centered Background Watermark -->
    @if(!empty($watermarkBase64))
        <div class="watermark-bg">
            <img src="{{ $watermarkBase64 }}" alt="Gosika Watermark">
        </div>
    @elseif(!empty($logoBase64))
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
                <table class="brand-table">
                    <tr>
                        @if(!empty($iconBase64))
                            <td style="width: 44px; padding-right: 6px;">
                                <img src="{{ $iconBase64 }}" alt="Gosika Icon" style="width: 38px; height: 32px; object-fit: contain;">
                            </td>
                        @elseif(!empty($logoBase64))
                            <td style="width: 44px; padding-right: 6px;">
                                <img src="{{ $logoBase64 }}" alt="Gosika Logo" style="width: 38px; height: 32px; object-fit: contain;">
                            </td>
                        @endif
                        <td>
                            <div class="company-name-heading">{{ $sections['header']['company_name'] ?? 'GOSIKA INFOSYS' }}</div>
                            <div class="company-tagline-text">{{ $sections['header']['tagline'] ?? 'Build • Innovate • Grow' }}</div>
                        </td>
                    </tr>
                </table>
            </td>
            <td class="header-right-td">
                <div class="top-right-slogan">
                    Together for a<br>
                    <span style="color: {{ $design['primary_color'] ?? '#F36B12' }}; font-weight: bold;">Better Tomorrow</span>
                </div>
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
    <table class="section-table">
        <tr>
            <td style="vertical-align: top; width: 78%;">
                <table class="section-table">
                    <tr>
                        <td class="label-cell" style="width: 130px;">Full Legal Name</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" colspan="4"><strong>{{ $v['employee_full_name'] ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Date of Birth</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" style="width: 110px;">{{ $v['employee_date_of_birth'] ?? '' }}</td>
                        <td class="label-cell" style="width: 50px;">Gender</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_gender'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Citizenship / ID Number</td>
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
                        <td class="label-cell">Email</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_email'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Emergency Contact Name</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell" colspan="4">{{ $v['employee_emergency_contact_name'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Emergency Contact Phone</td>
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
                        <div class="photo-placeholder">Affix Photo</div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <!-- SECTION B: Educational & Professional Information -->
    <div class="section-header">B. {{ $sections['section_b']['title'] ?? 'Educational & Professional Information' }}</div>
    <table class="section-table">
        <tr>
            <td class="label-cell" style="width: 155px;">Highest Qualification</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_highest_qualification'] ?? 'Not Provided' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Institution</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_institution'] ?? 'Not Provided' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Technical Skills</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_technical_skills'] ?? 'Not Provided' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Previous Work Experience</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_previous_experience'] ?? 'Not Provided' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Previous Organization</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_previous_organization'] ?? 'Not Provided' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Relevant Certifications</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_certifications'] ?? 'Not Provided' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Portfolio / GitHub / LinkedIn</td>
            <td class="colon-cell">:</td>
            <td class="val-cell" style="color: #2563EB;">{{ $v['employee_portfolio'] ?? 'Not Provided' }}</td>
        </tr>
    </table>

    <!-- SECTION C: Employment Information -->
    <div class="section-header">C. {{ $sections['section_c']['title'] ?? 'Employment Information' }}</div>
    <table class="section-table">
        <tr>
            <td style="width: 50%; vertical-align: top; padding-right: 6px;">
                <table class="section-table">
                    <tr>
                        <td class="label-cell" style="width: 120px;">Department</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell"><strong>{{ $v['employee_department'] ?? 'General' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Designation</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell"><strong>{{ $v['employee_designation'] ?? 'Not Assigned' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Employee Type</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_employment_type'] ?? 'Full Time' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Employment Start Date</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_joining_date'] ?? '' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Probation Period</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_probation_period'] ?? '3 Months' }}</td>
                    </tr>
                </table>
            </td>
            <td style="width: 50%; vertical-align: top; padding-left: 6px; border-left: 1px dashed #E5E7EB;">
                <table class="section-table">
                    <tr>
                        <td class="label-cell" style="width: 115px;">Reporting Manager</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_reporting_manager'] ?? 'Department Head' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Work Location</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_work_location'] ?? 'Kathmandu Office' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Working Schedule</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_working_schedule'] ?? '9 AM – 6 PM' }}</td>
                    </tr>
                    <tr>
                        <td class="label-cell">Monthly Salary</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell"><strong>{{ $v['employee_salary'] ?? '' }}</strong></td>
                    </tr>
                    <tr>
                        <td class="label-cell">Payment Method</td>
                        <td class="colon-cell">:</td>
                        <td class="val-cell">{{ $v['employee_payment_method'] ?? 'Bank Transfer' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <!-- SECTION D: Assigned Company Role & Responsibilities -->
    <div class="section-header">D. {{ $sections['section_d']['title'] ?? 'Assigned Company Role & Responsibilities' }}</div>
    <table class="section-table">
        <tr>
            <td class="label-cell" style="width: 155px;">Primary Responsibilities</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_primary_responsibilities'] ?? 'As assigned by Department Head and Employment Agreement' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Secondary Responsibilities</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_secondary_responsibilities'] ?? 'Adhere to company policies and assist team deliverables' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Assigned Projects</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_assigned_projects'] ?? 'Internal & Client Systems' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Performance Targets</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_performance_targets'] ?? 'Meet deliverables according to project timelines' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Reporting Requirements</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_reporting_requirements'] ?? 'Regular progress reports to supervisor' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Authorized System Access</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_authorized_system_access'] ?? 'Assigned company tools & workspace access' }}</td>
        </tr>
        <tr>
            <td class="label-cell">Department Head</td>
            <td class="colon-cell">:</td>
            <td class="val-cell">{{ $v['employee_department_head'] ?? '' }}</td>
        </tr>
    </table>

    <!-- SECTION E & F: Checklist (Left) and Declaration (Right) -->
    <table style="width: 100%; border-collapse: collapse; margin-top: 2px;">
        <tr>
            <!-- SECTION E: Checklist -->
            <td style="width: 44%; vertical-align: top; padding-right: 4px;">
                <div class="section-header" style="font-size: 7.2pt; padding: 1.5px 7px;">E. {{ $sections['section_e']['title'] ?? 'Documents Checklist' }}</div>
                <div class="box-panel" style="padding: 2.5px 5px;">
                    <table style="width: 100%; font-size: 6.3pt; border-collapse: collapse;">
                        <tr>
                            <td style="width: 50%; vertical-align: top;">
                                <div class="checklist-item"><span class="checkbox-square"></span> Citizenship Copy</div>
                                <div class="checklist-item"><span class="checkbox-square"></span> Passport Photo</div>
                                <div class="checklist-item"><span class="checkbox-square"></span> CV / Resume</div>
                                <div class="checklist-item"><span class="checkbox-square"></span> Academic Certs</div>
                                <div class="checklist-item"><span class="checkbox-square"></span> Other Documents</div>
                            </td>
                            <td style="width: 50%; vertical-align: top;">
                                <div class="checklist-item"><span class="checkbox-square"></span> Experience Cert</div>
                                <div class="checklist-item"><span class="checkbox-square"></span> Employment Agr.</div>
                                <div class="checklist-item"><span class="checkbox-square"></span> Confidentiality</div>
                                <div class="checklist-item"><span class="checkbox-square"></span> Employment Bond</div>
                            </td>
                        </tr>
                    </table>
                </div>
            </td>

            <!-- SECTION F: Employee Declaration -->
            <td style="width: 56%; vertical-align: top; padding-left: 4px;">
                <div class="section-header" style="font-size: 7.2pt; padding: 1.5px 7px;">F. {{ $sections['section_f']['title'] ?? 'Employee Declaration' }}</div>
                <div class="box-panel" style="padding: 2.5px 5px;">
                    <div class="declaration-text">
                        "{{ $sections['section_f']['declaration_text'] ?? 'I hereby declare that the information provided above is true and accurate. I agree to comply with Gosika Infosys company policies, professional standards, assigned responsibilities, confidentiality requirements and lawful instructions.' }}"
                    </div>
                    <table style="width: 100%; font-size: 6.8pt; border-collapse: collapse;">
                        <tr>
                            <td style="width: 85px; font-weight: bold; color: #374151;">Employee Name:</td>
                            <td><strong>{{ $v['employee_full_name'] ?? '' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #374151; padding-top: 1.5px;">Signature:</td>
                            <td style="padding-top: 1.5px;"><span class="sig-line" style="width: 110px;"></span></td>
                        </tr>
                        <tr>
                            <td style="font-weight: bold; color: #374151; padding-top: 1.5px;">Date:</td>
                            <td style="padding-top: 1.5px;">{{ $v['employee_registration_date'] ?? date('d-m-Y') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

    <!-- SECTION G: HR Verification -->
    <div class="section-header" style="font-size: 7.2pt; padding: 1.5px 7px; margin-top: 2px;">G. {{ $sections['section_g']['title'] ?? 'HR Verification' }}</div>
    <div class="box-panel" style="padding: 2.5px 5px;">
        <table style="width: 100%; font-size: 6.5pt; border-collapse: collapse;">
            <tr>
                <td style="width: 25%; vertical-align: top;">
                    <div class="checklist-item"><span class="checkbox-square"></span> Documents Verified</div>
                    <div class="checklist-item"><span class="checkbox-square"></span> Employee ID Generated</div>
                </td>
                <td style="width: 25%; vertical-align: top;">
                    <div class="checklist-item"><span class="checkbox-square"></span> Employment Agr. Signed</div>
                    <div class="checklist-item"><span class="checkbox-square"></span> Bond Agreement Signed</div>
                </td>
                <td style="width: 22%; vertical-align: top;">
                    <div class="checklist-item"><span class="checkbox-square"></span> Company ID Issued</div>
                    <div class="checklist-item"><span class="checkbox-square"></span> System Access Approved</div>
                </td>
                <td style="width: 28%; vertical-align: middle; padding-left: 8px; border-left: 1px dashed #D1D5DB;">
                    <table style="width: 100%; font-size: 6.5pt; border-collapse: collapse;">
                        <tr>
                            <td style="color: #4B5563; white-space: nowrap;">HR Rep:</td>
                            <td style="font-weight: bold;">{{ $v['hr_representative_name'] ?? 'HR Representative' }}</td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 1px; white-space: nowrap;">HR Sig:</td>
                            <td style="padding-top: 1px;"><span class="sig-line" style="width: 65px;"></span></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 1px; white-space: nowrap;">Auth Sig:</td>
                            <td style="padding-top: 1px;"><span class="sig-line" style="width: 65px;"></span></td>
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
                <td style="width: 32%;">&#128205; {{ $v['company_address'] ?? 'Corporate Tower, Tinkune' }}, {{ $v['company_city'] ?? 'Kathmandu' }}</td>
                <td style="width: 22%;">&#127760; {{ $v['company_website'] ?? 'www.gosika.com' }}</td>
                <td style="width: 24%;">&#9993; {{ $v['company_email'] ?? 'info@gosika.com' }}</td>
                <td style="width: 22%; text-align: right; font-weight: bold;">
                    {{ $sections['footer']['brand_text'] ?? 'Gosika Infosys' }} <span style="font-weight: normal; font-size: 6.2pt; display: block;">Growing Together</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

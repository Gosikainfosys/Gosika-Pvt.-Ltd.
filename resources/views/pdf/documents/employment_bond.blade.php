<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $docNumber }} - {{ $template->name }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 6mm 9mm 5mm 9mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7.2pt;
            color: {{ $design['text_color'] ?? '#202124' }};
            line-height: 1.25;
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

        /* Top orange fluid wave */
        .top-wave {
            width: 100%;
            height: 14px;
            background: linear-gradient(135deg, {{ $design['primary_color'] ?? '#F36B12' }}, {{ $design['secondary_color'] ?? '#E94B0B' }});
            border-bottom-left-radius: 50% 10px;
            border-bottom-right-radius: 50% 10px;
            margin-bottom: 4px;
        }

        .header-table {
            width: 100%;
            margin-bottom: 3px;
            border-collapse: collapse;
        }
        .header-logo-td {
            width: 58%;
            vertical-align: middle;
        }
        .header-right-td {
            width: 42%;
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
            font-size: 14.5pt;
            font-weight: bold;
            color: #111827;
            letter-spacing: 0.5px;
            line-height: 1.05;
        }
        .company-tagline-text {
            font-size: 6.8pt;
            color: {{ $design['primary_color'] ?? '#F36B12' }};
            font-weight: bold;
            letter-spacing: 1.5px;
            margin-top: 1px;
        }
        .top-right-slogan {
            font-size: 9pt;
            font-style: italic;
            color: #4B5563;
            line-height: 1.2;
        }

        /* Title Box */
        .doc-title-container {
            text-align: center;
            margin: 2px 0 4px 0;
        }
        .doc-title-banner {
            display: inline-block;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 10.5pt;
            font-weight: bold;
            letter-spacing: 1.2px;
            padding: 2.5px 34px;
            border-radius: 12px;
            text-transform: uppercase;
        }
        .doc-subtitle-text {
            font-size: 7.2pt;
            color: #4B5563;
            font-weight: 600;
            margin-top: 1.5px;
            letter-spacing: 0.3px;
        }

        /* Meta bar */
        .meta-bar-table {
            width: 100%;
            background-color: #FFF3EB;
            border: 1px solid #FFD6C0;
            border-radius: 5px;
            padding: 2.5px 10px;
            margin-bottom: 4px;
            font-size: 7.4pt;
            font-weight: bold;
        }

        /* Parties Intro */
        .parties-intro {
            font-size: 7.3pt;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 3px;
        }

        /* Parties Layout */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 5px;
        }
        .party-box {
            border: 1px solid #FFD6C0;
            border-radius: 5px;
            padding: 4px 7px;
            background-color: #FFFAF5;
            height: 58px;
        }
        .party-header {
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 6.8pt;
            font-weight: bold;
            padding: 1.2px 7px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 2px;
        }
        .and-badge {
            display: inline-block;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 7.2pt;
            font-weight: bold;
            padding: 2.5px 6px;
            border-radius: 8px;
        }

        /* Clauses 2-Column Grid */
        .clauses-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
        }
        .clauses-td {
            vertical-align: top;
            width: 50%;
        }
        .clause-item {
            margin-bottom: 4.5px;
        }
        .clause-title-bar {
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 6.8pt;
            font-weight: bold;
            padding: 1.5px 7px;
            border-radius: 7px;
            display: inline-block;
            margin-bottom: 1.5px;
        }
        .clause-num-badge {
            display: inline-block;
            background-color: #ffffff;
            color: {{ $design['primary_color'] ?? '#F36B12' }};
            font-size: 6pt;
            font-weight: bold;
            width: 11px;
            height: 11px;
            line-height: 11px;
            text-align: center;
            border-radius: 50%;
            margin-right: 3px;
            vertical-align: middle;
        }
        .clause-body {
            font-size: 6.6pt;
            color: #374151;
            line-height: 1.2;
            padding-left: 2px;
            white-space: pre-line;
        }

        /* Signatures Section - 2 Columns (Witness Removed) */
        .sig-container-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 3px;
        }
        .sig-card {
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            padding: 5px 8px;
            background-color: #FAFAFA;
            height: 82px;
        }
        .sig-card-header {
            font-size: 7.2pt;
            font-weight: bold;
            color: #111827;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 2px;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .sig-line {
            border-bottom: 1px solid #374151;
            display: inline-block;
            width: 125px;
            height: 10px;
            vertical-align: middle;
            margin-left: 4px;
        }
        .stamp-box {
            display: inline-block;
            width: 54px;
            height: 54px;
            border: 1.2px dashed #F36B12;
            border-radius: 50%;
            text-align: center;
            vertical-align: middle;
            font-size: 5.2pt;
            color: #F36B12;
            float: right;
            margin-left: 4px;
            overflow: hidden;
        }
        .stamp-img {
            width: 100%;
            height: 100%;
            object-fit: contain;
        }

        /* Footer */
        .footer-banner {
            margin-top: 4px;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            border-radius: 5px;
            padding: 3.5px 10px;
            font-size: 6.8pt;
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

    <!-- Centered Watermark -->
    @if(!empty($watermarkBase64))
        <div class="watermark-bg">
            <img src="{{ $watermarkBase64 }}" alt="Gosika Watermark">
        </div>
    @elseif(!empty($logoBase64))
        <div class="watermark-bg">
            <img src="{{ $logoBase64 }}" alt="Gosika Watermark">
        </div>
    @endif

    <!-- Top wave -->
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
        <div class="doc-title-banner">{{ $sections['header']['title'] ?? 'EMPLOYMENT BOND' }}</div>
        <div class="doc-subtitle-text">{{ $sections['header']['subtitle'] ?? 'Confidential – Human Resources Department' }}</div>
    </div>

    <!-- Meta Bar -->
    <table class="meta-bar-table">
        <tr>
            <td style="width: 50%; color: {{ $design['primary_color'] ?? '#F36B12' }};">
                &#128196; <strong>{{ $sections['header']['badge_bond_label'] ?? 'Bond Number' }}:</strong>
                <span style="color: #111827; margin-left: 4px;">{{ $v['agreement_number'] ?? $agreementNumber }}</span>
            </td>
            <td style="width: 50%; text-align: right; color: {{ $design['primary_color'] ?? '#F36B12' }};">
                &#128197; <strong>{{ $sections['header']['badge_date_label'] ?? 'Agreement Date' }}:</strong>
                <span style="color: #111827; margin-left: 4px;">{{ $v['agreement_date'] ?? date('d-m-Y') }}</span>
            </td>
        </tr>
    </table>

    <div class="parties-intro">{{ $sections['parties_intro'] ?? 'This Employment Bond is made between:' }}</div>

    <!-- Parties Grid -->
    <table class="parties-table">
        <tr>
            <!-- Company Box -->
            <td style="width: 46%; vertical-align: top;">
                <div class="party-box">
                    <div class="party-header">A. {{ $sections['parties']['company']['title'] ?? 'Company' }}</div>
                    <div style="font-size: 7.8pt; font-weight: bold; color: #111827;">{{ $sections['parties']['company']['name'] ?? 'Gosika Infosys' }}</div>
                    <div style="font-size: 6.9pt; color: #4B5563;">Address: {{ $sections['parties']['company']['address'] ?? (($v['company_address'] ?? 'Corporate Tower, Tinkune') . ', ' . ($v['company_city'] ?? 'Kathmandu')) }}</div>
                    <div style="font-size: 6.9pt; color: #4B5563;">Authorized Representative: <strong>{{ $sections['parties']['company']['representative'] ?? ($v['company_authorized_representative'] ?? 'Bikash Pandey') }}</strong></div>
                </div>
            </td>

            <!-- AND Badge -->
            <td style="width: 8%; text-align: center; vertical-align: middle;">
                <span class="and-badge">AND</span>
            </td>

            <!-- Employee Box -->
            <td style="width: 46%; vertical-align: top;">
                <div class="party-box">
                    <div class="party-header">B. {{ $sections['parties']['employee']['title'] ?? 'Employee' }}</div>
                    <div style="font-size: 7.8pt; font-weight: bold; color: #111827;">Full Name: {{ $sections['parties']['employee']['name'] ?? ($v['employee_full_name'] ?? '') }}</div>
                    <div style="font-size: 6.9pt; color: #4B5563;">Employee ID: <strong>{{ $sections['parties']['employee']['employee_id'] ?? ($v['employee_id'] ?? '') }}</strong></div>
                    <div style="font-size: 6.9pt; color: #4B5563;">Permanent Address: {{ $sections['parties']['employee']['address'] ?? ($v['employee_permanent_address'] ?? 'Not Provided') }}</div>
                    <div style="font-size: 6.9pt; color: #4B5563;">Position: <strong>{{ $sections['parties']['employee']['position'] ?? ($v['employee_designation'] ?? 'Not Assigned') }}</strong> | Dept: {{ $sections['parties']['employee']['department'] ?? ($v['employee_department'] ?? 'General') }}</div>
                </div>
            </td>
        </tr>
    </table>

    <!-- 12 Legal Clauses in 2 Columns -->
    @php
        $clauses = $sections['clauses'] ?? [];
        $col1 = array_slice($clauses, 0, 6);
        $col2 = array_slice($clauses, 6);
    @endphp

    <table class="clauses-table">
        <tr>
            <!-- Left Column (Clauses 1 to 6) -->
            <td class="clauses-td" style="padding-right: 5px;">
                @foreach($col1 as $c)
                    <div class="clause-item">
                        <div class="clause-title-bar"><span class="clause-num-badge">{{ $c['num'] ?? $loop->iteration }}</span>{{ $c['title'] }}</div>
                        <div class="clause-body">{{ $c['body'] }}</div>
                    </div>
                @endforeach
            </td>

            <!-- Right Column (Clauses 7 to 12) -->
            <td class="clauses-td" style="padding-left: 5px;">
                @foreach($col2 as $c)
                    <div class="clause-item">
                        <div class="clause-title-bar"><span class="clause-num-badge">{{ $c['num'] ?? ($loop->iteration + 6) }}</span>{{ $c['title'] }}</div>
                        <div class="clause-body">{{ $c['body'] }}</div>
                    </div>
                @endforeach
            </td>
        </tr>
    </table>

    <!-- Signatures Section - 2 Columns (Employee Left, Company Representative Right, Witnesses Removed) -->
    <table class="sig-container-table">
        <tr>
            <!-- Left: Employee -->
            <td style="width: 50%; vertical-align: top; padding-right: 5px;">
                <div class="sig-card">
                    <div class="sig-card-header">EMPLOYEE</div>
                    <table style="width: 100%; font-size: 7pt; border-collapse: collapse;">
                        <tr>
                            <td style="width: 80px; color: #4B5563; padding: 1.5px 0;">Full Name:</td>
                            <td style="padding: 1.5px 0;"><strong>{{ $v['employee_full_name'] ?? '' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding: 1.5px 0;">Employee ID:</td>
                            <td style="padding: 1.5px 0;"><strong>{{ $v['employee_id'] ?? '' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding: 2px 0;">Signature:</td>
                            <td style="padding: 2px 0;"><span class="sig-line" style="width: 130px;"></span></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding: 1.5px 0;">Date:</td>
                            <td style="padding: 1.5px 0;">{{ $v['agreement_date'] ?? date('d-m-Y') }}</td>
                        </tr>
                    </table>
                </div>
            </td>

            <!-- Right: Company Representative & Stamp -->
            <td style="width: 50%; vertical-align: top; padding-left: 5px;">
                <div class="sig-card">
                    <div class="stamp-box">
                        @if(!empty($stampBase64))
                            <img src="{{ $stampBase64 }}" alt="Official Stamp" class="stamp-img">
                        @else
                            <div style="padding-top: 14px; font-weight: bold;">OFFICIAL<br>STAMP</div>
                        @endif
                    </div>
                    <div class="sig-card-header">FOR GOSIKA INFOSYS</div>
                    <table style="width: 76%; font-size: 7pt; border-collapse: collapse;">
                        <tr>
                            <td style="width: 95px; color: #4B5563; padding: 1.5px 0;">Authorized Rep:</td>
                            <td style="padding: 1.5px 0;"><strong>{{ $v['company_authorized_representative'] ?? 'Bikash Pandey' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding: 1.5px 0;">Designation:</td>
                            <td style="padding: 1.5px 0;">Authorized Signatory</td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding: 2px 0;">Signature & Stamp:</td>
                            <td style="padding: 2px 0;"><span class="sig-line" style="width: 85px;"></span></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding: 1.5px 0;">Date:</td>
                            <td style="padding: 1.5px 0;">{{ $v['agreement_date'] ?? date('d-m-Y') }}</td>
                        </tr>
                    </table>
                    <div style="clear: both;"></div>
                </div>
            </td>
        </tr>
    </table>

    <!-- Footer Banner -->
    <div class="footer-banner">
        <table class="footer-table">
            <tr>
                <td style="width: 32%;">&#128205; {{ $v['company_address'] ?? 'Corporate Tower, Tinkune' }}, {{ $v['company_city'] ?? 'Kathmandu' }}</td>
                <td style="width: 22%;">&#127760; {{ $v['company_website'] ?? 'www.gosika.com' }}</td>
                <td style="width: 24%;">&#9993; {{ $v['company_email'] ?? 'info@gosika.com' }}</td>
                <td style="width: 22%; text-align: right; font-weight: bold;">
                    {{ $sections['footer']['brand_text'] ?? 'Gosika Infosys' }} <span style="font-weight: normal; font-size: 6.4pt; display: block;">Growing Together</span>
                </td>
            </tr>
        </table>
    </div>

</body>
</html>

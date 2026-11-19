<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{{ $docNumber }} - {{ $template->name }}</title>
    <style>
        @page {
            size: a4 portrait;
            margin: 7mm 10mm 7mm 10mm;
        }
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 7.6pt;
            color: {{ $design['text_color'] ?? '#202124' }};
            line-height: 1.3;
            background-color: #ffffff;
            position: relative;
        }

        /* Fluid top wave effect */
        .top-wave {
            width: 100%;
            height: 16px;
            background: linear-gradient(135deg, {{ $design['primary_color'] ?? '#F36B12' }}, {{ $design['secondary_color'] ?? '#E94B0B' }});
            border-bottom-left-radius: 50% 12px;
            border-bottom-right-radius: 50% 12px;
            margin-bottom: 6px;
        }

        .header-table {
            width: 100%;
            margin-bottom: 4px;
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
            max-height: 40px;
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
            margin: 3px 0 5px 0;
        }
        .doc-title-banner {
            display: inline-block;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 11pt;
            font-weight: bold;
            letter-spacing: 1px;
            padding: 3px 36px;
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

        /* Meta bar */
        .meta-bar-table {
            width: 100%;
            background-color: #FFF3EB;
            border: 1px solid #FFD6C0;
            border-radius: 6px;
            padding: 3px 12px;
            margin-bottom: 5px;
            font-size: 7.8pt;
            font-weight: bold;
        }

        /* Parties Intro */
        .parties-intro {
            font-size: 7.6pt;
            font-weight: bold;
            color: #1F2937;
            margin-bottom: 4px;
        }

        /* Parties Layout */
        .parties-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }
        .party-box {
            border: 1px solid #FFD6C0;
            border-radius: 6px;
            padding: 5px 8px;
            background-color: #FFFAF5;
            height: 65px;
        }
        .party-header {
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 7.2pt;
            font-weight: bold;
            padding: 1.5px 8px;
            border-radius: 4px;
            display: inline-block;
            margin-bottom: 4px;
        }
        .and-badge {
            display: inline-block;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 7.5pt;
            font-weight: bold;
            padding: 3px 6px;
            border-radius: 10px;
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
            margin-bottom: 5.5px;
        }
        .clause-title-bar {
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            font-size: 7pt;
            font-weight: bold;
            padding: 1.5px 6px;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 2px;
        }
        .clause-body {
            font-size: 6.9pt;
            color: #374151;
            line-height: 1.25;
            padding-left: 2px;
            white-space: pre-line;
        }

        /* Signatures Section */
        .sig-container-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 4px;
        }
        .sig-card {
            border: 1px solid #D1D5DB;
            border-radius: 6px;
            padding: 5px 8px;
            background-color: #FAFAFA;
            margin-bottom: 4px;
        }
        .sig-card-header {
            font-size: 7.2pt;
            font-weight: bold;
            color: #111827;
            border-bottom: 1px solid #E5E7EB;
            padding-bottom: 2px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .sig-line {
            border-bottom: 1px solid #374151;
            display: inline-block;
            width: 110px;
            height: 10px;
            vertical-align: middle;
            margin-left: 4px;
        }
        .stamp-box {
            display: inline-block;
            width: 58px;
            height: 48px;
            border: 1.5px dashed #F36B12;
            border-radius: 50%;
            text-align: center;
            vertical-align: middle;
            font-size: 5.5pt;
            color: #F36B12;
            padding-top: 14px;
            margin-left: 6px;
            float: right;
        }

        /* Footer */
        .footer-banner {
            margin-top: 4px;
            background-color: {{ $design['primary_color'] ?? '#F36B12' }};
            color: #ffffff;
            border-radius: 6px;
            padding: 4px 10px;
            font-size: 7.2pt;
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

    <!-- Top wave -->
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
                    <div style="font-size: 8pt; font-weight: bold; color: #111827;">{{ $sections['parties']['company']['name'] ?? 'Gosika Infosys' }}</div>
                    <div style="font-size: 7.2pt; color: #4B5563;">Address: {{ $sections['parties']['company']['address'] ?? (($v['company_address'] ?? 'Corporate Tower, Tinkune') . ', ' . ($v['company_city'] ?? 'Kathmandu')) }}</div>
                    <div style="font-size: 7.2pt; color: #4B5563;">Authorized Representative: <strong>{{ $sections['parties']['company']['representative'] ?? ($v['company_authorized_representative'] ?? 'Bikash Pandey') }}</strong></div>
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
                    <div style="font-size: 8pt; font-weight: bold; color: #111827;">Full Name: {{ $sections['parties']['employee']['name'] ?? ($v['employee_full_name'] ?? '') }}</div>
                    <div style="font-size: 7.2pt; color: #4B5563;">Employee ID: <strong>{{ $sections['parties']['employee']['employee_id'] ?? ($v['employee_id'] ?? '') }}</strong></div>
                    <div style="font-size: 7.2pt; color: #4B5563;">Permanent Address: {{ $sections['parties']['employee']['address'] ?? ($v['employee_permanent_address'] ?? '') }}</div>
                    <div style="font-size: 7.2pt; color: #4B5563;">Position: <strong>{{ $sections['parties']['employee']['position'] ?? ($v['employee_designation'] ?? '') }}</strong> | Dept: {{ $sections['parties']['employee']['department'] ?? ($v['employee_department'] ?? '') }}</div>
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
            <td class="clauses-td" style="padding-right: 6px;">
                @foreach($col1 as $c)
                    <div class="clause-item">
                        <div class="clause-title-bar">&#1010{{ ($c['num'] ?? $loop->iteration) }}&nbsp; {{ $c['title'] }}</div>
                        <div class="clause-body">{{ $c['body'] }}</div>
                    </div>
                @endforeach
            </td>

            <!-- Right Column (Clauses 7 to 12) -->
            <td class="clauses-td" style="padding-left: 6px;">
                @foreach($col2 as $c)
                    <div class="clause-item">
                        <div class="clause-title-bar">&#1010{{ ($c['num'] ?? ($loop->iteration + 6)) }}&nbsp; {{ $c['title'] }}</div>
                        <div class="clause-body">{{ $c['body'] }}</div>
                    </div>
                @endforeach
            </td>
        </tr>
    </table>

    <!-- Signatures Section -->
    <table class="sig-container-table">
        <tr>
            <!-- Left: Employee & Witness 1 -->
            <td style="width: 50%; vertical-align: top; padding-right: 4px;">
                <!-- Employee -->
                <div class="sig-card">
                    <div class="sig-card-header">EMPLOYEE</div>
                    <table style="width: 100%; font-size: 7.2pt;">
                        <tr>
                            <td style="width: 90px; color: #4B5563;">Full Name:</td>
                            <td><strong>{{ $v['employee_full_name'] ?? '' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563;">Employee ID:</td>
                            <td><strong>{{ $v['employee_id'] ?? '' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 3px;">Signature:</td>
                            <td style="padding-top: 3px;"><span class="sig-line" style="width: 120px;"></span></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 3px;">Date:</td>
                            <td style="padding-top: 3px;">{{ $v['agreement_date'] ?? date('d-m-Y') }}</td>
                        </tr>
                    </table>
                </div>

                <!-- Witness 1 -->
                <div class="sig-card">
                    <div class="sig-card-header">WITNESS 1</div>
                    <table style="width: 100%; font-size: 7.2pt;">
                        <tr>
                            <td style="width: 90px; color: #4B5563;">Name:</td>
                            <td>{{ $sections['signatures']['witness1']['default_name'] ?? 'Ramesh Sharma' }}</td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 2px;">Signature:</td>
                            <td style="padding-top: 2px;"><span class="sig-line" style="width: 120px;"></span></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 2px;">Date:</td>
                            <td style="padding-top: 2px;">{{ $v['agreement_date'] ?? date('d-m-Y') }}</td>
                        </tr>
                    </table>
                </div>
            </td>

            <!-- Right: Company & Witness 2 -->
            <td style="width: 50%; vertical-align: top; padding-left: 4px;">
                <!-- Company -->
                <div class="sig-card">
                    <div class="stamp-box">
                        OFFICIAL<br>STAMP
                    </div>
                    <div class="sig-card-header">FOR GOSIKA INFOSYS</div>
                    <table style="width: 75%; font-size: 7.2pt;">
                        <tr>
                            <td style="width: 110px; color: #4B5563;">Authorized Rep:</td>
                            <td><strong>{{ $v['company_authorized_representative'] ?? 'Bikash Pandey' }}</strong></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563;">Designation:</td>
                            <td>Managing Director</td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 3px;">Signature:</td>
                            <td style="padding-top: 3px;"><span class="sig-line" style="width: 90px;"></span></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 3px;">Date:</td>
                            <td style="padding-top: 3px;">{{ $v['agreement_date'] ?? date('d-m-Y') }}</td>
                        </tr>
                    </table>
                    <div style="clear: both;"></div>
                </div>

                <!-- Witness 2 -->
                <div class="sig-card">
                    <div class="sig-card-header">WITNESS 2</div>
                    <table style="width: 100%; font-size: 7.2pt;">
                        <tr>
                            <td style="width: 90px; color: #4B5563;">Name:</td>
                            <td>{{ $sections['signatures']['witness2']['default_name'] ?? 'Anita Karki' }}</td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 2px;">Signature:</td>
                            <td style="padding-top: 2px;"><span class="sig-line" style="width: 120px;"></span></td>
                        </tr>
                        <tr>
                            <td style="color: #4B5563; padding-top: 2px;">Date:</td>
                            <td style="padding-top: 2px;">{{ $v['agreement_date'] ?? date('d-m-Y') }}</td>
                        </tr>
                    </table>
                </div>
            </td>
        </tr>
    </table>

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

@php
    $employee = $salary->employee;
    $accountNumber = $employee->bank_account_no ?? '';
    $maskedAccountNumber = $accountNumber === ''
        ? 'N/A'
        : str_repeat('*', max(0, strlen($accountNumber) - 4)) . substr($accountNumber, -4);
    $header = $reportSettings['header'];
    $footer = $reportSettings['footer'];
    $footerContacts = collect([$footer['phone'], $footer['email'], $footer['website']])
        ->filter(fn ($value) => filled($value))
        ->values();
    $currency = $reportSettings['currency'];
    $currencySymbol = $reportSettings['currency_symbol'] ?: $currency;
@endphp
<table class="header">
    <tr>
        <td class="brand-cell">
            @if($reportSettings['logo_path'])
                <img src="{{ $reportSettings['logo_path'] }}" class="logo" alt="{{ $header['company_name'] ?: 'Company logo' }}">
            @endif
            <div class="company-details">
                @if($header['company_name'])<strong>{{ $header['company_name'] }}</strong><br>@endif
                @if($header['trading_name']){{ $header['trading_name'] }}<br>@endif
                @if($header['registration_no'])Registration No.: {{ $header['registration_no'] }}<br>@endif
                @if($header['pan_vat_no'])PAN/VAT: {{ $header['pan_vat_no'] }}<br>@endif
                @foreach(['address', 'phone', 'email', 'website'] as $field)
                    @if($header[$field]){{ $header[$field] }}@if(!$loop->last)<br>@endif @endif
                @endforeach
            </div>
        </td>
        <td class="title-cell">
            <span class="document-title">SALARY DISBURSEMENT SLIP</span>
            <div class="document-meta">
                <strong>Slip No:</strong> {{ $salary->slip_number }}<br>
                <strong>Period:</strong> {{ $salary->salary_period ?: sprintf('%02d/%d', $salary->month, $salary->year) }}<br>
                <strong>Payment Date:</strong> {{ $salary->payment_date ? $salary->payment_date->format('Y-m-d') : '—' }}
            </div>
        </td>
    </tr>
</table>

<table class="employee-card">
    <tr>
        @if($employee->photo_path && file_exists(public_path('storage/' . $employee->photo_path)))
            <td class="photo-cell">
                <img src="{{ public_path('storage/' . $employee->photo_path) }}" class="employee-photo" alt="Employee photo">
            </td>
        @endif
        <td class="details-cell">
            <table>
                <tr>
                    <td class="details-left">
                        <table>
                            <tr class="detail-row"><td class="detail-label">Employee Name</td><td class="detail-value">{{ $employee->full_name }}</td></tr>
                            <tr class="detail-row"><td class="detail-label">Employee Code</td><td class="detail-value">{{ $employee->employee_code }}</td></tr>
                            <tr class="detail-row"><td class="detail-label">Designation</td><td class="detail-value">{{ $employee->designation->title ?? 'Staff' }}</td></tr>
                            <tr class="detail-row"><td class="detail-label">Department</td><td class="detail-value">{{ $employee->department->name ?? 'General' }}</td></tr>
                        </table>
                    </td>
                    <td class="details-right">
                        <table>
                            <tr class="detail-row"><td class="detail-label">Bank Name</td><td class="detail-value">{{ $employee->bank_name ?: 'N/A' }}</td></tr>
                            <tr class="detail-row"><td class="detail-label">Account Number</td><td class="detail-value">{{ $maskedAccountNumber }}</td></tr>
                            <tr class="detail-row"><td class="detail-label">Payment Method</td><td class="detail-value">{{ strtoupper(str_replace('_', ' ', $salary->payment_method ?: 'N/A')) }}</td></tr>
                            <tr class="detail-row"><td class="detail-label">Status</td><td class="detail-value status">{{ strtoupper($salary->status) }}</td></tr>
                        </table>
                    </td>
                </tr>
            </table>
        </td>
    </tr>
</table>

<table class="breakdowns">
    <tr>
        <td class="breakdown-cell">
            <table class="breakdown-table">
                <thead><tr><th>EARNINGS</th><th class="amount">AMOUNT ({{ $currency }})</th></tr></thead>
                <tbody>
                    <tr><td>Basic Salary</td><td class="amount">{{ number_format((float) $salary->basic_salary, 2) }}</td></tr>
                    @if((float) $salary->allowances > 0)
                        <tr><td>Allowances &amp; Per Diem</td><td class="amount">{{ number_format((float) $salary->allowances, 2) }}</td></tr>
                    @endif
                    @if((float) $salary->bonuses > 0)
                        <tr><td>Bonus</td><td class="amount">{{ number_format((float) $salary->bonuses, 2) }}</td></tr>
                    @endif
                    <tr class="total-row"><td>GROSS EARNINGS</td><td class="amount">{{ number_format((float) $salary->gross_salary, 2) }}</td></tr>
                </tbody>
            </table>
        </td>
        <td class="breakdown-cell">
            <table class="breakdown-table">
                <thead><tr><th>DEDUCTIONS</th><th class="amount">AMOUNT ({{ $currency }})</th></tr></thead>
                <tbody>
                    @if((float) $salary->tax_deduction > 0)
                        <tr><td>Tax Deducted at Source (TDS)</td><td class="amount deduction">{{ number_format((float) $salary->tax_deduction, 2) }}</td></tr>
                    @endif
                    @if((float) $salary->provident_fund > 0)
                        <tr><td>Provident Fund / Social Security</td><td class="amount deduction">{{ number_format((float) $salary->provident_fund, 2) }}</td></tr>
                    @endif
                    @if((float) $salary->other_deductions > 0)
                        <tr><td>Other Deductions</td><td class="amount deduction">{{ number_format((float) $salary->other_deductions, 2) }}</td></tr>
                    @endif
                    <tr class="total-row"><td>TOTAL DEDUCTIONS</td><td class="amount deduction">{{ number_format((float) $salary->total_deductions, 2) }}</td></tr>
                </tbody>
            </table>
        </td>
    </tr>
</table>

<table class="net-pay">
    <tr>
        <td class="net-label-cell">
            <div class="net-label">NET SALARY PAYABLE</div>
            <div class="net-caption">
                Payment reference: {{ $salary->payment_reference ?: 'N/A' }}
                &nbsp;|&nbsp; This slip is generated from recorded payroll data.
            </div>
        </td>
        <td class="net-amount-cell">{{ $currencySymbol }} {{ number_format((float) $salary->net_salary, 2) }}</td>
    </tr>
</table>

<table class="footer">
    <tr>
        <td class="notes-cell">
            <strong>Important Notes</strong><br>
            Please contact HR if you have any questions about this salary statement.
        </td>
        <td class="signature-cell">
            @if($reportSettings['stamp_signature_path'])
                <img src="{{ $reportSettings['stamp_signature_path'] }}" class="signature-image" alt="Authorized signature">
            @endif
            <div class="signature-line"></div>
            <strong>Authorized Signature</strong><br>HR / Finance Department
        </td>
        <td class="acknowledgement-cell">
            <strong>Employee Acknowledgement</strong><br>
            I acknowledge receipt of this salary statement.<br>
            Signature: __________________ &nbsp; Date: __________
        </td>
    </tr>
</table>

<table class="footer-bar">
    <tr>
        <td class="footer-left">{{ $footer['text'] }}</td>
        <td class="footer-center">{{ $footerContacts->implode(' | ') }}</td>
        <td class="footer-right">Generated {{ $generatedAt->format('Y-m-d') }}</td>
    </tr>
</table>

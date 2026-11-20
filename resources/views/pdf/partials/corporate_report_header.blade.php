<table class="report-header">
    <tr>
        <td class="report-header-company">
            @php($logoSource = ($isPdf ?? true) ? $reportSettings['logo_path'] : $reportSettings['logo_url'])
            @if($logoSource)
                <img src="{{ $logoSource }}" class="report-logo" alt="{{ $reportSettings['header']['company_name'] ?: 'Company logo' }}">
            @endif
            @if($reportSettings['header']['company_name'])
                <div class="report-company-name">{{ $reportSettings['header']['company_name'] }}</div>
            @endif
            @if($reportSettings['header']['trading_name'])
                <div class="report-company-line">{{ $reportSettings['header']['trading_name'] }}</div>
            @endif
            @if($reportSettings['header']['registration_no'] || $reportSettings['header']['pan_vat_no'])
                <div class="report-company-line">
                    @if($reportSettings['header']['registration_no'])
                        Registration No.: {{ $reportSettings['header']['registration_no'] }}
                    @endif
                    @if($reportSettings['header']['registration_no'] && $reportSettings['header']['pan_vat_no'])
                        &bull;
                    @endif
                    @if($reportSettings['header']['pan_vat_no'])
                        PAN/VAT: {{ $reportSettings['header']['pan_vat_no'] }}
                    @endif
                </div>
            @endif
            @foreach(['address', 'phone', 'email', 'website'] as $field)
                @if($reportSettings['header'][$field])
                    <div class="report-company-line">{{ $reportSettings['header'][$field] }}</div>
                @endif
            @endforeach
        </td>
        <td class="report-header-meta">
            <div class="report-title">{{ $reportTitle }}</div>
            <div class="report-meta">
                @foreach($reportMeta as $label => $value)
                    @if(filled($value))
                        <strong>{{ $label }}:</strong> {{ $value }}<br>
                    @endif
                @endforeach
            </div>
        </td>
    </tr>
</table>

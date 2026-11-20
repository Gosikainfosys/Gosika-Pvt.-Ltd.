@php
    $footerContacts = collect([
        $reportSettings['footer']['phone'],
        $reportSettings['footer']['email'],
        $reportSettings['footer']['website'],
    ])->filter(fn ($value) => filled($value))->values();
@endphp
@if(filled($reportSettings['footer']['text']) || $footerContacts->isNotEmpty())
    <div class="footer-note">
        @if(filled($reportSettings['footer']['text']))
            <div>{{ $reportSettings['footer']['text'] }}</div>
        @endif
        @if($footerContacts->isNotEmpty())
            <div>{{ $footerContacts->implode(' | ') }}</div>
        @endif
    </div>
@endif

<?php

namespace App\Services;

use App\Models\CompanySetting;
use Illuminate\Support\Facades\Storage;

class CorporateReportSettingsService
{
    public function getCompany(): ?CompanySetting
    {
        return CompanySetting::query()->first();
    }

    /**
     * Resolve the current company identity for a single report generation.
     * Settings are intentionally read fresh so updates apply to the next report.
     *
     * @return array<string, mixed>
     */
    public function forReport(): array
    {
        $company = $this->getCompany();
        $logoPath = null;
        $logoUrl = null;
        $stampSignaturePath = null;

        if ($company?->logo_path && Storage::disk('public')->exists($company->logo_path)) {
            $logoPath = Storage::disk('public')->path($company->logo_path);
            $logoUrl = Storage::disk('public')->url($company->logo_path);
        }

        if ($company?->stamp_signature_path && Storage::disk('public')->exists($company->stamp_signature_path)) {
            $stampSignaturePath = Storage::disk('public')->path($company->stamp_signature_path);
        }

        $address = collect([
            $company?->address,
            $company?->city,
            $company?->country,
        ])->filter(fn ($value) => filled($value))->implode(', ');

        return [
            'company' => $company,
            'header' => [
                'company_name' => $company?->company_name,
                'trading_name' => $company?->trading_name,
                'registration_no' => $company?->registration_no,
                'pan_vat_no' => $company?->pan_vat_no,
                'address' => $address,
                'phone' => $company?->phone,
                'email' => $company?->email,
                'website' => $company?->website,
            ],
            'footer' => [
                'text' => $company?->report_footer_text,
                'phone' => $company?->phone,
                'email' => $company?->email,
                'website' => $company?->website,
            ],
            'logo_path' => $logoPath,
            'logo_url' => $logoUrl,
            'stamp_signature_path' => $stampSignaturePath,
            'fiscal_year' => $company?->fiscal_year,
            'currency' => $company?->default_currency,
            'currency_symbol' => $company?->currency_symbol,
        ];
    }

    /**
     * Provide the same Settings values to browser report previews.
     *
     * @return array<string, mixed>|null
     */
    public function forInertia(): ?array
    {
        $settings = $this->forReport();
        $company = $settings['company'];
        if (! $company) {
            return null;
        }

        return [
            ...$settings['header'],
            'name' => $settings['header']['company_name'],
            'city' => $company->city,
            'country' => $company->country,
            'currency' => $settings['currency'],
            'currency_symbol' => $settings['currency_symbol'],
            'fiscal_year' => $settings['fiscal_year'],
            'logo_path' => $company->logo_path,
            'logo_url' => $settings['logo_url'],
            'stamp_signature_url' => $settings['stamp_signature_path']
                ? Storage::disk('public')->url($company->stamp_signature_path)
                : null,
            'report_footer_text' => $settings['footer']['text'],
        ];
    }
}

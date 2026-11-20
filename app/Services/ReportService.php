<?php

namespace App\Services;

use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportService
{
    public function __construct(
        private PdfDocumentService $documents,
        private CorporateReportSettingsService $corporateSettings
    ) {}

    public function generateReportPdf(string $view, array $data, string $orientation = 'portrait')
    {
        $data = $this->prepareReportData($data, true);

        return $this->documents->render("pdf.reports.{$view}", $data, 'a4', $orientation);
    }

    public function prepareReportData(array $data, bool $isPdf = false): array
    {
        $data['reportSettings'] = $this->corporateSettings->forReport();
        $data['company'] = $data['reportSettings']['company'];
        $data['generatedAt'] = $data['generatedAt'] ?? now();
        $data['preparedBy'] = $data['preparedBy'] ?? auth()->user()?->name;
        $data['verifiedBy'] = $data['verifiedBy'] ?? null;
        $data['authorizedBy'] = $data['authorizedBy'] ?? null;
        $data['isPdf'] = $isPdf;

        return $data;
    }

    public function generateCsvResponse(string $filename, array $headers, array $rows): StreamedResponse
    {
        $response = new StreamedResponse(function () use ($headers, $rows) {
            $handle = fopen('php://output', 'w');
            // Write UTF-8 BOM for Excel compatibility
            fprintf($handle, chr(0xEF).chr(0xBB).chr(0xBF));
            fputcsv($handle, $headers);

            foreach ($rows as $row) {
                fputcsv($handle, $row);
            }

            fclose($handle);
        });

        $response->headers->set('Content-Type', 'text/csv; charset=utf-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$filename.'"');

        return $response;
    }

}

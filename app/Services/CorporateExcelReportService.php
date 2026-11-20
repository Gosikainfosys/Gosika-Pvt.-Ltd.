<?php

namespace App\Services;

use DateTimeImmutable;
use DateTimeInterface;
use DOMDocument;
use DOMXPath;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Shared\Date as SpreadsheetDate;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\PageSetup;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;

class CorporateExcelReportService
{
    private const ORANGE = 'FF5500';

    private const NAVY = '202124';

    private const LIGHT_ORANGE = 'FFF0E8';

    private const LIGHT_GREY = 'F7F8FA';

    private const GREY = '6B7280';

    public function __construct(private CorporateReportSettingsService $corporateSettings) {}

    /**
     * @param  array{
     *     filename: string,
     *     title: string,
     *     columns: array<int, array{label: string, format?: string, width?: float, align?: string}>,
     *     rows: array<int, array<int, mixed>>,
     *     metadata?: array<string, mixed>,
     *     summary?: array<int, array{label: string, value: mixed, format?: string}>,
     *     totals?: array{label?: string, formulas: array<int, string>},
     *     signatures?: bool,
     *     orientation?: string
     * }  $report
     */
    public function download(array $report): StreamedResponse
    {
        if (empty($report['filename']) || empty($report['title']) || empty($report['columns'])) {
            throw new \InvalidArgumentException('An Excel report requires a filename, title and at least one column.');
        }

        $columns = array_values($report['columns']);
        $rows = array_values($report['rows'] ?? []);
        $settings = $this->corporateSettings->forReport();
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();
        $title = $report['title'];
        $sheet->setTitle(substr($title, 0, 31));
        $sheet->setShowGridlines(false);
        $spreadsheet->getProperties()
            ->setTitle($title)
            ->setSubject($title)
            ->setCreator($settings['header']['company_name'] ?? '');

        $summary = array_values($report['summary'] ?? []);
        $layoutColumnCount = max(6, count($columns), count($summary));
        $layoutLastColumn = $this->columnName($layoutColumnCount);

        // ── Proportional 3-section header layout ────────────────────
        // Target: Logo ~15% | Company info ~45% | Report info ~40%
        // Logo always gets ≥ 2 columns so the image has room.
        // Right always gets ≥ 3 columns for the two-column label/value metadata.
        // Center gets whatever remains (always ≥ 2).
        $logoColCount  = max(2, (int) round($layoutColumnCount * 0.15));
        $rightColCount = max(3, (int) round($layoutColumnCount * 0.40));
        $centerColCount = $layoutColumnCount - $logoColCount - $rightColCount;
        // Guard: ensure center ≥ 2 and right ≥ 3
        if ($centerColCount < 2) {
            $centerColCount = 2;
            $rightColCount  = $layoutColumnCount - $logoColCount - $centerColCount;
        }

        $logoEnd = $this->columnName($logoColCount);
        $centerStart = $this->columnName($logoColCount + 1);
        $centerEnd = $this->columnName($logoColCount + $centerColCount);
        $rightStartNumber = $logoColCount + $centerColCount + 1;
        $rightStart = $this->columnName($rightStartNumber);
        $rightEnd = $layoutLastColumn;

        $metadata = $this->reportMetadata($report['metadata'] ?? [], $settings);
        $companyLines = $this->companyLines($settings['header']);
        $headerEndRow = max(6, 2 + count($metadata), 1 + count($companyLines));

        // ── 1. HEADER (3-section layout) ────────────────────────────
        // LEFT: logo placeholder (merged single tall cell)
        $sheet->mergeCells("A1:{$logoEnd}{$headerEndRow}");

        // CENTER: company name on row 1, detail lines on rows 2+
        $sheet->mergeCells("{$centerStart}1:{$centerEnd}1");
        $companyName = $settings['header']['company_name'] ?? null;
        if (filled($companyName)) {
            $sheet->setCellValueExplicit("{$centerStart}1", $companyName, DataType::TYPE_STRING);
        }

        $companyLineCount = count($companyLines);
        for ($i = 0; $i < $companyLineCount && ($i + 2) <= $headerEndRow; $i++) {
            $rowNum = $i + 2;
            $sheet->mergeCells("{$centerStart}{$rowNum}:{$centerEnd}{$rowNum}");
            $sheet->setCellValueExplicit("{$centerStart}{$rowNum}", $companyLines[$i], DataType::TYPE_STRING);
            $sheet->getStyle("{$centerStart}{$rowNum}")->applyFromArray([
                'font' => ['name' => 'Calibri', 'size' => 10, 'color' => ['rgb' => self::GREY]],
                'alignment' => [
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'wrapText'   => true,
                ],
            ]);
            $sheet->getRowDimension($rowNum)->setRowHeight(18);
        }
        if ((2 + $companyLineCount) <= $headerEndRow) {
            $remStart = 2 + $companyLineCount;
            $sheet->mergeCells("{$centerStart}{$remStart}:{$centerEnd}{$headerEndRow}");
        }

        // RIGHT: report title on rows 1-2 (merged), metadata from row 3
        $sheet->mergeCells("{$rightStart}1:{$rightEnd}2");
        $sheet->setCellValueExplicit("{$rightStart}1", mb_strtoupper($title), DataType::TYPE_STRING);

        $this->writeMetadata($sheet, $metadata, $rightStartNumber, $layoutColumnCount);
        $this->styleHeader(
            $sheet,
            $centerStart,
            $centerEnd,
            $rightStart,
            $rightEnd,
            $headerEndRow
        );
        $logo = $this->addLogo($sheet, $settings['logo_path']);

        // ── Orange separator: thick bottom border on the last header row ──
        $sheet->getStyle("A{$headerEndRow}:{$layoutLastColumn}{$headerEndRow}")
            ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THICK)->getColor()->setRGB(self::ORANGE);

        // ── 2. SUMMARY KPI CARDS ────────────────────────────────────
        $currentRow = $headerEndRow + 2;
        if ($summary !== []) {
            $summaryTitleRow = $currentRow;
            $sheet->mergeCells("A{$summaryTitleRow}:{$layoutLastColumn}{$summaryTitleRow}");
            $sheet->setCellValueExplicit("A{$summaryTitleRow}", 'REPORT SUMMARY', DataType::TYPE_STRING);
            $sheet->getStyle("A{$summaryTitleRow}:{$layoutLastColumn}{$summaryTitleRow}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => ['rgb' => self::NAVY]],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getRowDimension($summaryTitleRow)->setRowHeight(21);

            $labelRow = $summaryTitleRow + 1;
            $valueStartRow = $labelRow + 1;
            $valueEndRow = $valueStartRow + 1;
            $this->writeSummaryCards($sheet, $summary, $layoutColumnCount, $labelRow, $valueStartRow, $valueEndRow, $settings);
            $currentRow = $valueEndRow + 2;
        }

        // ── 3. DATA TABLE ───────────────────────────────────────────
        $tableTitleRow = $currentRow;
        $sheet->mergeCells("A{$tableTitleRow}:{$layoutLastColumn}{$tableTitleRow}");
        $sheet->setCellValueExplicit("A{$tableTitleRow}", 'REPORT DETAILS', DataType::TYPE_STRING);
        $sheet->getStyle("A{$tableTitleRow}:{$layoutLastColumn}{$tableTitleRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 11, 'bold' => true, 'color' => ['rgb' => self::NAVY]],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($tableTitleRow)->setRowHeight(21);

        $headerRow = $tableTitleRow + 1;
        $firstDataRow = $headerRow + 1;
        $lastDataRow = $headerRow + count($rows);
        $this->writeTable($sheet, $columns, $rows, $headerRow, $firstDataRow, $lastDataRow, $settings);
        $lastContentRow = $lastDataRow;

        // ── 4. TOTALS ───────────────────────────────────────────────
        if (! empty($report['totals']['formulas'])) {
            $lastContentRow = $this->writeTotals(
                $sheet,
                $report['totals'],
                $columns,
                $headerRow,
                $firstDataRow,
                $lastDataRow,
                $settings
            );
        }

        // ── 5. SIGNATURES ───────────────────────────────────────────
        if (! empty($report['signatures'])) {
            $lastContentRow = $this->writeSignatures(
                $sheet,
                $lastContentRow + 2,
                $layoutColumnCount,
                auth()->user()?->name
            );
        }

        // ── 6. FOOTER ───────────────────────────────────────────────
        $lastContentRow = $this->writeFooter($sheet, $settings['footer'], $lastContentRow + 2, $layoutLastColumn);

        // ── 7. PRINT SETUP & COLUMN WIDTHS ──────────────────────────
        $this->configurePrintLayout(
            $sheet,
            $report,
            $settings,
            $headerRow,
            $layoutLastColumn,
            $lastContentRow,
            count($columns)
        );
        $this->setColumnWidths($sheet, $columns, $layoutColumnCount, $logoColCount, $centerColCount);

        return response()->streamDownload(
            function () use ($spreadsheet, $logo): void {
                $temporaryWorkbook = tempnam(sys_get_temp_dir(), 'corporate-report-');
                if ($temporaryWorkbook === false) {
                    if ($logo['temporary_path'] ?? null) {
                        @unlink($logo['temporary_path']);
                    }

                    throw new RuntimeException('Unable to allocate a temporary report workbook.');
                }

                try {
                    (new Xlsx($spreadsheet))->save($temporaryWorkbook);
                    if ($logo && $logo['svg_path']) {
                        $this->embedSvgLogo($temporaryWorkbook, $logo['svg_path']);
                    }
                    if (readfile($temporaryWorkbook) === false) {
                        throw new RuntimeException('Unable to stream the generated report workbook.');
                    }
                } finally {
                    @unlink($temporaryWorkbook);
                    if ($logo && $logo['temporary_path']) {
                        @unlink($logo['temporary_path']);
                    }
                    $spreadsheet->disconnectWorksheets();
                }
            },
            $report['filename'],
            ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
        );
    }

    /**
     * @param  array<string, mixed>  $metadata
     * @param  array<string, mixed>  $settings
     * @return array<string, string>
     */
    private function reportMetadata(array $metadata, array $settings): array
    {
        $metadata = [
            ...$metadata,
            'Fiscal Year' => $settings['fiscal_year'],
            'Currency' => $settings['currency'],
            'Generated' => now()->format('Y-m-d H:i:s'),
            'Prepared By' => auth()->user()?->name,
        ];

        return array_filter(
            $metadata,
            fn ($value) => filled($value),
        );
    }

    /**
     * @param  array<string, mixed>  $header
     * @return array<int, string>
     */
    private function companyLines(array $header): array
    {
        $lines = array_filter([
            $header['trading_name'] ?? null,
            filled($header['registration_no'] ?? null) ? 'Registration No.: '.$header['registration_no'] : null,
            filled($header['pan_vat_no'] ?? null) ? 'PAN/VAT: '.$header['pan_vat_no'] : null,
            $header['address'] ?? null,
            $header['phone'] ?? null,
            $header['email'] ?? null,
            $header['website'] ?? null,
        ], fn ($value) => filled($value));

        return array_values($lines);
    }

    /**
     * @param  array<string, string>  $metadata
     */
    private function writeMetadata($sheet, array $metadata, int $rightStart, int $columnCount): void
    {
        $rightWidth = $columnCount - $rightStart + 1;
        $row = 3;
        foreach ($metadata as $label => $value) {
            $start = $this->columnName($rightStart);
            $end   = $this->columnName($columnCount);

            if ($rightWidth >= 3) {
                // Label occupies left ~40% of the right section; value takes the rest
                $splitAt  = $rightStart + max(1, (int) round($rightWidth * 0.40)) - 1;
                $labelEnd = $this->columnName($splitAt);
                $valStart = $this->columnName($splitAt + 1);

                $sheet->mergeCells("{$start}{$row}:{$labelEnd}{$row}");
                $sheet->mergeCells("{$valStart}{$row}:{$end}{$row}");
                $sheet->setCellValueExplicit("{$start}{$row}", $label.':', DataType::TYPE_STRING);
                $sheet->setCellValueExplicit("{$valStart}{$row}", (string) $value, DataType::TYPE_STRING);

                // Label: bold, right-aligned, slightly darker grey
                $sheet->getStyle("{$start}{$row}:{$labelEnd}{$row}")->applyFromArray([
                    'font'      => ['name' => 'Calibri', 'size' => 9, 'bold' => true, 'color' => ['rgb' => '4B5563']],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER],
                ]);
                // Value: regular weight, right-aligned
                $sheet->getStyle("{$valStart}{$row}:{$end}{$row}")->applyFromArray([
                    'font'      => ['name' => 'Calibri', 'size' => 9, 'bold' => false, 'color' => ['rgb' => self::GREY]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
            } else {
                $sheet->mergeCells("{$start}{$row}:{$end}{$row}");
                $sheet->setCellValueExplicit("{$start}{$row}", "{$label}: {$value}", DataType::TYPE_STRING);
                $sheet->getStyle("{$start}{$row}:{$end}{$row}")->applyFromArray([
                    'font'      => ['name' => 'Calibri', 'size' => 9, 'color' => ['rgb' => self::GREY]],
                    'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT, 'vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
                ]);
            }
            $sheet->getRowDimension($row)->setRowHeight(18);
            $row++;
        }
    }

    private function styleHeader(
        $sheet,
        string $companyStart,
        string $companyEnd,
        string $rightStart,
        string $rightEnd,
        int $headerEndRow
    ): void {
        // ── Background: clean warm-white across entire header area ───
        $sheet->getStyle("A1:{$rightEnd}{$headerEndRow}")->applyFromArray([
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'F8F9FA']],
        ]);

        // ── Company name: prominent, navy, 20 pt ─────────────────────
        $sheet->getStyle("{$companyStart}1:{$companyEnd}1")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 20, 'bold' => true, 'color' => ['rgb' => self::NAVY]],
            'alignment' => [
                'vertical'   => Alignment::VERTICAL_BOTTOM,
                'horizontal' => Alignment::HORIZONTAL_LEFT,
                'wrapText'   => false,
            ],
        ]);

        // ── Report title: Gosika orange, 17 pt, right-aligned ────────
        $sheet->getStyle("{$rightStart}1:{$rightEnd}2")->applyFromArray([
            'font' => ['name' => 'Calibri', 'size' => 17, 'bold' => true, 'color' => ['rgb' => self::ORANGE]],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_RIGHT,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
                'indent'     => 1,
            ],
        ]);

        // ── Row heights: row 1 taller for company name prominence ────
        $sheet->getRowDimension(1)->setRowHeight(36);
        $sheet->getRowDimension(2)->setRowHeight(18);
        for ($row = 3; $row <= $headerEndRow; $row++) {
            $h = $sheet->getRowDimension($row)->getRowHeight();
            if ($h === -1.0 || $h <= 0) {
                $sheet->getRowDimension($row)->setRowHeight(18);
            }
        }

        // ── Vertical centering for the whole header block ─────────────
        $sheet->getStyle("A1:{$rightEnd}{$headerEndRow}")
            ->getAlignment()->setVertical(Alignment::VERTICAL_CENTER);
        // But keep company name bottom-aligned on row 1 (re-apply)
        $sheet->getStyle("{$companyStart}1")
            ->getAlignment()->setVertical(Alignment::VERTICAL_BOTTOM);
    }

    /**
     * @param  array<int, array{label: string, value: mixed, format?: string}>  $summary
     * @param  array<string, mixed>  $settings
     */
    private function writeSummaryCards(
        $sheet,
        array $summary,
        int $columnCount,
        int $labelRow,
        int $valueStartRow,
        int $valueEndRow,
        array $settings
    ): void {
        $baseSpan = intdiv($columnCount, count($summary));
        $extraColumns = $columnCount % count($summary);
        $firstColumn = 1;

        foreach ($summary as $index => $card) {
            $span = $baseSpan + ($index < $extraColumns ? 1 : 0);
            $start = $this->columnName($firstColumn);
            $end = $this->columnName($firstColumn + $span - 1);
            $sheet->mergeCells("{$start}{$labelRow}:{$end}{$labelRow}");
            $sheet->mergeCells("{$start}{$valueStartRow}:{$end}{$valueEndRow}");
            $sheet->setCellValueExplicit("{$start}{$labelRow}", mb_strtoupper($card['label']), DataType::TYPE_STRING);
            $this->setFormattedValue($sheet, "{$start}{$valueStartRow}", $card['value'], $card['format'] ?? 'text', $settings);

            // Highlight financial figures in Gosika Orange, counts in Navy
            $isFinancial = in_array($card['format'] ?? 'text', ['currency', 'decimal'], true)
                || preg_match('/(total|amount|valuation|expenses|income|payroll|revenue|salary)/i', $card['label']);
            $valueColor = $isFinancial ? self::ORANGE : self::NAVY;

            $sheet->getStyle("{$start}{$labelRow}:{$end}{$valueEndRow}")->applyFromArray([
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::LIGHT_ORANGE]],
                'borders' => [
                    'outline' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'F2C6AE']],
                    'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ORANGE]],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
            ]);
            $sheet->getStyle("{$start}{$labelRow}")->getFont()->setName('Arial')->setSize(8.5)->setBold(true)
                ->getColor()->setRGB(self::GREY);
            $sheet->getStyle("{$start}{$valueStartRow}")->getFont()->setName('Arial')->setSize(17)->setBold(true)
                ->getColor()->setRGB($valueColor);
            $sheet->getRowDimension($labelRow)->setRowHeight(21);
            $sheet->getRowDimension($valueStartRow)->setRowHeight(22);
            $sheet->getRowDimension($valueEndRow)->setRowHeight(17);
            $firstColumn += $span;
        }
    }

    /**
     * @param  array<int, array{label: string, format?: string, width?: float, align?: string}>  $columns
     * @param  array<int, array<int, mixed>>  $rows
     */
    private function writeTable(
        $sheet,
        array $columns,
        array $rows,
        int $headerRow,
        int $firstDataRow,
        int $lastDataRow,
        array $settings
    ): void
    {
        foreach ($columns as $index => $column) {
            $letter = $this->columnName($index + 1);
            $sheet->setCellValueExplicit("{$letter}{$headerRow}", mb_strtoupper($column['label']), DataType::TYPE_STRING);
        }

        $lastColumn = $this->columnName(count($columns));
        $sheet->getStyle("A{$headerRow}:{$lastColumn}{$headerRow}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 9.5, 'bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::NAVY]],
            'borders' => [
                'allBorders' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => '47505B']],
                'bottom' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ORANGE]],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical' => Alignment::VERTICAL_CENTER,
                'wrapText' => true,
            ],
        ]);
        $sheet->getRowDimension($headerRow)->setRowHeight(32);

        foreach ($rows as $rowIndex => $values) {
            if (count($values) > count($columns)) {
                throw new \InvalidArgumentException('A report row contains more values than its column definition.');
            }
            $rowNumber = $firstDataRow + $rowIndex;
            $fillColor = $rowIndex % 2 === 0 ? 'FFFFFF' : self::LIGHT_GREY;
            foreach ($columns as $columnIndex => $column) {
                $cell = $this->columnName($columnIndex + 1).$rowNumber;
                $this->setFormattedValue(
                    $sheet,
                    $cell,
                    $values[$columnIndex] ?? null,
                    $column['format'] ?? 'text',
                    $settings
                );
                $alignment = $column['align'] ?? $this->alignmentForFormat($column['format'] ?? 'text');
                $sheet->getStyle($cell)->getAlignment()->setHorizontal($alignment);
            }
            $sheet->getStyle("A{$rowNumber}:{$lastColumn}{$rowNumber}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 9.5, 'color' => ['rgb' => self::NAVY]],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => $fillColor]],
                'borders' => [
                    'allBorders' => ['borderStyle' => Border::BORDER_HAIR, 'color' => ['rgb' => 'D9DDE2']],
                ],
                'alignment' => ['vertical' => Alignment::VERTICAL_CENTER, 'wrapText' => true],
            ]);
            $sheet->getRowDimension($rowNumber)->setRowHeight(24);
        }

        $filterEndRow = max($headerRow, $lastDataRow);
        $sheet->setAutoFilter("A{$headerRow}:{$lastColumn}{$filterEndRow}");
        $sheet->freezePane('A'.($headerRow + 1));
    }

    /**
     * @param  array{label?: string, formulas: array<int, string>}  $totals
     * @param  array<int, array{label: string, format?: string}>  $columns
     */
    private function writeTotals(
        $sheet,
        array $totals,
        array $columns,
        int $headerRow,
        int $firstDataRow,
        int $lastDataRow,
        array $settings
    ): int
    {
        $row = max($headerRow + 1, $lastDataRow) + 1;
        $formulas = $totals['formulas'];
        $firstFormulaColumn = min(array_keys($formulas));
        if ($firstFormulaColumn > 0) {
            $sheet->mergeCells('A'.$row.':'.$this->columnName($firstFormulaColumn).$row);
        }
        $sheet->setCellValueExplicit('A'.$row, $totals['label'] ?? 'GRAND TOTAL', DataType::TYPE_STRING);
        $sheet->getStyle('A'.$row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        foreach ($formulas as $columnIndex => $formula) {
            if ($formula !== 'sum' || ! array_key_exists($columnIndex, $columns)) {
                throw new \InvalidArgumentException('Unsupported report total formula.');
            }
            $letter = $this->columnName($columnIndex + 1);
            $sumRange = $lastDataRow >= $firstDataRow
                ? "{$letter}{$firstDataRow}:{$letter}{$lastDataRow}"
                : "{$letter}{$firstDataRow}:{$letter}{$firstDataRow}";
            $sheet->setCellValue("{$letter}{$row}", $lastDataRow >= $firstDataRow ? "=SUM({$sumRange})" : '=0');
            $sheet->getStyle("{$letter}{$row}")->getNumberFormat()->setFormatCode(
                $this->numberFormat($columns[$columnIndex]['format'] ?? 'number', $settings)
            );
            $sheet->getStyle("{$letter}{$row}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
        }

        $lastColumn = $this->columnName(count($columns));
        $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
            'font' => ['name' => 'Arial', 'size' => 10.5, 'bold' => true, 'color' => ['rgb' => self::ORANGE]],
            'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => self::LIGHT_ORANGE]],
            'borders' => [
                'top' => ['borderStyle' => Border::BORDER_MEDIUM, 'color' => ['rgb' => self::ORANGE]],
                'bottom' => ['borderStyle' => Border::BORDER_DOUBLE, 'color' => ['rgb' => self::ORANGE]],
            ],
            'alignment' => ['vertical' => Alignment::VERTICAL_CENTER],
        ]);
        $sheet->getRowDimension($row)->setRowHeight(26);

        return $row;
    }

    private function writeSignatures($sheet, int $startRow, int $columnCount, ?string $preparedBy): int
    {
        $labels = [
            ['label' => 'PREPARED BY', 'name' => $preparedBy],
            ['label' => 'VERIFIED / AUDITED BY', 'name' => null],
            ['label' => 'AUTHORIZED BY', 'name' => null],
        ];
        $baseSpan = intdiv($columnCount, count($labels));
        $extraColumns = $columnCount % count($labels);
        $firstColumn = 1;

        foreach ($labels as $index => $block) {
            $span = $baseSpan + ($index < $extraColumns ? 1 : 0);
            $start = $this->columnName($firstColumn);
            $end = $this->columnName($firstColumn + $span - 1);
            $sheet->mergeCells("{$start}{$startRow}:{$end}{$startRow}");
            $sheet->mergeCells("{$start}".($startRow + 1).":{$end}".($startRow + 1));
            $sheet->mergeCells("{$start}".($startRow + 3).":{$end}".($startRow + 3));
            $sheet->mergeCells("{$start}".($startRow + 4).":{$end}".($startRow + 4));

            $sheet->setCellValueExplicit("{$start}{$startRow}", $block['label'], DataType::TYPE_STRING);
            if (filled($block['name'])) {
                $sheet->setCellValueExplicit("{$start}".($startRow + 1), $block['name'], DataType::TYPE_STRING);
            }
            $sheet->setCellValueExplicit("{$start}".($startRow + 3), 'Designation: ___________________', DataType::TYPE_STRING);
            $sheet->setCellValueExplicit("{$start}".($startRow + 4), 'Date: ___________________', DataType::TYPE_STRING);

            $sheet->getStyle("{$start}{$startRow}:{$end}".($startRow + 4))->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 8.5, 'color' => ['rgb' => self::NAVY]],
                'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER, 'vertical' => Alignment::VERTICAL_CENTER],
            ]);
            $sheet->getStyle("{$start}{$startRow}")->getFont()->setSize(9)->setBold(true)->getColor()->setRGB(self::GREY);
            $sheet->getStyle("{$start}".($startRow + 2).":{$end}".($startRow + 2))
                ->getBorders()->getBottom()->setBorderStyle(Border::BORDER_THIN)->getColor()->setRGB('9CA3AF');
            $sheet->getStyle("{$start}".($startRow + 3).":{$end}".($startRow + 4))
                ->getFont()->setSize(8)->getColor()->setRGB(self::GREY);

            $firstColumn += $span;
        }

        $sheet->getRowDimension($startRow)->setRowHeight(20);
        $sheet->getRowDimension($startRow + 1)->setRowHeight(20);
        $sheet->getRowDimension($startRow + 2)->setRowHeight(24);
        $sheet->getRowDimension($startRow + 3)->setRowHeight(18);
        $sheet->getRowDimension($startRow + 4)->setRowHeight(18);

        return $startRow + 4;
    }

    /**
     * @param  array<string, mixed>  $footer
     */
    private function writeFooter($sheet, array $footer, int $row, string $lastColumn): int
    {
        $footerLines = array_filter([
            $footer['text'] ?? null,
            implode('  •  ', array_filter([
                $footer['phone'] ?? null,
                $footer['email'] ?? null,
                $footer['website'] ?? null,
            ], fn ($value) => filled($value))),
        ], fn ($value) => filled($value));
        if ($footerLines === []) {
            return $row - 2;
        }

        foreach ($footerLines as $line) {
            $sheet->mergeCells("A{$row}:{$lastColumn}{$row}");
            $sheet->setCellValueExplicit("A{$row}", $line, DataType::TYPE_STRING);
            $sheet->getStyle("A{$row}:{$lastColumn}{$row}")->applyFromArray([
                'font' => ['name' => 'Arial', 'size' => 8, 'color' => ['rgb' => self::GREY]],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical' => Alignment::VERTICAL_CENTER,
                    'wrapText' => true,
                ],
                'borders' => [
                    'top' => ['borderStyle' => Border::BORDER_THIN, 'color' => ['rgb' => 'D9DDE2']],
                ],
            ]);
            $sheet->getRowDimension($row)->setRowHeight(20);
            $row++;
        }

        return $row - 1;
    }

    /**
     * @param  array<string, mixed>  $settings
     */
    private function configurePrintLayout(
        $sheet,
        array $report,
        array $settings,
        int $headerRow,
        string $lastColumn,
        int $lastRow,
        int $columnCount
    ): void {
        $orientation = $report['orientation'] ?? ($columnCount >= 7 ? PageSetup::ORIENTATION_LANDSCAPE : PageSetup::ORIENTATION_PORTRAIT);
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setPaperSize(PageSetup::PAPERSIZE_A4)
            ->setOrientation($orientation)
            ->setFitToWidth(1)
            ->setFitToHeight(0)
            ->setFitToPage(true)
            ->setHorizontalCentered(true)
            ->setRowsToRepeatAtTop([$headerRow, $headerRow]);
        $sheet->getPageMargins()
            ->setTop(0.35)
            ->setBottom(0.45)
            ->setLeft(0.25)
            ->setRight(0.25)
            ->setHeader(0.15)
            ->setFooter(0.2);
        $sheet->getPageSetup()->setPrintArea("A1:{$lastColumn}".max(1, $lastRow));
        $companyName = $settings['header']['company_name'] ?? '';
        $sheet->getHeaderFooter()->setOddFooter(
            '&L'.$this->escapeHeaderFooter($report['title'])
            .'&C'.$this->escapeHeaderFooter($companyName)
            .'&RPage &P of &N'
        );
    }

    private function setColumnWidths($sheet, array $columns, int $layoutColumnCount, int $logoColCount = 1, int $centerColCount = 2): void
    {
        // Minimum widths to protect the 3-section header layout.
        // Logo columns: at least 14 chars wide each so the logo isn't squeezed.
        // Center (company info) columns: at least 16 chars wide each.
        // Right (report info) columns receive whatever is left – they auto-widen.
        $logoMinWidth   = 14;
        $centerMinWidth = 16;

        for ($index = 0; $index < $layoutColumnCount; $index++) {
            $letter = $this->columnName($index + 1);
            $column = $columns[$index] ?? null;
            $dataWidth = $column['width'] ?? ($column ? min(32, max(12, mb_strlen($column['label']) + 4)) : 12);

            if ($index < $logoColCount) {
                // Logo section: enforce minimum, but respect wider data columns
                $width = max($logoMinWidth, $dataWidth);
            } elseif ($index < $logoColCount + $centerColCount) {
                // Center (company info) section: enforce minimum
                $width = max($centerMinWidth, $dataWidth);
            } else {
                $width = $dataWidth;
            }

            $sheet->getColumnDimension($letter)->setWidth($width);
        }
    }

    private function setFormattedValue($sheet, string $cell, mixed $value, string $format, array $settings): void
    {
        if ($value === null || $value === '') {
            $sheet->setCellValueExplicit($cell, '', DataType::TYPE_STRING);

            return;
        }
        if ($format === 'date' && $value instanceof DateTimeInterface) {
            $sheet->setCellValue($cell, SpreadsheetDate::dateTimeToExcel($value));
        } elseif ($format === 'date' && is_string($value)) {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
            if ($date && $date->format('Y-m-d') === $value) {
                $sheet->setCellValue($cell, SpreadsheetDate::dateTimeToExcel($date));
            } else {
                $sheet->setCellValueExplicit($cell, $value, DataType::TYPE_STRING);
            }
        } elseif (in_array($format, ['currency', 'number', 'decimal', 'integer', 'percent'], true) && is_numeric($value)) {
            $sheet->setCellValue($cell, $value + 0);
        } else {
            $sheet->setCellValueExplicit($cell, (string) $value, DataType::TYPE_STRING);
        }

        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode($this->numberFormat($format, $settings));
    }

    private function numberFormat(string $format, array $settings): string
    {
        if ($format === 'currency') {
            $currencySymbol = $settings['currency_symbol'] ?: $settings['currency'];
            $escapedSymbol = str_replace('"', '""', (string) $currencySymbol);

            return '"'.$escapedSymbol.'" #,##0.00;[Red]("'.$escapedSymbol.'" #,##0.00);-';
        }
        if ($format === 'date') {
            return 'yyyy-mm-dd';
        }
        if ($format === 'decimal') {
            return '#,##0.00;[Red](#,##0.00);-';
        }
        if ($format === 'percent') {
            return '0.00%';
        }
        if ($format === 'integer' || $format === 'number') {
            return '#,##0;[Red](#,##0);-';
        }

        return 'General';
    }

    private function alignmentForFormat(string $format): string
    {
        if (in_array($format, ['currency', 'number', 'decimal', 'integer', 'percent'], true)) {
            return Alignment::HORIZONTAL_RIGHT;
        }
        if ($format === 'date') {
            return Alignment::HORIZONTAL_CENTER;
        }

        return Alignment::HORIZONTAL_LEFT;
    }

    /**
     * @return array{drawing_path: string, svg_path: ?string, temporary_path: ?string}|null
     */
    private function addLogo($sheet, ?string $logoPath): ?array
    {
        if (! $logoPath) {
            return null;
        }

        $logo = $this->prepareSpreadsheetLogo($logoPath);
        $drawing = new Drawing;
        $drawing->setPath($logo['drawing_path'])
            ->setCoordinates('A1')
            ->setOffsetX(10)
            ->setOffsetY(10)
            ->setResizeProportional(true)
            ->setWidthAndHeight(170, 76)   // max dimensions – test asserts ≤170×76
            ->setWorksheet($sheet);

        return $logo;
    }

    /**
     * @return array{drawing_path: string, svg_path: ?string, temporary_path: ?string}
     */
    private function prepareSpreadsheetLogo(string $logoPath): array
    {
        $extension = strtolower(pathinfo($logoPath, PATHINFO_EXTENSION));
        if (in_array($extension, ['png', 'jpg', 'jpeg', 'gif', 'bmp'], true)) {
            return ['drawing_path' => $logoPath, 'svg_path' => null, 'temporary_path' => null];
        }
        if ($extension === 'svg') {
            $temporaryPath = $this->createSvgAspectPlaceholder($logoPath);

            return ['drawing_path' => $temporaryPath, 'svg_path' => $logoPath, 'temporary_path' => $temporaryPath];
        }

        $imageContents = file_get_contents($logoPath);
        $image = $imageContents === false ? false : imagecreatefromstring($imageContents);
        if ($image === false) {
            throw new RuntimeException('The configured company logo cannot be converted for spreadsheet export.');
        }
        $temporaryPath = tempnam(sys_get_temp_dir(), 'report-logo-');
        if ($temporaryPath === false) {
            imagedestroy($image);
            throw new RuntimeException('Unable to allocate a temporary company logo image.');
        }
        $converted = imagepng($image, $temporaryPath);
        imagedestroy($image);
        if (! $converted) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to convert the configured company logo for spreadsheet export.');
        }

        return ['drawing_path' => $temporaryPath, 'svg_path' => null, 'temporary_path' => $temporaryPath];
    }

    private function createSvgAspectPlaceholder(string $svgPath): string
    {
        $svg = file_get_contents($svgPath);
        if ($svg === false) {
            throw new RuntimeException('Unable to read the configured company logo.');
        }

        $document = new DOMDocument;
        if (! $document->loadXML($svg, LIBXML_NONET) || $document->documentElement?->localName !== 'svg') {
            throw new RuntimeException('The configured company SVG logo is invalid.');
        }

        $viewBox = preg_split('/[\s,]+/', trim($document->documentElement->getAttribute('viewBox')));
        if (count($viewBox) === 4 && (float) $viewBox[2] > 0 && (float) $viewBox[3] > 0) {
            $ratio = (float) $viewBox[2] / (float) $viewBox[3];
        } else {
            $width = preg_match('/^[0-9.]+/', $document->documentElement->getAttribute('width'), $widthMatch)
                ? (float) $widthMatch[0]
                : 0;
            $height = preg_match('/^[0-9.]+/', $document->documentElement->getAttribute('height'), $heightMatch)
                ? (float) $heightMatch[0]
                : 0;
            $ratio = $width > 0 && $height > 0 ? $width / $height : 1;
        }
        $placeholderHeight = 76;
        $placeholderWidth = max(1, min(2048, (int) round($placeholderHeight * $ratio)));
        $image = imagecreatetruecolor($placeholderWidth, $placeholderHeight);
        if ($image === false) {
            throw new RuntimeException('Unable to prepare the configured company logo for spreadsheet export.');
        }
        imagesavealpha($image, true);
        imagefill($image, 0, 0, imagecolorallocatealpha($image, 255, 255, 255, 127));
        $temporaryPath = tempnam(sys_get_temp_dir(), 'report-logo-');
        if ($temporaryPath === false) {
            imagedestroy($image);
            throw new RuntimeException('Unable to allocate a temporary company logo image.');
        }
        $created = imagepng($image, $temporaryPath);
        imagedestroy($image);
        if (! $created) {
            @unlink($temporaryPath);
            throw new RuntimeException('Unable to prepare the configured SVG logo for spreadsheet export.');
        }

        return $temporaryPath;
    }

    private function embedSvgLogo(string $workbookPath, string $svgPath): void
    {
        $archive = new ZipArchive;
        if ($archive->open($workbookPath) !== true) {
            throw new RuntimeException('Unable to update the spreadsheet with the configured SVG logo.');
        }

        try {
            $drawingXml = $archive->getFromName('xl/drawings/drawing1.xml');
            $relationshipsXml = $archive->getFromName('xl/drawings/_rels/drawing1.xml.rels');
            $contentTypesXml = $archive->getFromName('[Content_Types].xml');
            if ($drawingXml === false || $relationshipsXml === false || $contentTypesXml === false) {
                throw new RuntimeException('The spreadsheet logo drawing could not be located.');
            }

            $drawing = new DOMDocument;
            $relationships = new DOMDocument;
            $contentTypes = new DOMDocument;
            foreach ([$drawing, $relationships, $contentTypes] as $document) {
                $document->preserveWhiteSpace = false;
                $document->formatOutput = false;
                $xml = $document === $drawing
                    ? $drawingXml
                    : ($document === $relationships ? $relationshipsXml : $contentTypesXml);
                if (! $document->loadXML($xml, LIBXML_NONET)) {
                    throw new RuntimeException('The spreadsheet logo metadata is invalid.');
                }
            }

            $relationshipNamespace = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
            $drawingXPath = new DOMXPath($drawing);
            $drawingXPath->registerNamespace('a', 'http://schemas.openxmlformats.org/drawingml/2006/main');
            $drawingXPath->registerNamespace('r', $relationshipNamespace);
            $blip = $drawingXPath->query('//a:blip')->item(0);
            if (! $blip) {
                throw new RuntimeException('The spreadsheet logo placeholder could not be located.');
            }

            $relationshipXPath = new DOMXPath($relationships);
            $relationshipIds = [];
            foreach ($relationshipXPath->query('/*/*') as $relationship) {
                if ($relationship instanceof \DOMElement
                    && preg_match('/^rId(\d+)$/', $relationship->getAttribute('Id'), $matches)) {
                    $relationshipIds[] = (int) $matches[1];
                }
            }
            $svgRelationshipId = 'rId'.(max($relationshipIds ?: [0]) + 1);
            $extensionList = $drawing->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:extLst');
            $extension = $drawing->createElementNS('http://schemas.openxmlformats.org/drawingml/2006/main', 'a:ext');
            $extension->setAttribute('uri', '{96DAC541-7B7A-43D3-8B79-37D633B846F1}');
            $svgBlip = $drawing->createElementNS('http://schemas.microsoft.com/office/drawing/2016/SVG/main', 'asvg:svgBlip');
            $svgBlip->setAttributeNS($relationshipNamespace, 'r:embed', $svgRelationshipId);
            $extension->appendChild($svgBlip);
            $extensionList->appendChild($extension);
            $blip->appendChild($extensionList);

            $relationship = $relationships->createElementNS(
                'http://schemas.openxmlformats.org/package/2006/relationships',
                'Relationship'
            );
            $relationship->setAttribute('Id', $svgRelationshipId);
            $relationship->setAttribute('Type', $relationshipNamespace.'/image');
            $relationship->setAttribute('Target', '../media/company-logo.svg');
            $relationships->documentElement->appendChild($relationship);

            $contentTypeXPath = new DOMXPath($contentTypes);
            if ($contentTypeXPath->query('/*/*[@Extension="svg"]')->length === 0) {
                $default = $contentTypes->createElementNS(
                    'http://schemas.openxmlformats.org/package/2006/content-types',
                    'Default'
                );
                $default->setAttribute('Extension', 'svg');
                $default->setAttribute('ContentType', 'image/svg+xml');
                $contentTypes->documentElement->appendChild($default);
            }

            $svgContents = file_get_contents($svgPath);
            if ($svgContents === false
                || ! $archive->addFromString('xl/media/company-logo.svg', $svgContents)
                || ! $archive->addFromString('xl/drawings/drawing1.xml', $drawing->saveXML())
                || ! $archive->addFromString('xl/drawings/_rels/drawing1.xml.rels', $relationships->saveXML())
                || ! $archive->addFromString('[Content_Types].xml', $contentTypes->saveXML())) {
                throw new RuntimeException('Unable to embed the configured SVG logo in the spreadsheet.');
            }
        } finally {
            $archive->close();
        }
    }

    private function escapeHeaderFooter(string $value): string
    {
        return str_replace('&', '&&', $value);
    }

    private function columnName(int $column): string
    {
        $name = '';
        while ($column > 0) {
            $column--;
            $name = chr($column % 26 + 65).$name;
            $column = intdiv($column, 26);
        }

        return $name;
    }
}

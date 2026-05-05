<?php
require_once __DIR__ . '/../config.php';

class PdfExporter
{
    private const PAGE_WIDTH = 841.89;
    private const PAGE_HEIGHT = 595.28;
    private const LEFT_MARGIN = 28;
    private const CONTENT_WIDTH = 785.89;
    private const ROW_HEIGHT = 20;
    private const TITLE_FONT_SIZE = 18;
    private const SUBTITLE_FONT_SIZE = 10;
    private const FONT_SIZE = 9;

    private array $pages = [];

    public function generateUsersReport(array $users): string
    {
        $columns = [
            ['label' => 'ID', 'width' => 28],
            ['label' => 'Nom', 'width' => 122],
            ['label' => 'Email', 'width' => 175],
            ['label' => 'Role', 'width' => 58],
            ['label' => 'Preference', 'width' => 150],
            ['label' => 'Date inscription', 'width' => 120],
            ['label' => 'Statut', 'width' => 82],
        ];

        $rowsPerPage = 18;
        $chunks = array_chunk($users, $rowsPerPage);
        if (empty($chunks)) {
            $chunks = [[]];
        }

        $totalPages = count($chunks);
        foreach ($chunks as $pageIndex => $pageUsers) {
            $this->pages[] = $this->buildPageContent($pageUsers, $columns, $pageIndex + 1, $totalPages);
        }

        return $this->buildPdfDocument();
    }

    private function buildPageContent(array $users, array $columns, int $pageNumber, int $totalPages): string
    {
        $content = [];
        $this->writeText($content, 248, 540, 'Liste des Utilisateurs - NutriSmart', self::TITLE_FONT_SIZE);
        $this->writeText($content, 272, 520, 'Export backoffice genere le ' . date('d/m/Y H:i'), self::SUBTITLE_FONT_SIZE);

        $tableTop = 490;
        $this->drawTableHeader($content, $columns, $tableTop);

        $rowY = $tableTop - self::ROW_HEIGHT;
        if (!empty($users)) {
            foreach ($users as $user) {
                $this->drawTableRow($content, $columns, $rowY, [
                    (string) ($user['id'] ?? ''),
                    (string) ($user['nom'] ?? ''),
                    (string) ($user['email'] ?? ''),
                    ucfirst((string) ($user['role'] ?? '')),
                    ucfirst((string) ($user['preference_alimentaire'] ?? '')),
                    $this->formatDate((string) ($user['date_inscription'] ?? '')),
                    ((int) ($user['statut_compte'] ?? 0) === 1) ? 'Actif' : 'Inactif',
                ]);
                $rowY -= self::ROW_HEIGHT;
            }
        } else {
            $this->drawEmptyRow($content, $columns, $rowY, 'Aucun utilisateur a exporter');
        }

        $this->writeText($content, 28, 20, 'Page ' . $pageNumber . ' / ' . $totalPages, 9);

        return implode("\n", $content);
    }

    private function drawTableHeader(array &$content, array $columns, float $y): void
    {
        $x = self::LEFT_MARGIN;
        $content[] = '0.86 0.91 0.98 rg';
        foreach ($columns as $column) {
            $content[] = sprintf('%.2F %.2F %.2F %.2F re f', $x, $y - self::ROW_HEIGHT, $column['width'], self::ROW_HEIGHT);
            $x += $column['width'];
        }

        $content[] = '0 0 0 rg';
        $x = self::LEFT_MARGIN;
        foreach ($columns as $column) {
            $content[] = sprintf('%.2F %.2F %.2F %.2F re S', $x, $y - self::ROW_HEIGHT, $column['width'], self::ROW_HEIGHT);
            $this->writeText($content, $x + 4, $y - 13, $column['label'], self::FONT_SIZE);
            $x += $column['width'];
        }
    }

    private function drawTableRow(array &$content, array $columns, float $y, array $values): void
    {
        $x = self::LEFT_MARGIN;
        foreach ($columns as $index => $column) {
            $content[] = sprintf('%.2F %.2F %.2F %.2F re S', $x, $y - self::ROW_HEIGHT, $column['width'], self::ROW_HEIGHT);
            $text = $this->truncateText($values[$index] ?? '', $column['width']);
            $this->writeText($content, $x + 4, $y - 13, $text, self::FONT_SIZE);
            $x += $column['width'];
        }
    }

    private function drawEmptyRow(array &$content, array $columns, float $y, string $message): void
    {
        $content[] = sprintf('%.2F %.2F %.2F %.2F re S', self::LEFT_MARGIN, $y - self::ROW_HEIGHT, self::CONTENT_WIDTH, self::ROW_HEIGHT);
        $this->writeText($content, self::LEFT_MARGIN + 4, $y - 13, $message, self::FONT_SIZE);
    }

    private function buildPdfDocument(): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[2] = '<< /Type /Pages /Kids [';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';

        $pageObjectStart = 4;
        $contentObjectStart = $pageObjectStart + count($this->pages);
        $kids = [];

        for ($i = 0; $i < count($this->pages); $i++) {
            $pageObjectId = $pageObjectStart + $i;
            $contentObjectId = $contentObjectStart + $i;
            $kids[] = $pageObjectId . ' 0 R';
            $objects[$pageObjectId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2F %.2F] /Resources << /Font << /F1 3 0 R >> >> /Contents %d 0 R >>',
                self::PAGE_WIDTH,
                self::PAGE_HEIGHT,
                $contentObjectId
            );

            $stream = $this->pages[$i];
            $objects[$contentObjectId] = '<< /Length ' . strlen($stream) . " >>\nstream\n" . $stream . "\nendstream";
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $kids) . '] /Count ' . count($this->pages) . ' >>';

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        ksort($objects);
        foreach ($objects as $objectId => $objectBody) {
            $offsets[$objectId] = strlen($pdf);
            $pdf .= $objectId . " 0 obj\n" . $objectBody . "\nendobj\n";
        }

        $xrefPosition = strlen($pdf);
        $maxObjectId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxObjectId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObjectId; $i++) {
            $pdf .= sprintf('%010d 00000 n %s', $offsets[$i] ?? 0, "\n");
        }

        $pdf .= "trailer\n<< /Size " . ($maxObjectId + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefPosition . "\n%%EOF";

        return $pdf;
    }

    private function writeText(array &$content, float $x, float $y, string $text, int $fontSize): void
    {
        $escaped = $this->escapePdfText($this->toPdfEncoding($text));
        $content[] = 'BT';
        $content[] = '/F1 ' . $fontSize . ' Tf';
        $content[] = sprintf('1 0 0 1 %.2F %.2F Tm', $x, $y);
        $content[] = '(' . $escaped . ') Tj';
        $content[] = 'ET';
    }

    private function formatDate(string $date): string
    {
        if ($date === '') {
            return '';
        }

        $timestamp = strtotime($date);
        return $timestamp !== false ? date('d/m/Y H:i', $timestamp) : $date;
    }

    private function truncateText(string $text, int $columnWidth): string
    {
        $maxChars = max(8, (int) floor($columnWidth / 5.5));
        if (function_exists('mb_strimwidth')) {
            return mb_strimwidth($text, 0, $maxChars, '...');
        }

        return strlen($text) > $maxChars ? substr($text, 0, $maxChars - 3) . '...' : $text;
    }

    private function toPdfEncoding(string $text): string
    {
        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT', $text);
        if ($converted !== false) {
            return $converted;
        }

        return utf8_decode($text);
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}

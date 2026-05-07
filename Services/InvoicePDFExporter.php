<?php


class InvoicePDFExporter {
    private array $pages = [];
    private string $current = '';
    private int $pageCount = 0;

    public static function generateInvoice(array $orderData, array $products): string {
        $pdf = new self();
        return $pdf->buildInvoice($orderData, $products);
    }

    public static function generateFromHtml($htmlContent, $filename = 'invoice.pdf') {
        $text = trim(html_entity_decode(strip_tags(preg_replace('/<style\b[^>]*>.*?<\/style>/is', '', $htmlContent)), ENT_QUOTES, 'UTF-8'));
        $pdf = new self();
        return $pdf->buildTextPdf($text);
    }

    private function buildInvoice(array $orderData, array $products): string {
        $isPreOrder = !empty($orderData['is_pre_order']) || ($orderData['statut'] ?? '') === 'pre-order';
        $documentTitle = $isPreOrder ? 'Facture de pre-commande' : 'Facture de commande';
        $footerText = $isPreOrder
            ? 'Merci d avoir choisi Stabilis. Votre pre-commande est prioritaire.'
            : 'Merci d avoir choisi Stabilis. Votre commande est en cours de traitement.';

        $this->addPage();
        $this->rect(0, 772, 595, 70, '1A4D3A');
        $this->rect(0, 767, 595, 5, 'C6A15B');
        $this->text(42, 812, 'Stabilis', 24, 'FFFFFF', true);
        $this->text(42, 792, $documentTitle, 12, 'D4E6DE');

        $orderId = $orderData['id'] ?? 'N/A';
        $date = date('d/m/Y H:i', strtotime($orderData['date_commande'] ?? 'now'));
        $customer = trim(($orderData['prenom'] ?? '') . ' ' . ($orderData['nom'] ?? ''));

        $this->sectionTitle(42, 730, 'Informations');
        $this->text(42, 708, 'Commande: ' . $orderId, 11, '2B2D2A', true);
        $this->text(42, 690, 'Date: ' . $date, 10, '6E6E68');
        $this->text(310, 708, 'Client: ' . $customer, 11, '2B2D2A', true);
        $this->text(310, 690, 'Email: ' . ($orderData['email'] ?? ''), 10, '6E6E68');

        $this->sectionTitle(42, 650, 'Adresse de livraison');
        $this->text(42, 628, $customer, 10, '2B2D2A', true);
        $this->text(42, 611, (string)($orderData['adresse'] ?? ''), 10, '6E6E68');
        $this->text(42, 594, trim(($orderData['ville'] ?? '') . ' ' . ($orderData['code_postal'] ?? '')), 10, '6E6E68');
        $this->text(310, 628, 'Telephone: ' . ($orderData['telephone'] ?? 'N/A'), 10, '6E6E68');
        $this->text(310, 611, 'Paiement: ' . ($orderData['paiement'] ?? 'N/A'), 10, '6E6E68');

        $y = 545;
        $this->sectionTitle(42, $y + 35, 'Articles commandes');
        $this->rect(42, $y, 511, 24, 'FCFCFA');
        $this->text(54, $y + 8, 'Produit', 9, '6E6E68', true);
        $this->text(300, $y + 8, 'Qte', 9, '6E6E68', true);
        $this->text(365, $y + 8, 'Prix unit.', 9, '6E6E68', true);
        $this->text(470, $y + 8, 'Total', 9, '6E6E68', true);

        $totalAmount = 0;
        $y -= 28;
        foreach ($products as $product) {
            $quantity = (int)($product['quantite'] ?? 0);
            $unitPrice = (float)($product['prix'] ?? 0);
            $originalPrice = (float)($product['prix_original'] ?? $unitPrice);
            $lineTotal = $quantity * $unitPrice;
            $totalAmount += $lineTotal;
            $unitPriceText = number_format($unitPrice, 2, ',', ' ') . ' EUR';
            if ($originalPrice > $unitPrice) {
                $unitPriceText = number_format($originalPrice, 2, ',', ' ') . ' > ' . $unitPriceText;
            }

            $this->text(54, $y, $this->clean($product['nom'] ?? 'Produit', 42), 10, '2B2D2A', true);
            $this->text(306, $y, (string)$quantity, 10, '2B2D2A');
            $this->text(365, $y, $unitPriceText, 10, '2B2D2A');
            $this->text(470, $y, number_format($lineTotal, 2, ',', ' ') . ' EUR', 10, '1A4D3A', true);
            $this->line(42, $y - 10, 553, $y - 10, 'EDEDE9');
            $y -= 24;
        }

        $discountPercent = (float)($orderData['discount_percent'] ?? 0);
        $discountAmount = (float)($orderData['discount_amount'] ?? 0);
        $finalTotal = (float)($orderData['final_total'] ?? 0);
        if ($finalTotal <= 0) {
            $finalTotal = $totalAmount - $discountAmount;
        }

        $y -= 10;
        $this->text(365, $y, 'Sous-total', 10, '6E6E68');
        $this->text(470, $y, number_format($totalAmount, 2, ',', ' ') . ' EUR', 10, '2B2D2A');
        $y -= 20;
        if ($discountPercent > 0) {
            $this->text(365, $y, 'Reduction -' . number_format($discountPercent, 0) . '%', 10, '3A6B4B');
            $this->text(470, $y, '-' . number_format($discountAmount, 2, ',', ' ') . ' EUR', 10, '3A6B4B');
            $y -= 20;
        }
        $this->line(350, $y + 8, 553, $y + 8, 'EDEDE9');
        $this->text(365, $y - 8, 'Total final', 13, '1A4D3A', true);
        $this->text(470, $y - 8, number_format($finalTotal, 2, ',', ' ') . ' EUR', 13, '1A4D3A', true);

        $this->rect(42, 55, 511, 38, 'E8F0E9');
        $this->text(58, 77, $footerText, 10, '1A4D3A', true);
        $this->text(42, 28, 'Stabilis - Nutrition adaptative et durable', 9, '6E6E68');

        return $this->output();
    }

    private function buildTextPdf(string $text): string {
        $this->addPage();
        $this->rect(0, 785, 595, 57, '1A4D3A');
        $this->text(42, 810, 'Stabilis', 22, 'FFFFFF', true);
        $y = 745;
        foreach (explode("\n", wordwrap($text, 90)) as $line) {
            $this->text(42, $y, $line, 10, '2B2D2A');
            $y -= 14;
            if ($y < 50) {
                break;
            }
        }
        return $this->output();
    }

    private function addPage(): void {
        if ($this->current !== '') {
            $this->pages[] = $this->current;
        }
        $this->current = '';
        $this->pageCount++;
    }

    private function sectionTitle(float $x, float $y, string $text): void {
        $this->text($x, $y, $text, 11, '6E6E68', true);
        $this->line($x, $y - 10, 553, $y - 10, 'EDEDE9');
    }

    private function rect(float $x, float $y, float $w, float $h, string $hex): void {
        [$r, $g, $b] = $this->rgb($hex);
        $this->current .= sprintf("%.3f %.3f %.3f rg %.2f %.2f %.2f %.2f re f\n", $r, $g, $b, $x, $y, $w, $h);
    }

    private function line(float $x1, float $y1, float $x2, float $y2, string $hex): void {
        [$r, $g, $b] = $this->rgb($hex);
        $this->current .= sprintf("%.3f %.3f %.3f RG %.2f %.2f m %.2f %.2f l S\n", $r, $g, $b, $x1, $y1, $x2, $y2);
    }

    private function text(float $x, float $y, string $text, int $size = 10, string $hex = '000000', bool $bold = false): void {
        [$r, $g, $b] = $this->rgb($hex);
        $font = $bold ? 'F2' : 'F1';
        $text = $this->escape($this->clean($text));
        $this->current .= sprintf("BT %.3f %.3f %.3f rg /%s %d Tf %.2f %.2f Td (%s) Tj ET\n", $r, $g, $b, $font, $size, $x, $y, $text);
    }

    private function clean(string $text, int $limit = 120): string {
        $text = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
        $text = preg_replace('/[^\x20-\x7E]/', '', $text);
        return substr($text, 0, $limit);
    }

    private function escape(string $text): string {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }

    private function rgb(string $hex): array {
        $hex = ltrim($hex, '#');
        return [
            hexdec(substr($hex, 0, 2)) / 255,
            hexdec(substr($hex, 2, 2)) / 255,
            hexdec(substr($hex, 4, 2)) / 255
        ];
    }

    private function output(): string {
        if ($this->current !== '') {
            $this->pages[] = $this->current;
            $this->current = '';
        }

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [];
        $objects = [];

        $objects[] = "<< /Type /Catalog /Pages 2 0 R >>";
        $kids = [];
        $pageObjectStart = 3;
        $contentObjectStart = $pageObjectStart + count($this->pages);

        foreach ($this->pages as $i => $content) {
            $kids[] = ($pageObjectStart + $i) . " 0 R";
        }

        $objects[] = "<< /Type /Pages /Kids [" . implode(' ', $kids) . "] /Count " . count($this->pages) . " >>";

        foreach ($this->pages as $i => $content) {
            $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 " . ($contentObjectStart + count($this->pages)) . " 0 R /F2 " . ($contentObjectStart + count($this->pages) + 1) . " 0 R >> >> /Contents " . ($contentObjectStart + $i) . " 0 R >>";
        }

        foreach ($this->pages as $content) {
            $objects[] = "<< /Length " . strlen($content) . " >>\nstream\n$content\nendstream";
        }

        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>";
        $objects[] = "<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>";

        foreach ($objects as $i => $object) {
            $num = $i + 1;
            $offsets[$num] = strlen($pdf);
            $pdf .= "$num 0 obj\n$object\nendobj\n";
        }

        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f\n";
        for ($i = 1; $i <= count($objects); $i++) {
            $pdf .= str_pad((string)$offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n\n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\nstartxref\n$xref\n%%EOF\n";

        return $pdf;
    }
}
?>

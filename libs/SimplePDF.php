<?php
/**
 * SimplePDF - Lightweight PDF Generator
 * Generates PDF files from HTML content
 */

class SimplePDF {
    private $pdf_version = '1.4';
    private $objects = [];
    private $object_count = 0;
    private $pages = [];
    private $current_page = 0;
    
    public function __construct() {
        $this->object_count = 0;
        $this->objects = [];
        $this->pages = [];
    }

    /**
     * Generate PDF from HTML string
     * Returns PDF binary content
     */
    public static function generateFromHtml($html, $title = 'Document') {
        $pdf = new self();
        
        // Simple HTML to PDF conversion
        $content = $pdf->stripHtmlTags($html);
        $lines = $pdf->wrapText($content, 190);
        
        $pdf->addPage();
        $pdf->setTitle($title);
        $pdf->setFont('Arial', '', 11);
        
        foreach ($lines as $line) {
            $pdf->cell(0, 10, $line, 0, 1);
        }
        
        return $pdf->output();
    }

    /**
     * Strip HTML tags from content
     */
    private function stripHtmlTags($html) {
        // Remove style and script tags
        $html = preg_replace('/<style\b[^>]*>(.*?)<\/style>/is', '', $html);
        $html = preg_replace('/<script\b[^>]*>(.*?)<\/script>/is', '', $html);
        
        // Replace line breaks
        $html = str_replace(['</p>', '</div>', '</tr>', '</td>', '<br>', '<br/>'], "\n", $html);
        
        // Remove all HTML tags
        $html = strip_tags($html);
        
        // Decode HTML entities
        $html = html_entity_decode($html, ENT_QUOTES, 'UTF-8');
        
        return $html;
    }

    /**
     * Wrap text to fit page width
     */
    private function wrapText($text, $width = 190) {
        $lines = [];
        $text_lines = explode("\n", $text);
        
        foreach ($text_lines as $line) {
            $line = trim($line);
            if (empty($line)) {
                $lines[] = '';
                continue;
            }
            
            // Simple word wrapping
            $words = explode(' ', $line);
            $current_line = '';
            
            foreach ($words as $word) {
                if (strlen($current_line . ' ' . $word) > 100) {
                    if (!empty($current_line)) {
                        $lines[] = $current_line;
                    }
                    $current_line = $word;
                } else {
                    $current_line .= ($current_line ? ' ' : '') . $word;
                }
            }
            
            if (!empty($current_line)) {
                $lines[] = $current_line;
            }
        }
        
        return $lines;
    }

    /**
     * Add page
     */
    public function addPage() {
        $this->pages[] = [];
        $this->current_page++;
    }

    /**
     * Set font
     */
    public function setFont($family, $style = '', $size = 12) {
        // Simplified font setting
    }

    /**
     * Set title
     */
    public function setTitle($title) {
        $this->title = $title;
    }

    /**
     * Add cell/text
     */
    public function cell($width, $height, $text, $border = 0, $newline = 0) {
        if ($this->current_page > 0) {
            $this->pages[$this->current_page - 1][] = [
                'width' => $width,
                'height' => $height,
                'text' => substr($text, 0, 255),
                'border' => $border,
                'newline' => $newline
            ];
        }
    }

    /**
     * Generate PDF output
     */
    public function output() {
        // Generate minimal PDF structure
        $pdf = "%PDF-1.4\n";
        $pdf .= "%âãÏÓ\n"; // Binary comment
        
        // Add objects
        $objects = [];
        $offsets = [];
        
        // Object 1: Catalog
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        // Object 2: Pages
        $page_array = '';
        for ($i = 1; $i <= count($this->pages); $i++) {
            $page_array .= ($i+2) . " 0 R ";
        }
        
        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [" . trim($page_array) . "] /Count " . count($this->pages) . " >>\nendobj\n";
        
        // Object 3: Page content
        $obj_num = 3;
        foreach ($this->pages as $page_idx => $page) {
            $offsets[$obj_num] = strlen($pdf);
            $content = "BT /F1 12 Tf 50 750 Td\n";
            
            foreach ($page as $cell) {
                $text = addslashes($cell['text']);
                $content .= "(" . $text . ") Tj\n0 -15 Td\n";
            }
            
            $content .= "ET\n";
            
            $pdf .= $obj_num . " 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream\nendobj\n";
            $obj_num++;
        }
        
        // Add font object
        $offsets[$obj_num] = strlen($pdf);
        $pdf .= $obj_num . " 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        // Xref table
        $xref_offset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 " . ($obj_num + 1) . "\n";
        $pdf .= "0000000000 65535 f\n";
        
        for ($i = 1; $i <= $obj_num; $i++) {
            $pdf .= str_pad($offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n\n";
        }
        
        // Trailer
        $pdf .= "trailer\n";
        $pdf .= "<< /Size " . ($obj_num + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref_offset . "\n";
        $pdf .= "%%EOF\n";
        
        return $pdf;
    }
}
?>

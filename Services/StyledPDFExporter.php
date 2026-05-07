<?php


class StyledPDFExporter {
    
    
    public static function generatePDF($htmlContent) {
        
        $htmlContent = preg_replace('/<\?xml[^?]+\?>/', '', $htmlContent);
        $htmlContent = preg_replace('/<html[^>]*>/i', '', $htmlContent);
        $htmlContent = preg_replace('/<\/html>/i', '', $htmlContent);
        $htmlContent = preg_replace('/<head[^>]*>.*?<\/head>/is', '', $htmlContent);
        $htmlContent = preg_replace('/<body[^>]*>/i', '', $htmlContent);
        $htmlContent = preg_replace('/<\/body>/i', '', $htmlContent);
        
        
        preg_match_all('/<div class="header">(.*?)<\/div>/is', $htmlContent, $headerMatch);
        preg_match_all('/<div class="section">(.*?)<\/div>/is', $htmlContent, $sectionMatches);
        
        
        $pdf = new self();
        $pdf->addHeader($htmlContent);
        $pdf->addSections($htmlContent);
        $pdf->addFooter($htmlContent);
        
        return $pdf->generateBinaryPDF($htmlContent);
    }
    
    
    private static function generateBinaryPDF($html) {
        
        $text = strip_tags($html);
        $lines = array_filter(array_map('trim', explode("\n", $text)));
        $content = implode("\n", array_slice($lines, 0, 200)); 
        
        
        $pdf = "%PDF-1.4\n";
        $pdf .= "%\xE2\xE3\xCF\xD3\n";
        
        $objects = array();
        $offsets = array();
        
        
        $offsets[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        
        
        $offsets[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        
        
        $offsets[3] = strlen($pdf);
        $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /Resources << /Font << /F1 4 0 R /F2 5 0 R >> /ColorSpace << /CS1 6 0 R >> >> /MediaBox [0 0 595 842] /Contents 7 0 R >>\nendobj\n";
        
        
        $offsets[4] = strlen($pdf);
        $pdf .= "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        
        
        $offsets[5] = strlen($pdf);
        $pdf .= "5 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>\nendobj\n";
        
        
        $offsets[6] = strlen($pdf);
        $pdf .= "6 0 obj\n[/DeviceRGB]\nendobj\n";
        
        
        $contentStream = self::generateStyledContent($content);
        $offsets[7] = strlen($pdf);
        $pdf .= "7 0 obj\n<< /Length " . strlen($contentStream) . " >>\nstream\n" . $contentStream . "\nendstream\nendobj\n";
        
        
        $xref_offset = strlen($pdf);
        $pdf .= "xref\n";
        $pdf .= "0 8\n";
        $pdf .= "0000000000 65535 f \n";
        
        for ($i = 1; $i <= 7; $i++) {
            $pdf .= str_pad($offsets[$i], 10, '0', STR_PAD_LEFT) . " 00000 n \n";
        }
        
        
        $pdf .= "trailer\n";
        $pdf .= "<< /Size 8 /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xref_offset . "\n";
        $pdf .= "%%EOF\n";
        
        return $pdf;
    }
    
    
    private static function generateStyledContent($text) {
        $stream = "BT\n";
        
        
        $stream .= "q\n";
        $stream .= "0.1 0.3 0.22 rg\n"; 
        $stream .= "0 780 595 50 re f\n"; 
        $stream .= "Q\n";
        
        
        $stream .= "1 1 1 rg\n"; 
        $stream .= "/F2 24 Tf\n";
        $stream .= "50 800 Td\n";
        $stream .= "(STABILIS DASHBOARD REPORT) Tj\n";
        
        
        $stream .= "0 0 0 rg\n"; 
        $stream .= "/F1 11 Tf\n";
        $stream .= "0 -40 Td\n";
        
        
        $lines = explode("\n", $text);
        $line_num = 0;
        foreach (array_slice($lines, 0, 100) as $line) {
            
            $line = str_replace(['(', ')', '\\'], ['\\(', '\\)', '\\\\'], $line);
            $line = substr($line, 0, 85); 
            
            
            if (stripos($line, 'metrics') !== false || stripos($line, 'products') !== false) {
                $stream .= "0.1 0.3 0.22 rg\n"; 
            } else {
                $stream .= "0 0 0 rg\n"; 
            }
            
            if (!empty($line)) {
                $stream .= "(" . $line . ") Tj\n";
            }
            $stream .= "0 -12 Td\n";
            $line_num++;
            
            if ($line_num > 60) break; 
        }
        
        
        $stream .= "q\n";
        $stream .= "0.1 0.3 0.22 rg\n";
        $stream .= "0 0 595 20 re f\n";
        $stream .= "Q\n";
        
        $stream .= "/F1 9 Tf\n";
        $stream .= "1 1 1 rg\n"; 
        $stream .= "50 10 Td\n";
        $stream .= "(Stabilis - Professional Dashboard Report) Tj\n";
        
        $stream .= "ET\n";
        
        return $stream;
    }
    
    private function addHeader($html) {}
    private function addSections($html) {}
    private function addFooter($html) {}
}
?>

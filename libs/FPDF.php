<?php
/**
 * FPDF - Minimal PDF Generator
 * Lightweight PDF library for generating PDF files
 */

class FPDF {
    public $page = 0;
    public $n = 2;
    public $objects = array();
    public $pages = array();
    public $state = 0;
    public $compress = true;
    public $DefOrientation;
    public $CurOrientation;
    public $OrientationChanges = array();
    public $k;
    public $fwPt;
    public $fhPt;
    public $fw;
    public $fh;
    public $wPt;
    public $hPt;
    public $w;
    public $h;
    public $lMargin;
    public $tMargin;
    public $rMargin;
    public $bMargin;
    public $cMargin;
    public $x;
    public $y;
    public $lasth;
    public $LineWidth;
    public $CoreFonts = array('courier', 'helvetica', 'times', 'symbol', 'zapfdingbats');
    public $CurrentFont = array();
    public $FontSizePt;
    public $FontSize;
    public $Underline = 0;
    public $DrawColor;
    public $FillColor;
    public $TextColor;
    public $ColorFlag = false;
    public $ws = 0;
    public $str_alias_nb_pages = '{nb}';
    public $AliasNbPages;

    public function __construct($orientation = 'P', $unit = 'mm', $format = 'A4') {
        $this->k = 2.83464567; // conversion factor
        $this->DefOrientation = $orientation;
        $this->CurOrientation = $orientation;
        $this->SetAutoPageBreak(true, 15);
        
        $format_map = array(
            'A4' => array(210, 297),
            'Letter' => array(216, 279)
        );
        
        $size = $format_map[strtoupper($format)] ?? array(210, 297);
        $this->fw = $size[0];
        $this->fh = $size[1];
        $this->w = $this->fw / $this->k;
        $this->h = $this->fh / $this->k;
        $this->wPt = $this->fw * $this->k;
        $this->hPt = $this->fh * $this->k;
        
        $this->SetMargins(10, 10, 10);
        $this->SetFont('helvetica', '', 12);
        $this->SetTextColor(0, 0, 0);
        $this->SetDrawColor(0, 0, 0);
        $this->SetFillColor(255, 255, 255);
        $this->state = 1;
    }

    public function SetMargins($left, $top, $right) {
        $this->lMargin = $left;
        $this->tMargin = $top;
        $this->rMargin = $right;
        $this->SetLeftMargin($left);
        $this->SetTopMargin($top);
    }

    public function SetLeftMargin($margin) {
        $this->lMargin = $margin;
        if ($this->page > 0) {
            $this->x = $margin;
        }
    }

    public function SetTopMargin($margin) {
        $this->tMargin = $margin;
    }

    public function SetAutoPageBreak($auto, $margin = 0) {
        $this->AutoPageBreak = $auto;
        $this->bMargin = $margin;
        $this->PageBreakTrigger = $this->h - $margin;
    }

    public function SetFont($family, $style = '', $size = 0) {
        if ($size == 0) {
            $size = $this->FontSizePt;
        }
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
        $family = strtolower($family);
        if (strpos($family, 'courier') !== false) {
            $family = 'courier';
        } elseif (strpos($family, 'times') !== false) {
            $family = 'times';
        } else {
            $family = 'helvetica';
        }
        
        if (strpos($style, 'B') !== false) {
            $style = 'B';
        } elseif (strpos($style, 'I') !== false) {
            $style = 'I';
        } else {
            $style = '';
        }
        
        $this->CurrentFont = array('name' => $family, 'style' => $style);
    }

    public function SetFontSize($size) {
        if ($this->FontSizePt == $size) {
            return;
        }
        $this->FontSizePt = $size;
        $this->FontSize = $size / $this->k;
    }

    public function SetTextColor($r, $g = null, $b = null) {
        $this->TextColor = array($r, $g, $b);
    }

    public function SetDrawColor($r, $g = null, $b = null) {
        $this->DrawColor = array($r, $g, $b);
    }

    public function SetFillColor($r, $g = null, $b = null) {
        $this->FillColor = array($r, $g, $b);
    }

    public function AddPage($orientation = '') {
        if ($this->state == 3) {
            return;
        }
        
        if (empty($this->CurrentFont)) {
            $this->SetFont('helvetica', '', 12);
        }
        
        if ($orientation == '') {
            $orientation = $this->DefOrientation;
        } else {
            $orientation = strtoupper($orientation[0]);
        }
        
        $this->OrientationChanges[$this->page + 1] = $orientation;
        
        if ($orientation != $this->CurOrientation) {
            $this->w = ($orientation == 'P') ? $this->fw / $this->k : $this->fh / $this->k;
            $this->h = ($orientation == 'P') ? $this->fh / $this->k : $this->fw / $this->k;
            $this->wPt = $this->w * $this->k;
            $this->hPt = $this->h * $this->k;
            $this->CurOrientation = $orientation;
            $this->PageBreakTrigger = $this->h - $this->bMargin;
        }
        
        $this->page++;
        $this->pages[$this->page] = '';
        $this->x = $this->lMargin;
        $this->y = $this->tMargin;
        $this->FontSizePt = 12;
        $this->FontSize = 12 / $this->k;
    }

    public function Cell($w, $h = 0, $txt = '', $border = 0, $ln = 0, $align = '', $fill = false) {
        $k = $this->k;
        if ($this->y + $h > $this->PageBreakTrigger && $this->AutoPageBreak) {
            $this->AddPage($this->CurOrientation);
        }
        
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        
        $s = '';
        if ($fill) {
            $s = sprintf('%.2f %.2f %.2f %.2f re f ', $this->x * $k, ($this->h - $this->y) * $k, $w * $k, -$h * $k);
        }
        
        if (!empty($txt)) {
            $s .= sprintf('BT /F1 %.2f Tf %.2f %.2f Td (%s) Tj ET ', 
                $this->FontSizePt, $this->x * $k, ($this->h - $this->y - $this->FontSize / 2) * $k, 
                addslashes($txt));
        }
        
        $this->pages[$this->page] .= $s;
        
        if ($ln == 1) {
            $this->y += $h;
            $this->x = $this->lMargin;
        } elseif ($ln == 2) {
            $this->x = $this->lMargin;
        } else {
            $this->x += $w;
        }
    }

    public function MultiCell($w, $h, $txt, $border = 0, $align = 'J', $fill = false) {
        $cw = &$this->CurrentFont;
        if ($w == 0) {
            $w = $this->w - $this->rMargin - $this->x;
        }
        
        $wmax = ($w - 2 * $this->cMargin) * 1000 / $this->FontSize;
        $s = str_replace("\r", '', $txt);
        $nb = strlen($s);
        
        while ($nb > 0) {
            $l = 1;
            $ns = 0;
            while ($l < $nb && $s[$l] != "\n") {
                if ($s[$l] == ' ') {
                    $ns = $l;
                }
                $l++;
            }
            
            if ($l < $nb) {
                $s_part = substr($s, 0, $l);
            } else {
                $s_part = $s;
            }
            
            $this->Cell($w, $h, $s_part, $border, 2, $align, $fill);
            $s = substr($s, $l);
            $nb -= $l;
        }
    }

    public function Ln($h = null) {
        $this->x = $this->lMargin;
        if (is_string($h)) {
            $this->y += $this->lasth;
        } else {
            $this->y += $h ?? $this->FontSize;
        }
    }

    public function Output($name = '', $dest = 'D') {
        if ($this->state < 3) {
            $this->Close();
        }
        
        // Build PDF
        $pdf = $this->_putpdf();
        
        if ($dest == 'I') {
            return $pdf;
        } elseif ($dest == 'D') {
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $name . '"');
            echo $pdf;
            exit;
        }
    }

    private function _putpdf() {
        $pdf = "%PDF-1.3\n";
        
        // Object 1: Catalog
        $objects[1] = strlen($pdf);
        $pdf .= "1 0 obj\n<</Type /Catalog /Pages 2 0 R>>\nendobj\n";
        
        // Object 2: Pages
        $object_list = '';
        for ($i = 1; $i <= $this->page; $i++) {
            $object_list .= (2 + $i) . " 0 R ";
        }
        
        $objects[2] = strlen($pdf);
        $pdf .= "2 0 obj\n<</Type /Pages /Kids [" . trim($object_list) . "] /Count " . $this->page . ">>\nendobj\n";
        
        // Pages objects
        $object_num = 3;
        foreach ($this->pages as $page_content) {
            $objects[$object_num] = strlen($pdf);
            $content_stream = "BT\n/F1 12 Tf\n50 750 Td\n";
            $content_stream .= $page_content;
            $content_stream .= "\nET\n";
            
            $pdf .= $object_num . " 0 obj\n<</Type /Page /Parent 2 0 R /Resources <</Font <</F1 <<>>>>>>>
/Contents " . ($object_num + $this->page) . " 0 R>>\nendobj\n";
            $object_num++;
        }
        
        // Xref and trailer (simplified)
        $xref = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n0000000000 65535 f\n";
        
        foreach (range(1, max(array_keys($objects))) as $i) {
            $pdf .= str_pad($objects[$i] ?? 0, 10, '0', STR_PAD_LEFT) . " 00000 n\n";
        }
        
        $pdf .= "trailer\n<</Size " . (count($objects) + 1) . " /Root 1 0 R>>\n";
        $pdf .= "startxref\n" . $xref . "\n%%EOF\n";
        
        return $pdf;
    }

    public function Close() {
        if ($this->state == 3) {
            return;
        }
        if ($this->page == 0) {
            $this->AddPage();
        }
        $this->state = 3;
    }
}
?>

<?php

namespace App\Libraries;

use TCPDF;

class BarcodeGenerator
{
    private $tcpdf;
    
    public function __construct()
    {
        require_once(APPPATH . 'ThirdParty/Tcpdf/tcpdf.php');
        $this->tcpdf = new TCPDF();
    }
    
    /**
     * Generate barcode image and save to file
     */
    public function generateBarcodeImage($barcode_text, $filename = null)
    {
        if (!$filename) {
            $filename = 'barcode_' . $barcode_text . '.png';
        }
        
        $filepath = WRITEPATH . 'uploads/barcodes/' . $filename;
        
        // Create directory if not exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        // Generate barcode using TCPDF
        $barcode = $this->tcpdf->serializeTCPDFtagParameters(array($barcode_text, 'C128', '', '', 80, 30, 0.4, array('position'=>'S', 'border'=>true, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N'));
        
        // Create a simple barcode image
        $this->tcpdf->SetCreator('Patrol System');
        $this->tcpdf->SetAuthor('Patrol System');
        $this->tcpdf->SetTitle('Barcode: ' . $barcode_text);
        $this->tcpdf->SetSubject('Patrol Point Barcode');
        $this->tcpdf->SetKeywords('barcode, patrol');
        
        // Remove default header/footer
        $this->tcpdf->setPrintHeader(false);
        $this->tcpdf->setPrintFooter(false);
        
        // Set margins
        $this->tcpdf->SetMargins(10, 10, 10);
        $this->tcpdf->SetAutoPageBreak(false, 0);
        
        // Add a page
        $this->tcpdf->AddPage();
        
        // Set font
        $this->tcpdf->SetFont('helvetica', '', 12);
        
        // Add barcode
        $this->tcpdf->write1DBarcode($barcode_text, 'C128', 50, 50, 100, 30, 0.4, array('position'=>'S', 'border'=>true, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N');
        
        // Add text below barcode
        $this->tcpdf->SetXY(50, 85);
        $this->tcpdf->Cell(100, 10, $barcode_text, 0, 0, 'C');
        
        // Output as PNG
        $this->tcpdf->Output($filepath, 'F');
        
        return $filepath;
    }
    
    /**
     * Generate barcode as base64 image
     */
    public function generateBarcodeBase64($barcode_text)
    {
        $this->tcpdf->SetCreator('Patrol System');
        $this->tcpdf->SetAuthor('Patrol System');
        $this->tcpdf->SetTitle('Barcode: ' . $barcode_text);
        $this->tcpdf->SetSubject('Patrol Point Barcode');
        $this->tcpdf->SetKeywords('barcode, patrol');
        
        // Remove default header/footer
        $this->tcpdf->setPrintHeader(false);
        $this->tcpdf->setPrintFooter(false);
        
        // Set margins
        $this->tcpdf->SetMargins(10, 10, 10);
        $this->tcpdf->SetAutoPageBreak(false, 0);
        
        // Add a page
        $this->tcpdf->AddPage();
        
        // Set font
        $this->tcpdf->SetFont('helvetica', '', 12);
        
        // Add barcode
        $this->tcpdf->write1DBarcode($barcode_text, 'C128', 50, 50, 100, 30, 0.4, array('position'=>'S', 'border'=>true, 'padding'=>4, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>8, 'stretchtext'=>4), 'N');
        
        // Add text below barcode
        $this->tcpdf->SetXY(50, 85);
        $this->tcpdf->Cell(100, 10, $barcode_text, 0, 0, 'C');
        
        // Output as base64
        $output = $this->tcpdf->Output('', 'S');
        return 'data:image/png;base64,' . base64_encode($output);
    }
    
    /**
     * Generate printable PDF with multiple barcodes
     */
    public function generatePrintablePDF($patrol_points, $company_name = '')
    {
        $this->tcpdf->SetCreator('Patrol System');
        $this->tcpdf->SetAuthor('Patrol System');
        $this->tcpdf->SetTitle('Patrol Points Barcodes - ' . $company_name);
        $this->tcpdf->SetSubject('Patrol Points Barcodes');
        $this->tcpdf->SetKeywords('barcode, patrol, ' . $company_name);
        
        // Remove default header/footer
        $this->tcpdf->setPrintHeader(false);
        $this->tcpdf->setPrintFooter(false);
        
        // Set margins
        $this->tcpdf->SetMargins(15, 15, 15);
        $this->tcpdf->SetAutoPageBreak(true, 15);
        
        // Add a page
        $this->tcpdf->AddPage();
        
        // Set font
        $this->tcpdf->SetFont('helvetica', 'B', 16);
        
        // Title
        $this->tcpdf->Cell(0, 10, 'Patrol Points Barcodes', 0, 1, 'C');
        if ($company_name) {
            $this->tcpdf->SetFont('helvetica', '', 12);
            $this->tcpdf->Cell(0, 5, $company_name, 0, 1, 'C');
        }
        $this->tcpdf->Ln(10);
        
        // Generate barcodes
        $x = 20;
        $y = 50;
        $count = 0;
        
        foreach ($patrol_points as $patrol) {
            if ($count > 0 && $count % 2 == 0) {
                $this->tcpdf->AddPage();
                $y = 20;
            }
            
            // Patrol name
            $this->tcpdf->SetFont('helvetica', 'B', 10);
            $this->tcpdf->SetXY($x, $y);
            $this->tcpdf->Cell(80, 5, $patrol['nama_patrol'], 0, 0, 'L');
            
            // Barcode
            $this->tcpdf->write1DBarcode($patrol['barcode'], 'C128', $x, $y + 8, 80, 25, 0.4, array('position'=>'S', 'border'=>true, 'padding'=>2, 'fgcolor'=>array(0,0,0), 'bgcolor'=>array(255,255,255), 'text'=>true, 'font'=>'helvetica', 'fontsize'=>6, 'stretchtext'=>3), 'N');
            
            // Barcode text
            $this->tcpdf->SetFont('helvetica', '', 8);
            $this->tcpdf->SetXY($x, $y + 35);
            $this->tcpdf->Cell(80, 4, $patrol['barcode'], 0, 0, 'C');
            
            if ($count % 2 == 0) {
                $x = 110; // Right column
            } else {
                $x = 20; // Left column
                $y += 50; // Next row
            }
            $count++;
        }
        
        return $this->tcpdf->Output('patrol_barcodes.pdf', 'D'); // Download
    }
}

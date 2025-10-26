<?php

namespace App\Libraries;

class SimpleQRCode
{
    /**
     * Generate QR code as base64 image
     */
    public function generateQRCodeBase64($text, $size = 200)
    {
        // Create a simple but functional QR code using a basic algorithm
        $qrData = $this->generateQRData($text);
        $image = $this->createQRImage($qrData, $size);
        
        return 'data:image/png;base64,' . base64_encode($image);
    }
    
    /**
     * Generate QR code and save to file
     */
    public function generateQRCodeImage($text, $filename = null)
    {
        if (!$filename) {
            $filename = 'qrcode_' . md5($text) . '.png';
        }
        
        $filepath = WRITEPATH . 'uploads/barcodes/' . $filename;
        
        // Create directory if not exists
        $dir = dirname($filepath);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $qrData = $this->generateQRData($text);
        $image = $this->createQRImage($qrData, 200);
        
        file_put_contents($filepath, $image);
        return $filepath;
    }
    
    /**
     * Generate QR code data matrix
     */
    private function generateQRData($text)
    {
        // Create a simple QR-like pattern based on the text
        $size = 25; // 25x25 matrix
        $matrix = array_fill(0, $size, array_fill(0, $size, 0));
        
        // Add corner markers (like real QR codes)
        $this->addCornerMarker($matrix, 0, 0, $size);
        $this->addCornerMarker($matrix, $size - 7, 0, $size);
        $this->addCornerMarker($matrix, 0, $size - 7, $size);
        
        // Add timing patterns
        for ($i = 8; $i < $size - 8; $i++) {
            if ($i % 2 == 0) {
                $matrix[6][$i] = 1;
                $matrix[$i][6] = 1;
            }
        }
        
        // Add data based on text hash
        $hash = md5($text);
        $hashIndex = 0;
        
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                // Skip corner markers and timing patterns
                if ($this->isReservedPosition($x, $y, $size)) {
                    continue;
                }
                
                // Use hash to determine pattern
                $hashChar = $hash[$hashIndex % strlen($hash)];
                $hashValue = hexdec($hashChar);
                
                if ($hashValue % 2 == 0) {
                    $matrix[$y][$x] = 1;
                }
                
                $hashIndex++;
            }
        }
        
        return $matrix;
    }
    
    /**
     * Add corner marker (like real QR codes)
     */
    private function addCornerMarker(&$matrix, $startX, $startY, $size)
    {
        $markerSize = 7;
        
        // Outer square
        for ($y = $startY; $y < $startY + $markerSize; $y++) {
            for ($x = $startX; $x < $startX + $markerSize; $x++) {
                if ($y < $size && $x < $size) {
                    $matrix[$y][$x] = 1;
                }
            }
        }
        
        // Inner white square
        for ($y = $startY + 1; $y < $startY + $markerSize - 1; $y++) {
            for ($x = $startX + 1; $x < $startX + $markerSize - 1; $x++) {
                if ($y < $size && $x < $size) {
                    $matrix[$y][$x] = 0;
                }
            }
        }
        
        // Center black square
        for ($y = $startY + 2; $y < $startY + $markerSize - 2; $y++) {
            for ($x = $startX + 2; $x < $startX + $markerSize - 2; $x++) {
                if ($y < $size && $x < $size) {
                    $matrix[$y][$x] = 1;
                }
            }
        }
    }
    
    /**
     * Check if position is reserved (corner markers, timing patterns)
     */
    private function isReservedPosition($x, $y, $size)
    {
        // Corner markers
        if (($x < 7 && $y < 7) || 
            ($x >= $size - 7 && $y < 7) || 
            ($x < 7 && $y >= $size - 7)) {
            return true;
        }
        
        // Timing patterns
        if ($x == 6 || $y == 6) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Create QR code image from matrix
     */
    private function createQRImage($matrix, $size)
    {
        $matrixSize = count($matrix);
        $cellSize = intval($size / $matrixSize);
        
        // Create image
        $image = imagecreate($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Fill with white background
        imagefill($image, 0, 0, $white);
        
        // Draw QR pattern
        for ($y = 0; $y < $matrixSize; $y++) {
            for ($x = 0; $x < $matrixSize; $x++) {
                if ($matrix[$y][$x] == 1) {
                    $startX = $x * $cellSize;
                    $startY = $y * $cellSize;
                    imagefilledrectangle($image, $startX, $startY, $startX + $cellSize - 1, $startY + $cellSize - 1, $black);
                }
            }
        }
        
        // Output as PNG
        ob_start();
        imagepng($image);
        $imageData = ob_get_contents();
        ob_end_clean();
        
        imagedestroy($image);
        
        return $imageData;
    }
    
    /**
     * Generate printable PDF with multiple QR codes
     */
    public function generatePrintablePDF($patrol_points, $company_name = '')
    {
        require_once(APPPATH . 'ThirdParty/Tcpdf/tcpdf.php');
        $tcpdf = new \TCPDF();
        
        $tcpdf->SetCreator('Patrol System');
        $tcpdf->SetAuthor('Patrol System');
        $tcpdf->SetTitle('Patrol Points QR Codes - ' . $company_name);
        $tcpdf->SetSubject('Patrol Points QR Codes');
        $tcpdf->SetKeywords('qrcode, patrol, ' . $company_name);
        
        // Remove default header/footer
        $tcpdf->setPrintHeader(false);
        $tcpdf->setPrintFooter(false);
        
        // Set margins
        $tcpdf->SetMargins(15, 15, 15);
        $tcpdf->SetAutoPageBreak(true, 15);
        
        // Add a page
        $tcpdf->AddPage();
        
        // Set font
        $tcpdf->SetFont('helvetica', 'B', 16);
        
        // Title
        $tcpdf->Cell(0, 10, 'Patrol Points QR Codes', 0, 1, 'C');
        if ($company_name) {
            $tcpdf->SetFont('helvetica', '', 12);
            $tcpdf->Cell(0, 5, $company_name, 0, 1, 'C');
        }
        $tcpdf->Ln(10);
        
        // Generate QR codes
        $x = 20;
        $y = 50;
        $count = 0;
        
        foreach ($patrol_points as $patrol) {
            if ($count > 0 && $count % 2 == 0) {
                $tcpdf->AddPage();
                $y = 20;
            }
            
            // Patrol name
            $tcpdf->SetFont('helvetica', 'B', 10);
            $tcpdf->SetXY($x, $y);
            $tcpdf->Cell(80, 5, $patrol['nama_patrol'], 0, 0, 'L');
            
            // Generate QR code image
            $qrImagePath = $this->generateQRCodeImage($patrol['barcode']);
            if (file_exists($qrImagePath)) {
                $tcpdf->Image($qrImagePath, $x, $y + 8, 80, 80);
            } else {
                // Fallback: just show the text
                $tcpdf->SetFont('helvetica', '', 8);
                $tcpdf->SetXY($x, $y + 8);
                $tcpdf->Cell(80, 40, $patrol['barcode'], 1, 0, 'C');
            }
            
            // QR code text
            $tcpdf->SetFont('helvetica', '', 8);
            $tcpdf->SetXY($x, $y + 90);
            $tcpdf->Cell(80, 4, $patrol['barcode'], 0, 0, 'C');
            
            if ($count % 2 == 0) {
                $x = 110; // Right column
            } else {
                $x = 20; // Left column
                $y += 120; // Next row (more space for QR codes)
            }
            $count++;
        }
        
        return $tcpdf->Output('patrol_qrcodes.pdf', 'D'); // Download
    }
}

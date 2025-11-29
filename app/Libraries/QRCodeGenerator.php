<?php

namespace App\Libraries;

class QRCodeGenerator
{
    /**
     * Generate QR code as base64 image using Google Charts API
     */
    public function generateQRCodeBase64($text, $size = 200)
    {
        // Try Google Charts API first
        $url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($text);
        
        // Get the image data with timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData === false || strlen($imageData) < 100) {
            // Fallback: create a simple text-based QR representation
            return $this->createFallbackQR($text, $size);
        }
        
        return 'data:image/png;base64,' . base64_encode($imageData);
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
        
        $size = 200;
        $url = 'https://chart.googleapis.com/chart?chs=' . $size . 'x' . $size . '&cht=qr&chl=' . urlencode($text);
        
        // Get the image data with timeout
        $context = stream_context_create([
            'http' => [
                'timeout' => 5,
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
            ]
        ]);
        
        $imageData = @file_get_contents($url, false, $context);
        
        if ($imageData === false || strlen($imageData) < 100) {
            // Fallback: create a simple text-based QR representation
            $imageData = $this->createFallbackQRImage($text, $size);
        }
        
        file_put_contents($filepath, $imageData);
        return $filepath;
    }
    
    /**
     * Create a fallback QR code representation using simple graphics
     */
    private function createFallbackQR($text, $size)
    {
        // Create a simple SVG-based QR code representation
        $svg = '<svg width="' . $size . '" height="' . $size . '" xmlns="http://www.w3.org/2000/svg">';
        $svg .= '<rect width="' . $size . '" height="' . $size . '" fill="white"/>';
        $svg .= '<rect x="10" y="10" width="20" height="20" fill="black"/>';
        $svg .= '<rect x="40" y="10" width="20" height="20" fill="black"/>';
        $svg .= '<rect x="70" y="10" width="20" height="20" fill="black"/>';
        $svg .= '<rect x="100" y="10" width="20" height="20" fill="black"/>';
        $svg .= '<rect x="130" y="10" width="20" height="20" fill="black"/>';
        $svg .= '<rect x="160" y="10" width="20" height="20" fill="black"/>';
        
        // Add more pattern elements
        for ($i = 0; $i < 8; $i++) {
            for ($j = 0; $j < 8; $j++) {
                if (($i + $j) % 2 == 0) {
                    $x = 10 + ($i * 20);
                    $y = 40 + ($j * 20);
                    $svg .= '<rect x="' . $x . '" y="' . $y . '" width="20" height="20" fill="black"/>';
                }
            }
        }
        
        $svg .= '<text x="' . ($size/2) . '" y="' . ($size - 10) . '" text-anchor="middle" font-family="Arial" font-size="12" fill="black">' . htmlspecialchars($text) . '</text>';
        $svg .= '</svg>';
        
        return 'data:image/svg+xml;base64,' . base64_encode($svg);
    }
    
    /**
     * Create a fallback QR code image file
     */
    private function createFallbackQRImage($text, $size)
    {
        // Create a simple PNG image
        $image = imagecreate($size, $size);
        $white = imagecolorallocate($image, 255, 255, 255);
        $black = imagecolorallocate($image, 0, 0, 0);
        
        // Fill with white background
        imagefill($image, 0, 0, $white);
        
        // Draw a simple pattern
        for ($i = 0; $i < $size; $i += 20) {
            for ($j = 0; $j < $size; $j += 20) {
                if (($i + $j) % 40 == 0) {
                    imagefilledrectangle($image, $i, $j, $i + 19, $j + 19, $black);
                }
            }
        }
        
        // Add text
        imagestring($image, 2, 10, $size - 20, $text, $black);
        
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

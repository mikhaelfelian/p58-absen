<?php
/**
 * PWA Manifest file for Absen Day Mobile
 * Outputs JSON manifest with dynamic base URL
 */

// Construct base URL from server variables
$protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || 
             (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) 
             ? 'https' : 'http';
$host = $_SERVER['HTTP_HOST'] ?? 'localhost';
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '/manifest.json.php';
$scriptDir = dirname($scriptName);

// Remove /public from path if present
if (strpos($scriptDir, '/public') !== false) {
    $scriptDir = str_replace('/public', '', $scriptDir);
}

// Construct base URL
$baseURL = $protocol . '://' . $host . $scriptDir;
// Ensure baseURL ends with /
if (substr($baseURL, -1) !== '/') {
    $baseURL .= '/';
}

// Set content type to JSON
header('Content-Type: application/json');

// Output manifest JSON
echo json_encode([
    'name' => 'Absen Day - Mobile',
    'short_name' => 'Absen Day',
    'description' => 'Aplikasi Absensi Online Mobile',
    'start_url' => $baseURL . 'mobile-presensi-home',
    'display' => 'standalone',
    'background_color' => '#0d6efd',
    'theme_color' => '#0d6efd',
    'orientation' => 'portrait',
    'icons' => [
        [
            'src' => $baseURL . 'public/images/manifest/icon-72x72.png',
            'sizes' => '72x72',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $baseURL . 'public/images/manifest/icon-96x96.png',
            'sizes' => '96x96',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $baseURL . 'public/images/manifest/icon-128x128.png',
            'sizes' => '128x128',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $baseURL . 'public/images/manifest/icon-144x144.png',
            'sizes' => '144x144',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $baseURL . 'public/images/manifest/icon-152x152.png',
            'sizes' => '152x152',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $baseURL . 'public/images/manifest/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $baseURL . 'public/images/manifest/icon-384x384.png',
            'sizes' => '384x384',
            'type' => 'image/png',
            'purpose' => 'any'
        ],
        [
            'src' => $baseURL . 'public/images/manifest/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ]
], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);


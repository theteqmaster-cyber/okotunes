<?php
/**
 * Okotune AI — Router Script for PHP Built-in Server
 * Ensures correct MIME types for static assets and handles /api routing
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . $uri;

// 1. Explicitly serve static files with correct Content-Type (fixes Firefox strict MIME checking)
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
    $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    $mimes = [
        'css'   => 'text/css; charset=UTF-8',
        'js'    => 'application/javascript; charset=UTF-8',
        'json'  => 'application/json; charset=UTF-8',
        'png'   => 'image/png',
        'jpg'   => 'image/jpeg',
        'jpeg'  => 'image/jpeg',
        'webp'  => 'image/webp',
        'svg'   => 'image/svg+xml',
        'ico'   => 'image/x-icon',
        'woff2' => 'font/woff2',
        'woff'  => 'font/woff',
        'ttf'   => 'font/ttf'
    ];

    if (isset($mimes[$ext])) {
        header('Content-Type: ' . $mimes[$ext]);
        header('Cache-Control: no-cache, must-revalidate');
        readfile($filePath);
        exit;
    }

    return false;
}

// 2. Route /api/... requests
if (str_starts_with($uri, '/api/')) {
    $script = __DIR__ . $uri;
    if (!str_ends_with($script, '.php')) {
        $script .= '.php';
    }
    if (file_exists($script)) {
        require $script;
        exit;
    }
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found: ' . $uri]);
    exit;
}

// 3. Fallback to index.html
if (file_exists(__DIR__ . '/index.html')) {
    header('Content-Type: text/html; charset=UTF-8');
    require __DIR__ . '/index.html';
    exit;
}

http_response_code(404);
echo "404 Not Found";

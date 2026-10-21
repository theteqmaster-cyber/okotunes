<?php
/**
 * Okotune AI — Router Script for PHP Built-in Server
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$filePath = __DIR__ . $uri;

// 1. If static file exists, let PHP server handle it directly
if ($uri !== '/' && file_exists($filePath) && !is_dir($filePath)) {
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

// 3. Root and frontend route
if (file_exists(__DIR__ . '/index.html')) {
    require __DIR__ . '/index.html';
    exit;
}

http_response_code(404);
echo "404 Not Found";

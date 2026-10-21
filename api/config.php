<?php
/**
 * Okotune AI — Configuration & Environment Handler
 * Part of M.P.A.S Sovereign Intelligence Ecosystem
 */

declare(strict_types=1);

// Prevent direct execution notices
if (!defined('OKOTUNES_INIT')) {
    define('OKOTUNES_INIT', true);
}

// Start secure session if not started
if (session_status() === PHP_SESSION_NONE) {
    session_start([
        'cookie_httponly' => true,
        'cookie_samesite' => 'Lax'
    ]);
}

// Function to load .env file if present
function loadEnv(string $path): void {
    if (!file_exists($path)) {
        return;
    }
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Remove surrounding quotes if any
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if (!getenv($key)) {
                putenv("$key=$val");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

// Load .env from root
loadEnv(dirname(__DIR__) . '/.env');

function env(string $key, ?string $default = null): ?string {
    $val = getenv($key);
    if ($val !== false && $val !== '') {
        return $val;
    }
    if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
        return $_ENV[$key];
    }
    if (isset($_SERVER[$key]) && $_SERVER[$key] !== '') {
        return $_SERVER[$key];
    }
    return $default;
}

// Core Config
define('GROQ_API_KEY', env('GROQ_API_KEY', ''));
define('AUTH_PASSWORD', env('AUTH_PASSWORD', 'okotunes2026'));
define('AUTH_EMAIL', env('AUTH_EMAIL', 'admin@okotunes.com'));

// Cloudflare R2 Config
define('CF_R2_ACCOUNT_ID', env('CF_R2_ACCOUNT_ID', ''));
define('CF_R2_ACCESS_KEY_ID', env('CF_R2_ACCESS_KEY_ID', ''));
define('CF_R2_SECRET_ACCESS_KEY', env('CF_R2_SECRET_ACCESS_KEY', ''));
define('CF_R2_BUCKET', env('CF_R2_BUCKET', 'notak2'));
define('CF_R2_PUBLIC_URL', env('CF_R2_PUBLIC_URL', 'https://notak2.mphathisi.com'));

// Paths
define('DATA_DIR', dirname(__DIR__) . '/data');
define('CHATS_DIR', DATA_DIR . '/chats');
define('LORE_FILE', DATA_DIR . '/lore.json');
define('THREADS_FILE', DATA_DIR . '/threads.json');
define('DATASET_FILE', DATA_DIR . '/training_dataset.jsonl');

// Helper to check authentication
function isAuthenticated(): bool {
    // If no password set, open access
    if (AUTH_PASSWORD === '') {
        return true;
    }
    if (!empty($_SESSION['okotunes_auth'])) {
        return true;
    }
    // Check Authorization header (Bearer token)
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        if ($matches[1] === AUTH_PASSWORD) {
            return true;
        }
    }
    return false;
}

function requireAuth(): void {
    if (!isAuthenticated()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Unauthorized: Please authenticate to access M.P.A.S Sovereign AI.']);
        exit;
    }
}

<?php
/**
 * Authentication Endpoint
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/r2.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check current auth status
    echo json_encode([
        'authenticated' => isAuthenticated(),
        'user' => isAuthenticated() ? 'Mphathisi Ndlovu' : null,
        'email' => AUTH_EMAIL,
        'r2_connected' => (new R2Client())->isConfigured(),
        'bucket' => CF_R2_BUCKET
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $password = $input['password'] ?? '';

    if ($password === AUTH_PASSWORD) {
        $_SESSION['okotunes_auth'] = true;
        $_SESSION['okotunes_user'] = 'Mphathisi Ndlovu';
        echo json_encode([
            'success' => true,
            'token' => AUTH_PASSWORD,
            'user' => 'Mphathisi Ndlovu',
            'message' => 'Authenticated to M.P.A.S Sovereign AI'
        ]);
        exit;
    }

    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid credentials for M.P.A.S Sovereign AI'
    ]);
    exit;
}

if ($method === 'DELETE') {
    // Logout
    unset($_SESSION['okotunes_auth']);
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out']);
    exit;
}

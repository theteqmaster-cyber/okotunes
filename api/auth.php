<?php
/**
 * Authentication Endpoint
 * Hard-gated access with AUTH_EMAIL and AUTH_PASSWORD
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';
require_once __DIR__ . '/r2.php';

header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // Check current auth status
    $authed = isAuthenticated();
    echo json_encode([
        'authenticated' => $authed,
        'user' => $authed ? ($_SESSION['okotunes_user'] ?? 'Mphathisi Ndlovu') : null,
        'email' => $authed ? ($_SESSION['okotunes_email'] ?? AUTH_EMAIL) : null,
        'r2_connected' => (new R2Client())->isConfigured(),
        'bucket' => CF_R2_BUCKET
    ]);
    exit;
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $email = trim($input['email'] ?? '');
    $password = trim($input['password'] ?? '');

    $targetEmail = AUTH_EMAIL;
    $targetPassword = AUTH_PASSWORD;

    // Verify email and password
    $emailMatches = ($targetEmail === '' || strcasecmp($email, $targetEmail) === 0);
    $passwordMatches = ($password === $targetPassword);

    if ($emailMatches && $passwordMatches) {
        $_SESSION['okotunes_auth'] = true;
        $_SESSION['okotunes_user'] = 'Mphathisi Ndlovu';
        $_SESSION['okotunes_email'] = $targetEmail;
        
        echo json_encode([
            'success' => true,
            'token' => $targetPassword,
            'user' => 'Mphathisi Ndlovu',
            'email' => $targetEmail,
            'message' => 'Authenticated to Okotune AI'
        ]);
        exit;
    }

    http_response_code(401);
    echo json_encode([
        'success' => false,
        'error' => 'Invalid email or password. Access is restricted to M.P.A.S ecosystem.'
    ]);
    exit;
}

if ($method === 'DELETE') {
    // Logout
    unset($_SESSION['okotunes_auth']);
    unset($_SESSION['okotunes_user']);
    unset($_SESSION['okotunes_email']);
    session_destroy();
    echo json_encode(['success' => true, 'message' => 'Logged out successfully']);
    exit;
}

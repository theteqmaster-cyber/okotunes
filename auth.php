<?php
/**
 * auth.php - Multi-Wall Authentication & Decoy Gatekeeper for okotunes.
 * Handles sessions, credential validation, verification challenge logic, rate limiting, and API access rules.
 */

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

function load_auth_env(): void {
    static $loaded = false;
    if ($loaded) return;
    $loaded = true;

    $envFile = __DIR__ . '/.env';
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if (empty($line) || str_starts_with($line, '#')) continue;
            if (str_contains($line, '=')) {
                list($name, $value) = explode('=', $line, 2);
                $name = trim($name);
                $value = trim($value, "\" '");
                if (!getenv($name)) {
                    putenv("$name=$value");
                    $_ENV[$name] = $value;
                }
            }
        }
    }
}

function get_auth_email(): string {
    load_auth_env();
    return getenv('AUTH_EMAIL') ?: 'admin@okotunes.com';
}

function get_auth_password(): string {
    load_auth_env();
    return getenv('AUTH_PASSWORD') ?: 'okotunes2026';
}

function get_auth_status(): string {
    return $_SESSION['auth_status'] ?? 'none';
}

function is_authenticated(): bool {
    return get_auth_status() === 'authenticated';
}

function is_decoy(): bool {
    return get_auth_status() === 'decoy';
}

function require_auth(bool $is_json = false): void {
    if (is_authenticated()) {
        return;
    }

    if ($is_json) {
        header('Content-Type: application/json', true, 401);
        echo json_encode(['error' => 'Unauthorized access. Authentication required.']);
        exit;
    }

    header('HTTP/1.1 403 Forbidden');
    echo "<h1>403 Forbidden</h1><p>Access Denied.</p>";
    exit;
}

// Check for POST authentication actions
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST' && isset($_POST['auth_action'])) {

    $action = $_POST['auth_action'];

    if ($action === 'login') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Rate limiting check
        $attempts = $_SESSION['login_attempts'] ?? 0;
        $lastAttempt = $_SESSION['last_attempt_time'] ?? 0;
        if ($attempts >= 5 && (time() - $lastAttempt) < 900) { // 15 mins block
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Too many failed login attempts. Please wait 15 minutes before trying again.']);
            exit;
        }

        if (strtolower($email) === strtolower(get_auth_email()) && $password === get_auth_password()) {
            $_SESSION['auth_status'] = 'pending_challenge';
            $_SESSION['login_attempts'] = 0;
            
            // Fixed target: Node 1 (44.1 kHz Lossless Frequency Node)
            $_SESSION['captcha_target'] = 1;

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true, 
                'next' => 'challenge',
                'target' => $_SESSION['captcha_target']
            ]);
            exit;
        }
 else {
            $_SESSION['login_attempts'] = ($attempts + 1);
            $_SESSION['last_attempt_time'] = time();

            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid email address or password. Access denied.']);
            exit;
        }
    }

    if ($action === 'verify_challenge') {
        if (($_SESSION['auth_status'] ?? '') !== 'pending_challenge') {
            header('Content-Type: application/json');
            echo json_encode(['success' => false, 'error' => 'Invalid session state.']);
            exit;
        }

        $selected = intval($_POST['selected_target'] ?? 0);
        $expected = intval($_SESSION['captcha_target'] ?? -1);

        if ($selected === $expected && $expected > 0) {
            $_SESSION['auth_status'] = 'authenticated';
            unset($_SESSION['captcha_target']);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect' => 'index.php']);
            exit;
        } else {
            // Failed the challenge -> Send to DECOY trap ("Bottomless Pit")
            $_SESSION['auth_status'] = 'decoy';
            unset($_SESSION['captcha_target']);

            header('Content-Type: application/json');
            echo json_encode(['success' => true, 'redirect' => 'index.php?mode=pit']);
            exit;
        }
    }
}

// Handle Logout
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}

<?php
/**
 * Pierre Gasly - Database & App Configuration
 * Supports Railway env vars with XAMPP localhost fallback
 */
session_start();

// ── Database ─────────────────────────────────────────────────────
$host = getenv('DB_HOST') ?: 'localhost';
$user = getenv('DB_USER') ?: 'root';
$pass = getenv('DB_PASS') ?: '';
$db   = getenv('DB_NAME') ?: 'pierre_gasly';
$port = getenv('DB_PORT') ?: '3306';

try {
    $pdo = new PDO(
        "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
        $user, $pass,
        [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]
    );
} catch (PDOException $e) {
    die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
}

// ── App Settings ─────────────────────────────────────────────────
define('APP_NAME',    'Pierre Gasly Gas Delivery');
define('APP_VERSION', '1.0.0');
define('BASE_URL',    getenv('APP_URL') ?: 'http://localhost/pierre-gasly-admin/');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');

// ── Auth helpers ──────────────────────────────────────────────────
function isLoggedIn(): bool {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function requireRole(string $role): void {
    requireLogin();
    if (($_SESSION['role'] ?? '') !== $role && ($_SESSION['role'] ?? '') !== 'master_admin') {
        header('Location: ' . BASE_URL . 'dashboard.php');
        exit();
    }
}

function currentUser(): array {
    return [
        'id'    => $_SESSION['user_id']  ?? null,
        'name'  => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['email']    ?? '',
        'role'  => $_SESSION['role']     ?? '',
    ];
}

// ── Sanitize ──────────────────────────────────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

// ── CSRF ──────────────────────────────────────────────────────────
function csrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrf(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

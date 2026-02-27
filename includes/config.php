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
function generateCSRFToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken(string $token): bool {
    return hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// Aliases for compatibility
function csrfToken(): string { return generateCSRFToken(); }
function verifyCsrf(string $token): bool { return verifyCSRFToken($token); }

// ── Password helpers ──────────────────────────────────────────────
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// ── Activity log ──────────────────────────────────────────────────
function logActivity(string $action, string $entityType, $entityId, string $details = ''): void {
    global $pdo;
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $stmt   = $pdo->prepare(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $stmt->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
    } catch (Exception $e) {
        // Silently fail - don't break the app if logging fails
        error_log('logActivity error: ' . $e->getMessage());
    }
}

// ── File upload ───────────────────────────────────────────────────
function uploadFile(array $file, string $folder = 'uploads'): array {
    $uploadDir = __DIR__ . '/../uploads/' . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }

    $filename = uniqid() . '_' . time() . '.' . $ext;
    $dest     = $uploadDir . $filename;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => true, 'filename' => $filename, 'path' => $folder . '/' . $filename];
    }

    return ['success' => false, 'error' => 'Upload failed'];
}

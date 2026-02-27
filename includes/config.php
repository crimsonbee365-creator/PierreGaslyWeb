<?php
/**
 * Pierre Gasly - Master Configuration
 * Supports Railway env vars with XAMPP localhost fallback
 */

// ── Session ───────────────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// ── Constants ─────────────────────────────────────────────────────
define('BASE_PATH',    __DIR__ . '/..');
define('SITE_NAME',    'Pierre Gasly Admin');
define('APP_NAME',     'Pierre Gasly Gas Delivery');
define('APP_VERSION',  '1.0.0');
define('BASE_URL',     rtrim(getenv('APP_URL') ?: 'http://localhost/pierre-gasly-admin/', '/') . '/');
define('UPLOAD_PATH',  BASE_PATH . '/uploads/');
define('UPLOAD_URL',   BASE_URL . 'uploads/');

// ── Database class (singleton) ────────────────────────────────────
class Database {
    private static ?Database $instance = null;
    private PDO $pdo;

    private function __construct() {
        $host = getenv('DB_HOST') ?: 'localhost';
        $user = getenv('DB_USER') ?: 'root';
        $pass = getenv('DB_PASS') ?: '';
        $db   = getenv('DB_NAME') ?: 'pierre_gasly';
        $port = getenv('DB_PORT') ?: '3306';

        $this->pdo = new PDO(
            "mysql:host=$host;port=$port;dbname=$db;charset=utf8mb4",
            $user, $pass,
            [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]
        );
    }

    public static function getInstance(): Database {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getPDO(): PDO { return $this->pdo; }

    public function query(string $sql, array $params = []): PDOStatement {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    public function prepare(string $sql): PDOStatement {
        return $this->pdo->prepare($sql);
    }

    public function fetchOne(string $sql, array $params = []): array|false {
        $result = $this->query($sql, $params)->fetch();
        return $result ?: false;
    }

    public function fetchAll(string $sql, array $params = []): array {
        return $this->query($sql, $params)->fetchAll();
    }

    public function execute(string $sql, array $params = []): bool {
        return $this->query($sql, $params)->rowCount() >= 0;
    }

    public function lastInsertId(): string {
        return $this->pdo->lastInsertId();
    }
}

// ── Global $pdo and $db for convenience ──────────────────────────
try {
    $db  = Database::getInstance();
    $pdo = $db->getPDO();
} catch (PDOException $e) {
    error_log('DB connection failed: ' . $e->getMessage());
    $db  = null;
    $pdo = null;
}

// ── Auth helpers ──────────────────────────────────────────────────
function isLoggedIn(): bool {
    return !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: ' . BASE_URL . 'login.php');
        exit();
    }
}

function requireAdmin(): void {
    requireLogin();
    $role = $_SESSION['role'] ?? '';
    if (!in_array($role, ['master_admin', 'sub_admin'])) {
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
        'id'    => $_SESSION['user_id']   ?? null,
        'name'  => $_SESSION['full_name'] ?? '',
        'email' => $_SESSION['email']     ?? '',
        'role'  => $_SESSION['role']      ?? '',
    ];
}

// ── Sanitize ──────────────────────────────────────────────────────
function sanitize(string $input): string {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function isValidEmail(string $email): bool {
    return (bool) filter_var($email, FILTER_VALIDATE_EMAIL);
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

// Aliases
function csrfToken(): string        { return generateCSRFToken(); }
function verifyCsrf(string $t):bool { return verifyCSRFToken($t); }
function getDBConnection(): Database { return Database::getInstance(); }

// ── Password ──────────────────────────────────────────────────────
function hashPassword(string $password): string {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword(string $password, string $hash): bool {
    return password_verify($password, $hash);
}

// ── Activity log ──────────────────────────────────────────────────
function logActivity(string $action, string $entityType, $entityId, string $details = ''): void {
    global $pdo;
    if (!$pdo) return;
    try {
        $userId = $_SESSION['user_id'] ?? null;
        $ip     = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $pdo->prepare(
            "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        )->execute([$userId, $action, $entityType, $entityId, $details, $ip]);
    } catch (Exception $e) {
        error_log('logActivity error: ' . $e->getMessage());
    }
}

// ── File upload ───────────────────────────────────────────────────
function uploadFile(array $file, string $folder = 'uploads'): array {
    $uploadDir = UPLOAD_PATH . $folder . '/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'error' => 'Invalid file type. Allowed: jpg, jpeg, png, gif, webp'];
    }
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'error' => 'File too large (max 5MB)'];
    }
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'error' => 'Upload error code: ' . $file['error']];
    }
    $filename = uniqid() . '_' . time() . '.' . $ext;
    $dest     = $uploadDir . $filename;
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        return ['success' => true, 'filename' => $filename, 'path' => $folder . '/' . $filename];
    }
    return ['success' => false, 'error' => 'Failed to move uploaded file'];
}

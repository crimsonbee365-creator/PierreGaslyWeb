<?php
/**
 * Pierre Gasly - Login API
 * POST /api/auth/login.php
 */
require_once __DIR__ . '/../../api_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$data     = getJsonInput();
$email    = sanitize($data['email'] ?? '');
$password = $data['password'] ?? '';

if (!$email || !$password) sendError('Email and password are required');
if (!isValidEmail($email))  sendError('Invalid email address');

try {
    $pdo = getConnection();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Brute force check
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE action='login_failed' AND ip_address=? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE)");
    $stmt->execute([$ip]);
    if ((int)$stmt->fetchColumn() >= 10) sendError('Too many failed attempts. Try again in 15 minutes.', 429);

    $stmt = $pdo->prepare("SELECT user_id,full_name,email,password_hash,phone,role,status,COALESCE(email_verified,0) as email_verified,failed_login_attempts,locked_until FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user) {
        $pdo->prepare("INSERT INTO activity_logs (action,ip_address,details,created_at) VALUES ('login_failed',?,?,NOW())")->execute([$ip, json_encode(['email'=>$email])]);
        sendError('Invalid email or password');
    }

    if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
        $mins = ceil((strtotime($user['locked_until']) - time()) / 60);
        sendError("Account locked for $mins more minute(s).");
    }

    if (!password_verify($password, $user['password_hash'])) {
        $attempts = (int)($user['failed_login_attempts'] ?? 0) + 1;
        $lock = $attempts >= 5 ? date('Y-m-d H:i:s', strtotime('+15 minutes')) : null;
        $pdo->prepare("UPDATE users SET failed_login_attempts=?,locked_until=? WHERE user_id=?")->execute([$attempts, $lock, $user['user_id']]);
        $pdo->prepare("INSERT INTO activity_logs (user_id,action,ip_address,created_at) VALUES (?,'login_failed',?,NOW())")->execute([$user['user_id'], $ip]);
        $remaining = max(0, 5 - $attempts);
        sendError($remaining > 0 ? "Invalid email or password. ($remaining attempts left)" : "Account locked for 15 minutes.");
    }

    if (in_array($user['status'], ['suspended', 'banned'])) sendError('Account suspended. Contact support.');
    if (!in_array($user['role'], ['customer', 'rider']))    sendError('Use the web admin panel for this account.');

    $pdo->prepare("UPDATE users SET last_login=NOW(),failed_login_attempts=0,locked_until=NULL WHERE user_id=?")->execute([$user['user_id']]);
    $pdo->prepare("INSERT INTO activity_logs (user_id,action,ip_address,created_at) VALUES (?,'login',?,NOW())")->execute([$user['user_id'], $ip]);

    sendSuccess([
        'token' => generateJWT($user['user_id'], $user['email'], $user['role']),
        'user'  => [
            'user_id'        => (int)$user['user_id'],
            'full_name'      => $user['full_name'],
            'email'          => $user['email'],
            'phone'          => $user['phone'],
            'role'           => $user['role'],
            'status'         => $user['status'],
            'email_verified' => (bool)$user['email_verified'],
        ]
    ], 'Login successful');

} catch (PDOException $e) {
    logError('Login: ' . $e->getMessage());
    sendError('Login failed. Please try again.', 500);
}

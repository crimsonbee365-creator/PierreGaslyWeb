<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(200); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Method not allowed']); exit;
}

require_once '../../config.php';

$data  = json_decode(file_get_contents('php://input'), true);
$email = isset($data['email']) ? trim($data['email']) : '';

if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Valid email is required']);
    exit;
}

try {
    $db   = getDBConnection();

    // Check if user exists (don't reveal this to the client — always return success)
    $stmt = $db->prepare("SELECT user_id, full_name FROM users WHERE email = ? AND role = 'customer' LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generate a reset token
        $token     = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store token in DB (add reset_tokens table or use a temp column)
        // For now we store it in activity_logs as a simple approach
        $logStmt = $db->prepare(
            "INSERT INTO activity_logs (user_id, action, details, created_at)
             VALUES (?, 'password_reset_requested', ?, NOW())"
        );
        $logStmt->execute([$user['user_id'], json_encode(['token' => $token, 'expires' => $expiresAt])]);

        // In production: send actual email via PHPMailer / SMTP
        // For now: just log it — the app shows success regardless
        error_log("Password reset token for {$email}: {$token} (expires: {$expiresAt})");
    }

    // Always return success to prevent email enumeration
    echo json_encode([
        'success' => true,
        'message' => 'If an account with that email exists, reset instructions have been sent.'
    ]);

} catch (Exception $e) {
    error_log('Forgot password error: ' . $e->getMessage());
    // Still return success to prevent enumeration
    echo json_encode([
        'success' => true,
        'message' => 'If an account with that email exists, reset instructions have been sent.'
    ]);
}

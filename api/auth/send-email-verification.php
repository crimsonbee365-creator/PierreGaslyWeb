<?php
/**
 * Send / Verify email verification
 * POST /api/auth/send-email-verification.php
 * Requires JWT Bearer token
 */
require_once __DIR__ . '/../../api_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$authUser = getAuthUser();
$data = getJson();
$action = $data['action'] ?? 'send'; // 'send' or 'verify'

try {
    $pdo = getDB();

    if ($action === 'verify') {
        // Verify token sent via email
        $token = sanitize($data['token'] ?? '');
        if (empty($token)) sendError('Verification token required');

        $stmt = $pdo->prepare("
            SELECT user_id FROM activity_logs
            WHERE action = 'email_verify_token'
            AND user_id = ?
            AND details LIKE ?
            AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
            ORDER BY created_at DESC LIMIT 1
        ");
        $stmt->execute([$authUser['user_id'], '%"token":"' . $token . '"%']);
        $log = $stmt->fetch();

        if (!$log) sendError('Invalid or expired verification link');

        $pdo->prepare("UPDATE users SET email_verified = 1 WHERE user_id = ?")
            ->execute([$authUser['user_id']]);

        respond(true, ['email_verified' => true], 'Email verified successfully! Rewards are now unlocked.');
    } else {
        // Generate token and "send" email
        $stmt = $pdo->prepare("SELECT email, full_name, email_verified FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$authUser['user_id']]);
        $user = $stmt->fetch();

        if (!$user) sendError('User not found');
        if ($user['email_verified']) sendError('Email is already verified');

        // Rate limit: 3 per hour
        $stmt = $pdo->prepare("
            SELECT COUNT(*) FROM activity_logs
            WHERE user_id = ? AND action = 'email_verify_token'
            AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)
        ");
        $stmt->execute([$authUser['user_id']]);
        if ((int)$stmt->fetchColumn() >= 3) sendError('Too many requests. Wait an hour before trying again.', 429);

        $token = bin2hex(random_bytes(32));
        $verifyUrl = API_SITE_URL . 'verify-email.php?token=' . $token . '&uid=' . $authUser['user_id'];

        $pdo->prepare("INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) VALUES (?, 'email_verify_token', ?, ?, NOW())")
            ->execute([$authUser['user_id'], json_encode(['token' => $token]), $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);

        // TODO: Send real email via PHPMailer
        // For now, log the URL for testing
        logError("EMAIL VERIFY URL for {$user['email']}: $verifyUrl");

        respond(true, ['message' => 'Verification email sent'], 'Check your inbox for a verification link.');
    }
} catch (PDOException $e) {
    logError('send-email-verification: ' . $e->getMessage());
    sendError('Server error.', 500);
}

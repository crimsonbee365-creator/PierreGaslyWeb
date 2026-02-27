<?php
/**
 * Reset Password via Phone OTP
 * POST /api/auth/reset-password-phone.php
 * Called after Firebase phone OTP is verified in the app
 */
require_once __DIR__ . '/../../api_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$data = getJson();
validateRequired($data, ['phone', 'new_password']);

$phone       = sanitize($data['phone']);
$newPassword = $data['new_password'];

// Normalize phone
$phone = ltrim($phone, '0');
if (!preg_match('/^9[0-9]{9}$/', $phone)) sendError('Invalid phone number');
$phone = '0' . $phone; // store as 09XXXXXXXXX

// Validate new password strength
if (strlen($newPassword) < 8)              sendError('Password must be at least 8 characters');
if (str_contains($newPassword, ' '))       sendError('Password cannot contain spaces');
if (!preg_match('/[A-Z]/', $newPassword))  sendError('Password must contain an uppercase letter');
if (!preg_match('/[a-z]/', $newPassword))  sendError('Password must contain a lowercase letter');
if (!preg_match('/[0-9]/', $newPassword))  sendError('Password must contain a number');
if (!preg_match('/[^a-zA-Z0-9]/', $newPassword)) sendError('Password must contain a special character');

try {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone = ? AND role IN ('customer','rider') LIMIT 1");
    $stmt->execute([$phone]);
    $user = $stmt->fetch();

    if (!$user) sendError('No account found with this phone number');

    $hash = hashPassword($newPassword);
    $pdo->prepare("UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE user_id = ?")
        ->execute([$hash, $user['user_id']]);

    $pdo->prepare("INSERT INTO activity_logs (user_id, action, ip_address, created_at) VALUES (?, 'password_reset_phone', ?, NOW())")
        ->execute([$user['user_id'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0']);

    respond(true, [], 'Password reset successfully');
} catch (PDOException $e) {
    logError('reset-password-phone: ' . $e->getMessage());
    sendError('Server error. Please try again.', 500);
}

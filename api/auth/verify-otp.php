<?php
/**
 * Verify OTP API
 * POST /pierre-gasly-admin/api/auth/verify-otp.php
 */

require_once __DIR__ . '/../api_config.php';

// Ensure OTP table exists
ensureOtpTable();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$data = getJsonInput();
validateRequired($data, ['phone', 'otp']);

$phone = sanitize($data['phone']);
$otp   = sanitize($data['otp']);

if (!isValidPhone($phone)) {
    sendError('Invalid phone number format');
}

if (!preg_match('/^\d{6}$/', $otp)) {
    sendError('OTP must be 6 digits');
}

try {
    $pdo = getConnection();

    $stmt = $pdo->prepare("
        SELECT id, otp_code, expires_at, verified
        FROM otp_codes
        WHERE phone = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $stmt->execute([$phone]);
    $record = $stmt->fetch();

    if (!$record) {
        sendError('No OTP found for this phone. Please request a new one.');
    }

    if ($record['verified']) {
        sendError('OTP already used. Please request a new one.');
    }

    if (strtotime($record['expires_at']) < time()) {
        sendError('OTP has expired. Please request a new one.');
    }

    if ($record['otp_code'] !== $otp) {
        sendError('Invalid OTP. Please check and try again.');
    }

    // Mark OTP as verified
    $pdo->prepare("UPDATE otp_codes SET verified = 1 WHERE id = ?")
        ->execute([$record['id']]);

    sendSuccess(['phone' => $phone], 'OTP verified successfully');

} catch (PDOException $e) {
    logError('verify-otp PDOException: ' . $e->getMessage());
    sendError('Verification failed. Please try again.', 500);
} catch (Exception $e) {
    logError('verify-otp Exception: ' . $e->getMessage());
    sendError('Server error. Please try again.', 500);
}

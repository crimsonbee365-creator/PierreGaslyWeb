<?php
/**
 * Send OTP API
 * POST /pierre-gasly-admin/api/auth/send-otp.php
 */

require_once __DIR__ . '/../api_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    sendError('Method not allowed', 405);
}

$data = getJsonInput();
validateRequired($data, ['phone']);

$phone = sanitize($data['phone']);

if (!isValidPhone($phone)) {
    sendError('Invalid phone number. Use format: 09XXXXXXXXX');
}

try {
    $pdo = getConnection();

    // Generate 6-digit OTP
    $otp     = generateOTP();
    $expiry  = date('Y-m-d H:i:s', strtotime('+10 minutes'));

    // Remove any existing OTP for this phone
    $pdo->prepare("DELETE FROM otp_codes WHERE phone = ?")->execute([$phone]);

    // Insert new OTP
    $pdo->prepare("
        INSERT INTO otp_codes (phone, otp_code, expires_at, created_at)
        VALUES (?, ?, ?, NOW())
    ")->execute([$phone, $otp, $expiry]);

    // ── TODO: Replace with real SMS gateway in production ──
    // Example (Semaphore):
    // $ch = curl_init('https://api.semaphore.co/api/v4/messages');
    // curl_setopt_array($ch, [
    //   CURLOPT_POST => true,
    //   CURLOPT_RETURNTRANSFER => true,
    //   CURLOPT_POSTFIELDS => http_build_query([
    //     'apikey'  => 'YOUR_API_KEY',
    //     'number'  => $phone,
    //     'message' => "Your Pierre Gasly OTP is: $otp. Valid 10 minutes.",
    //   ]),
    // ]);
    // curl_exec($ch); curl_close($ch);

    // Dev mode: return OTP in response so you can test without SMS
    sendSuccess([
        'phone'      => $phone,
        'otp'        => $otp,        // ← REMOVE IN PRODUCTION
        'expires_in' => 600,
    ], 'OTP sent successfully');

} catch (PDOException $e) {
    logError('send-otp PDOException: ' . $e->getMessage());
    sendError('Failed to send OTP. Please try again.', 500);
} catch (Exception $e) {
    logError('send-otp Exception: ' . $e->getMessage());
    sendError('Server error. Please try again.', 500);
}

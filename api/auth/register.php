<?php
/**
 * Pierre Gasly - Register API
 * POST /api/auth/register.php
 */
require_once __DIR__ . '/../../api_config.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') sendError('Method not allowed', 405);

$data     = getJsonInput();
$fullName = sanitize($data['full_name'] ?? '');
$email    = sanitize($data['email'] ?? '');
$phone    = sanitize($data['phone'] ?? '');
$password = $data['password'] ?? '';

// Validate name (letters, spaces, hyphens, apostrophes, commas, dots allowed)
if (strlen($fullName) < 2) sendError('Name must be at least 2 characters');
if (preg_match('/[0-9]/', $fullName)) sendError('Name cannot contain numbers');
if (!preg_match("/^[a-zA-ZÀ-ÿ][a-zA-ZÀ-ÿ '.,-]*$/u", $fullName)) sendError('Name contains invalid characters');

// Validate email
if (!isValidEmail($email)) sendError('Invalid email address');

// Validate phone: accept 9XXXXXXXXX (10 digits) or 09XXXXXXXXX (11 digits)
$phone = ltrim($phone, '0');
if (!preg_match('/^9[0-9]{9}$/', $phone)) sendError('Invalid phone. Enter 10 digits starting with 9');
$phone = '0' . $phone; // store as 09XXXXXXXXX

// Validate password
if (strlen($password) < 8)                     sendError('Password must be at least 8 characters');
if (!preg_match('/[A-Z]/', $password))          sendError('Password needs an uppercase letter');
if (!preg_match('/[a-z]/', $password))          sendError('Password needs a lowercase letter');
if (!preg_match('/[0-9]/', $password))          sendError('Password needs a number');
if (!preg_match('/[^a-zA-Z0-9]/', $password))   sendError('Password needs a special character');
if (strpos($password, ' ') !== false)           sendError('Password cannot contain spaces');

try {
    $pdo = getConnection();
    $ip  = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

    // Rate limit
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activity_logs WHERE action='register' AND ip_address=? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
    $stmt->execute([$ip]);
    if ((int)$stmt->fetchColumn() >= 5) sendError('Too many registrations. Try again later.', 429);

    // Check duplicates
    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE email=? LIMIT 1");
    $stmt->execute([$email]);
    if ($stmt->fetch()) sendError('This email is already registered');

    $stmt = $pdo->prepare("SELECT user_id FROM users WHERE phone=? LIMIT 1");
    $stmt->execute([$phone]);
    if ($stmt->fetch()) sendError('This phone number is already registered');

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("INSERT INTO users (full_name,email,password_hash,phone,role,status,email_verified,created_at) VALUES (?,?,?,?,'customer','active',0,NOW())");
    $stmt->execute([$fullName, $email, $hash, $phone]);
    $userId = (int)$pdo->lastInsertId();

    $pdo->prepare("INSERT INTO activity_logs (user_id,action,ip_address,created_at) VALUES (?,'register',?,NOW())")->execute([$userId, $ip]);

    sendSuccess([
        'token' => generateJWT($userId, $email, 'customer'),
        'user'  => [
            'user_id'        => $userId,
            'full_name'      => $fullName,
            'email'          => $email,
            'phone'          => $phone,
            'role'           => 'customer',
            'status'         => 'active',
            'email_verified' => false,
        ]
    ], 'Registration successful! Welcome to Pierre Gasly.');

} catch (PDOException $e) {
    logError('Register: ' . $e->getMessage());
    sendError('Registration failed. Please try again.', 500);
}

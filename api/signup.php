<?php
require_once __DIR__ . '/../bootstrap.php';
include __DIR__ . '/../config/db.php';

use Nithish\Medibook\Auth\JwtHandler;

header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);

$name     = isset($input['name']) ? trim($input['name']) : '';
$email    = isset($input['email']) ? trim($input['email']) : '';
$password = $input['password'] ?? '';
$confirm  = $input['confirm'] ?? '';

if (empty($name) || empty($email) || empty($password) || empty($confirm)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please fill in all fields.']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['error' => 'Please enter a valid email address.']);
    exit;
}
if (strlen($password) < 6) {
    http_response_code(400);
    echo json_encode(['error' => 'Password must be at least 6 characters.']);
    exit;
}
if ($password !== $confirm) {
    http_response_code(400);
    echo json_encode(['error' => 'Passwords do not match.']);
    exit;
}

$emailSafe = mysqli_real_escape_string($conn, $email);
$check = mysqli_query($conn, "SELECT id FROM users WHERE email = '$emailSafe'");

if (mysqli_num_rows($check) > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'An account with this email already exists.']);
    exit;
}

$nameSafe = mysqli_real_escape_string($conn, $name);
$hashed = password_hash($password, PASSWORD_DEFAULT);

mysqli_query($conn, "
    INSERT INTO users (name, email, password)
    VALUES ('$nameSafe', '$emailSafe', '$hashed')
");

$newId = mysqli_insert_id($conn);

// Auto-login: issue JWT immediately, same as login.php
$jwtHandler = new JwtHandler();
$token = $jwtHandler->generateToken($newId, $email);

setcookie('auth_token', $token, [
    'expires'  => time() + 3600,
    'path'     => '/',
    'httponly' => true,
    'samesite' => 'Lax'
]);

echo json_encode([
    'success' => true,
    'user' => [
        'id'    => $newId,
        'name'  => $name,
        'email' => $email,
        'is_subscribed' => false,
    ],
]);
<?php
require_once __DIR__ . '/../bootstrap.php';
include __DIR__ . '/../config/db.php';

use Nithish\Medibook\Auth\AuthMiddleware;

header('Content-Type: application/json');

$middleware = new AuthMiddleware();
$payload = $middleware->requireAuth(); // blocks with 401 if not logged in

$userId = (int) $payload['sub'];

// Check subscription
$r = mysqli_query($conn, "SELECT is_subscribed FROM users WHERE id = $userId");
$row = mysqli_fetch_assoc($r);
if (!$row || !$row['is_subscribed']) {
    http_response_code(403);
    echo json_encode(['error' => 'Subscription required to book appointments']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$doctor_id = isset($input['doctor_id']) ? (int) $input['doctor_id'] : 0;
$date = $input['date'] ?? '';
$time = $input['time'] ?? '';

if (!$doctor_id || !$date || !$time) {
    http_response_code(400);
    echo json_encode(['error' => 'doctor_id, date and time are required']);
    exit;
}
if (strtotime($date) < strtotime('today')) {
    http_response_code(400);
    echo json_encode(['error' => 'Please select a future date']);
    exit;
}

$dateEsc = mysqli_real_escape_string($conn, $date);
$timeEsc = mysqli_real_escape_string($conn, $time);

$check = mysqli_query($conn, "
    SELECT id FROM appointments
    WHERE user_id = $userId AND doctor_id = $doctor_id
    AND appt_date = '$dateEsc' AND status != 'cancelled'
");
if (mysqli_num_rows($check) > 0) {
    http_response_code(409);
    echo json_encode(['error' => 'You already have an appointment with this doctor on that date']);
    exit;
}

mysqli_query($conn, "
    INSERT INTO appointments (user_id, doctor_id, appt_date, appt_time, status)
    VALUES ($userId, $doctor_id, '$dateEsc', '$timeEsc', 'confirmed')
");

echo json_encode(['success' => true, 'message' => 'Appointment confirmed']);
<?php
require_once __DIR__ . '/../bootstrap.php';
include __DIR__ . '/../config/db.php';

use Nithish\Medibook\Auth\AuthMiddleware;

header('Content-Type: application/json');

$middleware = new AuthMiddleware();
$payload = $middleware->requireAuth(); // blocks with 401 if not logged in

$userId = (int) $payload['sub'];

$userResult = mysqli_query($conn, "SELECT id, name, email, is_subscribed FROM users WHERE id = $userId");
$user = mysqli_fetch_assoc($userResult);

if (!$user) {
    http_response_code(404);
    echo json_encode(['error' => 'User not found']);
    exit;
}

function fetch_appointments($conn, $userId, $upcoming = true) {
    $condition = $upcoming
        ? "a.appt_date >= CURDATE() AND a.status != 'cancelled'"
        : "(a.appt_date < CURDATE() OR a.status = 'cancelled')";
    $order = $upcoming
        ? "a.appt_date ASC, a.appt_time ASC"
        : "a.appt_date DESC, a.appt_time DESC";

    $result = mysqli_query($conn, "
        SELECT a.*, d.name AS doctor_name, d.speciality, d.rating,
               h.name AS hospital_name, h.city
        FROM appointments a
        JOIN doctors d ON a.doctor_id = d.id
        LEFT JOIN hospitals h ON d.hospital_id = h.id
        WHERE a.user_id = $userId AND $condition
        ORDER BY $order
    ");

    $appointments = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $appointments[] = [
            'id'          => (int) $row['id'],
            'doctor_id'   => (int) $row['doctor_id'],
            'doctor_name' => $row['doctor_name'],
            'speciality'  => $row['speciality'],
            'rating'      => (float) $row['rating'],
            'hospital'    => $row['hospital_name'],
            'city'        => $row['city'],
            'date'        => $row['appt_date'],
            'time'        => $row['appt_time'],
            'status'      => $row['status'],
        ];
    }
    return $appointments;
}

$upcoming = fetch_appointments($conn, $userId, true);
$past = fetch_appointments($conn, $userId, false);

echo json_encode([
    'user' => [
        'id'            => (int) $user['id'],
        'name'          => $user['name'],
        'email'         => $user['email'],
        'is_subscribed' => (bool) $user['is_subscribed'],
    ],
    'upcoming_count' => count($upcoming),
    'past_count'     => count($past),
    'upcoming'       => $upcoming,
    'past'           => $past,
]);
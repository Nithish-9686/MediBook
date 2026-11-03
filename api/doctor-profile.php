<?php
require_once __DIR__ . '/../bootstrap.php';
include __DIR__ . '/../config/db.php';

use Nithish\Medibook\Auth\AuthMiddleware;

header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid doctor id']);
    exit;
}
$doctor_id = (int) $_GET['id'];

$middleware = new AuthMiddleware();
$payload = $middleware->optionalAuth();

$is_subscribed = false;
if ($payload && isset($payload['sub'])) {
    $userId = (int) $payload['sub'];
    $r = mysqli_query($conn, "SELECT is_subscribed FROM users WHERE id = $userId");
    if ($r && $row = mysqli_fetch_assoc($r)) {
        $is_subscribed = (bool) $row['is_subscribed'];
    }
}

$result = mysqli_query($conn, "
    SELECT d.*, h.name AS hospital_name, h.city, h.address
    FROM doctors d
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    WHERE d.id = $doctor_id
");

if (mysqli_num_rows($result) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Doctor not found']);
    exit;
}

$doc = mysqli_fetch_assoc($result);

echo json_encode([
    'id'             => (int) $doc['id'],
    'name'           => $doc['name'],
    'speciality'     => $doc['speciality'],
    'expertise'      => array_map('trim', explode(',', $doc['expertise'] ?? '')),
    'education'      => array_map('trim', explode('|', $doc['education'] ?? '')),
    'languages'      => array_map('trim', explode(',', $doc['languages'] ?? '')),
    'rating'         => (float) $doc['rating'],
    'review_count'   => (int) $doc['review_count'],
    'experience'     => (int) $doc['experience'],
    'consultations'  => (int) ($doc['consultations'] ?? 0),
    'success_rate'   => $doc['success_rate'] !== null ? (float) $doc['success_rate'] : null,
    'available'      => (bool) $doc['available'],
    'about'          => $doc['about'] ?? null,
    'image'          => $doc['image'] ?? null,
    'is_subscribed'  => $is_subscribed,
    'contact'        => $is_subscribed ? [
        'hospital' => $doc['hospital_name'] ?? null,
        'city'     => $doc['city'] ?? null,
        'address'  => $doc['address'] ?? null,
        'phone'    => $doc['phone'] ?? null,
        'email'    => $doc['email'] ?? null,
    ] : null,
    'contact_locked' => !$is_subscribed,
]);
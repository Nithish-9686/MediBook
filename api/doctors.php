<?php
require_once __DIR__ . '/../bootstrap.php';
include __DIR__ . '/../config/db.php';

use Nithish\Medibook\Auth\AuthMiddleware;

header('Content-Type: application/json');

// ── AUTH (optional — endpoint works for guests too) ─────
$middleware = new AuthMiddleware();
$payload = $middleware->optionalAuth();

$is_subscribed = false;
if ($payload && isset($payload['sub'])) {
    $userId = (int) $payload['sub'];
    $userResult = mysqli_query($conn, "SELECT is_subscribed FROM users WHERE id = $userId");
    if ($userResult && $userRow = mysqli_fetch_assoc($userResult)) {
        $is_subscribed = (bool) $userRow['is_subscribed'];
    }
}

// ── FILTERS ──────────────────────────────
$search     = isset($_GET['search'])     ? trim($_GET['search'])     : '';
$speciality = isset($_GET['speciality']) ? trim($_GET['speciality']) : '';
$sort       = isset($_GET['sort'])       ? trim($_GET['sort'])       : 'rating';

$where = "WHERE 1=1";
if ($search !== '') {
    $s      = mysqli_real_escape_string($conn, $search);
    $where .= " AND (d.name LIKE '%$s%' OR d.speciality LIKE '%$s%' OR d.expertise LIKE '%$s%')";
}
if ($speciality !== '') {
    $sp     = mysqli_real_escape_string($conn, $speciality);
    $where .= " AND d.speciality = '$sp'";
}

$order = "d.rating DESC";
if ($sort === 'experience') $order = "d.experience DESC";
if ($sort === 'reviews')    $order = "d.review_count DESC";
if ($sort === 'name')       $order = "d.name ASC";

$result = mysqli_query($conn, "
    SELECT d.*, h.name AS hospital_name, h.city
    FROM doctors d
    LEFT JOIN hospitals h ON d.hospital_id = h.id
    $where
    ORDER BY $order
");

$doctors = [];
while ($doc = mysqli_fetch_assoc($result)) {
    $doctors[] = [
        'id'           => (int) $doc['id'],
        'name'         => $doc['name'],
        'speciality'   => $doc['speciality'],
        'expertise'    => array_map('trim', explode(',', $doc['expertise'] ?? '')),
        'rating'       => (float) $doc['rating'],
        'review_count' => (int) $doc['review_count'],
        'experience'   => (int) $doc['experience'],
        'available'    => (bool) $doc['available'],
        'image'        => $doc['image'] ?? null,
        'hospital'     => $is_subscribed ? [
            'name' => $doc['hospital_name'] ?? null,
            'city' => $doc['city'] ?? null,
        ] : null,
        'hospital_locked' => !$is_subscribed,
    ];
}

echo json_encode([
    'total'         => count($doctors),
    'is_subscribed' => $is_subscribed,
    'doctors'       => $doctors,
]);
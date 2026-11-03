<?php
require_once __DIR__ . '/../bootstrap.php';
include __DIR__ . '/../config/db.php';

use Nithish\Medibook\Auth\AuthMiddleware;

header('Content-Type: application/json');

$middleware = new AuthMiddleware();
$payload = $middleware->requireAuth();

$userId = (int) $payload['sub'];

$input = json_decode(file_get_contents('php://input'), true);
$appointmentId = isset($input['appointment_id']) ? (int) $input['appointment_id'] : 0;

if (!$appointmentId) {
    http_response_code(400);
    echo json_encode(['error' => 'appointment_id is required']);
    exit;
}

mysqli_query($conn, "
    UPDATE appointments
    SET status = 'cancelled'
    WHERE id = $appointmentId AND user_id = $userId
");

if (mysqli_affected_rows($conn) === 0) {
    http_response_code(404);
    echo json_encode(['error' => 'Appointment not found or already cancelled']);
    exit;
}

echo json_encode(['success' => true, 'message' => 'Appointment cancelled']);
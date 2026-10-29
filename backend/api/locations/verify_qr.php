<?php
// =============================================================================
// API Endpoint: Verify QR Code Token & Fetch Location
// Route: GET /backend/api/locations/verify_qr.php?token=CAMPUS-AB-101
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';

Response::initHeaders();

$qrToken = trim($_GET['token'] ?? '');

if (empty($qrToken)) {
    Response::error("QR code token parameter 'token' is required.", 422);
}

$pdo = Database::getConnection();

$stmt = $pdo->prepare("SELECT id, building, room, qr_code_token FROM locations WHERE qr_code_token = ?");
$stmt->execute([$qrToken]);
$location = $stmt->fetch();

if (!$location) {
    Response::error("Unrecognized campus location QR code. Please select manually.", 404);
}

Response::success("Campus location verified successfully.", $location);
?>

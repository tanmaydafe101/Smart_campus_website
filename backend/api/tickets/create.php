<?php
// =============================================================================
// API Endpoint: Create New Ticket / Complaint
// Route: POST /backend/api/tickets/create.php
// Requires Authentication (Student / Admin)
// Supports multipart/form-data for image uploads
// =============================================================================

require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../utils/response.php';
require_once __DIR__ . '/../../utils/auth_middleware.php';

Response::initHeaders();

$user = AuthMiddleware::authenticate();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Use POST.", 405);
}

// Retrieve form fields (supports both multipart and urlencoded)
$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category_id = (int)($_POST['category_id'] ?? 0);
$location_id = (int)($_POST['location_id'] ?? 0);
$priority = strtoupper(trim($_POST['priority'] ?? ''));

// Fallback to JSON payload if empty
if (empty($title)) {
    $rawInput = json_decode(file_get_contents('php://input'), true);
    if ($rawInput) {
        $title = trim($rawInput['title'] ?? '');
        $description = trim($rawInput['description'] ?? '');
        $category_id = (int)($rawInput['category_id'] ?? 0);
        $location_id = (int)($rawInput['location_id'] ?? 0);
        $priority = strtoupper(trim($rawInput['priority'] ?? ''));
    }
}

if (empty($title) || empty($description) || $category_id <= 0 || $location_id <= 0) {
    Response::error("Required fields: title, description, category_id, location_id.", 422);
}

$pdo = Database::getConnection();

// Validate category
$catStmt = $pdo->prepare("SELECT id, default_priority FROM categories WHERE id = ?");
$catStmt->execute([$category_id]);
$category = $catStmt->fetch();
if (!$category) {
    Response::error("Invalid category selected.", 422);
}

// Validate location
$locStmt = $pdo->prepare("SELECT id FROM locations WHERE id = ?");
$locStmt->execute([$location_id]);
if (!$locStmt->fetch()) {
    Response::error("Invalid campus location selected.", 422);
}

// Priority validation
$validPriorities = ['LOW', 'MEDIUM', 'HIGH', 'CRITICAL'];
if (!in_array($priority, $validPriorities, true)) {
    $priority = $category['default_priority'] ?? 'MEDIUM';
}

// Photo upload handling
$photoPath = null;
if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
    $fileTmpPath = $_FILES['photo']['tmp_name'];
    $fileSize = $_FILES['photo']['size'];
    $fileName = $_FILES['photo']['name'];
    
    // Size check: Max 5MB
    if ($fileSize > 5 * 1024 * 1024) {
        Response::error("Image file size exceeds the 5MB limit.", 422);
    }

    // Verify MIME type safely
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($fileTmpPath);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeType, $allowedMimes, true)) {
        Response::error("Invalid image format. Only JPG, PNG, and WebP are allowed.", 422);
    }

    $ext = pathinfo($fileName, PATHINFO_EXTENSION);
    if (empty($ext)) {
        $ext = ($mimeType === 'image/png') ? 'png' : 'jpg';
    }

    $uploadDir = __DIR__ . '/../../uploads/tickets/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $newFileName = 'ticket_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . strtolower($ext);
    $destPath = $uploadDir . $newFileName;

    if (move_uploaded_file($fileTmpPath, $destPath)) {
        $photoPath = 'uploads/tickets/' . $newFileName;
    }
}

// Generate unique ticket code: TICK-YYYY-XXXX
$year = date('Y');
$randCode = strtoupper(bin2hex(random_bytes(3))); // 6 hex chars
$ticketCode = "TICK-{$year}-{$randCode}";

// Default SLA calculation (High/Critical: 24h, Medium: 48h, Low: 72h)
$slaHours = 48;
if ($priority === 'CRITICAL') $slaHours = 12;
elseif ($priority === 'HIGH') $slaHours = 24;
elseif ($priority === 'LOW') $slaHours = 72;
$dueAt = date('Y-m-d H:i:s', strtotime("+{$slaHours} hours"));

$pdo->beginTransaction();
try {
    $insert = $pdo->prepare("
        INSERT INTO tickets 
        (ticket_code, reporter_id, category_id, location_id, title, description, photo_path, priority, status, created_at, due_at) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'OPEN', NOW(), ?)
    ");
    $insert->execute([
        $ticketCode,
        $user['id'],
        $category_id,
        $location_id,
        $title,
        $description,
        $photoPath,
        $priority,
        $dueAt
    ]);
    $ticketId = (int)$pdo->lastInsertId();

    // Record initial status in ticket_history
    $hist = $pdo->prepare("
        INSERT INTO ticket_history (ticket_id, changed_by, old_status, new_status, comment) 
        VALUES (?, ?, NULL, 'OPEN', 'Ticket registered by student.')
    ");
    $hist->execute([$ticketId, $user['id']]);

    $pdo->commit();

    Response::success("Complaint registered successfully!", [
        'ticket_id'   => $ticketId,
        'ticket_code' => $ticketCode,
        'status'      => 'OPEN',
        'priority'    => $priority,
        'photo_path'  => $photoPath,
        'due_at'      => $dueAt
    ], 201);
} catch (Exception $e) {
    $pdo->rollBack();
    Response::error("Failed to submit complaint: " . $e->getMessage(), 500);
}
?>

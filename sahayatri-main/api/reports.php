<?php
// system/api/reports.php
// Tourist Scam & Issue Reporting API

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$db = getDBConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list');

switch ($action) {
    case 'submit':
        requireLogin();
        $user = getCurrentUser();

        $category = $_POST['category'] ?? 'Scam';
        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $locationName = trim($_POST['location_name'] ?? 'Nepal');
        $serviceName = trim($_POST['service_name'] ?? '');
        $lat = floatval($_POST['latitude'] ?? 0);
        $lng = floatval($_POST['longitude'] ?? 0);

        if (empty($title) || empty($description)) {
            sendJsonResponse(false, 'Report title and description are required.', [], 400);
        }

        $stmt = $db->prepare("INSERT INTO reports (user_id, category, title, description, location_name, service_name, latitude, longitude, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')");
        $stmt->execute([$user['id'], $category, $title, $description, $locationName, $serviceName, $lat, $lng]);

        sendJsonResponse(true, 'Report submitted successfully! Tourism authorities and travel admins have been notified.', [
            'report_id' => $db->lastInsertId()
        ]);
        break;

    case 'list':
        $stmt = $db->prepare("SELECT r.*, u.full_name as reporter_name FROM reports r JOIN users u ON r.user_id = u.id ORDER BY r.id DESC");
        $stmt->execute();
        sendJsonResponse(true, 'Tourist issue reports', ['reports' => $stmt->fetchAll()]);
        break;

    case 'update_status':
        requireRole(['organization_admin', 'super_admin']);
        $reportId = intval($_POST['report_id'] ?? 0);
        $status = $_POST['status'] ?? 'RESOLVED';
        $adminNotes = trim($_POST['admin_notes'] ?? '');

        if (!$reportId) sendJsonResponse(false, 'Report ID required', [], 400);

        $stmt = $db->prepare("UPDATE reports SET status = ?, admin_notes = ? WHERE id = ?");
        $stmt->execute([$status, $adminNotes, $reportId]);

        sendJsonResponse(true, "Report status updated to {$status}.", ['report_id' => $reportId]);
        break;

    default:
        sendJsonResponse(false, 'Invalid report action', [], 400);
        break;
}

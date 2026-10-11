<?php
// system/api/safety.php
// Safety Map Overlays, Safety Zones & Organization Broadcast Notices API

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$db = getDBConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'zones');

switch ($action) {
    case 'zones':
        $stmt = $db->prepare("SELECT sz.*, o.name as organization_name 
                              FROM safety_zones sz 
                              LEFT JOIN organizations o ON sz.organization_id = o.id 
                              WHERE sz.is_active = 1 
                              ORDER BY FIELD(sz.risk_level, 'CRITICAL', 'HIGH', 'CAUTION', 'SAFE')");
        $stmt->execute();
        $zones = $stmt->fetchAll();

        // Format risk badges
        foreach ($zones as &$z) {
            $z['risk_badge_html'] = formatRiskBadge($z['risk_level']);
        }

        sendJsonResponse(true, 'Safety zones', ['zones' => $zones]);
        break;

    case 'broadcasts':
        $stmt = $db->prepare("SELECT ob.*, o.name as organization_name 
                              FROM organization_broadcasts ob 
                              JOIN organizations o ON ob.organization_id = o.id 
                              ORDER BY ob.id DESC LIMIT 10");
        $stmt->execute();
        sendJsonResponse(true, 'Broadcast alerts', ['broadcasts' => $stmt->fetchAll()]);
        break;

    case 'create_zone':
        requireRole(['organization_admin', 'super_admin']);
        $user = getCurrentUser();

        $title = trim($_POST['title'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $lat = floatval($_POST['latitude'] ?? 0);
        $lng = floatval($_POST['longitude'] ?? 0);
        $radius = intval($_POST['radius_meters'] ?? 500);
        $riskLevel = $_POST['risk_level'] ?? 'CAUTION';
        $riskType = $_POST['risk_type'] ?? 'General danger';

        if (empty($title) || $lat == 0 || $lng == 0) {
            sendJsonResponse(false, 'Zone title, latitude, and longitude are required.', [], 400);
        }

        $stmt = $db->prepare("INSERT INTO safety_zones (organization_id, title, description, latitude, longitude, radius_meters, risk_level, risk_type, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
        $stmt->execute([$user['organization_id'], $title, $description, $lat, $lng, $radius, $riskLevel, $riskType]);

        sendJsonResponse(true, 'Safety Zone successfully created and published!', ['zone_id' => $db->lastInsertId()]);
        break;

    case 'create_broadcast':
        requireRole(['organization_admin', 'super_admin']);
        $user = getCurrentUser();

        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $targetArea = trim($_POST['target_area'] ?? 'All Travelers');

        if (empty($title) || empty($message)) {
            sendJsonResponse(false, 'Broadcast title and message are required.', [], 400);
        }

        $stmt = $db->prepare("INSERT INTO organization_broadcasts (organization_id, title, message, target_area, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$user['organization_id'], $title, $message, $targetArea]);

        sendJsonResponse(true, 'Broadcast Notice successfully published!', ['broadcast_id' => $db->lastInsertId()]);
        break;

    default:
        sendJsonResponse(false, 'Invalid safety action', [], 400);
        break;
}

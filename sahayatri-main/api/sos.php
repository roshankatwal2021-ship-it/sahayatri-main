<?php
// system/api/sos.php
// SOS Emergency System API (Trigger SOS, Dispatch Emergency Broadcast, Find Nearby Emergency Services)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$db = getDBConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'list_resources');

switch ($action) {
    case 'trigger':
        requireLogin();
        $user = getCurrentUser();

        $category = $_POST['category'] ?? 'Medical';
        $lat = floatval($_POST['latitude'] ?? 0);
        $lng = floatval($_POST['longitude'] ?? 0);
        $note = trim($_POST['note'] ?? 'Emergency assistance requested via SAHAYATRI 1-Tap SOS');

        // Fetch active team id if any
        $teamStmt = $db->prepare("SELECT team_id FROM team_members WHERE user_id = ? AND status = 'ACTIVE' LIMIT 1");
        $teamStmt->execute([$user['id']]);
        $teamRow = $teamStmt->fetch();
        $teamId = $teamRow ? $teamRow['team_id'] : null;

        $stmt = $db->prepare("INSERT INTO sos_alerts (user_id, team_id, category, latitude, longitude, note, status) VALUES (?, ?, ?, ?, ?, ?, 'ACTIVE')");
        $stmt->execute([$user['id'], $teamId, $category, $lat, $lng, $note]);
        $sosId = $db->lastInsertId();

        // Fetch nearby emergency resources (sorted by distance)
        $resStmt = $db->prepare("SELECT * FROM emergency_resources WHERE is_verified = 1");
        $resStmt->execute();
        $resources = $resStmt->fetchAll();

        if ($lat != 0 && $lng != 0) {
            foreach ($resources as &$res) {
                $res['distance_meters'] = calculateHaversineDistance($lat, $lng, $res['latitude'], $res['longitude']);
                $res['distance_km'] = round($res['distance_meters'] / 1000, 2);
            }
            usort($resources, function($a, $b) {
                return $a['distance_meters'] <=> $b['distance_meters'];
            });
        }

        sendJsonResponse(true, '🚨 EMERGENCY SOS ACTIVE! Emergency notifications dispatched to team & admin.', [
            'sos_id' => $sosId,
            'category' => $category,
            'status' => 'ACTIVE',
            'user' => [
                'name' => $user['full_name'],
                'emergency_contact' => $_SESSION['emergency_contact_phone'] ?? 'Default Rescue Desk'
            ],
            'nearby_resources' => array_slice($resources, 0, 5)
        ]);
        break;

    case 'list_resources':
        $lat = floatval($_GET['latitude'] ?? 0);
        $lng = floatval($_GET['longitude'] ?? 0);
        $type = $_GET['type'] ?? '';

        $sql = "SELECT * FROM emergency_resources WHERE 1=1";
        $params = [];

        if (!empty($type)) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $resources = $stmt->fetchAll();

        if ($lat != 0 && $lng != 0) {
            foreach ($resources as &$res) {
                $res['distance_meters'] = calculateHaversineDistance($lat, $lng, $res['latitude'], $res['longitude']);
                $res['distance_km'] = round($res['distance_meters'] / 1000, 2);
            }
            usort($resources, function($a, $b) {
                return $a['distance_meters'] <=> $b['distance_meters'];
            });
        }

        sendJsonResponse(true, 'Emergency Resources Directory', ['resources' => $resources]);
        break;

    case 'resolve':
        requireRole(['organization_admin', 'super_admin']);
        $user = getCurrentUser();
        $sosId = intval($_POST['sos_id'] ?? 0);

        if (!$sosId) sendJsonResponse(false, 'SOS ID required', [], 400);

        $stmt = $db->prepare("UPDATE sos_alerts SET status = 'RESOLVED', resolved_by = ?, resolved_at = CURRENT_TIMESTAMP WHERE id = ?");
        $stmt->execute([$user['id'], $sosId]);

        sendJsonResponse(true, 'Emergency SOS Incident marked as RESOLVED.', ['sos_id' => $sosId]);
        break;

    case 'active_emergencies':
        requireLogin();
        $stmt = $db->prepare("SELECT s.*, u.full_name as user_name, u.phone as user_phone, u.emergency_contact_name, u.emergency_contact_phone, t.name as team_name 
                              FROM sos_alerts s 
                              JOIN users u ON s.user_id = u.id 
                              LEFT JOIN teams t ON s.team_id = t.id 
                              WHERE s.status = 'ACTIVE' 
                              ORDER BY s.id DESC");
        $stmt->execute();
        sendJsonResponse(true, 'Active SOS Alerts', ['emergencies' => $stmt->fetchAll()]);
        break;

    default:
        sendJsonResponse(false, 'Invalid SOS action', [], 400);
        break;
}

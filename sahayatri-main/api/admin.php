<?php
// system/api/admin.php
// Super Admin & Travel Admin (Tenant) Management API

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

requireRole(['organization_admin', 'super_admin']);
$user = getCurrentUser();
$db = getDBConnection();

$action = $_GET['action'] ?? ($_POST['action'] ?? 'stats');

switch ($action) {
    case 'stats':
        if ($user['role'] === 'super_admin') {
            // Platform wide stats
            $orgCount = $db->query("SELECT COUNT(*) FROM organizations")->fetchColumn();
            $userCount = $db->query("SELECT COUNT(*) FROM users WHERE role = 'user'")->fetchColumn();
            $activeTeams = $db->query("SELECT COUNT(*) FROM teams WHERE status = 'ACTIVE'")->fetchColumn();
            $sosActive = $db->query("SELECT COUNT(*) FROM sos_alerts WHERE status = 'ACTIVE'")->fetchColumn();
            $totalBookings = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
            $totalRevenue = $db->query("SELECT SUM(total_price) FROM bookings WHERE status = 'CONFIRMED'")->fetchColumn() ?: 0;

            sendJsonResponse(true, 'Super Admin Statistics', [
                'organizations' => $orgCount,
                'users' => $userCount,
                'active_teams' => $activeTeams,
                'active_sos' => $sosActive,
                'total_bookings' => $totalBookings,
                'total_revenue' => number_format($totalRevenue, 2)
            ]);
        } else {
            // Tenant isolated stats
            $orgId = $user['organization_id'];
            $pkgCount = $db->query("SELECT COUNT(*) FROM packages WHERE organization_id = $orgId")->fetchColumn();
            $accCount = $db->query("SELECT COUNT(*) FROM accommodations WHERE organization_id = $orgId")->fetchColumn();
            $zoneCount = $db->query("SELECT COUNT(*) FROM safety_zones WHERE organization_id = $orgId")->fetchColumn();
            $sosActive = $db->query("SELECT COUNT(*) FROM sos_alerts WHERE status = 'ACTIVE'")->fetchColumn();

            sendJsonResponse(true, 'Organization Admin Statistics', [
                'packages' => $pkgCount,
                'accommodations' => $accCount,
                'safety_zones' => $zoneCount,
                'active_sos' => $sosActive
            ]);
        }
        break;

    case 'toggle_org_status':
        requireRole(['super_admin']);
        $orgId = intval($_POST['org_id'] ?? 0);
        $status = $_POST['status'] ?? 'ACTIVE';

        if (!$orgId) sendJsonResponse(false, 'Organization ID required', [], 400);

        $stmt = $db->prepare("UPDATE organizations SET status = ? WHERE id = ?");
        $stmt->execute([$status, $orgId]);

        sendJsonResponse(true, "Organization status changed to {$status}.", ['org_id' => $orgId]);
        break;

    case 'review_listing':
        requireRole(['super_admin']);
        $itemType = trim($_POST['item_type'] ?? '');
        $itemId   = intval($_POST['item_id'] ?? 0);
        $decision = trim($_POST['decision'] ?? '');

        if (!$itemId || !in_array($decision, ['APPROVED', 'REJECTED'])) {
            sendJsonResponse(false, 'Invalid review parameters.', [], 400);
        }

        if ($itemType === 'package') {
            $stmt = $db->prepare("UPDATE packages SET approval_status = ? WHERE id = ?");
            $stmt->execute([$decision, $itemId]);
            sendJsonResponse(true, "Package #{$itemId} has been {$decision}.");
        } elseif ($itemType === 'accommodation') {
            $stmt = $db->prepare("UPDATE accommodations SET approval_status = ? WHERE id = ?");
            $stmt->execute([$decision, $itemId]);
            sendJsonResponse(true, "Accommodation #{$itemId} has been {$decision}.");
        } else {
            sendJsonResponse(false, 'Unknown item type.', [], 400);
        }
        break;

    case 'publish_broadcast':
        requireRole(['organization_admin', 'super_admin']);
        $title = trim($_POST['title'] ?? '');
        $message = trim($_POST['message'] ?? '');
        $targetArea = trim($_POST['target_area'] ?? 'All Region');
        $riskLevel = $_POST['risk_level'] ?? 'WARNING';

        if (empty($title) || empty($message)) {
            sendJsonResponse(false, 'Broadcast title and message are required.', [], 400);
        }

        $orgId = $user['organization_id'] ?: 1;
        $stmt = $db->prepare("INSERT INTO organization_broadcasts (organization_id, title, message, target_area, risk_level) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$orgId, $title, $message, $targetArea, $riskLevel]);

        sendJsonResponse(true, 'Organization Safety Broadcast alert published to all travelers in the region!', ['broadcast_id' => $db->lastInsertId()]);
        break;

    default:
        sendJsonResponse(false, 'Invalid admin action', [], 400);
        break;
}

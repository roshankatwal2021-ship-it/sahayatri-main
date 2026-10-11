<?php
// system/api/accommodations.php
// Hotels, Resorts, Homestays & Trust Score API

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$db = getDBConnection();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $type = $_GET['type'] ?? '';
        $destinationId = intval($_GET['destination_id'] ?? 0);
        $minTrustScore = intval($_GET['min_trust_score'] ?? 0);

        $sql = "SELECT a.*, d.name as destination_name, d.region 
                FROM accommodations a 
                JOIN destinations d ON a.destination_id = d.id 
                WHERE 1=1";
        $params = [];

        if (!empty($type)) {
            $sql .= " AND a.type = ?";
            $params[] = $type;
        }

        if ($destinationId > 0) {
            $sql .= " AND a.destination_id = ?";
            $params[] = $destinationId;
        }

        if ($minTrustScore > 0) {
            $sql .= " AND a.trust_score >= ?";
            $params[] = $minTrustScore;
        }

        $sql .= " ORDER BY a.trust_score DESC, a.rating DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $accommodations = $stmt->fetchAll();

        sendJsonResponse(true, 'Accommodations list', ['accommodations' => $accommodations]);
        break;

    case 'book':
        requireLogin();
        $user = getCurrentUser();
        $accommodationId = intval($_POST['accommodation_id'] ?? 0);
        $packageId = intval($_POST['package_id'] ?? 0);
        $travelDate = $_POST['travel_date'] ?? date('Y-m-d', strtotime('+3 days'));
        $travelersCount = intval($_POST['travelers_count'] ?? 1);
        $totalPrice = floatval($_POST['total_price'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');

        if ($accommodationId <= 0 && $packageId <= 0) {
            sendJsonResponse(false, 'Please select a valid hotel or tour package.', [], 400);
        }

        $bookingCode = 'SY-' . strtoupper(substr(md5(uniqid()), 0, 8));

        $stmt = $db->prepare("INSERT INTO bookings (booking_code, user_id, package_id, accommodation_id, travel_date, travelers_count, total_price, status, notes) VALUES (?, ?, ?, ?, ?, ?, ?, 'CONFIRMED', ?)");
        $stmt->execute([
            $bookingCode,
            $user['id'],
            $packageId > 0 ? $packageId : null,
            $accommodationId > 0 ? $accommodationId : null,
            $travelDate,
            $travelersCount,
            $totalPrice,
            $notes
        ]);

        sendJsonResponse(true, 'Booking confirmed! Reference Code: ' . $bookingCode, [
            'booking_code' => $bookingCode
        ]);
        break;

    default:
        sendJsonResponse(false, 'Invalid action', [], 400);
        break;
}

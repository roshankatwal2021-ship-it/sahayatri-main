<?php
// system/api/location.php
// Live Location Ping & Automatic Geofenced Dangerous Zone Safety Detection

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

requireLogin();
$user = getCurrentUser();
$db = getDBConnection();

$lat = floatval($_POST['latitude'] ?? $_GET['latitude'] ?? 0);
$lng = floatval($_POST['longitude'] ?? $_GET['longitude'] ?? 0);
$accuracy = floatval($_POST['accuracy'] ?? 10.0);

if ($lat == 0 || $lng == 0) {
    sendJsonResponse(false, 'Valid latitude and longitude coordinates required.', [], 400);
}

// 1. Update user location in user_locations
$locStmt = $db->prepare("INSERT INTO user_locations (user_id, latitude, longitude, accuracy) 
                         VALUES (?, ?, ?, ?) 
                         ON DUPLICATE KEY UPDATE latitude = VALUES(latitude), longitude = VALUES(longitude), accuracy = VALUES(accuracy), updated_at = CURRENT_TIMESTAMP");
$locStmt->execute([$user['id'], $lat, $lng, $accuracy]);

// 2. Geofencing Dangerous Zone Detection Algorithm
$activeZonesStmt = $db->prepare("SELECT * FROM safety_zones WHERE is_active = 1");
$activeZonesStmt->execute();
$safetyZones = $activeZonesStmt->fetchAll();

$detectedAlerts = [];

foreach ($safetyZones as $zone) {
    $distanceMeters = calculateHaversineDistance($lat, $lng, $zone['latitude'], $zone['longitude']);
    
    if ($distanceMeters <= $zone['radius_meters']) {
        // User is inside a dangerous / caution safety zone!
        
        // Cooldown check: Has an alert been logged for this user & zone in the last 15 minutes?
        $coolStmt = $db->prepare("SELECT id FROM safety_alerts WHERE user_id = ? AND zone_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 15 MINUTE) LIMIT 1");
        $coolStmt->execute([$user['id'], $zone['id']]);
        
        if (!$coolStmt->fetch()) {
            // Get user's active team id if any
            $teamStmt = $db->prepare("SELECT team_id FROM team_members WHERE user_id = ? AND status = 'ACTIVE' LIMIT 1");
            $teamStmt->execute([$user['id']]);
            $teamRow = $teamStmt->fetch();
            $teamId = $teamRow ? $teamRow['team_id'] : null;

            $alertMessage = "🚨 DANGER ZONE ALERT: You have entered a " . strtoupper($zone['risk_level']) . " zone (" . $zone['title'] . "). Risk: " . $zone['risk_type'] . ". " . $zone['description'];
            
            $logStmt = $db->prepare("INSERT INTO safety_alerts (zone_id, user_id, team_id, risk_level, message, latitude, longitude) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $logStmt->execute([$zone['id'], $user['id'], $teamId, $zone['risk_level'], $alertMessage, $lat, $lng]);

            $detectedAlerts[] = [
                'zone_id' => $zone['id'],
                'title' => $zone['title'],
                'risk_level' => $zone['risk_level'],
                'risk_type' => $zone['risk_type'],
                'description' => $zone['description'],
                'distance_meters' => round($distanceMeters),
                'message' => $alertMessage
            ];
        }
    }
}

sendJsonResponse(true, 'Location updated successfully', [
    'user_id' => $user['id'],
    'latitude' => $lat,
    'longitude' => $lng,
    'alerts_triggered' => count($detectedAlerts),
    'alerts' => $detectedAlerts
]);


<?php
// system/config/helpers.php
// Common Utility Helpers & Safety Formulas

function sendJsonResponse($success = true, $message = '', $data = [], $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success' => $success,
        'message' => $message,
        'data' => $data,
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}

function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    return htmlspecialchars(trim((string)$data), ENT_QUOTES, 'UTF-8');
}

/**
 * Calculate Haversine distance in meters between two lat/lng points
 */
function calculateHaversineDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371000; // Earth's radius in meters

    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);

    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);

    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

    return $earthRadius * $c; // Returns distance in meters
}

/**
 * Generate unique, hard-to-guess Team Code (e.g. NP-7K4X9)
 */
function generateTeamCode($prefix = 'NP') {
    $characters = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    $code = '';
    for ($i = 0; $i < 5; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    return strtoupper($prefix) . '-' . $code;
}

/**
 * Format Risk Level Badge per SAHAYATRI UI/UX PRD v2.0 rule:
 * Must always pair color token with icon AND text label.
 */
function formatRiskBadge($riskLevel) {
    switch (strtoupper($riskLevel)) {
        case 'SAFE':
            return '<span class="badge risk-safe">🟢 Safe</span>';
        case 'CAUTION':
            return '<span class="badge risk-caution">🟡 Caution</span>';
        case 'HIGH':
            return '<span class="badge risk-high">🟠 High Risk</span>';
        case 'CRITICAL':
            return '<span class="badge risk-critical">🔴 Critical</span>';
        default:
            return '<span class="badge risk-safe">🟢 Safe</span>';
    }
}

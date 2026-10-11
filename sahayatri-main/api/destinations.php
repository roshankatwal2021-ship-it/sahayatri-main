<?php
// system/api/destinations.php
// Destinations, Attractions & Packages API

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$db = getDBConnection();
$action = $_GET['action'] ?? 'list';

switch ($action) {
    case 'list':
        $category = $_GET['category'] ?? '';
        $search = $_GET['search'] ?? '';

        $sql = "SELECT d.*, o.name as organization_name 
                FROM destinations d 
                LEFT JOIN organizations o ON d.organization_id = o.id 
                WHERE 1=1";
        $params = [];

        if (!empty($category)) {
            $sql .= " AND d.category = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $sql .= " AND (d.name LIKE ? OR d.region LIKE ? OR d.description LIKE ?)";
            $searchTerm = '%' . $search . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }

        $sql .= " ORDER BY d.is_featured DESC, d.name ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $destinations = $stmt->fetchAll();

        sendJsonResponse(true, 'Destinations retrieved', ['destinations' => $destinations]);
        break;

    case 'details':
        $id = intval($_GET['id'] ?? 0);
        if (!$id) sendJsonResponse(false, 'Destination ID required', [], 400);

        $stmt = $db->prepare("SELECT d.*, o.name as organization_name FROM destinations d LEFT JOIN organizations o ON d.organization_id = o.id WHERE d.id = ?");
        $stmt->execute([$id]);
        $destination = $stmt->fetch();

        if (!$destination) sendJsonResponse(false, 'Destination not found', [], 404);

        // Fetch attractions inside this destination
        $attStmt = $db->prepare("SELECT * FROM tourist_places WHERE destination_id = ? ORDER BY rating DESC");
        $attStmt->execute([$id]);
        $destination['attractions'] = $attStmt->fetchAll();

        // Fetch tour packages
        $pkgStmt = $db->prepare("SELECT * FROM packages WHERE destination_id = ? AND status = 'ACTIVE'");
        $pkgStmt->execute([$id]);
        $destination['packages'] = $pkgStmt->fetchAll();

        // Fetch nearby accommodations
        $accStmt = $db->prepare("SELECT * FROM accommodations WHERE destination_id = ? ORDER BY rating DESC LIMIT 6");
        $accStmt->execute([$id]);
        $destination['accommodations'] = $accStmt->fetchAll();

        sendJsonResponse(true, 'Destination details', ['destination' => $destination]);
        break;

    case 'packages':
        $stmt = $db->prepare("SELECT p.*, d.name as destination_name, d.region, o.name as organization_name 
                              FROM packages p 
                              JOIN destinations d ON p.destination_id = d.id 
                              JOIN organizations o ON p.organization_id = o.id 
                              WHERE p.status = 'ACTIVE' 
                              ORDER BY p.id DESC");
        $stmt->execute();
        sendJsonResponse(true, 'Tour packages list', ['packages' => $stmt->fetchAll()]);
        break;

    default:
        sendJsonResponse(false, 'Invalid action', [], 400);
        break;
}

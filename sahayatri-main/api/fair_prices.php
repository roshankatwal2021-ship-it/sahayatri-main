<?php
// system/api/fair_prices.php
// Fair Price Checker API (Benchmark Pricing Lookup)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$db = getDBConnection();
$category = $_GET['category'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT * FROM fair_prices WHERE 1=1";
$params = [];

if (!empty($category)) {
    $sql .= " AND service_category = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $sql .= " AND (route_or_item LIKE ? OR notes LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$sql .= " ORDER BY service_category ASC, route_or_item ASC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$prices = $stmt->fetchAll();

sendJsonResponse(true, 'Fair Price benchmarks', ['prices' => $prices]);

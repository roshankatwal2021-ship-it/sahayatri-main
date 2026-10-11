<?php
// system/accommodations.php
// Dedicated Accommodations Page with Shared Navbar, Location Search & Price Filter

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$db = getDBConnection();

$type = trim($_GET['type'] ?? 'all');
$priceFilter = trim($_GET['price'] ?? 'all');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT a.*, d.name as destination_name, d.region 
        FROM accommodations a 
        JOIN destinations d ON a.destination_id = d.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (d.name LIKE ? OR d.region LIKE ? OR a.name LIKE ? OR a.address LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($type) && $type !== 'all') {
    $sql .= " AND a.type = ?";
    $params[] = $type;
}

if ($priceFilter === 'under_3000') {
    $sql .= " AND a.price_per_night <= 3000";
} elseif ($priceFilter === '3000_7000') {
    $sql .= " AND a.price_per_night BETWEEN 3000 AND 7000";
} elseif ($priceFilter === 'above_7000') {
    $sql .= " AND a.price_per_night >= 7000";
}

$sql .= " ORDER BY a.trust_score DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$accommodations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accommodations – SahaYatri Smart Tourism</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .filter-container { background: white; border-radius: 16px; padding: 20px; border: 1px solid var(--color-border); margin-bottom: 32px; box-shadow: var(--shadow-sm); }
        .filter-row { display: flex; gap: 16px; flex-wrap: wrap; align-items: center; }
        .filter-group { flex: 1; min-width: 200px; }
        .cat-pill { padding: 8px 18px; border-radius: 30px; background: white; border: 1px solid var(--color-border); font-size: 13px; font-weight: 600; color: var(--color-text-secondary); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .cat-pill.active { background: var(--color-primary-dark); color: white; border-color: var(--color-primary-dark); }
    </style>
</head>
<body>

    <!-- Include Shared Consistent Header Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-area" style="max-width: 1280px;">
        
        <!-- Title -->
        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 32px; font-weight: 800; color: var(--color-dark-accent);"><i class="fa-solid fa-hotel" style="color: var(--color-primary-dark);"></i> Verified Hotels & Stays</h1>
            <p style="color: var(--color-text-secondary);">Verified accommodations with transparent 0-100 Trust Scores</p>
        </div>

        <!-- Filter Box -->
        <div class="filter-container">
            <form action="accommodations.php" method="GET" class="filter-row">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">

                <!-- Type Filter -->
                <div class="filter-group">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 12px;"><i class="fa-solid fa-hotel"></i> Accommodation Type</label>
                    <select name="type" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo ($type === 'all') ? 'selected' : ''; ?>>All Stay Types</option>
                        <option value="Hotel" <?php echo $type === 'Hotel' ? 'selected' : ''; ?>>Hotels</option>
                        <option value="Resort" <?php echo $type === 'Resort' ? 'selected' : ''; ?>>Resorts</option>
                        <option value="Homestay" <?php echo $type === 'Homestay' ? 'selected' : ''; ?>>Local Homestays</option>
                    </select>
                </div>

                <!-- Price Filter -->
                <div class="filter-group">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 12px;"><i class="fa-solid fa-sack-dollar"></i> Price Per Night</label>
                    <select name="price" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo ($priceFilter === 'all') ? 'selected' : ''; ?>>All Rates</option>
                        <option value="under_3000" <?php echo $priceFilter === 'under_3000' ? 'selected' : ''; ?>>Under NPR 3,000 (Budget)</option>
                        <option value="3000_7000" <?php echo $priceFilter === '3000_7000' ? 'selected' : ''; ?>>NPR 3,000 – 7,000 (Mid-Range)</option>
                        <option value="above_7000" <?php echo $priceFilter === 'above_7000' ? 'selected' : ''; ?>>Above NPR 7,000 (Luxury)</option>
                    </select>
                </div>

                <div style="align-self: flex-end;">
                    <a href="accommodations.php" class="btn btn-outline" style="font-size: 13px;"><i class="fa-solid fa-rotate-left"></i> Reset Filters</a>
                </div>
            </form>
        </div>

        <!-- Grid -->
        <div class="dashboard-grid">
            <?php if (empty($accommodations)): ?>
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <i class="fa-solid fa-hotel" style="font-size: 40px; color: var(--color-text-muted); margin-bottom: 12px;"></i>
                    <h3>No accommodations found matching your criteria</h3>
                    <a href="accommodations.php" class="btn btn-primary" style="margin-top: 16px;">View All Stays</a>
                </div>
            <?php else: ?>
                <?php foreach ($accommodations as $acc): ?>
                    <div class="card">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="badge risk-safe"><i class="fa-solid fa-shield-halved"></i> Trust Score: <?php echo $acc['trust_score']; ?>/100</span>
                            <span style="font-size: 12px; font-weight: bold; color: var(--color-primary-dark);"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($acc['type']); ?></span>
                        </div>
                        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 4px;"><?php echo htmlspecialchars($acc['name']); ?></h3>
                        <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 8px;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($acc['address']); ?></p>
                        <p style="font-size: 16px; font-weight: bold; color: var(--color-primary-dark); margin-bottom: 12px;">NPR <?php echo number_format($acc['price_per_night']); ?> / night</p>
                        <p style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 16px;"><i class="fa-solid fa-wifi"></i> <?php echo htmlspecialchars($acc['facilities']); ?></p>
                        <a href="login.php" class="btn btn-outline" style="width: 100%; font-size: 13px;"><i class="fa-regular fa-calendar-check"></i> Check Availability</a>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

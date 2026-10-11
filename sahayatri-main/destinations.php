<?php
// system/destinations.php
// Dedicated Destinations Page with Shared Navbar, Location Search & Category Filters

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$db = getDBConnection();

$category = trim($_GET['category'] ?? '');
$search = trim($_GET['search'] ?? '');

$sql = "SELECT d.*, o.name as organization_name 
        FROM destinations d 
        LEFT JOIN organizations o ON d.organization_id = o.id 
        WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (d.name LIKE ? OR d.region LIKE ? OR d.description LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

if (!empty($category) && $category !== 'all') {
    $sql .= " AND d.category = ?";
    $params[] = $category;
}

$sql .= " ORDER BY d.is_featured DESC, d.name ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$destinations = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destinations – SahaYatri Smart Tourism</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .cat-pill { padding: 8px 18px; border-radius: 30px; background: white; border: 1px solid var(--color-border); font-size: 13px; font-weight: 600; color: var(--color-text-secondary); text-decoration: none; display: inline-flex; align-items: center; gap: 8px; }
        .cat-pill.active { background: var(--color-primary-dark); color: white; border-color: var(--color-primary-dark); }
    </style>
</head>
<body>

    <!-- Include Shared Consistent Header Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-area" style="max-width: 1280px;">
        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 32px; font-weight: 800; color: var(--color-dark-accent);"><i class="fa-solid fa-mountain-sun" style="color: var(--color-primary-dark);"></i> Regional Tourist Destinations</h1>
            <p style="color: var(--color-text-secondary);">Explore tourist regions in Nepal with verified safety monitoring level indicators</p>
        </div>

        <!-- Filter Bar -->
        <div style="display: flex; gap: 10px; margin-bottom: 32px; overflow-x: auto;">
            <a href="destinations.php" class="cat-pill <?php echo empty($category) ? 'active' : ''; ?>"><i class="fa-solid fa-layer-group"></i> All Regions</a>
            <a href="destinations.php?category=Historical" class="cat-pill <?php echo $category === 'Historical' ? 'active' : ''; ?>"><i class="fa-solid fa-landmark"></i> Historical</a>
            <a href="destinations.php?category=Natural" class="cat-pill <?php echo $category === 'Natural' ? 'active' : ''; ?>"><i class="fa-solid fa-tree"></i> Natural</a>
            <a href="destinations.php?category=Trekking" class="cat-pill <?php echo $category === 'Trekking' ? 'active' : ''; ?>"><i class="fa-solid fa-person-hiking"></i> Trekking</a>
            <a href="destinations.php?category=Wildlife" class="cat-pill <?php echo $category === 'Wildlife' ? 'active' : ''; ?>"><i class="fa-solid fa-hippo"></i> Wildlife</a>
        </div>

        <!-- Grid -->
        <div class="dashboard-grid">
            <?php foreach ($destinations as $d): ?>
                <div class="card" style="padding: 0; overflow: hidden;">
                    <img src="<?php echo htmlspecialchars($d['image_url']); ?>" style="width:100%;height:200px;object-fit:cover;" alt="<?php echo htmlspecialchars($d['name']); ?>">
                    <div style="padding: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span class="badge risk-safe"><i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($d['safety_level']); ?></span>
                            <span style="font-size: 12px; color: var(--color-text-secondary);"><?php echo htmlspecialchars($d['organization_name'] ?: 'Global Desk'); ?></span>
                        </div>
                        <h3 style="font-size: 18px; font-weight: 700; margin: 4px 0;"><?php echo htmlspecialchars($d['name']); ?></h3>
                        <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 12px;"><i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($d['category']); ?> · <?php echo htmlspecialchars($d['region']); ?></p>
                        <p style="font-size: 12px; color: #475569; margin-bottom: 16px;"><?php echo htmlspecialchars($d['description']); ?></p>
                        <a href="login.php" class="btn btn-secondary" style="width: 100%; font-size: 13px;"><i class="fa-solid fa-compass"></i> View Attractions & Tour Info</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>

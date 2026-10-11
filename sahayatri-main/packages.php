<?php
// system/packages.php
// Dedicated Tour Packages Page with Shared Navbar, Location Search, and Price & Difficulty Filtering

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$db = getDBConnection();

$search = trim($_GET['search'] ?? '');
$category = trim($_GET['category'] ?? '');
$priceFilter = trim($_GET['price'] ?? 'all');
$difficultyFilter = trim($_GET['difficulty'] ?? 'all');

$sql = "SELECT p.*, d.name as destination_name, d.region, d.category as dest_category, d.safety_level, o.name as organization_name 
        FROM packages p 
        JOIN destinations d ON p.destination_id = d.id 
        JOIN organizations o ON p.organization_id = o.id 
        WHERE p.status = 'ACTIVE' AND p.approval_status = 'APPROVED' AND o.status = 'ACTIVE'";
$params = [];

// Location / Search Filter
if (!empty($search)) {
    $sql .= " AND (d.name LIKE ? OR d.region LIKE ? OR p.title LIKE ? OR p.itinerary_summary LIKE ?)";
    $searchTerm = "%{$search}%";
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
    $params[] = $searchTerm;
}

// Category Filter
if (!empty($category) && $category !== 'all') {
    $sql .= " AND d.category = ?";
    $params[] = $category;
}

// Price Filter
if ($priceFilter === 'under_15k') {
    $sql .= " AND p.price_npr <= 15000";
} elseif ($priceFilter === '15k_25k') {
    $sql .= " AND p.price_npr BETWEEN 15000 AND 25000";
} elseif ($priceFilter === 'above_25k') {
    $sql .= " AND p.price_npr >= 25000";
}

// Difficulty / Safety Risk Filter
if ($difficultyFilter === 'Easy') {
    $sql .= " AND d.safety_level = 'SAFE' AND d.category IN ('Historical', 'Family', 'Heritage', 'Cultural')";
} elseif ($difficultyFilter === 'Moderate') {
    $sql .= " AND d.category IN ('Natural', 'Wildlife', 'Adventure')";
} elseif ($difficultyFilter === 'Challenging') {
    $sql .= " AND d.category = 'Trekking'";
}

$sql .= " ORDER BY p.id DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$packages = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tour Packages – SahaYatri Smart Tourism</title>
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
        
        <!-- Header Title -->
        <div style="margin-bottom: 24px;">
            <h1 style="font-size: 32px; font-weight: 800; color: var(--color-dark-accent);">
                <i class="fa-solid fa-box-archive" style="color: var(--color-primary-dark);"></i> Official Tour Packages
            </h1>
            <p style="color: var(--color-text-secondary);">
                <?php if (!empty($search)): ?>
                    Showing search results for location <b>"<?php echo htmlspecialchars($search); ?>"</b>
                <?php else: ?>
                    Browse packages published directly by verified travel agencies & tourism boards in Nepal
                <?php endif; ?>
            </p>
        </div>

        <!-- Location Search & Price + Difficulty Filter Box -->
        <div class="filter-container">
            <form action="packages.php" method="GET" class="filter-row">
                <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                
                <!-- Category Filter -->
                <div class="filter-group">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 12px;"><i class="fa-solid fa-layer-group"></i> Category</label>
                    <select name="category" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo ($category === 'all' || empty($category)) ? 'selected' : ''; ?>>All Categories</option>
                        <option value="Trekking" <?php echo $category === 'Trekking' ? 'selected' : ''; ?>>Trekking & High Altitude</option>
                        <option value="Adventure" <?php echo $category === 'Adventure' ? 'selected' : ''; ?>>Adventure Sports</option>
                        <option value="Historical" <?php echo $category === 'Historical' ? 'selected' : ''; ?>>Historical & Heritage</option>
                        <option value="Natural" <?php echo $category === 'Natural' ? 'selected' : ''; ?>>Lakes & Natural</option>
                        <option value="Wildlife" <?php echo $category === 'Wildlife' ? 'selected' : ''; ?>>Wildlife Safari</option>
                    </select>
                </div>

                <!-- Price Filter -->
                <div class="filter-group">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 12px;"><i class="fa-solid fa-sack-dollar"></i> Price Range</label>
                    <select name="price" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo ($priceFilter === 'all') ? 'selected' : ''; ?>>All Prices</option>
                        <option value="under_15k" <?php echo $priceFilter === 'under_15k' ? 'selected' : ''; ?>>Under NPR 15,000 (Budget)</option>
                        <option value="15k_25k" <?php echo $priceFilter === '15k_25k' ? 'selected' : ''; ?>>NPR 15,000 – 25,000 (Standard)</option>
                        <option value="above_25k" <?php echo $priceFilter === 'above_25k' ? 'selected' : ''; ?>>Above NPR 25,000 (Premium)</option>
                    </select>
                </div>

                <!-- Difficulty Filter -->
                <div class="filter-group">
                    <label class="form-label" style="margin-bottom: 4px; font-size: 12px;"><i class="fa-solid fa-gauge-high"></i> Difficulty Level</label>
                    <select name="difficulty" class="form-select" onchange="this.form.submit()">
                        <option value="all" <?php echo ($difficultyFilter === 'all') ? 'selected' : ''; ?>>All Difficulty Levels</option>
                        <option value="Easy" <?php echo $difficultyFilter === 'Easy' ? 'selected' : ''; ?>>🟢 Easy (Family / Heritage)</option>
                        <option value="Moderate" <?php echo $difficultyFilter === 'Moderate' ? 'selected' : ''; ?>>🟡 Moderate (Nature / Lakes)</option>
                        <option value="Challenging" <?php echo $difficultyFilter === 'Challenging' ? 'selected' : ''; ?>>🟠 Challenging (High Altitude Trekking)</option>
                    </select>
                </div>

                <div style="align-self: flex-end;">
                    <a href="packages.php" class="btn btn-outline" style="font-size: 13px;"><i class="fa-solid fa-rotate-left"></i> Reset Filters</a>
                </div>
            </form>
        </div>

        <!-- Listings Grid -->
        <div class="dashboard-grid">
            <?php if (empty($packages)): ?>
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                    <i class="fa-solid fa-magnifying-glass-location" style="font-size: 40px; color: var(--color-text-muted); margin-bottom: 12px;"></i>
                    <h3>No tour packages found matching your criteria</h3>
                    <p style="color: var(--color-text-secondary); margin-top: 6px;">Try adjusting your location search, price range, or difficulty filters.</p>
                    <a href="packages.php" class="btn btn-primary" style="margin-top: 16px;">View All Packages</a>
                </div>
            <?php else: ?>
                <?php foreach ($packages as $pkg): ?>
                    <div class="card" style="padding: 0; overflow: hidden;">
                        <img src="<?php echo htmlspecialchars($pkg['image_url'] ?: 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800'); ?>" style="width:100%;height:200px;object-fit:cover;" alt="<?php echo htmlspecialchars($pkg['title']); ?>">
                        <div style="padding: 20px;">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                <span class="badge risk-safe"><i class="fa-solid fa-building"></i> <?php echo htmlspecialchars($pkg['organization_name']); ?></span>
                                <span style="font-size: 12px; font-weight: bold; color: var(--color-text-secondary);"><i class="fa-regular fa-clock"></i> <?php echo $pkg['duration_days']; ?> Days</span>
                            </div>
                            <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;"><?php echo htmlspecialchars($pkg['title']); ?></h3>
                            <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 8px;"><i class="fa-solid fa-location-dot" style="color: var(--color-primary-dark);"></i> <?php echo htmlspecialchars($pkg['destination_name']); ?> · <?php echo htmlspecialchars($pkg['region']); ?></p>
                            
                            <!-- Difficulty Tag -->
                            <p style="font-size: 12px; margin-bottom: 12px;">
                                Difficulty: 
                                <?php if ($pkg['dest_category'] === 'Trekking'): ?>
                                    <span class="badge risk-high">🟠 Challenging Trek</span>
                                <?php elseif ($pkg['dest_category'] === 'Historical'): ?>
                                    <span class="badge risk-safe">🟢 Easy / Sightseeing</span>
                                <?php else: ?>
                                    <span class="badge risk-caution">🟡 Moderate</span>
                                <?php endif; ?>
                            </p>

                            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--color-border);">
                                <div>
                                    <span style="font-size: 11px; color: var(--color-text-secondary); display: block;">Price</span>
                                    <strong style="font-size: 18px; color: var(--color-primary-dark);">NPR <?php echo number_format($pkg['price_npr']); ?></strong>
                                </div>
                                <a href="package_details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-primary" style="font-size: 13px;"><i class="fa-solid fa-eye"></i> View Package & Details</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>

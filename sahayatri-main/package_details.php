<?php
// system/package_details.php
// Dedicated Package & Destination Details Page with Booking Form & Agency Contact

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$db = getDBConnection();

$packageId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Package Details along with Destination and Organization info
$stmt = $db->prepare("SELECT p.*, 
                             d.name as destination_name, d.region as dest_region, d.category as dest_category, 
                             d.description as dest_description, d.safety_level as dest_safety_level, 
                             d.best_season, d.latitude, d.longitude, d.image_url as dest_image,
                             o.id as org_id, o.name as organization_name, o.email as org_email, o.phone as org_phone, o.address as org_address
                      FROM packages p 
                      JOIN destinations d ON p.destination_id = d.id 
                      JOIN organizations o ON p.organization_id = o.id 
                      WHERE p.id = ?");
$stmt->execute([$packageId]);
$package = $stmt->fetch();

if (!$package) {
    header('Location: packages.php');
    exit();
}

$user = getCurrentUser();
$bookingSuccess = '';
$bookingError = '';

// Handle Booking Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_now') {
    if (!$user) {
        $bookingError = 'Please sign in or register a traveler account to book this package.';
    } else {
        $travelDate = trim($_POST['travel_date'] ?? '');
        $travelersCount = intval($_POST['travelers_count'] ?? 1);
        $notes = trim($_POST['notes'] ?? '');

        if (empty($travelDate)) {
            $bookingError = 'Please select a valid travel date.';
        } else {
            $totalPrice = $package['price_npr'] * $travelersCount;
            $bookingCode = 'BK-' . strtoupper(substr(md5(uniqid()), 0, 8));

            try {
                $bookStmt = $db->prepare("INSERT INTO bookings (booking_code, user_id, package_id, travel_date, travelers_count, total_price, status, notes) VALUES (?, ?, ?, ?, ?, ?, 'CONFIRMED', ?)");
                $bookStmt->execute([$bookingCode, $user['id'], $package['id'], $travelDate, $travelersCount, $totalPrice, $notes]);

                $bookingSuccess = "Booking Successful! Your Booking Code is <strong>{$bookingCode}</strong>. Total Price: NPR " . number_format($totalPrice);
            } catch (Exception $e) {
                $bookingError = "Booking failed: " . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($package['title']); ?> – SahaYatri Details</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .details-hero {
            background: linear-gradient(135deg, #0F172A 0%, #0284C7 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 32px;
        }
        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }
        @media (max-width: 900px) {
            .details-grid { grid-template-columns: 1fr; }
        }
        .info-section-title {
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--color-dark-accent);
            border-bottom: 2px solid var(--color-primary-light);
            padding-bottom: 8px;
        }
    </style>
</head>
<body>

    <!-- Include Shared Consistent Header Navbar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-area" style="max-width: 1280px; padding: 40px 20px;">

        <!-- Back Link -->
        <a href="packages.php" class="btn btn-outline" style="margin-bottom: 20px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Packages
        </a>

        <!-- Hero Header -->
        <div class="details-hero">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                <div>
                    <span class="badge risk-safe" style="background: rgba(255,255,255,0.2); color: white; margin-bottom: 12px;">
                        <i class="fa-solid fa-building"></i> Listed by <?php echo htmlspecialchars($package['organization_name']); ?>
                    </span>
                    <h1 style="font-size: 32px; font-weight: 800; margin: 8px 0;"><?php echo htmlspecialchars($package['title']); ?></h1>
                    <p style="font-size: 15px; opacity: 0.9;">
                        <i class="fa-solid fa-location-dot" style="color: #38BDF8;"></i> <?php echo htmlspecialchars($package['destination_name']); ?> · <?php echo htmlspecialchars($package['dest_region']); ?>
                    </p>
                </div>
                <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 16px 24px; border-radius: 16px; text-align: right;">
                    <span style="font-size: 12px; display: block; opacity: 0.8;">Package Rate</span>
                    <strong style="font-size: 28px; color: #FFFFFF;">NPR <?php echo number_format($package['price_npr']); ?></strong>
                    <span style="font-size: 13px; display: block; opacity: 0.9;"><i class="fa-regular fa-clock"></i> <?php echo $package['duration_days']; ?> Days Duration</span>
                </div>
            </div>
        </div>

        <?php if ($bookingSuccess): ?>
            <div style="background: #DCFCE7; color: #15803D; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 15px;">
                <i class="fa-solid fa-circle-check"></i> <?php echo $bookingSuccess; ?>
                <br><a href="traveler_dashboard.php" style="color: #15803D; font-weight: bold; text-decoration: underline; margin-top: 8px; display: inline-block;">Go to Traveler Dashboard</a>
            </div>
        <?php endif; ?>

        <?php if ($bookingError): ?>
            <div style="background: #FEE2E2; color: #B91C1C; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 14px; font-weight: 600;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $bookingError; ?>
                <?php if (!$user): ?>
                    <a href="login.php" class="btn btn-primary" style="margin-left: 12px; padding: 4px 12px; font-size: 12px;">Sign In Now</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <!-- Main Details Content Grid -->
        <div class="details-grid">
            
            <!-- Left Column: Complete Package & Destination Details -->
            <div>
                <!-- Image Banner -->
                <img src="<?php echo htmlspecialchars($package['image_url'] ?: 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1200'); ?>" style="width: 100%; height: 360px; object-fit: cover; border-radius: 16px; margin-bottom: 32px; box-shadow: var(--shadow-sm);" alt="<?php echo htmlspecialchars($package['title']); ?>">

                <!-- Destination Details Box -->
                <div class="card" style="margin-bottom: 32px; background: #F8FAFC; border-left: 4px solid var(--color-primary-dark);">
                    <h2 class="info-section-title"><i class="fa-solid fa-mountain-sun" style="color: var(--color-primary-dark);"></i> Destination Details & Safety Overview</h2>
                    <div style="display: flex; gap: 16px; align-items: center; margin-bottom: 12px; flex-wrap: wrap;">
                        <span class="badge risk-safe"><i class="fa-solid fa-shield-halved"></i> Safety Monitoring: <?php echo htmlspecialchars($package['dest_safety_level']); ?></span>
                        <span style="font-size: 13px; font-weight: bold; color: var(--color-text-secondary);"><i class="fa-solid fa-calendar-days"></i> Recommended Season: <?php echo htmlspecialchars($package['best_season']); ?></span>
                    </div>
                    <p style="font-size: 14px; line-height: 1.6; color: #334155; margin-bottom: 16px;">
                        <?php echo nl2br(htmlspecialchars($package['dest_description'])); ?>
                    </p>
                    <div style="font-size: 12px; color: #64748B; background: white; padding: 10px 14px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <i class="fa-solid fa-circle-info" style="color: var(--color-primary-dark);"></i> <b>Traveler Note:</b> This region is continuously monitored by SahaYatri Safety geofencing. High altitude trekking safety guidelines apply.
                    </div>
                </div>

                <!-- Itinerary & Inclusions -->
                <div class="card" style="margin-bottom: 32px;">
                    <h2 class="info-section-title"><i class="fa-solid fa-list-check" style="color: var(--color-primary-dark);"></i> Tour Itinerary Breakdown</h2>
                    <p style="font-size: 14px; line-height: 1.7; color: var(--color-text-primary); margin-bottom: 24px;">
                        <?php echo nl2br(htmlspecialchars($package['itinerary_summary'])); ?>
                    </p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; border-top: 1px solid var(--color-border); padding-top: 20px;">
                        <div>
                            <h4 style="color: #15803D; margin-bottom: 10px;"><i class="fa-solid fa-circle-check"></i> What's Included</h4>
                            <p style="font-size: 13px; color: #334155; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($package['inclusions'])); ?>
                            </p>
                        </div>
                        <div>
                            <h4 style="color: #B91C1C; margin-bottom: 10px;"><i class="fa-solid fa-circle-xmark"></i> What's Excluded</h4>
                            <p style="font-size: 13px; color: #334155; line-height: 1.6;">
                                <?php echo nl2br(htmlspecialchars($package['exclusions'])); ?>
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Organizing Agency Details -->
                <div class="card">
                    <h2 class="info-section-title"><i class="fa-solid fa-building-flag" style="color: var(--color-primary-dark);"></i> Travel Agency Partner Details</h2>
                    <h3 style="font-size: 18px; font-weight: 700;"><?php echo htmlspecialchars($package['organization_name']); ?></h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin: 6px 0 12px 0;">
                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($package['org_address']); ?>
                    </p>
                    <div style="display: flex; gap: 20px; font-size: 13px; flex-wrap: wrap;">
                        <span><i class="fa-solid fa-envelope" style="color: var(--color-primary-dark);"></i> Email: <?php echo htmlspecialchars($package['org_email']); ?></span>
                        <span><i class="fa-solid fa-phone" style="color: var(--color-primary-dark);"></i> Phone: <?php echo htmlspecialchars($package['org_phone']); ?></span>
                    </div>

                    <?php if ($user): ?>
                        <a href="traveler_dashboard.php?tab=chat&org_id=<?php echo $package['org_id']; ?>" class="btn btn-outline" style="margin-top: 16px; font-size: 13px; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-comments"></i> Send Direct Message to Agency
                        </a>
                    <?php endif; ?>
                </div>

            </div>

            <!-- Right Column: Booking Form Container -->
            <div>
                <div class="card" style="position: sticky; top: 90px; border-top: 4px solid var(--color-primary-dark); box-shadow: var(--shadow-md);">
                    <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 6px;"><i class="fa-solid fa-calendar-check" style="color: var(--color-primary-dark);"></i> Book This Package</h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 20px;">Instant reservation through SahaYatri safety portal</p>

                    <form method="POST">
                        <input type="hidden" name="action" value="book_now">

                        <div class="form-group">
                            <label class="form-label">Selected Package</label>
                            <input type="text" class="form-input" value="<?php echo htmlspecialchars($package['title']); ?>" disabled readonly style="background: #F1F5F9;">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Travel Date</label>
                            <input type="date" name="travel_date" class="form-input" min="<?php echo date('Y-m-d'); ?>" required>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Number of Travelers</label>
                            <input type="number" name="travelers_count" id="travelers_count" class="form-input" value="1" min="1" max="<?php echo $package['max_capacity']; ?>" required onchange="calculateTotal()">
                        </div>

                        <div class="form-group">
                            <label class="form-label">Special Requests / Notes (Optional)</label>
                            <textarea name="notes" class="form-textarea" rows="2" placeholder="e.g. Dietary preferences, airport pick-up request..."></textarea>
                        </div>

                        <div style="background: #F0F9FF; padding: 16px; border-radius: 12px; margin-bottom: 20px; border: 1px solid #BAE6FD;">
                            <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                                <span>Per Person Rate:</span>
                                <b>NPR <?php echo number_format($package['price_npr']); ?></b>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 16px; font-weight: bold; color: var(--color-primary-dark);">
                                <span>Estimated Total:</span>
                                <span id="display-total-price">NPR <?php echo number_format($package['price_npr']); ?></span>
                            </div>
                        </div>

                        <?php if ($user): ?>
                            <button type="submit" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 15px;">
                                <i class="fa-solid fa-cart-shopping"></i> Book Package Now
                            </button>
                        <?php else: ?>
                            <a href="login.php" class="btn btn-primary" style="width: 100%; padding: 14px; font-size: 14px; text-align: center;">
                                <i class="fa-solid fa-right-to-bracket"></i> Sign In to Book
                            </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

        </div>

    </div>

    <script>
        const unitPrice = <?php echo floatval($package['price_npr']); ?>;
        function calculateTotal() {
            const count = parseInt(document.getElementById('travelers_count').value) || 1;
            const total = unitPrice * count;
            document.getElementById('display-total-price').innerText = 'NPR ' + total.toLocaleString();
        }
    </script>
</body>
</html>

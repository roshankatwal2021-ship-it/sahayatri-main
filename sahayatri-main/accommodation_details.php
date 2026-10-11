<?php
// system/accommodation_details.php
// Detailed Accommodation View Page with Booking Form

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$db = getDBConnection();

$accId = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch Accommodation Details
$stmt = $db->prepare("SELECT a.*, 
                             d.name as destination_name, d.region as dest_region, d.category as dest_category,
                             d.description as dest_description, d.safety_level as dest_safety_level,
                             d.best_season, d.latitude as dest_lat, d.longitude as dest_lng,
                             o.id as org_id, o.name as organization_name, o.email as org_email, o.phone as org_phone, o.address as org_address
                      FROM accommodations a
                      JOIN destinations d ON a.destination_id = d.id
                      LEFT JOIN organizations o ON a.organization_id = o.id
                      WHERE a.id = ? AND a.is_verified = 1 AND a.approval_status = 'APPROVED'");
$stmt->execute([$accId]);
$hotel = $stmt->fetch();

if (!$hotel) {
    header('Location: traveler_dashboard.php?tab=tab-hotels');
    exit();
}

$user = getCurrentUser();
$bookingSuccess = '';
$bookingError = '';

// Handle Booking Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'book_accommodation') {
    if (!$user) {
        $bookingError = 'Please sign in or register a traveler account to book this accommodation.';
    } else {
        $checkIn  = trim($_POST['check_in'] ?? '');
        $checkOut = trim($_POST['check_out'] ?? '');
        $guests   = intval($_POST['guests'] ?? 1);
        $notes    = trim($_POST['notes'] ?? '');

        if (empty($checkIn) || empty($checkOut)) {
            $bookingError = 'Please select valid check-in and check-out dates.';
        } elseif (strtotime($checkOut) <= strtotime($checkIn)) {
            $bookingError = 'Check-out date must be after check-in date.';
        } else {
            $nights = (strtotime($checkOut) - strtotime($checkIn)) / 86400;
            $totalPrice = $hotel['price_per_night'] * $nights * $guests;
            $bookingCode = 'ACC-' . strtoupper(substr(md5(uniqid()), 0, 8));

            try {
                $bookStmt = $db->prepare("INSERT INTO bookings (booking_code, user_id, accommodation_id, travel_date, travelers_count, total_price, status, notes) VALUES (?, ?, ?, ?, ?, ?, 'CONFIRMED', ?)");
                $bookStmt->execute([$bookingCode, $user['id'], $hotel['id'], $checkIn, $guests, $totalPrice, $notes]);
                $bookingSuccess = "Booking Confirmed! Your Booking Code is <strong>{$bookingCode}</strong>. Total: NPR " . number_format($totalPrice) . " for {$nights} night(s).";
            } catch (Exception $e) {
                $bookingError = 'Booking failed. Please try again.';
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
    <title><?php echo htmlspecialchars($hotel['name']); ?> – SahaYatri Accommodation</title>
    <meta name="description" content="Book <?php echo htmlspecialchars($hotel['name']); ?> in <?php echo htmlspecialchars($hotel['destination_name']); ?> – Verified by SahaYatri Safety Platform.">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .details-hero {
            background: linear-gradient(135deg, #0F172A 0%, #0369A1 100%);
            color: white;
            padding: 40px;
            border-radius: 20px;
            margin-bottom: 32px;
            position: relative;
            overflow: hidden;
        }
        .details-hero::before {
            content: '';
            position: absolute;
            inset: 0;
            background: url('<?php echo htmlspecialchars($hotel['image_url'] ?: ''); ?>') center/cover no-repeat;
            opacity: 0.18;
            z-index: 0;
        }
        .details-hero > * { position: relative; z-index: 1; }

        .details-grid {
            display: grid;
            grid-template-columns: 2fr 1fr;
            gap: 32px;
        }
        @media (max-width: 900px) {
            .details-grid { grid-template-columns: 1fr; }
        }
        .info-section-title {
            font-size: 19px;
            font-weight: 700;
            margin-bottom: 16px;
            color: var(--color-dark-accent);
            border-bottom: 2px solid var(--color-primary-light);
            padding-bottom: 8px;
        }
        .facility-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #F0F9FF;
            color: #0369A1;
            border: 1px solid #BAE6FD;
            border-radius: 20px;
            padding: 5px 12px;
            font-size: 12px;
            font-weight: 600;
            margin: 4px;
        }
        .trust-bar-wrap { background: #E2E8F0; border-radius: 20px; height: 10px; overflow: hidden; margin-top: 6px; }
        .trust-bar { height: 100%; border-radius: 20px; background: linear-gradient(90deg, #10B981, #0EA5E9); }
        .stars { color: #F59E0B; }
        .booking-sticky {
            position: sticky;
            top: 88px;
            border-top: 4px solid #0369A1;
            box-shadow: 0 8px 32px rgba(3,105,161,0.12);
        }
        .total-price-box {
            background: #F0F9FF;
            border: 1px solid #BAE6FD;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <div class="content-area" style="max-width: 1280px; padding: 40px 20px;">

        <!-- Back Link -->
        <a href="traveler_dashboard.php?tab=tab-hotels" class="btn btn-outline" style="margin-bottom: 24px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-arrow-left"></i> Back to Hotels & Stays
        </a>

        <!-- Hero Banner -->
        <div class="details-hero">
            <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px;">
                <div>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap; margin-bottom: 14px;">
                        <span class="badge" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fa-solid fa-shield-halved"></i> SahaYatri Verified
                        </span>
                        <span class="badge" style="background: rgba(255,255,255,0.2); color: white; border: 1px solid rgba(255,255,255,0.3);">
                            <i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($hotel['type']); ?>
                        </span>
                    </div>
                    <h1 style="font-size: 34px; font-weight: 800; margin: 0 0 10px 0; text-shadow: 0 2px 8px rgba(0,0,0,0.4);">
                        <?php echo htmlspecialchars($hotel['name']); ?>
                    </h1>
                    <p style="font-size: 15px; opacity: 0.9; margin-bottom: 10px;">
                        <i class="fa-solid fa-location-dot" style="color: #38BDF8;"></i>
                        <?php echo htmlspecialchars($hotel['address']); ?> ·
                        <?php echo htmlspecialchars($hotel['destination_name']); ?>, <?php echo htmlspecialchars($hotel['dest_region']); ?>
                    </p>
                    <div style="display: flex; gap: 16px; align-items: center; flex-wrap: wrap;">
                        <span style="font-size: 14px;" class="stars">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fa-<?php echo $i <= round($hotel['rating']) ? 'solid' : 'regular'; ?> fa-star"></i>
                            <?php endfor; ?>
                            <span style="color: white; margin-left: 4px;"><?php echo $hotel['rating']; ?>/5.0</span>
                        </span>
                        <span style="font-size: 13px; color: rgba(255,255,255,0.85);">
                            <i class="fa-solid fa-shield-check"></i> Trust Score: <?php echo $hotel['trust_score']; ?>/100
                        </span>
                    </div>
                </div>
                <div style="background: rgba(255,255,255,0.15); backdrop-filter: blur(10px); padding: 20px 28px; border-radius: 18px; text-align: center; border: 1px solid rgba(255,255,255,0.25);">
                    <span style="font-size: 12px; display: block; opacity: 0.8; margin-bottom: 4px;">Price Per Night</span>
                    <strong style="font-size: 30px; color: #FFFFFF; display: block;">NPR <?php echo number_format($hotel['price_per_night']); ?></strong>
                    <span style="font-size: 12px; opacity: 0.8;">per room/night</span>
                </div>
            </div>
        </div>

        <!-- Success / Error Messages -->
        <?php if ($bookingSuccess): ?>
            <div style="background: #DCFCE7; color: #15803D; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 15px; border: 1px solid #86EFAC;">
                <i class="fa-solid fa-circle-check"></i> <?php echo $bookingSuccess; ?>
            </div>
        <?php endif; ?>
        <?php if ($bookingError): ?>
            <div style="background: #FEE2E2; color: #B91C1C; padding: 16px 20px; border-radius: 12px; margin-bottom: 24px; font-size: 15px; border: 1px solid #FCA5A5;">
                <i class="fa-solid fa-circle-exclamation"></i> <?php echo $bookingError; ?>
            </div>
        <?php endif; ?>

        <!-- Main Content Grid -->
        <div class="details-grid">

            <!-- LEFT: Details Column -->
            <div>
                <!-- Photo -->
                <img src="<?php echo htmlspecialchars($hotel['image_url'] ?: 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1200'); ?>"
                     style="width: 100%; height: 340px; object-fit: cover; border-radius: 16px; margin-bottom: 28px; box-shadow: 0 4px 20px rgba(0,0,0,0.12);"
                     alt="<?php echo htmlspecialchars($hotel['name']); ?>">

                <!-- Trust Score Card -->
                <div class="card" style="margin-bottom: 28px; background: #F8FAFC; border-left: 4px solid #10B981;">
                    <h2 class="info-section-title"><i class="fa-solid fa-shield-halved" style="color: #10B981;"></i> Safety & Trust Assessment</h2>
                    <div style="display: flex; gap: 32px; flex-wrap: wrap; margin-bottom: 16px;">
                        <div>
                            <div style="font-size: 13px; color: #64748B; margin-bottom: 4px;">SahaYatri Trust Score</div>
                            <div style="font-size: 28px; font-weight: 800; color: #10B981;"><?php echo $hotel['trust_score']; ?><span style="font-size: 14px;">/100</span></div>
                            <div class="trust-bar-wrap" style="width: 180px;">
                                <div class="trust-bar" style="width: <?php echo $hotel['trust_score']; ?>%;"></div>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 13px; color: #64748B; margin-bottom: 4px;">Guest Rating</div>
                            <div style="font-size: 28px; font-weight: 800; color: #F59E0B;"><?php echo $hotel['rating']; ?><span style="font-size: 14px;">/5.0</span></div>
                            <div class="stars" style="font-size: 14px;">
                                <?php for ($i = 1; $i <= 5; $i++): ?><i class="fa-<?php echo $i <= round($hotel['rating']) ? 'solid' : 'regular'; ?> fa-star"></i><?php endfor; ?>
                            </div>
                        </div>
                        <div>
                            <div style="font-size: 13px; color: #64748B; margin-bottom: 4px;">Safety Zone</div>
                            <span class="badge risk-safe" style="font-size: 13px; padding: 6px 12px;">
                                <i class="fa-solid fa-circle-check"></i> <?php echo htmlspecialchars($hotel['dest_safety_level'] ?? 'Safe'); ?>
                            </span>
                        </div>
                    </div>
                    <div style="font-size: 12px; color: #64748B; background: white; padding: 10px 14px; border-radius: 8px; border: 1px solid #E2E8F0;">
                        <i class="fa-solid fa-circle-info" style="color: #0369A1;"></i>
                        <b>Traveler Note:</b> This accommodation has been independently verified and rated by SahaYatri safety officers. Trust Score is based on sanitation, fire safety, emergency exits, and guest feedback.
                    </div>
                </div>

                <!-- About / Description -->
                <?php if (!empty($hotel['description'])): ?>
                <div class="card" style="margin-bottom: 28px;">
                    <h2 class="info-section-title"><i class="fa-solid fa-circle-info" style="color: #0369A1;"></i> About This Accommodation</h2>
                    <p style="font-size: 14px; line-height: 1.8; color: #334155;">
                        <?php echo nl2br(htmlspecialchars($hotel['description'])); ?>
                    </p>
                </div>
                <?php endif; ?>

                <!-- Facilities -->
                <?php if (!empty($hotel['facilities'])): ?>
                <div class="card" style="margin-bottom: 28px;">
                    <h2 class="info-section-title"><i class="fa-solid fa-list-check" style="color: #0369A1;"></i> Facilities & Amenities</h2>
                    <div>
                        <?php
                        $facilityIcons = [
                            'wifi' => 'fa-wifi', 'wi-fi' => 'fa-wifi',
                            'parking' => 'fa-square-parking',
                            'restaurant' => 'fa-utensils',
                            'pool' => 'fa-person-swimming', 'swimming' => 'fa-person-swimming',
                            'gym' => 'fa-dumbbell',
                            'spa' => 'fa-spa',
                            'bar' => 'fa-martini-glass',
                            'laundry' => 'fa-shirt',
                            'ac' => 'fa-snowflake', 'air' => 'fa-snowflake',
                            'tv' => 'fa-tv',
                            'heater' => 'fa-fire',
                            'hot' => 'fa-hot-tub-person',
                            'breakfast' => 'fa-mug-hot',
                            'airport' => 'fa-plane',
                            'safe' => 'fa-lock',
                        ];
                        $facilities = array_map('trim', explode(',', $hotel['facilities']));
                        foreach ($facilities as $fac):
                            if (empty($fac)) continue;
                            $icon = 'fa-check-circle';
                            foreach ($facilityIcons as $key => $ico) {
                                if (stripos($fac, $key) !== false) { $icon = $ico; break; }
                            }
                        ?>
                            <span class="facility-chip">
                                <i class="fa-solid <?php echo $icon; ?>"></i>
                                <?php echo htmlspecialchars($fac); ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Destination Info -->
                <div class="card" style="margin-bottom: 28px; background: #F8FAFC; border-left: 4px solid #0369A1;">
                    <h2 class="info-section-title"><i class="fa-solid fa-mountain-sun" style="color: #0369A1;"></i> Destination Overview</h2>
                    <div style="display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 12px;">
                        <span class="badge risk-safe"><i class="fa-solid fa-map-location-dot"></i> <?php echo htmlspecialchars($hotel['dest_region']); ?></span>
                        <?php if ($hotel['dest_category']): ?>
                            <span class="badge risk-caution"><i class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($hotel['dest_category']); ?></span>
                        <?php endif; ?>
                        <?php if ($hotel['best_season']): ?>
                            <span style="font-size: 13px; color: var(--color-text-secondary);"><i class="fa-solid fa-sun"></i> Best Season: <?php echo htmlspecialchars($hotel['best_season']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($hotel['dest_description']): ?>
                        <p style="font-size: 14px; line-height: 1.6; color: #334155;"><?php echo nl2br(htmlspecialchars($hotel['dest_description'])); ?></p>
                    <?php endif; ?>
                </div>

                <!-- Agency/Provider Contact -->
                <?php if ($hotel['org_id']): ?>
                <div class="card">
                    <h2 class="info-section-title"><i class="fa-solid fa-building-flag" style="color: #0369A1;"></i> Property Manager / Agency</h2>
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 4px;"><?php echo htmlspecialchars($hotel['organization_name']); ?></h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 12px;">
                        <i class="fa-solid fa-location-dot"></i> <?php echo htmlspecialchars($hotel['org_address'] ?? 'Nepal'); ?>
                    </p>
                    <div style="display: flex; gap: 20px; font-size: 13px; flex-wrap: wrap; margin-bottom: 16px;">
                        <?php if ($hotel['org_email']): ?>
                            <span><i class="fa-solid fa-envelope" style="color: #0369A1;"></i> <?php echo htmlspecialchars($hotel['org_email']); ?></span>
                        <?php endif; ?>
                        <?php if ($hotel['org_phone']): ?>
                            <span><i class="fa-solid fa-phone" style="color: #0369A1;"></i> <?php echo htmlspecialchars($hotel['org_phone']); ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($user): ?>
                        <a href="traveler_dashboard.php?tab=tab-agency-chat&org_id=<?php echo $hotel['org_id']; ?>" class="btn btn-outline" style="font-size: 13px; display: inline-flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-comments"></i> Message This Agency
                        </a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Booking Form Column -->
            <div>
                <div class="card booking-sticky">
                    <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 6px; color: #0F172A;">
                        <i class="fa-solid fa-calendar-check" style="color: #0369A1;"></i> Reserve This Stay
                    </h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 20px;">Instant confirmation via SahaYatri portal</p>

                    <?php if (!$user): ?>
                        <div style="text-align: center; padding: 24px;">
                            <i class="fa-solid fa-lock" style="font-size: 32px; color: #CBD5E1; margin-bottom: 12px;"></i>
                            <p style="color: #64748B; font-size: 13px; margin-bottom: 16px;">Please sign in to book this accommodation.</p>
                            <a href="index.php" class="btn btn-primary" style="width: 100%;">Sign In to Book</a>
                        </div>
                    <?php else: ?>
                        <form method="POST" id="booking-form">
                            <input type="hidden" name="action" value="book_accommodation">

                            <div class="form-group">
                                <label class="form-label">Selected Accommodation</label>
                                <input type="text" class="form-input" value="<?php echo htmlspecialchars($hotel['name']); ?>" disabled readonly style="background: #F1F5F9;">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Check-In Date</label>
                                <input type="date" name="check_in" id="check-in" class="form-input" min="<?php echo date('Y-m-d'); ?>" required onchange="calculateNights()">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Check-Out Date</label>
                                <input type="date" name="check_out" id="check-out" class="form-input" min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" required onchange="calculateNights()">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Number of Guests</label>
                                <input type="number" name="guests" id="guests" class="form-input" value="1" min="1" max="10" required onchange="calculateNights()">
                            </div>

                            <div class="form-group">
                                <label class="form-label">Special Requests (Optional)</label>
                                <textarea name="notes" class="form-textarea" rows="2" placeholder="e.g. Late check-in, extra bed, dietary needs..."></textarea>
                            </div>

                            <!-- Price Calculator -->
                            <div class="total-price-box">
                                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                                    <span>Per Night Rate:</span>
                                    <b>NPR <?php echo number_format($hotel['price_per_night']); ?></b>
                                </div>
                                <div style="display: flex; justify-content: space-between; font-size: 13px; margin-bottom: 6px;">
                                    <span>Duration:</span>
                                    <b id="nights-display">Select dates</b>
                                </div>
                                <hr style="border-color: #BAE6FD; margin: 10px 0;">
                                <div style="display: flex; justify-content: space-between; font-size: 17px; font-weight: 800; color: #0369A1;">
                                    <span>Estimated Total:</span>
                                    <span id="total-price-display">NPR <?php echo number_format($hotel['price_per_night']); ?></span>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary" style="width: 100%; font-size: 15px; padding: 14px; font-weight: 700; border-radius: 12px;">
                                <i class="fa-solid fa-calendar-check"></i> Confirm Booking
                            </button>
                            <p style="font-size: 11px; color: #94A3B8; text-align: center; margin-top: 10px;">
                                <i class="fa-solid fa-shield-halved"></i> Booking is verified and secured by SahaYatri
                            </p>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        const pricePerNight = <?php echo $hotel['price_per_night']; ?>;

        function calculateNights() {
            const checkIn  = document.getElementById('check-in').value;
            const checkOut = document.getElementById('check-out').value;
            const guests   = parseInt(document.getElementById('guests').value) || 1;

            if (checkIn && checkOut) {
                const d1 = new Date(checkIn), d2 = new Date(checkOut);
                const nights = Math.max(1, Math.round((d2 - d1) / 86400000));
                const total  = pricePerNight * nights * guests;
                document.getElementById('nights-display').textContent = nights + ' night(s) × ' + guests + ' guest(s)';
                document.getElementById('total-price-display').textContent = 'NPR ' + total.toLocaleString('en-IN');
            }
        }
    </script>
</body>
</html>

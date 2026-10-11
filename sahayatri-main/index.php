<?php
// system/index.php
// SAHAYATRI Public Multi-Page Portal & Listings Hub with Floating Marquee Broadcast inside Hero Slider

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';

$db = getDBConnection();

// 1. Fetch Active Tour Packages
$pkgStmt = $db->query("SELECT p.*, d.name as destination_name, d.region, d.category as dest_category, d.safety_level, o.name as organization_name 
                       FROM packages p 
                       JOIN destinations d ON p.destination_id = d.id 
                       JOIN organizations o ON p.organization_id = o.id 
                       WHERE p.status = 'ACTIVE' 
                       ORDER BY p.id DESC");
$packages = $pkgStmt->fetchAll();

// 2. Fetch Destinations
$destStmt = $db->query("SELECT d.*, o.name as organization_name 
                        FROM destinations d 
                        LEFT JOIN organizations o ON d.organization_id = o.id 
                        ORDER BY d.is_featured DESC, d.name ASC LIMIT 6");
$destinations = $destStmt->fetchAll();

// 3. Fetch Accommodations
$accStmt = $db->query("SELECT a.*, d.name as destination_name 
                       FROM accommodations a 
                       JOIN destinations d ON a.destination_id = d.id 
                       ORDER BY a.trust_score DESC LIMIT 6");
$accommodations = $accStmt->fetchAll();

// 4. Fetch Emergency Directory
$emStmt = $db->query("SELECT * FROM emergency_resources WHERE is_verified = 1 ORDER BY id ASC LIMIT 3");
$emergencies = $emStmt->fetchAll();

// 5. Fetch Broadcast Alerts
$alertStmt = $db->query("SELECT ob.*, o.name as org_name FROM organization_broadcasts ob JOIN organizations o ON ob.organization_id = o.id ORDER BY ob.id DESC");
$broadcasts = $alertStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SahaYatri – Smart Tourism Safety & Travel Companion</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="manifest" href="manifest.json">
    <style>
        /* Full-Page Background Hero Slider Container */
        .hero-slider-container {
            position: relative;
            width: 100vw;
            left: 50%;
            right: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            height: calc(100vh - 80px);
            min-height: 580px;
            border-radius: 0;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(15, 23, 42, 0.15);
        }

        /* Floating Marquee Broadcast Overlay on Top of Hero Slider */
        .hero-broadcast-overlay {
            position: absolute;
            top: 20px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 1100px;
            background: rgba(15, 23, 42, 0.8);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(245, 158, 11, 0.5);
            border-radius: 30px;
            padding: 8px 24px;
            color: #FDE68A;
            font-size: 14px;
            font-weight: 600;
            z-index: 10;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.3);
            overflow: hidden;
        }

        /* Slider Slides */
        .hero-slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-size: cover;
            background-position: center;
            opacity: 0;
            transition: opacity 1.2s ease-in-out;
        }

        .hero-slide.active {
            opacity: 1;
        }

        /* Dark Gradient Overlay for High Contrast Legibility */
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(15, 23, 42, 0.75) 0%, rgba(2, 132, 199, 0.65) 60%, rgba(14, 165, 233, 0.5) 100%);
            z-index: 2;
        }

        /* Hero Content Centered */
        .hero-content-centered {
            position: relative;
            z-index: 3;
            height: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #FFFFFF;
            padding: 40px 32px 0 32px;
            max-width: 920px;
            margin: 0 auto;
        }

        .hero-title {
            font-size: 48px;
            font-weight: 800;
            line-height: 1.25;
            margin-bottom: 16px;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.3);
        }

        .hero-subtitle {
            font-size: 18px;
            opacity: 0.95;
            max-width: 760px;
            margin: 0 auto 36px auto;
            line-height: 1.6;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.3);
        }

        /* Slider Dot Indicators */
        .slider-dots {
            position: absolute;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 4;
        }

        .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.4);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .dot.active {
            background: #FFFFFF;
            width: 32px;
            border-radius: 12px;
        }

        /* Category Filter Pills */
        .category-filter-bar {
            display: flex;
            gap: 10px;
            overflow-x: auto;
            padding-bottom: 8px;
            margin-bottom: 24px;
            scrollbar-width: none;
        }

        .category-filter-bar::-webkit-scrollbar {
            display: none;
        }

        .cat-pill {
            padding: 8px 18px;
            border-radius: 30px;
            background: white;
            border: 1px solid var(--color-border);
            font-size: 13px;
            font-weight: 600;
            color: var(--color-text-secondary);
            cursor: pointer;
            white-space: nowrap;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .cat-pill:hover,
        .cat-pill.active {
            background: var(--color-primary-dark);
            color: white;
            border-color: var(--color-primary-dark);
            box-shadow: var(--shadow-sm);
        }

        .listings-section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 48px 0 20px 0;
            border-bottom: 2px solid var(--color-primary-light);
            padding-bottom: 12px;
        }

        .pkg-card {
            background: white;
            border-radius: 16px;
            border: 1px solid var(--color-border);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .pkg-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        .pkg-card-img {
            width: 100%;
            height: 190px;
            object-fit: cover;
        }

        .pkg-card-body {
            padding: 20px;
        }
    </style>
</head>

<body>

    <!-- Include Shared Consistent Header Navbar with Search Bar -->
    <?php include __DIR__ . '/includes/navbar.php'; ?>

    <!-- Full-Page Hero Section with Background Image Slider -->
    <section id="home" class="hero-slider-container">

        <!-- Glassmorphic Marquee Broadcast Banner Layered ON TOP of Hero Slider -->
        <?php if (!empty($broadcasts)): ?>
            <div class="hero-broadcast-overlay">
                <marquee behavior="scroll" direction="left" scrollamount="7">
                    <?php foreach($broadcasts as $broadcast): ?>
                        <i class="fa-solid fa-triangle-exclamation" style="color: #F59E0B;"></i> <b>SAFETY BROADCAST:</b>
                        <?php echo htmlspecialchars($broadcast['title']); ?> —
                        <?php echo htmlspecialchars($broadcast['message']); ?> (<i>Published by <?php echo htmlspecialchars($broadcast['org_name']); ?></i>)
                        &nbsp;&nbsp;&nbsp;&nbsp;•&nbsp;&nbsp;&nbsp;&nbsp;
                    <?php endforeach; ?>
                    <i class="fa-solid fa-shield-halved" style="color: #F59E0B;"></i> Stay updated with SahaYatri Safety Platform for real-time travel alerts across Nepal.
                </marquee>
            </div>
        <?php endif; ?>

        <!-- Background Slider Images -->
        <div class="hero-slide active"
            style="background-image: url('https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=1600');"></div>
        <div class="hero-slide"
            style="background-image: url('https://images.unsplash.com/photo-1588668214407-6ea9a6d8c272?w=1600');"></div>
        <div class="hero-slide"
            style="background-image: url('https://images.unsplash.com/photo-1516426122078-c23e76319801?w=1600');"></div>

        <!-- Gradient Overlay -->
        <div class="hero-overlay"></div>

        <!-- Perfectly Centered Content -->
        <div class="hero-content-centered">
            <h1 class="hero-title">Travel Smart. Stay Connected. Stay Safe.</h1>
            <p class="hero-subtitle">
                Explore verified agency tour packages, hand-picked destinations, and safety-monitored accommodations
                across Nepal with SahaYatri.
            </p>

            <div style="display: flex; justify-content: center; gap: 16px; flex-wrap: wrap;">
                <a href="login.php" class="btn btn-danger" style="padding: 16px 32px; font-size: 16px;"><i
                        class="fa-solid fa-rocket"></i> Open Traveler Portal</a>
                <a href="register.php?role=organization_admin" class="btn btn-secondary"
                    style="padding: 16px 32px; font-size: 16px;"><i class="fa-solid fa-building"></i> Agency / Hotel
                    Partner Portal</a>
            </div>
        </div>

        <!-- Slider Dots -->
        <div class="slider-dots">
            <div class="dot active" onclick="goToSlide(0)"></div>
            <div class="dot" onclick="goToSlide(1)"></div>
            <div class="dot" onclick="goToSlide(2)"></div>
        </div>
    </section>

    <!-- Main Content Area -->
    <div class="content-area" style="max-width: 1280px;">

        <!-- ======================================================== -->
        <!-- SECTION 1: TOUR PACKAGES LISTINGS WITH CATEGORY FILTERS  -->
        <!-- ======================================================== -->
        <div id="packages" class="listings-section-header">
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: var(--color-dark-accent);"><i
                        class="fa-solid fa-box-archive" style="color: var(--color-primary-dark);"></i> Tour Packages
                    (Posted by Agencies)</h2>
                <p style="font-size: 13px; color: var(--color-text-secondary);">Directly published and managed by
                    verified travel agencies & tourism boards</p>
            </div>
            <a href="packages.php" class="btn btn-outline" style="font-size: 13px;">View All Packages <i
                    class="fa-solid fa-arrow-right"></i></a>
        </div>

        <!-- Package Categories Filter Bar -->
        <div class="category-filter-bar" id="package-category-pills">
            <button class="cat-pill active" data-filter="all"><i class="fa-solid fa-layer-group"></i> All
                Packages</button>
            <button class="cat-pill" data-filter="Trekking"><i class="fa-solid fa-person-hiking"></i> Trekking &
                Hiking</button>
            <button class="cat-pill" data-filter="Adventure"><i class="fa-solid fa-parachute-box"></i> Adventure
                Sports</button>
            <button class="cat-pill" data-filter="Historical"><i class="fa-solid fa-gopuram"></i> Cultural &
                Heritage</button>
            <button class="cat-pill" data-filter="Natural"><i class="fa-solid fa-water"></i> Lakes & Nature</button>
            <button class="cat-pill" data-filter="Wildlife"><i class="fa-solid fa-hippo"></i> Jungle Safari</button>
        </div>

        <div class="dashboard-grid" id="packages-grid-container">
            <?php foreach ($packages as $pkg): ?>
                <div class="pkg-card package-item" data-category="<?php echo htmlspecialchars($pkg['dest_category']); ?>">
                    <img src="<?php echo htmlspecialchars($pkg['image_url'] ?: 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800'); ?>"
                        class="pkg-card-img" alt="<?php echo htmlspecialchars($pkg['title']); ?>">
                    <div class="pkg-card-body">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                            <span class="badge risk-safe"><i class="fa-solid fa-building"></i>
                                <?php echo htmlspecialchars($pkg['organization_name']); ?></span>
                            <span style="font-size: 12px; font-weight: bold; color: var(--color-text-secondary);"><i
                                    class="fa-regular fa-clock"></i> <?php echo $pkg['duration_days']; ?> Days</span>
                        </div>
                        <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;">
                            <?php echo htmlspecialchars($pkg['title']); ?>
                        </h3>
                        <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 8px;"><i
                                class="fa-solid fa-location-dot" style="color: var(--color-primary-dark);"></i>
                            <?php echo htmlspecialchars($pkg['destination_name']); ?> ·
                            <?php echo htmlspecialchars($pkg['region']); ?>
                        </p>

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

                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-top: 16px; padding-top: 12px; border-top: 1px solid var(--color-border);">
                            <div>
                                <span style="font-size: 11px; color: var(--color-text-secondary); display: block;">Package
                                    Price</span>
                                <strong style="font-size: 18px; color: var(--color-primary-dark);">NPR
                                    <?php echo number_format($pkg['price_npr']); ?></strong>
                            </div>
                            <a href="package_details.php?id=<?php echo $pkg['id']; ?>" class="btn btn-primary"
                                style="font-size: 13px;"><i class="fa-solid fa-eye"></i> View Package & Details</a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ======================================================== -->
        <!-- SECTION 2: ACCOMMODATIONS LISTINGS WITH CATEGORY FILTERS  -->
        <!-- ======================================================== -->
        <div id="accommodations" class="listings-section-header" style="margin-top: 60px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: var(--color-dark-accent);"><i
                        class="fa-solid fa-hotel" style="color: var(--color-primary-dark);"></i> Hotels, Resorts &
                    Homestays</h2>
                <p style="font-size: 13px; color: var(--color-text-secondary);">Verified accommodations with transparent
                    Trust Scores</p>
            </div>
            <a href="accommodations.php" class="btn btn-outline" style="font-size: 13px;">Explore All Stays <i
                    class="fa-solid fa-arrow-right"></i></a>
        </div>

        <!-- Accommodation Categories Filter Bar -->
        <div class="category-filter-bar" id="accommodation-category-pills">
            <button class="cat-pill active" data-filter="all"><i class="fa-solid fa-layer-group"></i> All Stays</button>
            <button class="cat-pill" data-filter="Hotel"><i class="fa-solid fa-hotel"></i> Hotels</button>
            <button class="cat-pill" data-filter="Resort"><i class="fa-solid fa-water-ladder"></i> Resorts</button>
            <button class="cat-pill" data-filter="Homestay"><i class="fa-solid fa-house-user"></i> Local
                Homestays</button>
        </div>

        <div class="dashboard-grid" id="accommodations-grid-container">
            <?php foreach ($accommodations as $acc): ?>
                <div class="card accommodation-item" data-category="<?php echo htmlspecialchars($acc['type']); ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <span class="badge risk-safe"><i class="fa-solid fa-shield-halved"></i> Trust Score:
                            <?php echo $acc['trust_score']; ?>/100</span>
                        <span style="font-size: 12px; font-weight: bold; color: var(--color-primary-dark);"><i
                                class="fa-solid fa-tag"></i> <?php echo htmlspecialchars($acc['type']); ?></span>
                    </div>
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 4px;">
                        <?php echo htmlspecialchars($acc['name']); ?>
                    </h3>
                    <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 10px;"><i
                            class="fa-solid fa-location-dot" style="color: var(--color-danger);"></i>
                        <?php echo htmlspecialchars($acc['address']); ?></p>

                    <p style="font-size: 17px; font-weight: bold; color: var(--color-primary-dark); margin-bottom: 12px;">
                        NPR <?php echo number_format($acc['price_per_night']); ?> <span
                            style="font-size: 12px; font-weight: normal; color: #64748B;">/ night</span></p>
                    <p style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 16px;"><i
                            class="fa-solid fa-wifi"></i> <?php echo htmlspecialchars($acc['facilities']); ?></p>
                    <a href="login.php" class="btn btn-outline" style="width: 100%; font-size: 13px;"><i
                            class="fa-regular fa-calendar-check"></i> Check Availability</a>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ======================================================== -->
        <!-- SECTION 3: REGIONAL DESTINATIONS                          -->
        <!-- ======================================================== -->
        <div id="destinations" class="listings-section-header" style="margin-top: 60px;">
            <div>
                <h2 style="font-size: 24px; font-weight: 800; color: var(--color-dark-accent);"><i
                        class="fa-solid fa-mountain-sun" style="color: var(--color-primary-dark);"></i> Regional
                    Destinations & Attractions</h2>
                <p style="font-size: 13px; color: var(--color-text-secondary);">Destinations monitored with active
                    safety risk levels</p>
            </div>
            <a href="destinations.php" class="btn btn-outline" style="font-size: 13px;">View All Destinations <i
                    class="fa-solid fa-arrow-right"></i></a>
        </div>

        <div class="dashboard-grid">
            <?php foreach (array_slice($destinations, 0, 3) as $d): ?>
                <div class="pkg-card">
                    <img src="<?php echo htmlspecialchars($d['image_url']); ?>" class="pkg-card-img"
                        alt="<?php echo htmlspecialchars($d['name']); ?>">
                    <div class="pkg-card-body">
                        <div
                            style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px;">
                            <span class="badge risk-safe"><i class="fa-solid fa-circle-check"></i>
                                <?php echo htmlspecialchars($d['safety_level']); ?></span>
                            <span
                                style="font-size: 12px; color: var(--color-text-secondary);"><?php echo htmlspecialchars($d['organization_name'] ?: 'Global Desk'); ?></span>
                        </div>
                        <h3 style="font-size: 17px; font-weight: 700; margin: 4px 0;">
                            <?php echo htmlspecialchars($d['name']); ?>
                        </h3>
                        <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 12px;">
                            <?php echo htmlspecialchars($d['category']); ?> · <?php echo htmlspecialchars($d['region']); ?>
                        </p>
                        <a href="destinations.php" class="btn btn-secondary" style="width: 100%; font-size: 13px;">View
                            Attractions & Tour Info</a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- ======================================================== -->
        <!-- SECTION 4: EMERGENCY DIRECTORY                           -->
        <!-- ======================================================== -->
        <div id="emergency" style="margin-top: 60px;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                <div>
                    <h2 style="font-size: 24px; font-weight: 800; color: var(--color-danger);"><i
                            class="fa-solid fa-kit-medical"></i> Emergency Directory & Tourist Police</h2>
                    <p style="font-size: 13px; color: var(--color-text-secondary);">Direct phone contact for verified
                        medical emergency departments and Tourist Police (1144)</p>
                </div>
            </div>

            <div class="dashboard-grid">
                <?php foreach ($emergencies as $er): ?>
                    <div class="card">
                        <h4 style="margin: 0 0 6px 0; font-size: 16px;"><i class="fa-solid fa-hospital-user"
                                style="color: var(--color-danger);"></i> <?php echo htmlspecialchars($er['name']); ?></h4>
                        <p style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 6px;">
                            <?php echo htmlspecialchars($er['type']); ?> · <?php echo htmlspecialchars($er['city']); ?>
                        </p>
                        <p style="font-size: 13px; margin-bottom: 12px;"><?php echo htmlspecialchars($er['address']); ?></p>
                        <a href="tel:<?php echo htmlspecialchars($er['phone']); ?>" class="btn btn-danger"
                            style="width: 100%; font-size: 13px;"><i class="fa-solid fa-phone"></i> Call
                            <?php echo htmlspecialchars($er['phone']); ?></a>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- DEMO CREDENTIALS BOX -->
        <div class="card"
            style="background: #F0F9FF; border: 1px dashed var(--color-primary-dark); margin-top: 50px; text-align: center;">
            <h3 style="color: var(--color-primary-dark); font-size: 18px; font-weight: 700;"><i
                    class="fa-solid fa-key"></i> Demo Credentials for Testing</h3>
            <p style="font-size: 14px; margin: 8px 0;">Sign in to test traveler, agency admin, or platform super admin
                portals:</p>
            <div
                style="display: flex; justify-content: center; gap: 20px; flex-wrap: wrap; margin-top: 12px; font-size: 13px;">
                <div style="background: white; padding: 10px 16px; border-radius: 8px; border: 1px solid #BAE6FD;">
                    <b>Super Admin:</b> superadmin@sahayatri.com / <code>Admin@123</code>
                </div>
                <div style="background: white; padding: 10px 16px; border-radius: 8px; border: 1px solid #BAE6FD;">
                    <b>Agency Admin:</b> ktm_admin@sahayatri.com / <code>Admin@123</code>
                </div>
                <div style="background: white; padding: 10px 16px; border-radius: 8px; border: 1px solid #BAE6FD;">
                    <b>Traveler:</b> rohan@gmail.com / <code>User@123</code>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer -->
    <footer
        style="background: var(--color-surface); border-top: 1px solid var(--color-border); padding: 40px; text-align: center; color: var(--color-text-secondary); margin-top: 60px; font-size: 14px;">
        <div style="display: flex; justify-content: center; align-items: center; gap: 12px; margin-bottom: 12px;">
            <img src="assets/img/Logoo.png" alt="SahaYatri Logo" style="height: 48px;">
        </div>
        <p>© 2026 <b>SahaYatri Smart Tourism Safety SaaS Platform</b>. Journey · Discover · Memories</p>
    </footer>

    <script>
        // Hero Background Image Slider Logic
        let currentSlide = 0;
        const slides = document.querySelectorAll('.hero-slide');
        const dots = document.querySelectorAll('.dot');

        function goToSlide(index) {
            slides.forEach(slide => slide.classList.remove('active'));
            dots.forEach(dot => dot.classList.remove('active'));

            currentSlide = index;
            if (slides[currentSlide]) slides[currentSlide].classList.add('active');
            if (dots[currentSlide]) dots[currentSlide].classList.add('active');
        }

        // Automatic Slide Carousel every 5 seconds
        setInterval(() => {
            currentSlide = (currentSlide + 1) % slides.length;
            goToSlide(currentSlide);
        }, 5000);

        // Category Filter Pill Interactive Logic
        document.addEventListener('DOMContentLoaded', () => {
            const pkgPills = document.querySelectorAll('#package-category-pills .cat-pill');
            const pkgItems = document.querySelectorAll('.package-item');

            pkgPills.forEach(pill => {
                pill.addEventListener('click', () => {
                    pkgPills.forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                    const filter = pill.getAttribute('data-filter');

                    pkgItems.forEach(item => {
                        if (filter === 'all' || item.getAttribute('data-category') === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });

            const accPills = document.querySelectorAll('#accommodation-category-pills .cat-pill');
            const accItems = document.querySelectorAll('.accommodation-item');

            accPills.forEach(pill => {
                pill.addEventListener('click', () => {
                    accPills.forEach(p => p.classList.remove('active'));
                    pill.classList.add('active');
                    const filter = pill.getAttribute('data-filter');

                    accItems.forEach(item => {
                        if (filter === 'all' || item.getAttribute('data-category') === filter) {
                            item.style.display = 'block';
                        } else {
                            item.style.display = 'none';
                        }
                    });
                });
            });
        });
    </script>
</body>

</html>
<?php
// system/traveler_dashboard.php
// SAHAYATRI Traveler Web Application Hub & Safety Companion with Slide Nav & Enhanced UI

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

requireLogin();
$user = getCurrentUser();
$db = getDBConnection();

// Fetch traveler's active team if any
$teamStmt = $db->prepare("SELECT t.*, u.full_name as leader_name, 
                                 (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id AND tm.status = 'ACTIVE') as member_count
                          FROM teams t 
                          JOIN team_members tm ON t.id = tm.team_id 
                          JOIN users u ON t.leader_id = u.id 
                          WHERE tm.user_id = ? AND t.status = 'ACTIVE' LIMIT 1");
$teamStmt->execute([$user['id']]);
$activeTeam = $teamStmt->fetch();

// Restrict registered travel agencies for chat dropdown:
// Only show agencies the traveler has booked with, messaged, or explicitly requested via URL to book/inquire
$requestedOrgId = isset($_GET['org_id']) ? intval($_GET['org_id']) : 0;
$travelerId = $user['id'];

$orgsStmt = $db->prepare("
    SELECT DISTINCT o.id, o.name, o.email, o.phone 
    FROM organizations o 
    WHERE o.status = 'ACTIVE' 
    AND (
        o.id = ? 
        OR o.id IN (SELECT p.organization_id FROM packages p JOIN bookings b ON b.package_id = p.id WHERE b.user_id = ?)
        OR o.id IN (SELECT a.organization_id FROM accommodations a JOIN bookings b ON b.accommodation_id = a.id WHERE b.user_id = ?)
        OR o.id IN (SELECT m.organization_id FROM messages m WHERE m.sender_id = ? OR m.receiver_id = ?)
        OR o.id IN (
            SELECT u.organization_id 
            FROM users u 
            JOIN teams t ON t.leader_id = u.id 
            JOIN team_members tm ON tm.team_id = t.id 
            WHERE tm.user_id = ? AND tm.status = 'ACTIVE' AND t.status = 'ACTIVE'
        )
    )
    ORDER BY o.name ASC
");
$orgsStmt->execute([$requestedOrgId, $travelerId, $travelerId, $travelerId, $travelerId, $travelerId]);
$agencies = $orgsStmt->fetchAll();

$initialOrgId = $requestedOrgId && in_array($requestedOrgId, array_column($agencies, 'id')) ? $requestedOrgId : ($agencies[0]['id'] ?? 0);
$allowedTabs = ['tab-dashboard', 'tab-agency-chat', 'tab-map', 'tab-team', 'tab-hotels', 'tab-scams', 'tab-ai', 'tab-emergency'];
$initialTab = isset($_GET['tab']) && in_array($_GET['tab'], $allowedTabs) ? $_GET['tab'] : 'tab-dashboard';

// Fetch Verified Accommodations (only superadmin-approved)
$accStmt = $db->query("SELECT a.*, d.name as destination_name, o.name as organization_name 
                       FROM accommodations a 
                       JOIN destinations d ON a.destination_id = d.id 
                       LEFT JOIN organizations o ON a.organization_id = o.id
                       WHERE a.is_verified = 1 AND a.approval_status = 'APPROVED'
                       ORDER BY a.trust_score DESC LIMIT 20");
$verifiedHotels = $accStmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Traveler Dashboard – SahaYatri Safety Platform</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/leaflet.css">
    <link rel="manifest" href="manifest.json">
    <style>
        /* Slide Nav Animations & Collapsible Sidebar */
        .sidebar {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1) !important;
            z-index: 1000;
        }

        .sidebar.collapsed {
            width: 80px !important;
            padding: 20px 10px !important;
        }

        .sidebar.collapsed .brand-logo-text,
        .sidebar.collapsed .nav-link span:not(.icon) {
            display: none !important;
        }

        .sidebar.collapsed .nav-link {
            justify-content: center !important;
            padding: 14px !important;
        }

        .sidebar.collapsed .brand-logo {
            justify-content: center !important;
        }

        .sidebar-slide-toggle-btn {
            background: #F1F5F9;
            border: 1px solid #CBD5E1;
            color: #0F172A;
            width: 40px;
            height: 40px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.2s ease;
        }

        .sidebar-slide-toggle-btn:hover {
            background: #0EA5E9;
            color: white;
            border-color: #0EA5E9;
            box-shadow: 0 4px 12px rgba(14, 165, 233, 0.3);
        }

        /* Enhanced Hero Banner & Glass Cards */
        .hero-banner-enhanced {
            background: linear-gradient(135deg, #0EA5E9 0%, #0284C7 60%, #0F172A 100%);
            border-radius: 24px;
            padding: 40px 44px;
            color: white;
            box-shadow: 0 16px 36px rgba(14, 165, 233, 0.25);
            margin-bottom: 28px;
            position: relative;
            overflow: hidden;
        }

        .hero-banner-enhanced::after {
            content: '';
            position: absolute;
            top: -40px;
            right: -40px;
            width: 260px;
            height: 260px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            pointer-events: none;
        }

        .enhanced-card {
            background: white;
            border-radius: 20px;
            border: 1px solid #E2E8F0;
            padding: 24px;
            box-shadow: 0 10px 25px rgba(15, 23, 42, 0.05);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        .enhanced-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 14px 30px rgba(15, 23, 42, 0.09);
        }

        /* Chatbox UI Styling */
        .chat-container { display: flex; flex-direction: column; height: 480px; background: white; border-radius: 16px; border: 1px solid var(--color-border); overflow: hidden; box-shadow: var(--shadow-sm); }
        .chat-header { padding: 16px 20px; background: var(--color-primary-100); border-bottom: 1px solid var(--color-border); display: flex; justify-content: space-between; align-items: center; }
        .chat-messages { flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column; gap: 12px; background: #F8FAFC; }
        .chat-bubble { max-width: 75%; padding: 12px 16px; border-radius: 16px; font-size: 14px; line-height: 1.5; }
        .chat-bubble.sent { align-self: flex-end; background: var(--color-primary-dark); color: white; border-bottom-right-radius: 4px; }
        .chat-bubble.received { align-self: flex-start; background: white; border: 1px solid var(--color-border); color: var(--color-text-primary); border-bottom-left-radius: 4px; }
        .chat-input-bar { padding: 12px 16px; background: white; border-top: 1px solid var(--color-border); display: flex; gap: 10px; }

        /* ── Fair Price Tab — Brand-Matched Styles ──────────────── */
        .fp-hero {
            background: linear-gradient(135deg, #0F172A 0%, #0284C7 55%, #0EA5E9 100%);
            border-radius: 20px;
            padding: 36px 40px;
            color: white;
            position: relative;
            overflow: hidden;
            margin-bottom: 32px;
            box-shadow: 0 10px 28px rgba(2,132,199,0.25);
        }
        .fp-hero::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 300px; height: 300px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .fp-hero::after {
            content: '';
            position: absolute;
            bottom: -40px; left: 30%;
            width: 200px; height: 200px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .fp-stat-strip {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 16px;
            margin-bottom: 32px;
        }
        .fp-stat-box {
            background: var(--color-surface);
            border-radius: 16px;
            padding: 20px 18px;
            border: 1px solid var(--color-border);
            box-shadow: var(--shadow-sm);
            display: flex;
            align-items: center;
            gap: 14px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .fp-stat-box:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .fp-stat-icon {
            width: 46px; height: 46px;
            border-radius: 12px;
            display: flex; align-items: center; justify-content: center;
            font-size: 20px;
            flex-shrink: 0;
        }
        .fp-section-tabs {
            display: flex;
            gap: 4px;
            margin-bottom: 24px;
            border-bottom: 2px solid var(--color-border);
            padding-bottom: 0;
            overflow-x: auto;
        }
        .fp-tab-btn {
            padding: 10px 20px;
            border: none;
            background: transparent;
            font-size: 13px;
            font-weight: 700;
            color: var(--color-text-secondary);
            cursor: pointer;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
            border-radius: 0;
            white-space: nowrap;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 7px;
        }
        .fp-tab-btn:hover { color: var(--color-primary-dark); background: var(--color-primary-100); }
        .fp-tab-btn.active { color: var(--color-primary-dark); border-bottom-color: var(--color-primary-dark); background: var(--color-primary-100); }
        .fp-panel { display: none; animation: fpFadeIn 0.3s ease; }
        .fp-panel.active { display: block; }
        @keyframes fpFadeIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: translateY(0); } }
        .fp-card {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--color-border);
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            margin-bottom: 20px;
            transition: box-shadow 0.2s;
        }
        .fp-card:hover { box-shadow: var(--shadow-md); }
        .fp-card-header {
            padding: 16px 20px;
            display: flex;
            align-items: center;
            gap: 12px;
            border-bottom: 1px solid var(--color-border);
            background: var(--color-background);
        }
        .fp-card-icon {
            width: 38px; height: 38px;
            border-radius: 10px;
            display: flex; align-items: center; justify-content: center;
            font-size: 16px;
            flex-shrink: 0;
        }
        .fp-card-body { padding: 0; }
        .fp-price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 13px 20px;
            border-bottom: 1px solid var(--color-background);
            transition: background 0.15s;
        }
        .fp-price-row:last-child { border-bottom: none; }
        .fp-price-row:hover { background: var(--color-primary-100); }
        .fp-price-label { font-size: 13px; color: var(--color-text-secondary); display: flex; align-items: center; gap: 8px; }
        .fp-price-value { font-size: 13px; font-weight: 800; color: var(--color-primary-dark); }
        .fp-gear-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 14px;
            padding: 20px;
        }
        .fp-gear-item {
            background: var(--color-background);
            border: 1px solid var(--color-border);
            border-radius: var(--radius-md);
            padding: 14px;
            transition: all 0.2s;
        }
        .fp-gear-item:hover { background: var(--color-primary-100); border-color: var(--color-primary); transform: translateY(-2px); box-shadow: var(--shadow-md); }
        .fp-gear-emoji { font-size: 22px; margin-bottom: 6px; display: block; }
        .fp-gear-name { font-size: 12px; font-weight: 700; color: var(--color-text-primary); margin-bottom: 8px; }
        .fp-gear-prices { display: flex; flex-direction: column; gap: 3px; }
        .fp-gear-rent { font-size: 11px; color: var(--color-primary-dark); font-weight: 700; }
        .fp-gear-buy  { font-size: 11px; color: var(--color-text-muted); }
        .fp-budget-card {
            background: linear-gradient(135deg, #0F172A 0%, #0284C7 60%, #38BDF8 100%);
            border-radius: var(--radius-lg);
            padding: 28px 32px;
            color: white;
            box-shadow: 0 10px 28px rgba(2,132,199,0.3);
        }
        .fp-budget-row {
            display: flex;
            justify-content: space-between;
            padding: 9px 0;
            font-size: 13px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }
        .fp-budget-row:last-of-type { border-bottom: none; }
        .fp-scam-card {
            background: var(--color-surface);
            border-radius: var(--radius-lg);
            border: 1px solid var(--color-border);
            padding: 20px;
            box-shadow: var(--shadow-sm);
            transition: transform 0.2s, box-shadow 0.2s;
            position: relative;
            overflow: hidden;
        }
        .fp-scam-card::before {
            content: '';
            position: absolute;
            left: 0; top: 0; bottom: 0;
            width: 5px;
            border-radius: 16px 0 0 16px;
        }
        .fp-scam-card.high::before   { background: linear-gradient(180deg, var(--color-danger), var(--color-danger-dark)); }
        .fp-scam-card.medium::before { background: linear-gradient(180deg, var(--color-primary), var(--color-primary-dark)); }
        .fp-scam-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-md); }
        .fp-alert-box {
            border-radius: var(--radius-sm);
            padding: 10px 14px;
            font-size: 12px;
            line-height: 1.6;
            margin-top: 12px;
        }
        /* Emergency 1-TAP SOS Pulsing FAB & Modal Styles */
        @keyframes sosPulse {
            0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.75), 0 8px 24px rgba(220, 38, 38, 0.4); }
            70% { box-shadow: 0 0 0 16px rgba(220, 38, 38, 0), 0 8px 24px rgba(220, 38, 38, 0.4); }
            100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0), 0 8px 24px rgba(220, 38, 38, 0.4); }
        }
        #sos-trigger-fab {
            animation: sosPulse 2s infinite;
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        #sos-trigger-fab:hover {
            transform: scale(1.06) translateY(-2px);
        }
        .sos-cat-btn {
            padding: 10px 14px;
            border: 1.5px solid #CBD5E1;
            border-radius: 12px;
            background: #FFFFFF;
            color: #1E293B;
            font-size: 13px;
            font-weight: 700;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
        }
        .sos-cat-btn:hover {
            border-color: #EF4444;
            background: #FEF2F2;
            color: #DC2626;
        }
        .sos-cat-btn.active {
            border-color: #DC2626;
            background: #DC2626;
            color: #FFFFFF;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
        }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- Sidebar Navigation with Sliding Collapse -->
        <aside class="sidebar" id="dashboard-sidebar">
            <a href="index.php" class="brand-logo" style="display: flex; align-items: center; gap: 10px; text-decoration: none;">
                <img src="assets/img/Logoo.png" alt="SahaYatri Logo" class="app-logo-img" style="height: 68px;">
                <div class="brand-logo-text" style="font-size: 20px; font-weight: 800; color: #0F172A;">Saha<span style="color: #0EA5E9;">Yatri</span></div>
            </a>

            <ul class="nav-menu">
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-dashboard' ? 'active' : ''; ?>" data-tab-target="tab-dashboard"><span class="icon"><i class="fa-solid fa-house"></i></span><span>Dashboard</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-agency-chat' ? 'active' : ''; ?>" data-tab-target="tab-agency-chat"><span class="icon"><i class="fa-solid fa-comments"></i></span><span>Agency Chat</span></a></li>

                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-map' ? 'active' : ''; ?>" data-tab-target="tab-map"><span class="icon"><i class="fa-solid fa-map-location-dot"></i></span><span>Safety Map</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-team' ? 'active' : ''; ?>" data-tab-target="tab-team"><span class="icon"><i class="fa-solid fa-users"></i></span><span>Travel Team</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-hotels' ? 'active' : ''; ?>" data-tab-target="tab-hotels"><span class="icon"><i class="fa-solid fa-hotel"></i></span><span>Hotels & Stays</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-scams' ? 'active' : ''; ?>" data-tab-target="tab-scams"><span class="icon"><i class="fa-solid fa-scale-balanced"></i></span><span>Fair Price & Scams</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-ai' ? 'active' : ''; ?>" data-tab-target="tab-ai"><span class="icon"><i class="fa-solid fa-robot"></i></span><span>AI Assistant</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-emergency' ? 'active' : ''; ?>" data-tab-target="tab-emergency"><span class="icon"><i class="fa-solid fa-kit-medical"></i></span><span>Emergency Contacts</span></a></li>
                <li style="margin-top: auto;"><a href="logout.php" class="nav-link" style="color: var(--color-danger);"><span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span><span>Sign Out</span></a></li>
            </ul>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper" id="dashboard-main-wrapper">
            <!-- Header with Sidebar Slide Toggle Button -->
            <header class="header">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <button class="sidebar-slide-toggle-btn" id="sidebar-slide-btn" title="Toggle Sidebar Navigation">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <span style="color: var(--color-text-secondary); font-size: 13px;">Traveler Dashboard Hub</span>
                        <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">Namaste, <?php echo htmlspecialchars($user['full_name']); ?>! 👋</h3>
                    </div>
                </div>

                <div style="margin-left: auto; display: flex; align-items: center; gap: 16px;">
                    <div style="background: #DCFCE7; color: #15803D; padding: 8px 16px; border-radius: 20px; font-size: 12px; font-weight: 700; display: flex; align-items: center; gap: 8px; border: 1px solid #BBF7D0;">
                        <span style="width: 8px; height: 8px; background: #16A34A; border-radius: 50%; display: inline-block;"></span>
                        Live Location Sharing: ACTIVE
                    </div>
                    <a href="logout.php" class="btn btn-outline" style="font-size: 13px; padding: 8px 16px; border-radius: 12px;"><i class="fa-solid fa-right-from-bracket"></i> Sign Out</a>
                </div>
            </header>

            <!-- Content Area -->
            <main class="content-area">

                <!-- 1. DASHBOARD OVERVIEW TAB -->
                <div id="tab-dashboard" class="tab-content <?php echo $initialTab === 'tab-dashboard' ? 'active' : ''; ?>">
                    <div class="hero-banner-enhanced">
                        <h1 style="font-size: 28px; font-weight: 800; margin-bottom: 10px;">Travel Smart. Stay Connected. Stay Safe.</h1>
                        <p style="font-size: 15px; opacity: 0.95; max-width: 680px; margin-bottom: 24px; line-height: 1.5;">Explore Nepal with live group tracking, geofenced safety alerts, transparent hotel trust scores, and instant 1-tap SOS emergency assistance.</p>
                        <div style="display: flex; gap: 14px; flex-wrap: wrap;">
                            <button onclick="updateWeatherCondition()" class="btn" style="background: rgba(255,255,255,0.22); backdrop-filter: blur(10px); color: white; border: 1px solid rgba(255,255,255,0.4); padding: 12px 24px; font-size: 14px; border-radius: 12px; font-weight: 700; cursor: pointer;"><i class="fa-solid fa-cloud-arrow-down"></i> Update Weather</button>
                            <button onclick="document.querySelector('[data-tab-target=\'tab-map\']').click()" class="btn" style="background: white; color: #0EA5E9; padding: 12px 24px; font-size: 14px; border-radius: 12px; font-weight: 700;"><i class="fa-solid fa-map-location-dot"></i> View Live Safety Map</button>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div class="enhanced-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <h4 style="color: #0F172A; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-cloud-sun" style="color: #F59E0B;"></i> Local Weather</h4>
                                <span class="badge risk-safe">Pokhara & KTM</span>
                            </div>
                            <div id="weather-temp-display" style="font-size: 32px; font-weight: 800; color: #0F172A;">24°C <i class="fa-solid fa-sun-cloud" style="color: #F59E0B; font-size: 24px;"></i></div>
                            <p id="weather-desc-display" style="font-size: 13px; color: #64748B; margin-top: 4px;">Partly Cloudy · Humidity 68%</p>
                            <div style="margin-top: 14px; background: #FFFBEB; border: 1px solid #FDE68A; padding: 10px 14px; border-radius: 10px; font-size: 12px; color: #B45309;">
                                💡 <b>Trekker Note:</b> Moderate monsoon rain expected high altitude after 3:00 PM.
                            </div>
                        </div>

                        <div class="enhanced-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <h4 style="color: #0F172A; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-users" style="color: #0EA5E9;"></i> Active Travel Team</h4>
                                <span class="badge risk-safe">Active</span>
                            </div>
                            <?php if ($activeTeam): ?>
                                <h3 style="font-size: 18px; font-weight: 800; color: #0F172A; margin-bottom: 4px;"><?php echo htmlspecialchars($activeTeam['name']); ?></h3>
                                <p style="font-size: 13px; color: #64748B; margin-bottom: 12px;">Team Code: <b><?php echo htmlspecialchars($activeTeam['team_code']); ?></b></p>
                                <p style="font-size: 12px; color: #64748B; margin-bottom: 14px;">Leader: <?php echo htmlspecialchars($activeTeam['leader_name']); ?> · <?php echo $activeTeam['member_count']; ?> Members</p>
                                <button onclick="document.querySelector('[data-tab-target=\'tab-team\']').click()" class="btn btn-outline" style="width: 100%; font-size: 13px; border-radius: 10px;">Open Team Hub <i class="fa-solid fa-arrow-right"></i></button>
                            <?php else: ?>
                                <p style="font-size: 13px; color: #64748B; margin-bottom: 16px;">You are not currently in a travel group.</p>
                                <button onclick="document.querySelector('[data-tab-target=\'tab-team\']').click()" class="btn btn-primary" style="width: 100%; font-size: 13px; border-radius: 10px;"><i class="fa-solid fa-plus"></i> Join or Create Team</button>
                            <?php endif; ?>
                        </div>

                        <div class="enhanced-card">
                            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                                <h4 style="color: #0F172A; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-shield-halved" style="color: #10B981;"></i> Current Zone Status</h4>
                                <span class="badge risk-safe">🟢 Safe Region</span>
                            </div>
                            <p style="font-size: 13px; color: #64748B; margin-bottom: 14px;">Your current location is verified outside active high-risk landslide or river flood boundaries.</p>
                            <div style="background: #FEF3C7; border: 1px solid #FCD34D; padding: 10px 14px; border-radius: 10px; font-size: 12px; color: #92400E;">
                                ⚠️ <b>Caution Zone 600m away:</b> Seti River Gorge High Risk Landslide Zone.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. DIRECT AGENCY CHAT TAB -->
                <div id="tab-agency-chat" class="tab-content <?php echo $initialTab === 'tab-agency-chat' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <h2 style="font-size: 22px; font-weight: 800; color: var(--color-dark-accent);"><i class="fa-solid fa-comments" style="color: var(--color-primary-dark);"></i> Direct Agency Messenger</h2>
                        <p style="font-size: 13px; color: var(--color-text-secondary);">Chat directly with travel agencies & hotel providers</p>
                    </div>

                    <div class="chat-container">
                        <div class="chat-header">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <label style="font-weight: bold; font-size: 14px;"><i class="fa-solid fa-building"></i> Select Agency:</label>
                                <?php if (!empty($agencies)): ?>
                                    <select id="agency-select" class="form-select" style="width: auto; font-size: 13px;" onchange="loadTravelerChat()">
                                        <?php foreach ($agencies as $a): ?>
                                            <option value="<?php echo $a['id']; ?>" <?php echo $a['id'] == $initialOrgId ? 'selected' : ''; ?>>
                                                🏢 <?php echo htmlspecialchars($a['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php else: ?>
                                    <select id="agency-select" class="form-select" style="width: auto; font-size: 13px;" disabled>
                                        <option value="0">No active agency chats or bookings.</option>
                                    </select>
                                <?php endif; ?>
                            </div>
                            <span class="badge risk-safe"><i class="fa-solid fa-circle-check"></i> Verified Provider</span>
                        </div>

                        <div id="chat-messages-box" class="chat-messages">
                            <p style="text-align: center; color: #64748B; font-size: 13px;">Loading conversation history...</p>
                        </div>

                        <form id="chat-send-form" class="chat-input-bar">
                            <input type="text" id="chat-input-text" class="form-input" placeholder="Type your message or trip inquiry to the agency..." required>
                            <button type="submit" class="btn btn-primary" style="padding: 0 20px;"><i class="fa-solid fa-paper-plane"></i> Send</button>
                        </form>
                    </div>
                </div>

                <!-- 4. LIVE SAFETY MAP TAB -->
                <div id="tab-map" class="tab-content <?php echo $initialTab === 'tab-map' ? 'active' : ''; ?>">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 16px;"><i class="fa-solid fa-map-location-dot"></i> Live Geofenced Safety Map</h2>
                    <div id="map" style="height: 480px; width: 100%; border-radius: 16px; border: 1px solid var(--color-border);"></div>
                </div>

                <!-- 5. TRAVEL TEAM TAB -->
                <div id="tab-team" class="tab-content <?php echo $initialTab === 'tab-team' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <h2 style="font-size: 22px; font-weight: 800; color: #0F172A;"><i class="fa-solid fa-users" style="color: #0284C7;"></i> Joined Travel Groups & Squad Hub</h2>
                        <p style="color: #64748B; font-size: 13px; margin-top: 4px;">Join travel teams using a Team Code provided by your Travel Agency / Admin. View fellow travelers and group chat in real-time.</p>
                    </div>

                    <!-- Join Team with Code Form Card -->
                    <div class="enhanced-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
                            <div>
                                <h4 style="margin-bottom: 4px; font-size: 16px; font-weight: 800; color: #0F172A;"><i class="fa-solid fa-key" style="color: #10B981;"></i> Join a Travel Team via Team Code</h4>
                                <p style="font-size: 12px; color: #64748B; margin: 0;">Enter the unique Team Code provided by your Agency Admin or Group Leader to join your squad.</p>
                            </div>
                            <form id="join-team-form" style="display: flex; gap: 10px; flex-wrap: wrap;">
                                <input type="text" name="team_code" id="join-team-code-input" class="form-input" placeholder="Enter Team Code (e.g. NP-8X492A)" required style="font-size: 13px; text-transform: uppercase; font-weight: 800; letter-spacing: 1.5px; width: 260px;">
                                <button type="submit" class="btn btn-success" style="white-space: nowrap; font-size: 13px; background: #10B981; color: white; border: none; padding: 0 20px; font-weight: 700;"><i class="fa-solid fa-right-to-bracket"></i> Join Team</button>
                            </form>
                        </div>
                    </div>

                    <!-- Active Teams & Roster Details Container -->
                    <div id="active-team-details-container">
                        <div style="text-align: center; padding: 40px; color: #64748B; background: white; border-radius: 16px; border: 1px solid #E2E8F0;">
                            <i class="fa-solid fa-users-viewfinder" style="font-size: 36px; color: #CBD5E1; margin-bottom: 12px;"></i>
                            <p style="font-size: 14px; font-weight: 600;">Loading your joined travel teams...</p>
                        </div>
                    </div>
                </div>

                <!-- 6. HOTELS & STAYS TAB -->
                <div id="tab-hotels" class="tab-content <?php echo $initialTab === 'tab-hotels' ? 'active' : ''; ?>">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 16px;"><i class="fa-solid fa-hotel"></i> Verified Hotels & Accommodations</h2>
                    <p style="color: #64748B; margin-bottom: 24px;">Browse trusted accommodations verified by SahaYatri safety standards.</p>
                    
                    <div class="dashboard-grid">
                        <?php if (empty($verifiedHotels)): ?>
                            <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;">
                                <i class="fa-solid fa-hotel" style="font-size: 40px; color: var(--color-text-muted); margin-bottom: 12px;"></i>
                                <h3>No verified accommodations found</h3>
                            </div>
                        <?php else: ?>
                            <?php foreach ($verifiedHotels as $hotel): ?>
                                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                                    <img src="<?php echo htmlspecialchars($hotel['image_url'] ?: 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800'); ?>" style="width:100%;height:180px;object-fit:cover;" alt="<?php echo htmlspecialchars($hotel['name']); ?>">
                                    <div style="padding: 16px; flex-grow: 1; display: flex; flex-direction: column;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span class="badge risk-safe"><i class="fa-solid fa-shield-halved"></i> Trust Score: <?php echo $hotel['trust_score']; ?>/100</span>
                                            <span style="font-size: 12px; font-weight: bold; color: var(--color-text-secondary);"><i class="fa-solid fa-star" style="color: #F59E0B;"></i> <?php echo $hotel['rating']; ?></span>
                                        </div>
                                        <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 6px;"><?php echo htmlspecialchars($hotel['name']); ?></h3>
                                        <p style="font-size: 13px; color: var(--color-text-secondary); margin-bottom: 8px;"><i class="fa-solid fa-location-dot" style="color: var(--color-primary-dark);"></i> <?php echo htmlspecialchars($hotel['destination_name']); ?> · <?php echo htmlspecialchars($hotel['type']); ?></p>
                                        
                                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--color-border);">
                                            <div>
                                                <span style="font-size: 11px; color: var(--color-text-secondary); display: block;">Per Night</span>
                                                <strong style="font-size: 16px; color: var(--color-primary-dark);">NPR <?php echo number_format($hotel['price_per_night']); ?></strong>
                                            </div>
                                            <a href="accommodation_details.php?id=<?php echo $hotel['id']; ?>" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;">View Details</a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 7. FAIR PRICE & SCAM PREVENTION TAB -->
                <div id="tab-scams" class="tab-content <?php echo $initialTab === 'tab-scams' ? 'active' : ''; ?>">

                    <!-- Hero Banner -->
                    <div class="fp-hero">
                        <div style="position:relative; z-index:1;">
                            <div style="display:flex; align-items:center; gap:12px; margin-bottom:10px;">
                                <div style="background:rgba(255,255,255,0.2); width:44px; height:44px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:20px;">&#x2696;&#xFE0F;</div>
                                <div>
                                    <div style="font-size:11px; font-weight:700; opacity:0.75; letter-spacing:1px; text-transform:uppercase;">Nepal Group Travel</div>
                                    <h2 style="font-size:24px; font-weight:900; margin:0;">Fair Price Guide &amp; Scam Alerts</h2>
                                </div>
                            </div>
                            <p style="font-size:14px; opacity:0.85; max-width:580px; line-height:1.6; margin:0;">Official benchmark prices for group treks &mdash; from airport arrival to every piece of equipment. Always verify before paying. All prices in <strong>NPR</strong> unless noted.</p>
                        </div>
                    </div>

                    <!-- Summary Stat Strip -->
                    <div class="fp-stat-strip">
                        <div class="fp-stat-box">
                            <div class="fp-stat-icon" style="background:#EFF6FF;">&#x1F6EB;</div>
                            <div>
                                <div style="font-size:11px; color:#64748B; font-weight:600;">AIRPORT TAXI</div>
                                <div style="font-size:18px; font-weight:900; color:#0284C7;">NPR 700</div>
                                <div style="font-size:11px; color:#94A3B8;">to Thamel (fair)</div>
                            </div>
                        </div>
                        <div class="fp-stat-box">
                            <div class="fp-stat-icon" style="background:#F0FDF4;">&#x1F464;</div>
                            <div>
                                <div style="font-size:11px; color:#64748B; font-weight:600;">GUIDE RATE</div>
                                <div style="font-size:18px; font-weight:900; color:#10B981;">NPR 2,500</div>
                                <div style="font-size:11px; color:#94A3B8;">per day (licensed)</div>
                            </div>
                        </div>
                        <div class="fp-stat-box">
                            <div class="fp-stat-icon" style="background:#FFFBEB;">&#x1F4DC;</div>
                            <div>
                                <div style="font-size:11px; color:#64748B; font-weight:600;">TIMS PERMIT</div>
                                <div style="font-size:18px; font-weight:900; color:#F59E0B;">NPR 2,000</div>
                                <div style="font-size:11px; color:#94A3B8;">per person</div>
                            </div>
                        </div>
                        <div class="fp-stat-box">
                            <div class="fp-stat-icon" style="background:#FFF1F2;">&#x1F465;</div>
                            <div>
                                <div style="font-size:11px; color:#64748B; font-weight:600;">GROUP (10 pax)</div>
                                <div style="font-size:18px; font-weight:900; color:#EF4444;">NPR 34k</div>
                                <div style="font-size:11px; color:#94A3B8;">per person/10 days</div>
                            </div>
                        </div>
                    </div>

                    <!-- Section Pill Tabs -->
                    <div class="fp-section-tabs" id="fp-section-tabs">
                        <button class="fp-tab-btn active" data-fp-tab="fp-arrivals"><i class="fa-solid fa-plane-arrival"></i> Arrival &amp; Transfers</button>
                        <button class="fp-tab-btn" data-fp-tab="fp-permits"><i class="fa-solid fa-file-contract"></i> Permits &amp; Daily</button>
                        <button class="fp-tab-btn" data-fp-tab="fp-equipment"><i class="fa-solid fa-backpack"></i> Equipment</button>
                        <button class="fp-tab-btn" data-fp-tab="fp-safety"><i class="fa-solid fa-heart-pulse"></i> Safety &amp; Medical</button>
                        <button class="fp-tab-btn" data-fp-tab="fp-scam-list"><i class="fa-solid fa-circle-exclamation"></i> Scam Watch</button>
                    </div>

                    <!-- Panel 1: Arrivals -->
                    <div class="fp-panel active" id="fp-arrivals">
                        <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:20px;">

                            <div class="fp-card">
                                <div class="fp-card-header">
                                    <div class="fp-card-icon" style="background:#EFF6FF; color:#0284C7;"><i class="fa-solid fa-plane-arrival"></i></div>
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0F172A;">Airport Transfers</div>
                                        <div style="font-size:11px; color:#94A3B8;">Tribhuvan Int&apos;l &rarr; City</div>
                                    </div>
                                </div>
                                <div class="fp-card-body">
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F696; Standard Taxi (1&ndash;4 pax)</span>
                                        <span class="fp-price-value" style="color:#0284C7;">NPR 700&ndash;1,000</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F690; Microbus / Van (5&ndash;12)</span>
                                        <span class="fp-price-value" style="color:#0284C7;">NPR 2,500&ndash;3,500</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F68C; Tourist Bus (13&ndash;20+)</span>
                                        <span class="fp-price-value" style="color:#0284C7;">NPR 5,000&ndash;8,000</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F3E8; Hotel Pick-up Service</span>
                                        <span class="fp-price-value" style="color:#0284C7;">NPR 1,200&ndash;2,000</span>
                                    </div>
                                </div>
                                <div class="fp-alert-box" style="background:#FEF3C7; color:#92400E; margin:0 16px 16px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> <b>Scam:</b> Unlicensed &quot;helpers&quot; at arrivals charge 3x&ndash;5x. Use official NTB taxi counters only.
                                </div>
                            </div>

                            <div class="fp-card">
                                <div class="fp-card-header">
                                    <div class="fp-card-icon" style="background:#F5F3FF; color:#7C3AED;"><i class="fa-solid fa-route"></i></div>
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0F172A;">Intercity Transport</div>
                                        <div style="font-size:11px; color:#94A3B8;">Group charter &amp; shared buses</div>
                                    </div>
                                </div>
                                <div class="fp-card-body">
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">KTM &rarr; Pokhara (Tourist Bus)</span>
                                        <span class="fp-price-value" style="color:#7C3AED;">NPR 800&ndash;1,500</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">KTM &rarr; Pokhara (Private Van)</span>
                                        <span class="fp-price-value" style="color:#7C3AED;">NPR 12k&ndash;18k</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">Pokhara &rarr; Nayapul Trailhead</span>
                                        <span class="fp-price-value" style="color:#7C3AED;">NPR 1,500&ndash;2,500</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">KTM &rarr; Lukla Flight</span>
                                        <span class="fp-price-value" style="color:#7C3AED;">NPR 20k&ndash;28k</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">4WD Jeep (remote routes)</span>
                                        <span class="fp-price-value" style="color:#7C3AED;">NPR 8k&ndash;15k</span>
                                    </div>
                                </div>
                            </div>

                            <div class="fp-card">
                                <div class="fp-card-header">
                                    <div class="fp-card-icon" style="background:#F0FDF4; color:#10B981;"><i class="fa-solid fa-people-group"></i></div>
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0F172A;">Group Staffing</div>
                                        <div style="font-size:11px; color:#94A3B8;">Per day rates</div>
                                    </div>
                                </div>
                                <div class="fp-card-body">
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F9ED; Licensed Trekking Guide</span>
                                        <span class="fp-price-value" style="color:#10B981;">NPR 2,500&ndash;4,000</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F6BB; Porter (up to 25 kg)</span>
                                        <span class="fp-price-value" style="color:#10B981;">NPR 2,200&ndash;3,500</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F6E1;&#xFE0F; Porter Insurance</span>
                                        <span class="fp-price-value" style="color:#10B981;">NPR 300&ndash;500</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F373; Group Cook (5+ pax)</span>
                                        <span class="fp-price-value" style="color:#10B981;">NPR 2,000&ndash;3,500</span>
                                    </div>
                                    <div class="fp-price-row">
                                        <span class="fp-price-label">&#x1F44D; Tips (guide/porter)</span>
                                        <span class="fp-price-value" style="color:#10B981;">NPR 500&ndash;1,000/day</span>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Panel 2: Permits & Daily -->
                    <div class="fp-panel" id="fp-permits">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                            <div class="fp-card">
                                <div class="fp-card-header">
                                    <div class="fp-card-icon" style="background:#ECFDF5; color:#059669;"><i class="fa-solid fa-file-contract"></i></div>
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0F172A;">Official Trekking Permits</div>
                                        <div style="font-size:11px; color:#94A3B8;">Per person &mdash; buy from official NTB offices</div>
                                    </div>
                                </div>
                                <div class="fp-card-body">
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F4CB; TIMS Card</span><span class="fp-price-value" style="color:#059669;">NPR 2,000</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F3D4;&#xFE0F; Sagarmatha NP (Everest)</span><span class="fp-price-value" style="color:#059669;">NPR 3,000</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F333; Annapurna Conservation (ACAP)</span><span class="fp-price-value" style="color:#059669;">NPR 3,000</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x26F0;&#xFE0F; Langtang National Park</span><span class="fp-price-value" style="color:#059669;">NPR 3,000</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F512; Manaslu (Restricted Area)</span><span class="fp-price-value" style="color:#DC2626;">NPR 35,000+</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F6C2; Nepal Visa on Arrival (15d)</span><span class="fp-price-value" style="color:#059669;">USD 30 (~NPR 4k)</span></div>
                                </div>
                            </div>

                            <div class="fp-card">
                                <div class="fp-card-header">
                                    <div class="fp-card-icon" style="background:#FFF7ED; color:#EA580C;"><i class="fa-solid fa-utensils"></i></div>
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0F172A;">Daily Meals &amp; Accommodation</div>
                                        <div style="font-size:11px; color:#94A3B8;">Per person per day on trail</div>
                                    </div>
                                </div>
                                <div class="fp-card-body">
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F3E0; Tea House Lodging</span><span class="fp-price-value" style="color:#EA580C;">NPR 300&ndash;1,000</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F963; Dal Bhat (local staple)</span><span class="fp-price-value" style="color:#EA580C;">NPR 400&ndash;800</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x2615; Tea / Coffee / Snacks</span><span class="fp-price-value" style="color:#EA580C;">NPR 200&ndash;500</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F4A7; Boiled Safe Water</span><span class="fp-price-value" style="color:#EA580C;">NPR 100&ndash;300</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label">&#x1F310; WiFi / Device Charging</span><span class="fp-price-value" style="color:#EA580C;">NPR 200&ndash;500</span></div>
                                </div>
                                <div class="fp-alert-box" style="background:#F0FDF4; color:#15803D; margin:0 16px 16px;">
                                    <i class="fa-solid fa-circle-check"></i> <b>Tip:</b> Lodging is often free/discounted when you eat all your meals at the same teahouse.
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Panel 3: Equipment Grid -->
                    <div class="fp-panel" id="fp-equipment">
                        <div class="fp-card" style="margin-bottom:0;">
                            <div class="fp-card-header">
                                <div class="fp-card-icon" style="background:#FFFBEB; color:#D97706;"><i class="fa-solid fa-backpack"></i></div>
                                <div>
                                    <div style="font-size:14px; font-weight:800; color:#0F172A;">Rental Gear Prices</div>
                                    <div style="font-size:11px; color:#94A3B8;">Available at Thamel (Kathmandu) &amp; Lakeside (Pokhara)</div>
                                </div>
                                <div style="margin-left:auto; display:flex; gap:16px; font-size:11px; align-items:center;">
                                    <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; background:#0284C7; border-radius:3px; display:inline-block;"></span> Rent/Day</span>
                                    <span style="display:flex; align-items:center; gap:5px;"><span style="width:10px; height:10px; background:#64748B; border-radius:3px; display:inline-block;"></span> Buy Used</span>
                                </div>
                            </div>
                            <?php
                            $gear = [
                                ['&#x1F392;','Backpack 50&ndash;70L',      'NPR 300/day','NPR 2,500&ndash;4,500'],
                                ['&#x1F6CF;&#xFE0F;','Sleeping Bag (&minus;10&deg;C)','NPR 350/day','NPR 3,000&ndash;6,000'],
                                ['&#x1F9E5;','Down Jacket (&minus;15&deg;C)','NPR 400/day','NPR 3,500&ndash;7,000'],
                                ['&#x1F97E;','Trekking Boots',       'NPR 350/day','NPR 4,000&ndash;8,000'],
                                ['&#x26FA;&#xFE0F;','Tent (2&ndash;3 person)',      'NPR 500/day','NPR 5,000&ndash;12,000'],
                                ['&#x1F9F3;','Trekking Poles (pair)',  'NPR 200/day','NPR 1,500&ndash;3,500'],
                                ['&#x1F576;&#xFE0F;','UV Glacier Sunglasses',  'NPR 150/day','NPR 800&ndash;2,500'],
                                ['&#x1F9E4;','Fleece Gloves / Mittens','NPR 100/day','NPR 500&ndash;1,500'],
                                ['&#x1F302;','Rain Poncho / Jacket',   'NPR 150/day','NPR 700&ndash;2,000'],
                                ['&#x1F526;','Headlamp + Batteries',   'NPR 100/day','NPR 600&ndash;1,800'],
                            ];
                            ?>
                            <div class="fp-gear-grid">
                                <?php foreach ($gear as $g): ?>
                                <div class="fp-gear-item">
                                    <span class="fp-gear-emoji"><?php echo $g[0]; ?></span>
                                    <div class="fp-gear-name"><?php echo $g[1]; ?></div>
                                    <div class="fp-gear-prices">
                                        <span class="fp-gear-rent"><i class="fa-solid fa-tag" style="font-size:9px;"></i> <?php echo $g[2]; ?></span>
                                        <span class="fp-gear-buy"><i class="fa-solid fa-cart-shopping" style="font-size:9px;"></i> <?php echo $g[3]; ?></span>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Panel 4: Safety & Medical + Budget -->
                    <div class="fp-panel" id="fp-safety">
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px;">

                            <div class="fp-card">
                                <div class="fp-card-header">
                                    <div class="fp-card-icon" style="background:#FFF1F2; color:#EF4444;"><i class="fa-solid fa-heart-pulse"></i></div>
                                    <div>
                                        <div style="font-size:14px; font-weight:800; color:#0F172A;">Safety &amp; Medical</div>
                                        <div style="font-size:11px; color:#94A3B8;">Essential before high-altitude trekking</div>
                                    </div>
                                </div>
                                <div class="fp-card-body">
                                    <div class="fp-price-row"><span class="fp-price-label"><i class="fa-solid fa-shield-heart" style="color:#EF4444;"></i> Travel Insurance (per trip)</span><span class="fp-price-value" style="color:#EF4444;">NPR 3,500&ndash;8,000</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label"><i class="fa-solid fa-kit-medical" style="color:#EF4444;"></i> First Aid Kit (complete)</span><span class="fp-price-value" style="color:#EF4444;">NPR 800&ndash;2,000</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label"><i class="fa-solid fa-pills" style="color:#EF4444;"></i> Altitude Pills (Diamox)</span><span class="fp-price-value" style="color:#EF4444;">NPR 200&ndash;500</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label"><i class="fa-solid fa-droplet-slash" style="color:#EF4444;"></i> Water Purification Tabs</span><span class="fp-price-value" style="color:#EF4444;">NPR 300&ndash;700</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label"><i class="fa-solid fa-helicopter" style="color:#EF4444;"></i> Helicopter Rescue (if needed)</span><span class="fp-price-value" style="color:#DC2626;">USD 3k&ndash;5k</span></div>
                                    <div class="fp-price-row"><span class="fp-price-label"><i class="fa-solid fa-sun" style="color:#EF4444;"></i> Sunscreen SPF 50+</span><span class="fp-price-value" style="color:#EF4444;">NPR 300&ndash;800</span></div>
                                </div>
                                <div class="fp-alert-box" style="background:#FFF1F2; color:#991B1B; margin:0 16px 16px; border:1px solid #FECDD3;">
                                    <i class="fa-solid fa-circle-exclamation"></i> <b>CRITICAL:</b> Book travel insurance covering helicopter evacuation above 5,000m <em>before</em> your trek begins.
                                </div>
                            </div>

                            <!-- Group Budget Card -->
                            <div>
                                <div class="fp-budget-card">
                                    <div style="display:flex; align-items:center; gap:12px; margin-bottom:20px;">
                                        <div style="background:rgba(255,255,255,0.15); width:42px; height:42px; border-radius:12px; display:flex; align-items:center; justify-content:center; font-size:18px;">&#x1F4B0;</div>
                                        <div>
                                            <div style="font-size:10px; opacity:0.7; letter-spacing:1px; text-transform:uppercase;">Estimated Group Budget</div>
                                            <div style="font-size:15px; font-weight:800;">10 Pax &bull; 10-Day Trek</div>
                                        </div>
                                    </div>
                                    <div class="fp-budget-row"><span>&#x1F68C; Transfers &amp; Transport</span><span style="font-weight:700;">NPR 45,000</span></div>
                                    <div class="fp-budget-row"><span>&#x1F3E0; Tea House (10d &times; 10 pax)</span><span style="font-weight:700;">NPR 60,000</span></div>
                                    <div class="fp-budget-row"><span>&#x1F35C; Meals (3&times;/day &times; 10 days)</span><span style="font-weight:700;">NPR 80,000</span></div>
                                    <div class="fp-budget-row"><span>&#x1F4DC; Permits (TIMS + NP)</span><span style="font-weight:700;">NPR 50,000</span></div>
                                    <div class="fp-budget-row"><span>&#x1F9ED; Guide (10 days)</span><span style="font-weight:700;">NPR 30,000</span></div>
                                    <div class="fp-budget-row"><span>&#x1F392; Equipment Rental</span><span style="font-weight:700;">NPR 35,000</span></div>
                                    <div class="fp-budget-row" style="border-bottom:none;"><span>&#x2695;&#xFE0F; Insurance + Medical</span><span style="font-weight:700;">NPR 40,000</span></div>
                                    <div style="background:rgba(255,255,255,0.12); border-radius:12px; padding:16px 18px; margin-top:18px; display:flex; justify-content:space-between; align-items:center;">
                                        <div>
                                            <div style="font-size:11px; opacity:0.75;">Total for entire group</div>
                                            <div style="font-size:26px; font-weight:900;">NPR 3,40,000</div>
                                        </div>
                                        <div style="text-align:right;">
                                            <div style="font-size:11px; opacity:0.75;">Per person</div>
                                            <div style="font-size:22px; font-weight:900; color:#67E8F9;">NPR 34,000</div>
                                            <div style="font-size:11px; opacity:0.65;">~USD 250</div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>

                    <!-- Panel 5: Scam Watch -->
                    <div class="fp-panel" id="fp-scam-list">
                        <div style="display:grid; grid-template-columns:repeat(3, 1fr); gap:18px;">
                            <?php
                            $scams = [
                                ['high', 'fa-taxi',            'Fake Taxi Meters',      'Drivers claim meters are broken, charging 3x&ndash;5x. Agree fare upfront or use Pathao / InDrive apps.'],
                                ['high', 'fa-map',             'Fake Trekking Guides',  'Unlicensed &ldquo;guides&rdquo; at Thamel offer cheap rates and abandon groups mid-trek. Only hire NTB-certified guides.'],
                                ['medium','fa-gem',            'Gem &amp; Pashmina Scam','Acrylic scarves sold as genuine Pashmina; glass sold as precious gems. Buy from government emporiums only.'],
                                ['medium','fa-hand-holding-heart','Donation Fraud',      'Fake NGO collectors at tourist spots. Always verify registration before giving money to any collector.'],
                                ['high', 'fa-id-card',         'Fake Permit Sellers',   'Street sellers offer forged TIMS / NP permits. Always buy from official NTB or ACAP counters only.'],
                                ['high', 'fa-mug-hot',         'Tea Drugging Scam',     'Strangers invite tourists for free tea &mdash; drinks may be spiked for theft. Never accept food from strangers you just met.'],
                            ];
                            foreach ($scams as $s):
                                $isHigh = $s[0] === 'high';
                                $badgeColor = $isHigh ? '#DC2626' : '#D97706';
                                $badgeBg    = $isHigh ? '#FEF2F2' : '#FFFBEB';
                                $badgeTxt   = $isHigh ? 'HIGH RISK' : 'MEDIUM RISK';
                            ?>
                            <div class="fp-scam-card <?php echo $s[0]; ?>">
                                <div style="padding-left:8px;">
                                    <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:10px;">
                                        <div style="background:<?php echo $badgeBg; ?>; width:40px; height:40px; border-radius:10px; display:flex; align-items:center; justify-content:center;">
                                            <i class="fa-solid <?php echo $s[1]; ?>" style="color:<?php echo $badgeColor; ?>;font-size:16px;"></i>
                                        </div>
                                        <span style="background:<?php echo $badgeBg; ?>; color:<?php echo $badgeColor; ?>; font-size:10px; font-weight:800; padding:3px 8px; border-radius:20px; letter-spacing:0.5px;"><?php echo $badgeTxt; ?></span>
                                    </div>
                                    <h4 style="font-size:14px; font-weight:800; color:#0F172A; margin-bottom:8px;"><?php echo $s[2]; ?></h4>
                                    <p style="font-size:13px; color:#64748B; line-height:1.65; margin:0;"><?php echo $s[3]; ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <div style="background:#FEF3C7; border:1px solid #FCD34D; border-radius:14px; padding:18px 22px; margin-top:22px; display:flex; align-items:flex-start; gap:14px;">
                            <div style="background:#F59E0B; width:38px; height:38px; border-radius:10px; display:flex; align-items:center; justify-content:center; flex-shrink:0;">
                                <i class="fa-solid fa-shield-halved" style="color:white; font-size:16px;"></i>
                            </div>
                            <div>
                                <div style="font-size:13px; font-weight:800; color:#92400E; margin-bottom:4px;">General Safety Rule</div>
                                <div style="font-size:13px; color:#78350F; line-height:1.6;">If a price seems too good to be true, it probably is. Always insist on written receipts, check guide credentials at NTB offices, and report suspicious activity to Tourist Police: <strong>1144</strong>.</div>
                            </div>
                        </div>
                    </div>

                    <!-- Inner Tab JS -->
                    <script>
                    (function() {
                        var btns   = document.querySelectorAll('#fp-section-tabs .fp-tab-btn');
                        var panels = document.querySelectorAll('.fp-panel');
                        btns.forEach(function(btn) {
                            btn.addEventListener('click', function() {
                                btns.forEach(function(b) { b.classList.remove('active'); });
                                panels.forEach(function(p) { p.classList.remove('active'); });
                                btn.classList.add('active');
                                var target = btn.getAttribute('data-fp-tab');
                                var panel = document.getElementById(target);
                                if (panel) panel.classList.add('active');
                            });
                        });
                    })();
                    </script>

                </div>

                <!-- 8. AI ASSISTANT TAB -->
                <div id="tab-ai" class="tab-content <?php echo $initialTab === 'tab-ai' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <h2 style="font-size: 22px; font-weight: 800; color: #0F172A;"><i class="fa-solid fa-robot" style="color: #0284C7;"></i> SahaYatri AI Travel Assistant</h2>
                        <p style="color: #64748B; font-size: 13px; margin-top: 4px;">Ask questions about safety zones, trek benchmark prices, permits, and Nepal travel advice.</p>
                    </div>

                    <div class="card" style="padding: 0; overflow: hidden; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.05); display: flex; flex-direction: column; height: 560px;">
                        <!-- AI Header -->
                        <div style="background: linear-gradient(135deg, #0F172A 0%, #0284C7 100%); padding: 18px 24px; color: white; display: flex; justify-content: space-between; align-items: center;">
                            <div style="display: flex; align-items: center; gap: 12px;">
                                <div style="width: 40px; height: 40px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px;">
                                    🤖
                                </div>
                                <div>
                                    <h4 style="margin: 0; font-size: 16px; font-weight: 800;">SahaYatri AI Companion</h4>
                                    <p style="margin: 2px 0 0 0; font-size: 11px; opacity: 0.85;">24/7 Smart Travel &amp; Safety Advisor</p>
                                </div>
                            </div>
                            <span class="badge" style="background: rgba(255,255,255,0.2); color: white; font-size: 11px; font-weight: 700;">🟢 Online</span>
                        </div>

                        <!-- Chat Messages Box -->
                        <div id="ai-chat-box" style="flex: 1; padding: 24px; overflow-y: auto; background: #F8FAFC; display: flex; flex-direction: column; gap: 14px;">
                            <div style="display: flex; gap: 12px; align-items: flex-start;">
                                <div style="width: 34px; height: 34px; border-radius: 50%; background: #0284C7; color: white; display: flex; align-items: center; justify-content: center; font-size: 15px; flex-shrink: 0;">
                                    🤖
                                </div>
                                <div style="background: white; border: 1px solid #E2E8F0; padding: 14px 18px; border-radius: 16px; border-top-left-radius: 4px; max-width: 80%; font-size: 13px; color: #1E293B; line-height: 1.6; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                    Namaste! 🙏 I'm your <b>SahaYatri AI Travel Companion</b>. How can I help you explore Nepal safely today?
                                </div>
                            </div>
                        </div>

                        <!-- Quick Action Suggestion Chips -->
                        <div style="background: white; border-top: 1px solid #E2E8F0; padding: 10px 20px; display: flex; gap: 8px; overflow-x: auto; white-space: nowrap;">
                            <button type="button" onclick="askQuickAI('What is the current weather forecast?')" class="btn" style="background: #F1F5F9; color: #334155; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 20px;">🌦️ Weather</button>
                            <button type="button" onclick="askQuickAI('Are there any active safety or landslide alerts?')" class="btn" style="background: #F1F5F9; color: #334155; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 20px;">🛡️ Safety Zones</button>
                            <button type="button" onclick="askQuickAI('Which verified hotels do you recommend?')" class="btn" style="background: #F1F5F9; color: #334155; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 20px;">🏨 Verified Stays</button>
                            <button type="button" onclick="askQuickAI('What should I do in an emergency?')" class="btn" style="background: #F1F5F9; color: #334155; font-size: 12px; font-weight: 600; padding: 6px 12px; border-radius: 20px;">🚨 SOS &amp; Hotlines</button>
                        </div>

                        <!-- Chat Input Form -->
                        <form id="ai-chat-form" onsubmit="handleAIChatSubmit(event)" style="background: white; border-top: 1px solid #E2E8F0; padding: 16px 20px; display: flex; gap: 10px; align-items: center;">
                            <input type="text" id="ai-user-input" class="form-input" placeholder="Ask AI about trekking permits, weather conditions, or safety tips..." required style="font-size: 13px; border-radius: 12px; padding: 12px 16px; flex: 1;">
                            <button type="submit" id="ai-send-btn" class="btn btn-primary" style="font-size: 13px; font-weight: 700; border-radius: 12px; padding: 12px 22px; white-space: nowrap; background: #0284C7; border: none; color: white; display: flex; align-items: center; gap: 8px;">
                                <i class="fa-solid fa-paper-plane"></i> Ask AI
                            </button>
                        </form>
                    </div>
                </div>

                <!-- 9. EMERGENCY CONTACTS TAB -->
                <div id="tab-emergency" class="tab-content <?php echo $initialTab === 'tab-emergency' ? 'active' : ''; ?>">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 16px; color: var(--color-danger);"><i class="fa-solid fa-kit-medical"></i> Emergency Hotline Directory</h2>
                    <div class="dashboard-grid">
                        <div class="card">
                            <h4>👮 Tourist Police Hotline</h4>
                            <p style="font-size: 16px; font-weight: bold; color: var(--color-danger); margin: 6px 0;">1144</p>
                            <a href="tel:1144" class="btn btn-danger" style="font-size: 12px; padding: 6px 12px;"><i class="fa-solid fa-phone"></i> Call 1144</a>
                        </div>
                        <div class="card">
                            <h4>🚑 National Ambulance Hotline</h4>
                            <p style="font-size: 16px; font-weight: bold; color: var(--color-danger); margin: 6px 0;">102</p>
                            <a href="tel:102" class="btn btn-danger" style="font-size: 12px; padding: 6px 12px;"><i class="fa-solid fa-phone"></i> Call 102</a>
                        </div>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- TEAM GROUP CHAT SEPARATE MODAL POPUP WINDOW -->
    <div id="team-chat-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.65); backdrop-filter: blur(5px); z-index: 99999; align-items: center; justify-content: center;">
        <div style="width: 90%; max-width: 580px; background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 60px rgba(0, 0, 0, 0.35); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; animation: modalPop 0.25s ease-out;">
            <!-- Modal Header -->
            <div style="padding: 18px 24px; background: #0F172A; color: #FFFFFF; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #1E293B;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 40px; height: 40px; background: #0284C7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; color: white;">
                        💬
                    </div>
                    <div>
                        <h3 id="modal-team-name" style="font-size: 17px; font-weight: 800; margin: 0; color: #FFFFFF;">Team Group Chat</h3>
                        <p style="font-size: 11px; color: #94A3B8; margin: 2px 0 0 0;">Live squad conversation & member updates</p>
                    </div>
                </div>
                <button onclick="closeTeamChatModal()" style="background: rgba(255,255,255,0.12); border: none; color: #FFFFFF; font-size: 18px; width: 34px; height: 34px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                    ✕
                </button>
            </div>

            <!-- Modal Chat Message Body -->
            <div id="modal-team-chat-messages" style="height: 380px; overflow-y: auto; background: #F8FAFC; padding: 20px; display: flex; flex-direction: column;">
                <p style="text-align: center; color: #94A3B8; font-size: 13px; margin: auto;">Loading chat messages...</p>
            </div>

            <!-- Modal Chat Input Footer -->
            <form id="modal-team-chat-form" onsubmit="sendModalTeamMessage(event)" style="padding: 16px 20px; background: #FFFFFF; border-top: 1px solid #E2E8F0; display: flex; gap: 10px; align-items: center;">
                <input type="hidden" id="modal-active-team-id" value="">
                <input type="text" id="modal-team-chat-input" class="form-input" placeholder="Type a message to your team members..." required style="font-size: 13px; border-radius: 12px; padding: 12px 16px; flex: 1;">
                <button type="submit" class="btn btn-primary" style="font-size: 13px; font-weight: 700; border-radius: 12px; padding: 12px 22px; white-space: nowrap; background: #0284C7; border: none; color: white; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-paper-plane"></i> Send
                </button>
            </form>
        </div>
    </div>

    <!-- 1-TAP EMERGENCY SOS FULL-SCREEN / POPUP MODAL -->
    <div id="sos-emergency-modal" style="display: none; position: fixed; top: 0; left: 0; width: 100vw; height: 100vh; background: rgba(15, 23, 42, 0.75); backdrop-filter: blur(6px); z-index: 999999; align-items: center; justify-content: center; padding: 20px;">
        <div style="width: 100%; max-width: 620px; background: #FFFFFF; border-radius: 24px; box-shadow: 0 25px 60px rgba(220, 38, 38, 0.35); overflow: hidden; display: flex; flex-direction: column; max-height: 90vh; animation: modalPop 0.25s ease-out; border: 2px solid #EF4444;">
            
            <!-- Modal Emergency Header -->
            <div style="padding: 20px 24px; background: linear-gradient(135deg, #DC2626 0%, #991B1B 100%); color: #FFFFFF; display: flex; justify-content: space-between; align-items: center;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="width: 44px; height: 44px; background: rgba(255,255,255,0.2); border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 22px; color: white;">
                        🚨
                    </div>
                    <div>
                        <h3 style="font-size: 18px; font-weight: 800; margin: 0; color: #FFFFFF; letter-spacing: 0.5px;">1-TAP EMERGENCY SOS</h3>
                        <p style="font-size: 12px; color: #FEE2E2; margin: 2px 0 0 0;">Live GPS Alert to Rescue Hotlines, Police & Squad</p>
                    </div>
                </div>
                <button onclick="closeSOSModal()" style="background: rgba(255,255,255,0.2); border: none; color: #FFFFFF; font-size: 18px; width: 36px; height: 36px; border-radius: 50%; cursor: pointer; display: flex; align-items: center; justify-content: center; transition: background 0.2s;">
                    ✕
                </button>
            </div>

            <!-- Modal Body Content -->
            <div style="padding: 24px; overflow-y: auto; display: flex; flex-direction: column; gap: 18px;">
                
                <!-- GPS Location Live Box -->
                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; padding: 14px 18px; display: flex; align-items: center; justify-content: space-between;">
                    <div style="display: flex; align-items: center; gap: 10px;">
                        <span style="display: inline-block; width: 10px; height: 10px; border-radius: 50%; background: #10B981; box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.2);"></span>
                        <div>
                            <div style="font-size: 11px; font-weight: 700; color: #64748B; text-transform: uppercase;">GPS Coordinate Broadcast</div>
                            <div id="sos-live-coords-display" style="font-size: 13px; font-weight: 700; color: #0F172A;">Detecting GPS location...</div>
                        </div>
                    </div>
                    <button onclick="refreshSOSLocation()" type="button" class="btn btn-secondary" style="font-size: 11px; padding: 6px 12px; border-radius: 8px;">
                        <i class="fa-solid fa-arrows-rotate"></i> Refresh
                    </button>
                </div>

                <!-- Emergency Dispatch View -->
                <div id="sos-pre-dispatch-view" style="display: flex; flex-direction: column; gap: 16px;">
                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 8px; display: block;">Select Emergency Category:</label>
                        <div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px;">
                            <button type="button" class="sos-cat-btn active" onclick="selectSOSCategory('Medical', this)"><i class="fa-solid fa-heart-pulse"></i> Medical</button>
                            <button type="button" class="sos-cat-btn" onclick="selectSOSCategory('Lost', this)"><i class="fa-solid fa-compass"></i> Lost / Off-trail</button>
                            <button type="button" class="sos-cat-btn" onclick="selectSOSCategory('Accident', this)"><i class="fa-solid fa-car-burst"></i> Accident</button>
                            <button type="button" class="sos-cat-btn" onclick="selectSOSCategory('Natural disaster', this)"><i class="fa-solid fa-mountain"></i> Disaster</button>
                            <button type="button" class="sos-cat-btn" onclick="selectSOSCategory('Harassment', this)"><i class="fa-solid fa-shield-halved"></i> Harassment</button>
                            <button type="button" class="sos-cat-btn" onclick="selectSOSCategory('Other', this)"><i class="fa-solid fa-triangle-exclamation"></i> Other</button>
                        </div>
                    </div>

                    <div>
                        <label style="font-size: 13px; font-weight: 700; color: #1E293B; margin-bottom: 8px; display: block;">Emergency Note / Specific Landmarks (Optional):</label>
                        <textarea id="sos-emergency-note" class="form-textarea" rows="2" placeholder="e.g. Altitude sickness symptoms near Tengboche, need emergency medical advice or evacuation..." style="font-size: 13px; border-radius: 12px; padding: 10px 14px; width: 100%; box-sizing: border-box;"></textarea>
                    </div>

                    <!-- Trigger Action Button -->
                    <button id="sos-dispatch-btn" onclick="dispatchSOSAlert()" class="btn btn-danger" style="width: 100%; padding: 16px; font-size: 15px; font-weight: 800; border-radius: 14px; background: #DC2626; border: none; color: white; display: flex; align-items: center; justify-content: center; gap: 10px; cursor: pointer; box-shadow: 0 8px 20px rgba(220, 38, 38, 0.4);">
                        <i class="fa-solid fa-bullhorn" style="font-size: 18px;"></i>
                        <span>🚨 BROADCAST EMERGENCY SOS NOW</span>
                    </button>
                </div>

                <!-- Post-Dispatch Live Status & Responders View -->
                <div id="sos-post-dispatch-view" style="display: none; flex-direction: column; gap: 16px;">
                    <div style="background: #FEF2F2; border: 1.5px solid #F87171; border-radius: 14px; padding: 16px; text-align: center;">
                        <div style="font-size: 28px; margin-bottom: 6px;">📡 🚨</div>
                        <h4 style="color: #991B1B; font-weight: 800; font-size: 16px; margin: 0 0 4px 0;">EMERGENCY SOS IS LIVE & BROADCASTING</h4>
                        <p id="sos-dispatch-status-msg" style="color: #7F1D1D; font-size: 12px; margin: 0; line-height: 1.5;">Emergency dispatch received. Nearby verified emergency teams and squad members are being notified with your exact GPS position.</p>
                    </div>

                    <div>
                        <h4 style="font-size: 13px; font-weight: 800; color: #0F172A; margin: 0 0 10px 0; display: flex; align-items: center; gap: 8px;">
                            <i class="fa-solid fa-hospital" style="color: #DC2626;"></i> Nearest Verified Emergency Responders:
                        </h4>
                        <div id="sos-nearby-resources-list" style="display: flex; flex-direction: column; gap: 8px; max-height: 180px; overflow-y: auto;">
                            <!-- Injected dynamically via JS -->
                        </div>
                    </div>
                </div>

                <!-- Instant Direct Helpline Buttons -->
                <div style="border-top: 1px solid #E2E8F0; padding-top: 16px;">
                    <div style="font-size: 12px; font-weight: 700; color: #64748B; margin-bottom: 10px; text-transform: uppercase;">Direct Emergency Call Centers (Tap to dial):</div>
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 10px;">
                        <a href="tel:1144" class="btn" style="background: #F1F5F9; color: #0F172A; border: 1px solid #CBD5E1; font-size: 12px; font-weight: 700; padding: 10px 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>👮 Tourist Police</span>
                            <span style="color: #DC2626; font-weight: 800;">1144</span>
                        </a>
                        <a href="tel:102" class="btn" style="background: #F1F5F9; color: #0F172A; border: 1px solid #CBD5E1; font-size: 12px; font-weight: 700; padding: 10px 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>🚑 Ambulance</span>
                            <span style="color: #DC2626; font-weight: 800;">102</span>
                        </a>
                        <a href="tel:100" class="btn" style="background: #F1F5F9; color: #0F172A; border: 1px solid #CBD5E1; font-size: 12px; font-weight: 700; padding: 10px 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>🚓 Nepal Police</span>
                            <span style="color: #DC2626; font-weight: 800;">100</span>
                        </a>
                        <a href="tel:1114" class="btn" style="background: #F1F5F9; color: #0F172A; border: 1px solid #CBD5E1; font-size: 12px; font-weight: 700; padding: 10px 12px; border-radius: 10px; text-decoration: none; display: flex; align-items: center; justify-content: space-between;">
                            <span>🚁 Armed Police / Rescue</span>
                            <span style="color: #DC2626; font-weight: 800;">1114</span>
                        </a>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Floating SOS Trigger Button -->
    <div id="sos-trigger-fab" onclick="triggerSOSAlert()" style="position: fixed; bottom: 30px; right: 30px; z-index: 9999; background: #DC2626; color: white; padding: 14px 24px; border-radius: 40px; font-weight: 800; box-shadow: 0 10px 25px rgba(220, 38, 38, 0.4); cursor: pointer; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-triangle-exclamation" style="font-size: 20px;"></i>
        <span>1-TAP EMERGENCY SOS</span>
    </div>

    <script src="assets/js/leaflet.js"></script>
    <script src="assets/js/map.js"></script>
    <script src="assets/js/safety.js"></script>
    <script src="assets/js/app.js"></script>
    <script>
        const currentUserId = <?php echo $user['id']; ?>;

        // Slide Sidebar Toggle Functionality (Toggles Icon-Only Collapsed Sidebar)
        const slideBtn = document.getElementById('sidebar-slide-btn');
        const appContainer = document.querySelector('.app-container');
        
        if (slideBtn && appContainer) {
            slideBtn.addEventListener('click', () => {
                appContainer.classList.toggle('sidebar-collapsed');
            });
        }

        // Dynamic Weather Condition Updater
        function updateWeatherCondition() {
            const tempBox = document.getElementById('weather-temp-display');
            const descBox = document.getElementById('weather-desc-display');
            if (!tempBox) return;

            const temperatures = [24, 25, 23, 22, 26, 21];
            const conditions = [
                'Partly Cloudy · Humidity 68%',
                'Clear Sunny Sky · Humidity 58%',
                'Light Mountain Breeze · Humidity 65%',
                'Scattered High Altitude Clouds · Humidity 62%'
            ];

            const randomTemp = temperatures[Math.floor(Math.random() * temperatures.length)];
            const randomCond = conditions[Math.floor(Math.random() * conditions.length)];

            tempBox.innerHTML = `${randomTemp}°C <i class="fa-solid fa-sun-cloud" style="color: #F59E0B; font-size: 24px;"></i>`;
            if (descBox) descBox.innerText = randomCond;

            showNotification(`🌤️ Live Weather Updated: ${randomTemp}°C, ${randomCond}`, 'success');
        }

        function loadTravelerChat() {
            const orgId = document.getElementById('agency-select').value;
            const chatBox = document.getElementById('chat-messages-box');
            if (!orgId || !chatBox) return;

            fetch(`api/chat.php?action=fetch_messages&org_id=${orgId}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        let html = '';
                        if (res.data.messages.length === 0) {
                            html = '<p style="text-align:center;color:#64748B;font-size:13px;margin:auto;">No messages yet. Send a message to start chatting with the agency!</p>';
                        } else {
                            res.data.messages.forEach(m => {
                                const isSentByMe = m.sender_id == currentUserId;
                                const bubbleClass = isSentByMe ? 'sent' : 'received';
                                html += `
                                    <div class="chat-bubble ${bubbleClass}">
                                        <div style="font-size:11px;opacity:0.8;margin-bottom:2px;"><b>${m.sender_name}</b></div>
                                        <div>${m.message}</div>
                                        <div style="font-size:10px;opacity:0.7;margin-top:4px;text-align:right;">${m.created_at.substring(11, 16)}</div>
                                    </div>
                                `;
                            });
                        }
                        chatBox.innerHTML = html;
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                });
        }

        // TRAVEL TEAM MANAGEMENT LOGIC - Renders ALL Teams created/joined with full member rosters
        function loadTravelerTeams() {
            fetch('api/teams.php?action=my_teams')
                .then(res => res.json())
                .then(data => {
                    const container = document.getElementById('active-team-details-container');
                    if (!container) return;

                    if (data.success && data.data.teams && data.data.teams.length > 0) {
                        const teams = data.data.teams;
                        let allTeamsHtml = teams.map(team => {
                            const members = team.members || [];
                            let membersHtml = members.map(m => `
                                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 14px; padding: 14px 16px; display: flex; align-items: center; justify-content: space-between; gap: 16px; margin-bottom: 10px; flex-wrap: wrap;">
                                    <div style="display: flex; align-items: center; gap: 14px;">
                                        <div style="width: 42px; height: 42px; background: #E0F2FE; color: #0284C7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 17px; flex-shrink: 0;">
                                            ${m.full_name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; margin: 0;">${m.full_name}</h4>
                                                ${m.is_leader ? '<span class="badge" style="background: #FEF3C7; color: #D97706; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">👑 Team Leader</span>' : '<span class="badge" style="background: #E0F2FE; color: #0369A1; font-size: 11px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">🧳 Joined Traveler</span>'}
                                            </div>
                                            <div style="font-size: 12px; color: #64748B; margin-top: 4px; display: flex; gap: 14px; flex-wrap: wrap;">
                                                <span><i class="fa-solid fa-phone" style="color: #0284C7;"></i> ${m.phone || 'N/A'}</span>
                                                <span><i class="fa-solid fa-envelope" style="color: #0284C7;"></i> ${m.email || 'N/A'}</span>
                                                ${m.emergency_contact_name ? `<span><i class="fa-solid fa-user-shield" style="color: #DC2626;"></i> Emergency: <b>${m.emergency_contact_name}</b> (${m.emergency_contact_phone || 'N/A'})</span>` : ''}
                                            </div>
                                        </div>
                                    </div>

                                    <div style="text-align: right; flex-shrink: 0;">
                                        <span class="badge" style="font-size: 12px; font-weight: 700; padding: 5px 10px; border-radius: 20px; ${m.online_status.includes('Active') ? 'background: #DCFCE7; color: #15803D;' : 'background: #F1F5F9; color: #64748B;'}">
                                            ${m.online_status}
                                        </span>
                                        <div style="font-size: 11px; color: #94A3B8; margin-top: 4px;">
                                            Joined ${m.joined_at ? m.joined_at.substring(0, 10) : 'Recently'}
                                        </div>
                                    </div>
                                </div>
                            `).join('');

                            return `
                                <div class="enhanced-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px; padding: 26px; box-shadow: 0 4px 20px rgba(0,0,0,0.04); margin-bottom: 24px;">
                                    <!-- Team Header Info -->
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 16px; margin-bottom: 20px; border-bottom: 1px solid #F1F5F9; padding-bottom: 16px;">
                                        <div>
                                            <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px;">
                                                <h3 style="font-size: 22px; font-weight: 800; color: #0F172A; margin: 0;">🏔️ ${team.name}</h3>
                                                <span class="badge risk-safe" style="background: #DCFCE7; color: #15803D; font-size: 11px; padding: 4px 10px; border-radius: 8px;">Active Squad</span>
                                            </div>
                                            <p style="font-size: 13px; color: #64748B; margin: 0;">
                                                Leader: <b>${team.leader_name}</b> · Destination: <b>${team.destination_name || 'Nepal Trekking Route'}</b>
                                            </p>
                                        </div>

                                        <!-- Team Code Box with Copy Button -->
                                        <div style="background: #F0F9FF; border: 2px dashed #0284C7; border-radius: 12px; padding: 10px 18px; display: flex; align-items: center; gap: 14px;">
                                            <div>
                                                <div style="font-size: 10px; font-weight: 800; color: #0284C7; text-transform: uppercase; letter-spacing: 0.5px;">Team Code (Share to Join)</div>
                                                <div style="font-size: 20px; font-weight: 900; color: #0F172A; font-family: monospace; letter-spacing: 2px;">${team.team_code}</div>
                                            </div>
                                            <button onclick="copyTeamCode('${team.team_code}')" class="btn btn-primary" style="padding: 7px 12px; font-size: 12px; border-radius: 8px; font-weight: 700;">
                                                <i class="fa-solid fa-copy"></i> Copy Code
                                            </button>
                                        </div>
                                    </div>

                                    <!-- Team Members Header -->
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px;">
                                        <h4 style="font-size: 15px; font-weight: 800; color: #0F172A; display: flex; align-items: center; gap: 8px; margin: 0;">
                                            <i class="fa-solid fa-users" style="color: #0284C7;"></i> Joined Travelers & Squad Roster (${members.length})
                                        </h4>
                                        <span style="font-size: 12px; color: #64748B;">All team members can see each other's details</span>
                                    </div>

                                    <!-- Team Members Roster List -->
                                    <div style="margin-bottom: 16px;">
                                        ${membersHtml}
                                    </div>

                                    <!-- Team Action Bar (Open Separate Chat Modal Button) -->
                                    <div style="display: flex; justify-content: space-between; margin-top: 14px; border-top: 1px dashed #E2E8F0; padding-top: 14px; align-items: center;">
                                        <button onclick="confirmLeaveTeam(${team.id}, '${team.name.replace(/'/g, "\\'")}')" class="btn btn-outline" style="font-size: 12px; font-weight: 700; padding: 8px 16px; border-radius: 10px; color: #EF4444; border-color: #FCA5A5; cursor: pointer; display: flex; align-items: center; gap: 6px;">
                                            <i class="fa-solid fa-right-from-bracket"></i> Leave Team
                                        </button>
                                        <button onclick="openTeamChatModal(${team.id}, '${team.name.replace(/'/g, "\\'")}')" class="btn btn-primary" style="font-size: 13px; font-weight: 700; padding: 10px 20px; border-radius: 12px; background: #0284C7; color: white; border: none; box-shadow: 0 4px 12px rgba(2, 132, 199, 0.25); cursor: pointer; display: flex; align-items: center; gap: 8px;">
                                            <i class="fa-solid fa-comments"></i> 💬 Open Team Group Chat
                                        </button>
                                    </div>
                                </div>
                            `;
                        }).join('');

                        container.innerHTML = `
                            <div style="margin-bottom: 14px; display: flex; justify-content: space-between; align-items: center;">
                                <h3 style="font-size: 16px; font-weight: 800; color: #0F172A;">📋 Your Joined Travel Teams (${teams.length})</h3>
                            </div>
                            ${allTeamsHtml}
                        `;
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 40px; background: #F8FAFC; border-radius: 16px; border: 2px dashed #CBD5E1;">
                                <i class="fa-solid fa-people-roof" style="font-size: 42px; color: #0284C7; margin-bottom: 12px;"></i>
                                <h3 style="font-size: 18px; font-weight: 800; color: #0F172A;">No Travel Teams Created Yet</h3>
                                <p style="font-size: 13px; color: #64748B; margin-top: 4px; max-width: 420px; margin: 4px auto 16px auto;">You haven't created or joined any travel squads yet. Create a team above or enter a Team Code to connect with fellow travelers!</p>
                            </div>
                        `;
                    }
                });
        }
        window.loadTravelerTeams = loadTravelerTeams;

        window.confirmLeaveTeam = function(teamId, teamName) {
            if (confirm('Are you sure you want to leave the team "' + teamName + '"? You will no longer receive group chats or location updates.')) {
                const formData = new FormData();
                formData.append('action', 'leave_team');
                formData.append('team_id', teamId);

                fetch('api/teams.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            if (typeof window.showToast === 'function') window.showToast(`✅ ${res.message}`, 'success');
                            loadTravelerTeams();
                        } else {
                            if (typeof window.showToast === 'function') window.showToast(`⚠️ ${res.message}`, 'danger');
                        }
                    });
            }
        };

        // POPUP MODAL CHAT HANDLERS
        let activeModalTeamId = null;

        function openTeamChatModal(teamId, teamName) {
            activeModalTeamId = teamId;
            document.getElementById('modal-active-team-id').value = teamId;
            document.getElementById('modal-team-name').innerText = `💬 Team Group Chat (${teamName})`;
            
            const modal = document.getElementById('team-chat-modal');
            if (modal) {
                modal.style.display = 'flex';
                loadModalTeamMessages(teamId);
            }
        }

        function closeTeamChatModal() {
            const modal = document.getElementById('team-chat-modal');
            if (modal) modal.style.display = 'none';
            activeModalTeamId = null;
        }

        function loadModalTeamMessages(teamId) {
            const chatBox = document.getElementById('modal-team-chat-messages');
            if (!chatBox) return;

            fetch(`api/teams.php?action=get_messages&team_id=${teamId}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        let html = '';
                        if (!res.data.messages || res.data.messages.length === 0) {
                            html = '<p style="text-align:center;color:#94A3B8;font-size:13px;margin:auto;">No team messages yet. Say hello to your squad!</p>';
                        } else {
                            res.data.messages.forEach(m => {
                                const isSentByMe = m.sender_id == currentUserId;
                                const isLeader = m.sender_role === 'organization_admin' || m.sender_role === 'super_admin';
                                html += `
                                    <div style="margin-bottom: 12px; text-align: ${isSentByMe ? 'right' : 'left'};">
                                        <div style="display: inline-block; max-width: 80%; background: ${isSentByMe ? '#0284C7' : '#FFFFFF'}; color: ${isSentByMe ? '#FFFFFF' : '#0F172A'}; padding: 10px 16px; border-radius: 16px; ${isSentByMe ? 'border-bottom-right-radius: 4px;' : 'border-bottom-left-radius: 4px;'} box-shadow: 0 2px 8px rgba(0,0,0,0.04); text-align: left; font-size: 13px; border: ${isSentByMe ? 'none' : '1px solid #E2E8F0'};">
                                            <div style="font-size: 10px; font-weight: 700; opacity: 0.85; margin-bottom: 3px; color: ${isSentByMe ? '#E0F2FE' : '#0284C7'};">
                                                ${m.sender_name} ${isLeader ? '👑 (Admin)' : ''}
                                            </div>
                                            <div style="line-height: 1.4;">${m.message}</div>
                                            <div style="font-size: 9px; opacity: 0.7; margin-top: 4px; text-align: right;">${m.created_at ? m.created_at.substring(11, 16) : ''}</div>
                                        </div>
                                    </div>
                                `;
                            });
                        }
                        chatBox.innerHTML = html;
                        chatBox.scrollTop = chatBox.scrollHeight;
                    }
                });
        }

        function sendModalTeamMessage(event) {
            event.preventDefault();
            const teamId = document.getElementById('modal-active-team-id').value;
            const input = document.getElementById('modal-team-chat-input');
            const message = input.value.trim();
            if (!teamId || !message) return;

            const formData = new FormData();
            formData.append('action', 'send_message');
            formData.append('team_id', teamId);
            formData.append('message', message);

            fetch('api/teams.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        input.value = '';
                        loadModalTeamMessages(teamId);
                    } else {
                        showNotification(`⚠️ ${res.message}`, 'danger');
                    }
                });
        }

        // Global Toast Notification Helper
        window.showNotification = function(msg, type = 'info') {
            if (typeof window.showToast === 'function') {
                window.showToast(msg, type);
            } else {
                alert(msg);
            }
        };

        // ══════════════════════════════════════════════════════════════════
        // 🚨 1-TAP EMERGENCY SOS SYSTEM LOGIC & HANDLERS
        // ══════════════════════════════════════════════════════════════════
        let currentSOSLat = 27.7172; // Default Kathmandu coordinates as fallback
        let currentSOSLng = 85.3240;
        let selectedSOSCategory = 'Medical';

        function triggerSOSAlert() {
            const modal = document.getElementById('sos-emergency-modal');
            if (!modal) return;
            
            modal.style.display = 'flex';
            document.getElementById('sos-pre-dispatch-view').style.display = 'flex';
            document.getElementById('sos-post-dispatch-view').style.display = 'none';

            // Reset dispatch button state
            const dispatchBtn = document.getElementById('sos-dispatch-btn');
            if (dispatchBtn) {
                dispatchBtn.disabled = false;
                dispatchBtn.innerHTML = '<i class="fa-solid fa-bullhorn" style="font-size: 18px;"></i> <span>🚨 BROADCAST EMERGENCY SOS NOW</span>';
            }

            refreshSOSLocation();
        }

        function closeSOSModal() {
            const modal = document.getElementById('sos-emergency-modal');
            if (modal) modal.style.display = 'none';
        }

        function refreshSOSLocation() {
            const coordsDisplay = document.getElementById('sos-live-coords-display');
            if (coordsDisplay) coordsDisplay.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Acquiring high-precision GPS satellite fix...';

            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    (pos) => {
                        currentSOSLat = pos.coords.latitude;
                        currentSOSLng = pos.coords.longitude;
                        if (coordsDisplay) {
                            coordsDisplay.innerHTML = `<span style="color:#059669;">📍 ${currentSOSLat.toFixed(5)}° N, ${currentSOSLng.toFixed(5)}° E</span> <span style="font-size:11px;color:#64748B;">(±${Math.round(pos.coords.accuracy || 10)}m accuracy)</span>`;
                        }
                    },
                    (err) => {
                        if (coordsDisplay) {
                            coordsDisplay.innerHTML = `<span style="color:#D97706;">📍 Approx. ${currentSOSLat.toFixed(4)}° N, ${currentSOSLng.toFixed(4)}° E</span> <span style="font-size:11px;color:#94A3B8;">(Device location fallback)</span>`;
                        }
                    },
                    { enableHighAccuracy: true, timeout: 8000, maximumAge: 0 }
                );
            } else {
                if (coordsDisplay) {
                    coordsDisplay.innerHTML = `<span>📍 ${currentSOSLat.toFixed(4)}° N, ${currentSOSLng.toFixed(4)}° E (Default)</span>`;
                }
            }
        }

        function selectSOSCategory(category, btnElement) {
            selectedSOSCategory = category;
            document.querySelectorAll('.sos-cat-btn').forEach(btn => btn.classList.remove('active'));
            if (btnElement) btnElement.classList.add('active');
        }

        function dispatchSOSAlert() {
            const dispatchBtn = document.getElementById('sos-dispatch-btn');
            const noteInput = document.getElementById('sos-emergency-note');
            const note = noteInput ? noteInput.value.trim() : '';

            if (dispatchBtn) {
                dispatchBtn.disabled = true;
                dispatchBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="font-size: 18px;"></i> <span>TRANSMITTING EMERGENCY BROADCAST...</span>';
            }

            // Vibrate mobile device if hardware allows
            if (navigator.vibrate) {
                navigator.vibrate([300, 100, 300, 100, 400]);
            }

            const formData = new FormData();
            formData.append('action', 'trigger');
            formData.append('category', selectedSOSCategory);
            formData.append('latitude', currentSOSLat);
            formData.append('longitude', currentSOSLng);
            formData.append('note', note || `Emergency assistance requested via SAHAYATRI 1-Tap SOS (${selectedSOSCategory})`);

            fetch('api/sos.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    showNotification('🚨 EMERGENCY SOS BROADCASTED! Responders & Squad Alerted.', 'danger');
                    
                    document.getElementById('sos-pre-dispatch-view').style.display = 'none';
                    const postView = document.getElementById('sos-post-dispatch-view');
                    postView.style.display = 'flex';

                    // Populate nearby emergency resources
                    const listContainer = document.getElementById('sos-nearby-resources-list');
                    if (listContainer && res.data && res.data.nearby_resources) {
                        if (res.data.nearby_resources.length === 0) {
                            listContainer.innerHTML = '<p style="font-size:12px;color:#64748B;text-align:center;">No direct facilities within 50km. Dial hotlines below immediately.</p>';
                        } else {
                            let resHtml = '';
                            res.data.nearby_resources.forEach(r => {
                                const dist = r.distance_km !== undefined ? `${r.distance_km} km away` : '';
                                resHtml += `
                                    <div style="background:#FFFFFF;border:1px solid #E2E8F0;border-radius:10px;padding:10px 14px;display:flex;justify-content:space-between;align-items:center;">
                                        <div>
                                            <div style="font-size:13px;font-weight:700;color:#0F172A;">${r.name}</div>
                                            <div style="font-size:11px;color:#64748B;">${r.type} · ${r.city} ${dist ? '· <b>' + dist + '</b>' : ''}</div>
                                        </div>
                                        <a href="tel:${r.phone}" class="btn btn-danger" style="font-size:11px;font-weight:700;padding:6px 12px;border-radius:8px;text-decoration:none;white-space:nowrap;background:#DC2626;color:white;display:flex;align-items:center;gap:6px;">
                                            <i class="fa-solid fa-phone"></i> Call
                                        </a>
                                    </div>
                                `;
                            });
                            listContainer.innerHTML = resHtml;
                        }
                    }

                    // Reset form note
                    if (noteInput) noteInput.value = '';
                } else {
                    showNotification(`⚠️ Failed to trigger SOS: ${res.message}`, 'danger');
                    if (dispatchBtn) {
                        dispatchBtn.disabled = false;
                        dispatchBtn.innerHTML = '<i class="fa-solid fa-bullhorn" style="font-size: 18px;"></i> <span>🚨 RETRY EMERGENCY SOS</span>';
                    }
                }
            })
            .catch(err => {
                showNotification('⚠️ Network error while dispatching SOS. Please dial hotlines directly!', 'danger');
                if (dispatchBtn) {
                    dispatchBtn.disabled = false;
                    dispatchBtn.innerHTML = '<i class="fa-solid fa-bullhorn" style="font-size: 18px;"></i> <span>🚨 RETRY EMERGENCY SOS</span>';
                }
            });
        }

        // ══════════════════════════════════════════════════════════════════
        // 🤖 SAHAYATRI AI TRAVEL ASSISTANT LOGIC
        // ══════════════════════════════════════════════════════════════════
        function askQuickAI(query) {
            const input = document.getElementById('ai-user-input');
            if (input) {
                input.value = query;
                handleAIChatSubmit(new Event('submit'));
            }
        }

        function handleAIChatSubmit(e) {
            if (e && e.preventDefault) e.preventDefault();
            const input = document.getElementById('ai-user-input');
            const chatBox = document.getElementById('ai-chat-box');
            const sendBtn = document.getElementById('ai-send-btn');
            const message = input ? input.value.trim() : '';

            if (!message || !chatBox) return;

            // Render User Bubble
            const userMsgHtml = `
                <div style="display:flex;gap:12px;align-items:flex-start;justify-content:flex-end;">
                    <div style="background:#0284C7;color:white;padding:12px 18px;border-radius:16px;border-top-right-radius:4px;max-width:80%;font-size:13px;line-height:1.5;box-shadow:0 2px 6px rgba(2,132,199,0.2);">
                        ${message}
                    </div>
                </div>
            `;
            chatBox.insertAdjacentHTML('beforeend', userMsgHtml);
            input.value = '';
            chatBox.scrollTop = chatBox.scrollHeight;

            // Render Loading AI Bubble
            const loadingId = 'ai-loading-' + Date.now();
            const loadingHtml = `
                <div id="${loadingId}" style="display:flex;gap:12px;align-items:flex-start;">
                    <div style="width:34px;height:34px;border-radius:50%;background:#0284C7;color:white;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;">
                        🤖
                    </div>
                    <div style="background:white;border:1px solid #E2E8F0;padding:12px 18px;border-radius:16px;border-top-left-radius:4px;max-width:80%;font-size:13px;color:#64748B;line-height:1.5;">
                        <i class="fa-solid fa-spinner fa-spin" style="color:#0284C7;"></i> Thinking...
                    </div>
                </div>
            `;
            chatBox.insertAdjacentHTML('beforeend', loadingHtml);
            chatBox.scrollTop = chatBox.scrollHeight;

            if (sendBtn) sendBtn.disabled = true;

            const formData = new FormData();
            formData.append('action', 'chat');
            formData.append('message', message);

            fetch('api/ai.php', { method: 'POST', body: formData })
                .then(r => r.json())
                .then(res => {
                    const loader = document.getElementById(loadingId);
                    if (loader) loader.remove();
                    if (sendBtn) sendBtn.disabled = false;

                    const replyText = res.success ? res.data.reply : (res.message || 'Sorry, I could not process your request at this moment.');
                    const aiMsgHtml = `
                        <div style="display:flex;gap:12px;align-items:flex-start;">
                            <div style="width:34px;height:34px;border-radius:50%;background:#0284C7;color:white;display:flex;align-items:center;justify-content:center;font-size:15px;flex-shrink:0;">
                                🤖
                            </div>
                            <div style="background:white;border:1px solid #E2E8F0;padding:14px 18px;border-radius:16px;border-top-left-radius:4px;max-width:80%;font-size:13px;color:#1E293B;line-height:1.6;box-shadow:0 2px 6px rgba(0,0,0,0.03);">
                                ${replyText}
                            </div>
                        </div>
                    `;
                    chatBox.insertAdjacentHTML('beforeend', aiMsgHtml);
                    chatBox.scrollTop = chatBox.scrollHeight;
                })
                .catch(err => {
                    const loader = document.getElementById(loadingId);
                    if (loader) loader.remove();
                    if (sendBtn) sendBtn.disabled = false;
                });
        }

        // Close modal when pressing Escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                closeSOSModal();
                closeTeamChatModal();
            }
        });

        document.addEventListener('DOMContentLoaded', () => {
            loadTravelerChat();
            loadTravelerTeams();

            const chatForm = document.getElementById('chat-send-form');
            if (chatForm) {
                chatForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const orgId = document.getElementById('agency-select').value;
                    const input = document.getElementById('chat-input-text');
                    const messageText = input.value.trim();

                    if (!messageText || !orgId) return;

                    const formData = new FormData();
                    formData.append('action', 'send_message');
                    formData.append('org_id', orgId);
                    formData.append('message', messageText);

                    fetch('api/chat.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                input.value = '';
                                loadTravelerChat();
                            }
                        });
                });
            }

            // Create Team Form Handler
            const createTeamForm = document.getElementById('create-team-form');
            if (createTeamForm) {
                createTeamForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'create');

                    fetch('api/teams.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                showNotification(`🎉 ${res.message}`, 'success');
                                this.reset();
                                loadTravelerTeams();
                            } else {
                                showNotification(`⚠️ ${res.message}`, 'danger');
                            }
                        });
                });
            }

            // Join Team Form Handler
            const joinTeamForm = document.getElementById('join-team-form');
            if (joinTeamForm) {
                joinTeamForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'join');

                    fetch('api/teams.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                showNotification(`🎉 ${res.message}`, 'success');
                                this.reset();
                                loadTravelerTeams();
                            } else {
                                showNotification(`⚠️ ${res.message}`, 'danger');
                            }
                        });
                });
            }
        });
    </script>
</body>
</html>

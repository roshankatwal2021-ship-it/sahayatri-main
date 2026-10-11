<?php
// system/includes/navbar.php
// Beautiful, High-Aesthetic Horizontal Navbar with Brand Text "SahaYatri" beside Logo & Location Search

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$currentUser = isset($_SESSION['user_id']) ? [
    'id' => $_SESSION['user_id'],
    'full_name' => $_SESSION['user_name'] ?? 'User',
    'role' => $_SESSION['user_role'] ?? 'user'
] : null;

$activePage = basename($_SERVER['PHP_SELF']);
$searchQuery = htmlspecialchars($_GET['search'] ?? '');
?>

<style>
    /* Scope Navbar Styling */
    .sahayatri-navbar {
        display: flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        padding: 12px 32px !important;
        background: #FFFFFF !important;
        border-bottom: 1px solid #E2E8F0 !important;
        position: sticky !important;
        top: 0 !important;
        z-index: 1000 !important;
        box-shadow: 0 2px 10px rgba(15, 23, 42, 0.05) !important;
        font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
    }

    .brand-container {
        display: flex !important;
        align-items: center !important;
        gap: 12px !important;
        text-decoration: none !important;
    }

    .brand-logo-img {
        height: 68px !important;
        width: auto !important;
        object-fit: contain !important;
        transition: transform 0.2s ease !important;
    }

    .brand-logo-img:hover {
        transform: scale(1.04) !important;
    }

    .brand-text-box {
        display: flex !important;
        flex-direction: column !important;
        justify-content: center !important;
    }

    .brand-title {
        font-size: 22px !important;
        font-weight: 800 !important;
        line-height: 1.1 !important;
        letter-spacing: -0.5px !important;
        color: #0F172A !important;
    }

    .brand-title span {
        color: #0EA5E9 !important;
    }

    .brand-subtext {
        font-size: 11px !important;
        color: #64748B !important;
        font-weight: 600 !important;
        letter-spacing: 0.2px !important;
    }

    .navbar-menu-row {
        display: flex !important;
        align-items: center !important;
        gap: 8px !important;
        list-style: none !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    .navbar-menu-item {
        margin: 0 !important;
        padding: 0 !important;
        list-style: none !important;
    }

    .navbar-menu-link {
        display: inline-flex !important;
        align-items: center !important;
        gap: 8px !important;
        padding: 8px 14px !important;
        border-radius: 20px !important;
        color: #334155 !important;
        text-decoration: none !important;
        font-size: 14px !important;
        font-weight: 600 !important;
        transition: all 0.2s ease !important;
    }

    .navbar-menu-link:hover,
    .navbar-menu-link.active {
        background: #F0F9FF !important;
        color: #0284C7 !important;
    }

    .navbar-search-box {
        position: relative !important;
        display: flex !important;
        align-items: center !important;
        background: #F8FAFC !important;
        border: 1px solid #CBD5E1 !important;
        border-radius: 24px !important;
        padding: 4px 4px 4px 12px !important;
        gap: 8px !important;
        transition: border-color 0.2s ease, box-shadow 0.2s ease !important;
    }

    .navbar-search-box:focus-within {
        border-color: #0284C7 !important;
        box-shadow: 0 0 0 3px #E0F2FE !important;
        background: #FFFFFF !important;
    }

    .navbar-search-input {
        border: none !important;
        background: transparent !important;
        outline: none !important;
        font-size: 13px !important;
        width: 190px !important;
        padding: 6px 8px 6px 4px !important;
        color: #0F172A !important;
        font-family: inherit !important;
    }

    .navbar-search-btn {
        background: #0284C7 !important;
        color: #FFFFFF !important;
        border: none !important;
        border-radius: 18px !important;
        padding: 6px 14px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        cursor: pointer !important;
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        transition: background 0.2s ease !important;
    }

    .navbar-search-btn:hover {
        background: #0369A1 !important;
    }

    .navbar-auth-actions {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
    }
</style>

<header class="sahayatri-navbar">
    <!-- Brand Logo & Text Beside Logo -->
    <a href="index.php" class="brand-container">
        <img src="assets/img/Logoo.png" alt="SahaYatri Logo" class="brand-logo-img">
        <div class="brand-text-box">
            <div class="brand-title" style="margin-left:30px">Saha<span>Yatri</span></div>
            <span class="brand-subtext">Journey · Discover · Memories</span>
        </div>
    </a>

    <!-- Multi-Page Navigation Links (Horizontal Row) -->
    <ul class="navbar-menu-row">
        <li class="navbar-menu-item">
            <a href="index.php"
                class="navbar-menu-link <?php echo ($activePage === 'index.php' || $activePage === '') ? 'active' : ''; ?>">
                <i class="fa-solid fa-house" style="color: #0284C7;"></i> Home
            </a>
        </li>
        <li class="navbar-menu-item">
            <a href="packages.php"
                class="navbar-menu-link <?php echo $activePage === 'packages.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-box-archive" style="color: #0284C7;"></i> Tour Packages
            </a>
        </li>
        <li class="navbar-menu-item">
            <a href="destinations.php"
                class="navbar-menu-link <?php echo $activePage === 'destinations.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-mountain-sun" style="color: #0284C7;"></i> Destinations
            </a>
        </li>
        <li class="navbar-menu-item">
            <a href="accommodations.php"
                class="navbar-menu-link <?php echo $activePage === 'accommodations.php' ? 'active' : ''; ?>">
                <i class="fa-solid fa-hotel" style="color: #0284C7;"></i> Accommodations
            </a>
        </li>
    </ul>

    <!-- Search Bar -->
    <form action="packages.php" method="GET" style="margin: 0;">
        <div class="navbar-search-box">
            <i class="fa-solid fa-magnifying-glass" style="color: #94A3B8; font-size: 13px;"></i>
            <input type="text" name="search" class="navbar-search-input" value="<?php echo $searchQuery; ?>"
                placeholder="Search (e.g. Pokhara)..." required>
            <button type="submit" class="navbar-search-btn">
                Search
            </button>
        </div>
    </form>

    <!-- Auth Actions -->
    <div class="navbar-auth-actions">
        <?php if ($currentUser): ?>
            <?php
            $dashboardLink = 'traveler_dashboard.php';
            if ($currentUser['role'] === 'super_admin')
                $dashboardLink = 'super_admin_dashboard.php';
            if ($currentUser['role'] === 'organization_admin')
                $dashboardLink = 'admin_dashboard.php';
            ?>
            <a href="<?php echo $dashboardLink; ?>" class="btn btn-primary" style="font-size: 13px; padding: 8px 16px;">
                <i class="fa-solid fa-user-gear"></i> Portal
            </a>
            <a href="logout.php" class="btn btn-outline" style="font-size: 13px; padding: 8px 16px;">
                <i class="fa-solid fa-right-from-bracket"></i> Logout
            </a>
        <?php else: ?>
            <a href="login.php" class="btn btn-outline" style="font-size: 13px; padding: 8px 16px;">
                <i class="fa-solid fa-right-to-bracket"></i> Sign In
            </a>
            <a href="register.php" class="btn btn-primary" style="font-size: 13px; padding: 8px 16px;">
                <i class="fa-solid fa-user-plus"></i> Get Started
            </a>
        <?php endif; ?>
    </div>
</header>
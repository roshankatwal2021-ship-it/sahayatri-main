<?php
// system/super_admin_dashboard.php
// SaaS Platform Super Admin Control Center with FontAwesome Icons

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

requireRole(['super_admin']);
$user = getCurrentUser();
$db = getDBConnection();

// Fetch All Organizations
$orgsStmt = $db->query("SELECT o.*, (SELECT COUNT(*) FROM packages WHERE organization_id = o.id) as pkg_count FROM organizations o ORDER BY o.id ASC");
$organizations = $orgsStmt->fetchAll();

// Fetch All Users
$usersStmt = $db->query("SELECT u.*, o.name as organization_name FROM users u LEFT JOIN organizations o ON u.organization_id = o.id ORDER BY u.id DESC LIMIT 20");
$usersList = $usersStmt->fetchAll();

// Fetch Pending Approval Queue
$pendingPackages = $db->query("SELECT p.*, d.name as destination_name, o.name as organization_name
    FROM packages p
    JOIN destinations d ON p.destination_id = d.id
    JOIN organizations o ON p.organization_id = o.id
    WHERE p.approval_status = 'PENDING'
    ORDER BY p.created_at DESC")->fetchAll();

$pendingAccommodations = $db->query("SELECT a.*, d.name as destination_name, o.name as organization_name
    FROM accommodations a
    JOIN destinations d ON a.destination_id = d.id
    LEFT JOIN organizations o ON a.organization_id = o.id
    WHERE a.approval_status = 'PENDING'
    ORDER BY a.created_at DESC")->fetchAll();

$approvalCount = count($pendingPackages) + count($pendingAccommodations);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Desk – SahaYatri Multi-Tenant SaaS</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="index.php" class="brand-logo">
                <img src="assets/img/Logoo.png" alt="SahaYatri Logo" class="app-logo-img">
            </a>

            <ul class="nav-menu">
                <li><a href="#" class="nav-link active" data-tab-target="tab-super-overview"><span class="icon"><i class="fa-solid fa-chart-pie"></i></span><span>Platform Analytics</span></a></li>
                <li><a href="#" class="nav-link" data-tab-target="tab-super-orgs"><span class="icon"><i class="fa-solid fa-building"></i></span><span>Manage Organizations</span></a></li>
                <li><a href="#" class="nav-link" data-tab-target="tab-super-users"><span class="icon"><i class="fa-solid fa-users"></i></span><span>Platform Users</span></a></li>
                <li><a href="#" class="nav-link" data-tab-target="tab-super-emergencies"><span class="icon"><i class="fa-solid fa-triangle-exclamation"></i></span><span>Global SOS Logs</span></a></li>
                <li><a href="#" class="nav-link" data-tab-target="tab-super-approvals" style="position:relative;">
                    <span class="icon"><i class="fa-solid fa-clipboard-check"></i></span>
                    <span>Approval Queue</span>
                    <?php if ($approvalCount > 0): ?>
                        <span style="position:absolute; right:12px; top:50%; transform:translateY(-50%); background:#EF4444; color:white; border-radius:50%; width:20px; height:20px; font-size:11px; font-weight:800; display:flex; align-items:center; justify-content:center;"><?php echo $approvalCount; ?></span>
                    <?php endif; ?>
                </a></li>
                <li style="margin-top: auto;"><a href="logout.php" class="nav-link" style="color: var(--color-danger);"><span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span><span>Sign Out</span></a></li>
            </ul>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <header class="header">
                <div>
                    <span style="color: var(--color-text-secondary); font-size: 13px;">Global Platform Owner Desk</span>
                    <h3 style="font-size: 18px; font-weight: 700;">SahaYatri Central Control</h3>
                </div>

                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="badge risk-safe"><i class="fa-solid fa-circle-check"></i> Platform: ONLINE</span>
                    <a href="logout.php" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </header>

            <main class="content-area">

                <!-- 1. PLATFORM ANALYTICS OVERVIEW -->
                <div id="tab-super-overview" class="tab-content active">
                    <div class="hero-banner">
                        <h1>SahaYatri SaaS Ecosystem Overview</h1>
                        <p>Global multi-tenant analytics across all registered travel agencies, tour operators, accommodations, and travelers.</p>
                    </div>

                    <div class="dashboard-grid">
                        <div class="card">
                            <h4 style="color: var(--color-text-secondary); font-size: 13px;"><i class="fa-solid fa-building"></i> REGISTERED TENANTS</h4>
                            <div id="super-orgs" style="font-size: 32px; font-weight: 800; margin-top: 8px;">--</div>
                        </div>

                        <div class="card">
                            <h4 style="color: var(--color-text-secondary); font-size: 13px;"><i class="fa-solid fa-users"></i> GLOBAL TRAVELERS</h4>
                            <div id="super-users" style="font-size: 32px; font-weight: 800; margin-top: 8px;">--</div>
                        </div>

                        <div class="card">
                            <h4 style="color: var(--color-text-secondary); font-size: 13px;"><i class="fa-solid fa-people-group"></i> ACTIVE TRAVEL TEAMS</h4>
                            <div id="super-teams" style="font-size: 32px; font-weight: 800; margin-top: 8px;">--</div>
                        </div>

                        <div class="card">
                            <h4 style="color: var(--color-text-secondary); font-size: 13px;"><i class="fa-solid fa-sack-dollar"></i> PLATFORM BOOKING REVENUE</h4>
                            <div id="super-rev" style="font-size: 28px; font-weight: 800; color: #10B981; margin-top: 8px;">NPR --</div>
                        </div>
                    </div>
                </div>

                <!-- 2. ORGANIZATIONS MANAGER -->
                <div id="tab-super-orgs" class="tab-content">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px;"><i class="fa-solid fa-building"></i> Travel Agencies & Tourism Organizations (SaaS Tenants)</h2>

                    <div class="card">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--color-border); color: var(--color-text-secondary);">
                                    <th style="padding: 12px;">ID</th>
                                    <th style="padding: 12px;">Organization Name</th>
                                    <th style="padding: 12px;">Contact Email</th>
                                    <th style="padding: 12px;">Phone</th>
                                    <th style="padding: 12px;">Packages</th>
                                    <th style="padding: 12px;">Status</th>
                                    <th style="padding: 12px;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($organizations as $o): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 12px; font-weight: bold;">#<?php echo $o['id']; ?></td>
                                        <td style="padding: 12px; font-weight: bold;"><?php echo htmlspecialchars($o['name']); ?></td>
                                        <td style="padding: 12px;"><?php echo htmlspecialchars($o['email']); ?></td>
                                        <td style="padding: 12px;"><?php echo htmlspecialchars($o['phone']); ?></td>
                                        <td style="padding: 12px; font-weight: bold;"><?php echo $o['pkg_count']; ?> Packages</td>
                                        <td style="padding: 12px;">
                                            <span class="badge <?php echo $o['status'] === 'ACTIVE' ? 'risk-safe' : 'risk-critical'; ?>">
                                                <?php echo $o['status']; ?>
                                            </span>
                                        </td>
                                        <td style="padding: 12px;">
                                            <?php if ($o['status'] === 'ACTIVE'): ?>
                                                <button onclick="toggleOrgStatus(<?php echo $o['id']; ?>, 'SUSPENDED')" class="btn btn-danger" style="font-size: 12px; padding: 4px 10px;"><i class="fa-solid fa-ban"></i> Suspend</button>
                                            <?php else: ?>
                                                <button onclick="toggleOrgStatus(<?php echo $o['id']; ?>, 'ACTIVE')" class="btn btn-primary" style="font-size: 12px; padding: 4px 10px;"><i class="fa-solid fa-check"></i> Activate</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 3. PLATFORM USERS -->
                <div id="tab-super-users" class="tab-content">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px;"><i class="fa-solid fa-users"></i> Platform Registered Users</h2>

                    <div class="card">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--color-border); color: var(--color-text-secondary);">
                                    <th style="padding: 12px;">Name</th>
                                    <th style="padding: 12px;">Email</th>
                                    <th style="padding: 12px;">Role</th>
                                    <th style="padding: 12px;">Organization</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($usersList as $u): ?>
                                    <tr style="border-bottom: 1px solid #eee;">
                                        <td style="padding: 12px; font-weight: bold;"><?php echo htmlspecialchars($u['full_name']); ?></td>
                                        <td style="padding: 12px;"><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td style="padding: 12px;"><span class="badge risk-caution"><?php echo strtoupper($u['role']); ?></span></td>
                                        <td style="padding: 12px; color: #666;"><?php echo htmlspecialchars($u['organization_name'] ?: 'Global Traveler'); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 5. APPROVAL QUEUE -->
                <div id="tab-super-approvals" class="tab-content">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 8px;"><i class="fa-solid fa-clipboard-check" style="color: #0369A1;"></i> Approval Queue</h2>
                    <p style="color: #64748B; font-size: 13px; margin-bottom: 24px;">Review and approve/reject listings submitted by travel agency admins before they become visible to travelers.</p>

                    <!-- Pending Packages -->
                    <h3 style="font-size: 17px; font-weight: 700; margin-bottom: 14px; color: #0F172A;"><i class="fa-solid fa-box-archive" style="color: #0284C7;"></i> Tour Packages (<?php echo count($pendingPackages); ?> pending)</h3>
                    <div class="card" style="margin-bottom: 28px; padding: 0; overflow: hidden;">
                        <?php if (empty($pendingPackages)): ?>
                            <div style="padding: 28px; text-align: center; color: #64748B;">
                                <i class="fa-solid fa-circle-check" style="font-size: 28px; color: #10B981; margin-bottom: 8px;"></i>
                                <p>No packages pending approval.</p>
                            </div>
                        <?php else: ?>
                            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                <thead>
                                    <tr style="background:#F8FAFC; border-bottom:2px solid #E2E8F0; color:#64748B;">
                                        <th style="padding:12px;">ID</th>
                                        <th style="padding:12px;">Package Title</th>
                                        <th style="padding:12px;">Agency</th>
                                        <th style="padding:12px;">Destination</th>
                                        <th style="padding:12px;">Price (NPR)</th>
                                        <th style="padding:12px;">Duration</th>
                                        <th style="padding:12px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingPackages as $pkg): ?>
                                        <tr style="border-bottom:1px solid #F1F5F9;">
                                            <td style="padding:12px; font-weight:700;">#<?php echo $pkg['id']; ?></td>
                                            <td style="padding:12px; font-weight:600;"><?php echo htmlspecialchars($pkg['title']); ?></td>
                                            <td style="padding:12px;"><?php echo htmlspecialchars($pkg['organization_name']); ?></td>
                                            <td style="padding:12px;"><?php echo htmlspecialchars($pkg['destination_name']); ?></td>
                                            <td style="padding:12px; font-weight:700; color:#0369A1;">NPR <?php echo number_format($pkg['price_npr']); ?></td>
                                            <td style="padding:12px;"><?php echo $pkg['duration_days']; ?> Days</td>
                                            <td style="padding:12px;">
                                                <button onclick="reviewItem('package', <?php echo $pkg['id']; ?>, 'APPROVED')" class="btn btn-primary" style="font-size:11px; padding:4px 10px; margin-right:4px;"><i class="fa-solid fa-circle-check"></i> Approve</button>
                                                <button onclick="reviewItem('package', <?php echo $pkg['id']; ?>, 'REJECTED')" class="btn btn-danger" style="font-size:11px; padding:4px 10px;"><i class="fa-solid fa-circle-xmark"></i> Reject</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>

                    <!-- Pending Accommodations -->
                    <h3 style="font-size: 17px; font-weight: 700; margin-bottom: 14px; color: #0F172A;"><i class="fa-solid fa-hotel" style="color: #0284C7;"></i> Accommodations (<?php echo count($pendingAccommodations); ?> pending)</h3>
                    <div class="card" style="padding: 0; overflow: hidden;">
                        <?php if (empty($pendingAccommodations)): ?>
                            <div style="padding: 28px; text-align: center; color: #64748B;">
                                <i class="fa-solid fa-circle-check" style="font-size: 28px; color: #10B981; margin-bottom: 8px;"></i>
                                <p>No accommodations pending approval.</p>
                            </div>
                        <?php else: ?>
                            <table style="width:100%; border-collapse:collapse; font-size:13px;">
                                <thead>
                                    <tr style="background:#F8FAFC; border-bottom:2px solid #E2E8F0; color:#64748B;">
                                        <th style="padding:12px;">ID</th>
                                        <th style="padding:12px;">Name</th>
                                        <th style="padding:12px;">Agency</th>
                                        <th style="padding:12px;">Destination</th>
                                        <th style="padding:12px;">Type</th>
                                        <th style="padding:12px;">Price/Night</th>
                                        <th style="padding:12px;">Trust</th>
                                        <th style="padding:12px;">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($pendingAccommodations as $acc): ?>
                                        <tr style="border-bottom:1px solid #F1F5F9;">
                                            <td style="padding:12px; font-weight:700;">#<?php echo $acc['id']; ?></td>
                                            <td style="padding:12px; font-weight:600;"><?php echo htmlspecialchars($acc['name']); ?></td>
                                            <td style="padding:12px;"><?php echo htmlspecialchars($acc['organization_name'] ?? '—'); ?></td>
                                            <td style="padding:12px;"><?php echo htmlspecialchars($acc['destination_name']); ?></td>
                                            <td style="padding:12px;"><?php echo htmlspecialchars($acc['type']); ?></td>
                                            <td style="padding:12px; font-weight:700; color:#0369A1;">NPR <?php echo number_format($acc['price_per_night']); ?></td>
                                            <td style="padding:12px;"><?php echo $acc['trust_score']; ?>/100</td>
                                            <td style="padding:12px;">
                                                <button onclick="reviewItem('accommodation', <?php echo $acc['id']; ?>, 'APPROVED')" class="btn btn-primary" style="font-size:11px; padding:4px 10px; margin-right:4px;"><i class="fa-solid fa-circle-check"></i> Approve</button>
                                                <button onclick="reviewItem('accommodation', <?php echo $acc['id']; ?>, 'REJECTED')" class="btn btn-danger" style="font-size:11px; padding:4px 10px;"><i class="fa-solid fa-circle-xmark"></i> Reject</button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            fetch('api/admin.php?action=stats')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('super-orgs').innerText = res.data.organizations;
                        document.getElementById('super-users').innerText = res.data.users;
                        document.getElementById('super-teams').innerText = res.data.active_teams;
                        document.getElementById('super-rev').innerText = 'NPR ' + res.data.total_revenue;
                    }
                });

            window.toggleOrgStatus = function(orgId, status) {
                const formData = new FormData();
                formData.append('org_id', orgId);
                formData.append('status', status);

                fetch('api/admin.php?action=toggle_org_status', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast('Organization status updated', 'success');
                            setTimeout(() => location.reload(), 1000);
                        }
                    });
            };

            window.reviewItem = function(itemType, itemId, decision) {
                const label = decision === 'APPROVED' ? 'approve' : 'reject';
                if (!confirm(`Are you sure you want to ${label} this ${itemType}?`)) return;
                const formData = new FormData();
                formData.append('item_type', itemType);
                formData.append('item_id', itemId);
                formData.append('decision', decision);
                fetch('api/admin.php?action=review_listing', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(data => {
                        if (data.success) {
                            showToast(`✅ ${itemType.charAt(0).toUpperCase() + itemType.slice(1)} ${decision.toLowerCase()} successfully.`, 'success');
                            setTimeout(() => location.reload(), 1000);
                        } else {
                            showToast('⚠️ ' + data.message, 'danger');
                        }
                    });
            };
        });
    </script>
</body>
</html>

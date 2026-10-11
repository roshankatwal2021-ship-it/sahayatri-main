<?php
// system/admin_dashboard.php
// Travel Admin & Organization Tenant Control Center with Traveler Chat Desk

require_once __DIR__ . '/config/auth.php';
require_once __DIR__ . '/config/helpers.php';
require_once __DIR__ . '/config/database.php';

requireRole(['organization_admin', 'super_admin']);
$user = getCurrentUser();
$db = getDBConnection();
$orgId = getTenantId();

// Fetch Organization Info
$orgStmt = $db->prepare("SELECT * FROM organizations WHERE id = ?");
$orgStmt->execute([$orgId]);
$org = $orgStmt->fetch();

$allowedAdminTabs = ['tab-admin-overview', 'tab-admin-teams', 'tab-admin-chat', 'tab-admin-zones', 'tab-admin-sos', 'tab-admin-broadcast', 'tab-admin-packages', 'tab-admin-hotels'];
$initialTab = isset($_GET['tab']) && in_array($_GET['tab'], $allowedAdminTabs) ? $_GET['tab'] : 'tab-admin-overview';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Organization Admin – SahaYatri Safety Platform</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/leaflet.css">
    <style>
        .chat-admin-grid { display: grid; grid-template-columns: 280px 1fr; gap: 20px; height: 500px; background: white; border-radius: 16px; border: 1px solid var(--color-border); overflow: hidden; }
        .conversation-list { border-right: 1px solid var(--color-border); background: #F8FAFC; overflow-y: auto; }
        .conv-item { padding: 14px; border-bottom: 1px solid var(--color-border); cursor: pointer; transition: background 0.2s ease; }
        .conv-item:hover, .conv-item.active { background: #E0F2FE; }
        .chat-messages-area { display: flex; flex-direction: column; height: 100%; }
        .chat-bubble { max-width: 75%; padding: 12px 16px; border-radius: 16px; font-size: 14px; line-height: 1.5; margin-bottom: 8px; }
        .chat-bubble.sent { align-self: flex-end; background: var(--color-primary-dark); color: white; border-bottom-right-radius: 4px; }
        .chat-bubble.received { align-self: flex-start; background: #F1F5F9; color: var(--color-text-primary); border-bottom-left-radius: 4px; }
    </style>
</head>
<body>

    <div class="app-container">
        <!-- Sidebar -->
        <aside class="sidebar">
            <a href="index.php" class="brand-logo">
                <img src="assets/img/Logoo.png" alt="SahaYatri Logo" class="app-logo-img">
            </a>

            <ul class="nav-menu">
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-admin-overview' ? 'active' : ''; ?>" data-tab-target="tab-admin-overview"><span class="icon"><i class="fa-solid fa-chart-line"></i></span><span>Overview</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-admin-teams' ? 'active' : ''; ?>" data-tab-target="tab-admin-teams"><span class="icon"><i class="fa-solid fa-users"></i></span><span>Travel Teams</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-admin-chat' ? 'active' : ''; ?>" data-tab-target="tab-admin-chat"><span class="icon"><i class="fa-solid fa-comments"></i></span><span>Traveler Chat Desk</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-admin-zones' ? 'active' : ''; ?>" data-tab-target="tab-admin-zones"><span class="icon"><i class="fa-solid fa-triangle-exclamation"></i></span><span>Safety Zones</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-admin-sos' ? 'active' : ''; ?>" data-tab-target="tab-admin-sos"><span class="icon"><i class="fa-solid fa-truck-medical"></i></span><span>SOS Incident Desk</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-admin-broadcast' ? 'active' : ''; ?>" data-tab-target="tab-admin-broadcast"><span class="icon"><i class="fa-solid fa-bullhorn"></i></span><span>Publish Broadcast</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-admin-packages' ? 'active' : ''; ?>" data-tab-target="tab-admin-packages"><span class="icon"><i class="fa-solid fa-box-archive"></i></span><span>Tour Packages</span></a></li>
                <li><a href="#" class="nav-link <?php echo $initialTab === 'tab-admin-hotels' ? 'active' : ''; ?>" data-tab-target="tab-admin-hotels"><span class="icon"><i class="fa-solid fa-hotel"></i></span><span>Accommodations</span></a></li>
                <li style="margin-top: auto;"><a href="logout.php" class="nav-link" style="color: var(--color-danger);"><span class="icon"><i class="fa-solid fa-right-from-bracket"></i></span><span>Sign Out</span></a></li>
            </ul>
        </aside>

        <!-- Main Wrapper -->
        <div class="main-wrapper">
            <header class="header">
                <div style="display: flex; align-items: center; gap: 14px;">
                    <button class="sidebar-slide-toggle-btn" id="admin-sidebar-slide-btn" title="Toggle Sidebar Navigation">
                        <i class="fa-solid fa-bars"></i>
                    </button>
                    <div>
                        <span style="color: var(--color-text-secondary); font-size: 13px;">Organization Admin Desk</span>
                        <h3 style="font-size: 18px; font-weight: 700;"><?php echo htmlspecialchars($org['name'] ?? 'Tourism Organization'); ?></h3>
                    </div>
                </div>

                <div style="display: flex; gap: 12px; align-items: center;">
                    <span class="badge risk-safe">Tenant ID: #<?php echo $orgId; ?></span>
                    <a href="logout.php" class="btn btn-outline" style="font-size: 12px; padding: 6px 12px;"><i class="fa-solid fa-right-from-bracket"></i> Logout</a>
                </div>
            </header>

            <main class="content-area">

                <!-- 1. OVERVIEW TAB -->
                <div id="tab-admin-overview" class="tab-content <?php echo $initialTab === 'tab-admin-overview' ? 'active' : ''; ?>">
                    <div class="hero-banner">
                        <h1>Organization Operations Dashboard</h1>
                        <p>Manage tour packages, define dangerous safety zone geofences, publish emergency notices, and monitor traveler SOS incidents.</p>
                        <div style="display: flex; gap: 12px;">
                            <button onclick="openModal('create-zone-modal')" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Define Safety Zone</button>
                            <button onclick="openModal('publish-broadcast-modal')" class="btn btn-secondary"><i class="fa-solid fa-bullhorn"></i> Publish Safety Alert</button>
                        </div>
                    </div>

                    <div class="dashboard-grid">
                        <div class="card">
                            <h4 style="color: var(--color-text-secondary); font-size: 13px;"><i class="fa-solid fa-shield-halved"></i> ACTIVE SAFETY ZONES</h4>
                            <div id="stat-zones" style="font-size: 32px; font-weight: 800; margin-top: 8px;">--</div>
                        </div>

                        <div class="card">
                            <h4 style="color: var(--color-text-secondary); font-size: 13px;"><i class="fa-solid fa-box-archive"></i> TOUR PACKAGES LISTED</h4>
                            <div id="stat-packages" style="font-size: 32px; font-weight: 800; margin-top: 8px;">--</div>
                        </div>

                        <div class="card">
                            <h4 style="color: var(--color-text-secondary); font-size: 13px;"><i class="fa-solid fa-hotel"></i> HOTELS & ACCOMMODATIONS</h4>
                            <div id="stat-hotels" style="font-size: 32px; font-weight: 800; margin-top: 8px;">--</div>
                        </div>

                        <div class="card" style="border-left: 4px solid var(--color-danger);">
                            <h4 style="color: var(--color-danger); font-size: 13px;"><i class="fa-solid fa-triangle-exclamation"></i> ACTIVE SOS EMERGENCIES</h4>
                            <div id="stat-sos" style="font-size: 32px; font-weight: 800; color: var(--color-danger); margin-top: 8px;">--</div>
                        </div>
                    </div>
                </div>

                <!-- 2. TRAVEL TEAMS MANAGER TAB FOR ADMIN -->
                <div id="tab-admin-teams" class="tab-content <?php echo $initialTab === 'tab-admin-teams' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <h2 style="font-size: 22px; font-weight: 800; color: #0F172A;"><i class="fa-solid fa-users" style="color: #0284C7;"></i> Travel Teams & Trekking Squad Management Desk</h2>
                        <p style="color: #64748B; font-size: 13px; margin-top: 4px;">Create travel teams for tours, generate unique Team Codes, view joined travelers, and communicate via team group chat.</p>
                    </div>

                    <!-- Create Team Card -->
                    <div class="enhanced-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 18px; padding: 24px; margin-bottom: 24px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
                        <h4 style="font-size: 16px; font-weight: 800; color: #0F172A; margin-bottom: 14px;"><i class="fa-solid fa-plus-circle" style="color: #0284C7;"></i> Create New Travel Team / Trekking Group</h4>
                        <form id="admin-create-team-form" style="display: grid; grid-template-columns: 2fr 2fr 1fr; gap: 14px; align-items: flex-end;">
                            <div>
                                <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Team / Squad Name</label>
                                <input type="text" name="team_name" class="form-input" placeholder="e.g. Annapurna Base Camp Group A" required style="font-size: 13px;">
                            </div>
                            <div>
                                <label style="font-size: 12px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">Destination / Trek Route</label>
                                <input type="text" name="destination_name" class="form-input" placeholder="e.g. Pokhara to ABC Route" required style="font-size: 13px;">
                            </div>
                            <button type="submit" class="btn btn-primary" style="font-size: 13px; font-weight: 700; padding: 12px;"><i class="fa-solid fa-square-plus"></i> Create Team</button>
                        </form>
                    </div>

                    <!-- Roster of Created Teams -->
                    <div id="admin-teams-list-container">
                        <div style="text-align: center; padding: 40px; background: white; border-radius: 16px; border: 1px solid #E2E8F0;">
                            <i class="fa-solid fa-spinner fa-spin" style="font-size: 32px; color: #0284C7; margin-bottom: 10px;"></i>
                            <p style="font-size: 14px; font-weight: 600; color: #64748B;">Loading created travel teams...</p>
                        </div>
                    </div>
                </div>

                <!-- 2. TRAVELER CHAT DESK TAB -->
                <div id="tab-admin-chat" class="tab-content <?php echo $initialTab === 'tab-admin-chat' ? 'active' : ''; ?>">
                    <div style="margin-bottom: 20px;">
                        <h2 style="font-size: 22px; font-weight: 800; color: var(--color-dark-accent);"><i class="fa-solid fa-comments" style="color: var(--color-primary-dark);"></i> Traveler Inquiries & Direct Chat Desk</h2>
                        <p style="font-size: 13px; color: var(--color-text-secondary);">Respond to direct traveler messages and trip package inquiries</p>
                    </div>

                    <div class="chat-admin-grid">
                        <div class="conversation-list" id="conv-list-container">
                            <p style="padding: 16px; font-size: 13px; color: #64748B;">Loading conversations...</p>
                        </div>

                        <div class="chat-messages-area">
                            <div style="padding: 14px 20px; background: #F8FAFC; border-bottom: 1px solid var(--color-border); font-weight: bold;" id="admin-chat-header">
                                Select a traveler to view message history
                            </div>
                            <div id="admin-chat-messages-box" style="flex: 1; padding: 20px; overflow-y: auto; display: flex; flex-direction: column;">
                                <p style="margin: auto; color: #64748B; font-size: 13px;">No conversation selected.</p>
                            </div>
                            <form id="admin-chat-send-form" style="padding: 12px; border-top: 1px solid var(--color-border); display: flex; gap: 10px;">
                                <input type="hidden" id="selected-traveler-id" value="">
                                <input type="text" id="admin-chat-input" class="form-input" placeholder="Type your reply to traveler..." required>
                                <button type="submit" class="btn btn-primary" style="padding: 0 20px;"><i class="fa-solid fa-paper-plane"></i> Reply</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- 3. SAFETY ZONES MANAGER TAB -->
                <div id="tab-admin-zones" class="tab-content <?php echo $initialTab === 'tab-admin-zones' ? 'active' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="font-size: 22px; font-weight: 800;">Safety Zones Geofence Manager</h2>
                        <button onclick="openModal('create-zone-modal')" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Define New Danger Zone</button>
                    </div>

                    <div class="card">
                        <table style="width: 100%; border-collapse: collapse; text-align: left; font-size: 14px;">
                            <thead>
                                <tr style="border-bottom: 2px solid var(--color-border); color: var(--color-text-secondary);">
                                    <th style="padding: 12px;">Zone Title</th>
                                    <th style="padding: 12px;">Risk Level</th>
                                    <th style="padding: 12px;">Risk Type</th>
                                    <th style="padding: 12px;">Radius</th>
                                    <th style="padding: 12px;">Coordinates</th>
                                </tr>
                            </thead>
                            <tbody id="admin-zones-table">
                                <tr><td colspan="5" style="padding: 16px; text-align: center;">Loading safety zones...</td></tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- 4. SOS INCIDENT DESK TAB -->
                <div id="tab-admin-sos" class="tab-content <?php echo $initialTab === 'tab-admin-sos' ? 'active' : ''; ?>">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px; color: var(--color-danger);"><i class="fa-solid fa-triangle-exclamation"></i> Live SOS Emergency Incident Desk</h2>
                    <div id="admin-sos-list" class="dashboard-grid">
                        <p>Loading active SOS events...</p>
                    </div>
                </div>

                <!-- 5. BROADCAST NOTICES TAB -->
                <div id="tab-admin-broadcast" class="tab-content <?php echo $initialTab === 'tab-admin-broadcast' ? 'active' : ''; ?>">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                        <h2 style="font-size: 22px; font-weight: 800;"><i class="fa-solid fa-bullhorn"></i> Organization Safety Broadcast Notices</h2>
                        <button onclick="openModal('publish-broadcast-modal')" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Publish Broadcast Notice</button>
                    </div>
                    <div id="broadcasts-list" class="dashboard-grid">
                        <p>Loading broadcast alerts...</p>
                    </div>
                </div>

                <!-- 6. PACKAGES TAB -->
                <div id="tab-admin-packages" class="tab-content <?php echo $initialTab === 'tab-admin-packages' ? 'active' : ''; ?>">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px;"><i class="fa-solid fa-box-archive"></i> Organization Tour Packages</h2>
                    <div id="admin-packages-list" class="dashboard-grid">
                        <p>Loading tour packages...</p>
                    </div>
                </div>

                <!-- 7. HOTELS TAB -->
                <div id="tab-admin-hotels" class="tab-content <?php echo $initialTab === 'tab-admin-hotels' ? 'active' : ''; ?>">
                    <h2 style="font-size: 22px; font-weight: 800; margin-bottom: 20px;"><i class="fa-solid fa-hotel"></i> Listed Hotels & Accommodations</h2>
                    <div id="admin-hotels-list" class="dashboard-grid">
                        <p>Loading hotel inventory...</p>
                    </div>
                </div>

            </main>
        </div>
    </div>

    <!-- CREATE SAFETY ZONE MODAL -->
    <div id="create-zone-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Define Safety / Dangerous Zone Geofence</h3>
                <button onclick="closeModal('create-zone-modal')" class="btn btn-outline" style="padding: 4px 10px;">✕</button>
            </div>
            <form id="create-zone-form">
                <div class="form-group">
                    <label class="form-label">Zone Title</label>
                    <input type="text" name="title" class="form-input" placeholder="e.g. Seti River Gorge High Risk Landslide Zone" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Risk Level (PRD Scale)</label>
                    <select name="risk_level" class="form-select">
                        <option value="SAFE">🟢 Safe</option>
                        <option value="CAUTION">🟡 Caution</option>
                        <option value="HIGH">🟠 High Risk</option>
                        <option value="CRITICAL" selected>🔴 Critical</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Risk Type</label>
                    <select name="risk_type" class="form-select">
                        <option value="Landslide">Landslide</option>
                        <option value="Flood">Flood</option>
                        <option value="Avalanche">Avalanche</option>
                        <option value="Road accident">Road accident</option>
                        <option value="Wildlife">Wildlife</option>
                        <option value="General danger">General danger</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Latitude</label>
                    <input type="text" name="latitude" class="form-input" value="28.2312" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Longitude</label>
                    <input type="text" name="longitude" class="form-input" value="83.9834" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Geofence Radius (Meters)</label>
                    <input type="number" name="radius_meters" class="form-input" value="500" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Description & Traveler Guidance</label>
                    <textarea name="description" class="form-textarea" rows="2" placeholder="Detail safety instructions for travelers..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-paper-plane"></i> Publish Safety Zone</button>
            </form>
        </div>
    </div>

    <!-- PUBLISH BROADCAST MODAL -->
    <div id="publish-broadcast-modal" class="modal-overlay">
        <div class="modal-card">
            <div class="modal-header">
                <h3>Publish Region Broadcast Alert</h3>
                <button onclick="closeModal('publish-broadcast-modal')" class="btn btn-outline" style="padding: 4px 10px;">✕</button>
            </div>
            <form id="publish-broadcast-form">
                <div class="form-group">
                    <label class="form-label">Broadcast Title</label>
                    <input type="text" name="title" class="form-input" placeholder="e.g. MONSOON HIGHWAY CAUTION ALERT" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Message for Regional Travelers</label>
                    <textarea name="message" class="form-textarea" rows="3" placeholder="Provide warning details..." required></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Target Region / Area</label>
                    <input type="text" name="target_area" class="form-input" value="Pokhara & Surrounding Regions">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%;"><i class="fa-solid fa-bullhorn"></i> Broadcast Alert to All Travelers</button>
            </form>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        const adminUserId = <?php echo $user['id']; ?>;
        const currentOrgId = <?php echo $orgId; ?>;

        function loadConversations() {
            fetch('api/chat.php?action=list_conversations')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        let html = '';
                        if (res.data.conversations.length === 0) {
                            html = '<p style="padding:16px;color:#64748B;font-size:13px;">No traveler messages yet.</p>';
                        } else {
                            res.data.conversations.forEach((c, idx) => {
                                html += `
                                    <div class="conv-item ${idx === 0 ? 'active' : ''}" onclick="selectConversation(${c.user_id}, '${c.full_name}')">
                                        <div style="font-weight:bold;font-size:14px;color:#0F172A;"><i class="fa-solid fa-user"></i> ${c.full_name}</div>
                                        <div style="font-size:12px;color:#64748B;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">${c.last_message || ''}</div>
                                    </div>
                                `;
                            });
                            // Auto-select first conversation
                            if (res.data.conversations[0]) {
                                selectConversation(res.data.conversations[0].user_id, res.data.conversations[0].full_name);
                            }
                        }
                        document.getElementById('conv-list-container').innerHTML = html;
                    }
                });
        }

        function selectConversation(userId, userName) {
            document.getElementById('selected-traveler-id').value = userId;
            document.getElementById('admin-chat-header').innerText = 'Conversation with ' + userName;

            fetch(`api/chat.php?action=fetch_messages&user_id=${userId}&org_id=${currentOrgId}`)
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        let html = '';
                        res.data.messages.forEach(m => {
                            const isSentByAdmin = m.sender_id == adminUserId;
                            const bubbleClass = isSentByAdmin ? 'sent' : 'received';
                            html += `
                                <div class="chat-bubble ${bubbleClass}">
                                    <div style="font-size:11px;opacity:0.8;margin-bottom:4px;"><b>${m.sender_name}</b></div>
                                    <div>${m.message}</div>
                                    <div style="font-size:10px;opacity:0.7;margin-top:4px;text-align:right;">${m.created_at.substring(11, 16)}</div>
                                </div>
                            `;
                        });
                        const msgBox = document.getElementById('admin-chat-messages-box');
                        msgBox.innerHTML = html;
                        msgBox.scrollTop = msgBox.scrollHeight;
                    }
                });
        }

        document.addEventListener('DOMContentLoaded', () => {
            // Slide Sidebar Toggle Functionality (Toggles Icon-Only Collapsed Sidebar)
            const adminSlideBtn = document.getElementById('admin-sidebar-slide-btn');
            const appContainer = document.querySelector('.app-container');
            
            if (adminSlideBtn && appContainer) {
                adminSlideBtn.addEventListener('click', () => {
                    appContainer.classList.toggle('sidebar-collapsed');
                });
            }

            loadConversations();

            const adminChatForm = document.getElementById('admin-chat-send-form');
            if (adminChatForm) {
                adminChatForm.addEventListener('submit', (e) => {
                    e.preventDefault();
                    const receiverId = document.getElementById('selected-traveler-id').value;
                    const input = document.getElementById('admin-chat-input');
                    const messageText = input.value.trim();

                    if (!messageText || !receiverId) return;

                    const formData = new FormData();
                    formData.append('action', 'send_message');
                    formData.append('receiver_id', receiverId);
                    formData.append('org_id', currentOrgId);
                    formData.append('message', messageText);

                    fetch('api/chat.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                input.value = '';
                                selectConversation(receiverId, document.getElementById('admin-chat-header').innerText.replace('Conversation with ', ''));
                            }
                        });
                });
            }

            fetch('api/admin.php?action=stats')
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        document.getElementById('stat-zones').innerText = res.data.safety_zones;
                        document.getElementById('stat-packages').innerText = res.data.packages;
                        document.getElementById('stat-hotels').innerText = res.data.accommodations;
                        document.getElementById('stat-sos').innerText = res.data.active_sos;
                    }
                });

            fetch('api/safety.php?action=zones')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data.zones) {
                        let html = '';
                        res.data.zones.forEach(z => {
                            html += `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding:12px;font-weight:bold;">${z.title}</td>
                                    <td style="padding:12px;">${z.risk_badge_html}</td>
                                    <td style="padding:12px;">${z.risk_type}</td>
                                    <td style="padding:12px;">${z.radius_meters}m</td>
                                    <td style="padding:12px;font-size:12px;color:#666;">${z.latitude}, ${z.longitude}</td>
                                </tr>
                            `;
                        });
                        document.getElementById('admin-zones-table').innerHTML = html;
                    }
                });

            fetch('api/sos.php?action=active_emergencies')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data.emergencies) {
                        let html = '';
                        if (res.data.emergencies.length === 0) {
                            html = '<div class="card"><p style="color:#10B981;font-weight:bold;"><i class="fa-solid fa-circle-check"></i> No active emergency SOS alerts reported right now.</p></div>';
                        } else {
                            res.data.emergencies.forEach(e => {
                                html += `
                                    <div class="card" style="border-left:5px solid #DC2626;">
                                        <h4 style="color:#DC2626;margin-bottom:4px;"><i class="fa-solid fa-triangle-exclamation"></i> SOS Category: ${e.category}</h4>
                                        <p style="font-size:14px;font-weight:bold;margin-bottom:6px;">Traveler: ${e.user_name} (Phone: ${e.user_phone})</p>
                                        <p style="font-size:12px;color:#666;margin-bottom:12px;">Emergency Contact: ${e.emergency_contact_name || 'N/A'} (${e.emergency_contact_phone || 'N/A'})</p>
                                        <button class="btn btn-primary" style="font-size:12px;" onclick="resolveSOS(${e.id})"><i class="fa-solid fa-check"></i> Mark as RESOLVED</button>
                                    </div>
                                `;
                            });
                        }
                        document.getElementById('admin-sos-list').innerHTML = html;
                    }
                });

            // Load all admin tabs on start
            loadAdminTeams();
            loadAdminHotels();
            loadAdminPackages();
            loadAdminBroadcasts();
            loadAdminZones();
            loadAdminSOS();

            // Admin Create Team Form Listener
            const createTeamForm = document.getElementById('admin-create-team-form');
            if (createTeamForm) {
                createTeamForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'create');

                    fetch('api/teams.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                this.reset();
                                loadAdminTeams();
                                if (typeof window.showToast === 'function') {
                                    window.showToast(`🎉 ${res.message}`, 'success');
                                } else {
                                    alert(`🎉 ${res.message}`);
                                }
                            } else {
                                if (typeof window.showToast === 'function') {
                                    window.showToast(`⚠️ ${res.message}`, 'danger');
                                } else {
                                    alert(`⚠️ ${res.message}`);
                                }
                            }
                        });
                });
            }

            // Create Safety Zone Form Listener
            const createZoneForm = document.getElementById('create-zone-form');
            if (createZoneForm) {
                createZoneForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'create_zone');

                    fetch('api/safety.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                this.reset();
                                closeModal('create-zone-modal');
                                loadAdminZones();
                                alert(`🛡️ ${res.message}`);
                            } else {
                                alert(`⚠️ ${res.message}`);
                            }
                        });
                });
            }

            // Publish Broadcast Form Listener
            const publishBroadcastForm = document.getElementById('publish-broadcast-form');
            if (publishBroadcastForm) {
                publishBroadcastForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    const formData = new FormData(this);
                    formData.append('action', 'create_broadcast');

                    fetch('api/safety.php', { method: 'POST', body: formData })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                this.reset();
                                closeModal('publish-broadcast-modal');
                                loadAdminBroadcasts();
                                alert(`📢 ${res.message}`);
                            } else {
                                alert(`⚠️ ${res.message}`);
                            }
                        });
                });
            }
        });

        // ══════════════════════════════════════════════════════════════════
        // 🏨 ADMIN HOTELS & ACCOMMODATIONS LOADER
        // ══════════════════════════════════════════════════════════════════
        function loadAdminHotels() {
            const listContainer = document.getElementById('admin-hotels-list');
            if (!listContainer) return;

            fetch('api/accommodations.php?action=list')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.accommodations) {
                        if (res.data.accommodations.length === 0) {
                            listContainer.innerHTML = '<div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;"><p style="color:#64748B;">No accommodations listed yet.</p></div>';
                            return;
                        }
                        let html = '';
                        res.data.accommodations.forEach(h => {
                            html += `
                                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                                    <img src="${h.image_url || 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800'}" style="width:100%;height:160px;object-fit:cover;" alt="${h.name}">
                                    <div style="padding: 16px; flex-grow: 1; display: flex; flex-direction: column;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span class="badge risk-safe"><i class="fa-solid fa-shield-halved"></i> Trust Score: ${h.trust_score}/100</span>
                                            <span style="font-size: 12px; font-weight: bold; color: var(--color-text-secondary);"><i class="fa-solid fa-star" style="color: #F59E0B;"></i> ${h.rating}</span>
                                        </div>
                                        <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 4px; color: #0F172A;">${h.name}</h4>
                                        <p style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 12px;"><i class="fa-solid fa-location-dot" style="color: var(--color-primary-dark);"></i> ${h.destination_name} · ${h.type}</p>
                                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--color-border);">
                                            <strong style="font-size: 15px; color: var(--color-primary-dark);">NPR ${Number(h.price_per_night).toLocaleString()} <small style="font-weight: normal; font-size: 11px; color: #64748B;">/ night</small></strong>
                                            <span class="badge" style="background: #DCFCE7; color: #15803D; font-size: 11px; font-weight: 700;">Verified</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        listContainer.innerHTML = html;
                    } else {
                        listContainer.innerHTML = '<p style="color:#DC2626;">Failed to load accommodations.</p>';
                    }
                })
                .catch(err => {
                    listContainer.innerHTML = '<p style="color:#DC2626;">Network error loading accommodations.</p>';
                });
        }
        window.loadAdminHotels = loadAdminHotels;

        // ══════════════════════════════════════════════════════════════════
        // 🎒 ADMIN TOUR PACKAGES LOADER
        // ══════════════════════════════════════════════════════════════════
        function loadAdminPackages() {
            const listContainer = document.getElementById('admin-packages-list');
            if (!listContainer) return;

            fetch('api/destinations.php?action=packages')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.packages) {
                        if (res.data.packages.length === 0) {
                            listContainer.innerHTML = '<div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;"><p style="color:#64748B;">No tour packages available.</p></div>';
                            return;
                        }
                        let html = '';
                        res.data.packages.forEach(p => {
                            html += `
                                <div class="card" style="padding: 0; overflow: hidden; display: flex; flex-direction: column;">
                                    <img src="${p.image_url || 'https://images.unsplash.com/photo-1544735716-392fe2489ffa?w=800'}" style="width:100%;height:160px;object-fit:cover;" alt="${p.title}">
                                    <div style="padding: 16px; flex-grow: 1; display: flex; flex-direction: column;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px;">
                                            <span class="badge" style="background:#E0F2FE;color:#0369A1;font-weight:700;">${p.duration_days} Days</span>
                                            <span class="badge" style="background:#FEF3C7;color:#D97706;font-weight:700;">${p.difficulty_level}</span>
                                        </div>
                                        <h4 style="font-size: 16px; font-weight: 700; margin-bottom: 4px; color: #0F172A;">${p.title}</h4>
                                        <p style="font-size: 12px; color: var(--color-text-secondary); margin-bottom: 12px;"><i class="fa-solid fa-location-dot" style="color: var(--color-primary-dark);"></i> ${p.destination_name} (${p.region})</p>
                                        <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; padding-top: 12px; border-top: 1px solid var(--color-border);">
                                            <strong style="font-size: 15px; color: var(--color-primary-dark);">NPR ${Number(p.price).toLocaleString()} <small style="font-weight: normal; font-size: 11px; color: #64748B;">/ person</small></strong>
                                            <span class="badge" style="background: #DCFCE7; color: #15803D; font-size: 11px; font-weight: 700;">Active</span>
                                        </div>
                                    </div>
                                </div>
                            `;
                        });
                        listContainer.innerHTML = html;
                    } else {
                        listContainer.innerHTML = '<p style="color:#DC2626;">Failed to load tour packages.</p>';
                    }
                })
                .catch(err => {
                    listContainer.innerHTML = '<p style="color:#DC2626;">Network error loading tour packages.</p>';
                });
        }
        window.loadAdminPackages = loadAdminPackages;

        // ══════════════════════════════════════════════════════════════════
        // 📢 ADMIN BROADCAST NOTICES LOADER
        // ══════════════════════════════════════════════════════════════════
        function loadAdminBroadcasts() {
            const listContainer = document.getElementById('broadcasts-list');
            if (!listContainer) return;

            fetch('api/safety.php?action=broadcasts')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.broadcasts) {
                        if (res.data.broadcasts.length === 0) {
                            listContainer.innerHTML = '<div class="card" style="grid-column: 1 / -1; text-align: center; padding: 40px;"><p style="color:#64748B;">No broadcast notices published yet.</p></div>';
                            return;
                        }
                        let html = '';
                        res.data.broadcasts.forEach(b => {
                            html += `
                                <div class="card" style="border-left: 4px solid var(--color-primary-dark); padding: 20px;">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 8px;">
                                        <h4 style="font-size: 15px; font-weight: 800; color: #0F172A;"><i class="fa-solid fa-bullhorn" style="color: var(--color-primary-dark);"></i> ${b.title}</h4>
                                        <span style="font-size: 11px; color: #64748B;">${b.created_at ? b.created_at.substring(0, 16) : ''}</span>
                                    </div>
                                    <p style="font-size: 13px; color: #334155; line-height: 1.5; margin-bottom: 12px;">${b.message}</p>
                                    <div style="font-size: 11px; color: #64748B; font-weight: 600;">
                                        📍 Target Region: <b>${b.target_area || 'All Travelers'}</b> · Org: <b>${b.organization_name || 'Tourism Authority'}</b>
                                    </div>
                                </div>
                            `;
                        });
                        listContainer.innerHTML = html;
                    } else {
                        listContainer.innerHTML = '<p style="color:#DC2626;">Failed to load broadcasts.</p>';
                    }
                })
                .catch(err => {
                    listContainer.innerHTML = '<p style="color:#DC2626;">Network error loading broadcasts.</p>';
                });
        }
        window.loadAdminBroadcasts = loadAdminBroadcasts;

        // ══════════════════════════════════════════════════════════════════
        // 🛡️ ADMIN SAFETY ZONES LOADER
        // ══════════════════════════════════════════════════════════════════
        function loadAdminZones() {
            const tbody = document.getElementById('admin-zones-table');
            if (!tbody) return;

            fetch('api/safety.php?action=zones')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.zones) {
                        if (res.data.zones.length === 0) {
                            tbody.innerHTML = '<tr><td colspan="5" style="padding: 20px; text-align: center; color: #64748B;">No safety zones defined yet.</td></tr>';
                            return;
                        }
                        let html = '';
                        res.data.zones.forEach(z => {
                            html += `
                                <tr style="border-bottom: 1px solid #eee;">
                                    <td style="padding:12px;font-weight:bold;color:#0F172A;">${z.title}</td>
                                    <td style="padding:12px;">${z.risk_badge_html}</td>
                                    <td style="padding:12px;">${z.risk_type}</td>
                                    <td style="padding:12px;">${z.radius_meters}m</td>
                                    <td style="padding:12px;font-size:12px;color:#666;">${Number(z.latitude).toFixed(4)}, ${Number(z.longitude).toFixed(4)}</td>
                                </tr>
                            `;
                        });
                        tbody.innerHTML = html;
                    }
                });
        }
        window.loadAdminZones = loadAdminZones;

        // ══════════════════════════════════════════════════════════════════
        // 🚨 ADMIN SOS INCIDENTS LOADER
        // ══════════════════════════════════════════════════════════════════
        function loadAdminSOS() {
            const listContainer = document.getElementById('admin-sos-list');
            if (!listContainer) return;

            fetch('api/sos.php?action=active_emergencies')
                .then(r => r.json())
                .then(res => {
                    if (res.success && res.data && res.data.emergencies) {
                        if (res.data.emergencies.length === 0) {
                            listContainer.innerHTML = '<div class="card" style="grid-column: 1 / -1;"><p style="color:#10B981;font-weight:bold;margin:0;"><i class="fa-solid fa-circle-check"></i> No active emergency SOS alerts reported right now.</p></div>';
                        } else {
                            let html = '';
                            res.data.emergencies.forEach(e => {
                                html += `
                                    <div class="card" style="border-left:5px solid #DC2626; padding:20px;">
                                        <h4 style="color:#DC2626;margin-bottom:6px;font-size:16px;"><i class="fa-solid fa-triangle-exclamation"></i> SOS Category: ${e.category}</h4>
                                        <p style="font-size:14px;font-weight:bold;margin-bottom:6px;color:#0F172A;">Traveler: ${e.user_name} (Phone: ${e.user_phone})</p>
                                        <p style="font-size:12px;color:#64748B;margin-bottom:12px;">Emergency Contact: ${e.emergency_contact_name || 'N/A'} (${e.emergency_contact_phone || 'N/A'})</p>
                                        <button class="btn btn-primary" style="font-size:12px;padding:8px 16px;" onclick="resolveSOS(${e.id})"><i class="fa-solid fa-check"></i> Mark as RESOLVED</button>
                                    </div>
                                `;
                            });
                            listContainer.innerHTML = html;
                        }
                    }
                });
        }
        window.loadAdminSOS = loadAdminSOS;

        function resolveSOS(sosId) {
            if (confirm('Mark this Emergency SOS alert as RESOLVED?')) {
                const formData = new FormData();
                formData.append('action', 'resolve');
                formData.append('sos_id', sosId);

                fetch('api/sos.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            if (typeof window.showToast === 'function') window.showToast('✅ SOS marked as RESOLVED', 'success');
                            else alert('✅ SOS marked as RESOLVED');
                            loadAdminSOS();
                        } else {
                            alert('⚠️ ' + res.message);
                        }
                    });
            }
        }

        // ADMIN TRAVEL TEAMS MANAGEMENT FUNCTIONS
        function loadAdminTeams() {
            fetch('api/teams.php?action=my_teams')
                .then(r => r.json())
                .then(data => {
                    const container = document.getElementById('admin-teams-list-container');
                    if (!container) return;

                    if (data.success && data.data.teams && data.data.teams.length > 0) {
                        const teams = data.data.teams;
                        let html = teams.map(t => {
                            const members = t.members || [];
                            let membersHtml = members.map(m => `
                                <div style="background: #F8FAFC; border: 1px solid #E2E8F0; border-radius: 12px; padding: 12px 14px; display: flex; align-items: center; justify-content: space-between; gap: 14px; margin-bottom: 8px; flex-wrap: wrap;">
                                    <div style="display: flex; align-items: center; gap: 12px;">
                                        <div style="width: 38px; height: 38px; background: #E0F2FE; color: #0284C7; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; flex-shrink: 0;">
                                            ${m.full_name.charAt(0).toUpperCase()}
                                        </div>
                                        <div>
                                            <div style="font-size: 14px; font-weight: 800; color: #0F172A;">${m.full_name} ${m.is_leader ? '<span class="badge" style="background:#FEF3C7;color:#D97706;">👑 (Admin Leader)</span>' : '<span class="badge" style="background:#E0F2FE;color:#0369A1;">🧳 (Joined Traveler)</span>'}</div>
                                            <div style="font-size: 11px; color: #64748B; margin-top: 2px;">
                                                📞 ${m.phone || 'N/A'} · ✉️ ${m.email || 'N/A'} ${m.emergency_contact_name ? `· 🚨 Emergency: <b>${m.emergency_contact_name}</b> (${m.emergency_contact_phone || 'N/A'})` : ''}
                                            </div>
                                        </div>
                                    </div>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <span class="badge" style="font-size: 11px; ${m.online_status.includes('Active') ? 'background: #DCFCE7; color: #15803D;' : 'background: #F1F5F9; color: #64748B;'}">
                                            ${m.online_status}
                                        </span>
                                        ${!m.is_leader ? `
                                            <button onclick="confirmRemoveMember(${t.id}, ${m.user_id}, '${m.full_name.replace(/'/g, "\\'")}')" class="btn btn-outline" style="font-size: 11px; padding: 4px 10px; color: #DC2626; border-color: #FECACA; background: #FEF2F2;">
                                                <i class="fa-solid fa-user-minus"></i> Remove Member
                                            </button>
                                        ` : ''}
                                    </div>
                                </div>
                            `).join('');

                            return `
                                <div class="enhanced-card" style="background: #FFFFFF; border: 1px solid #E2E8F0; border-radius: 16px; padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 16px rgba(0,0,0,0.03);">
                                    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; margin-bottom: 16px; border-bottom: 1px solid #F1F5F9; padding-bottom: 14px; flex-wrap: wrap;">
                                        <div>
                                            <h3 style="font-size: 20px; font-weight: 800; color: #0F172A; margin: 0;">🏔️ ${t.name}</h3>
                                            <p style="font-size: 12px; color: #64748B; margin-top: 4px; margin-bottom: 0;">Destination: <b>${t.destination_name || 'Nepal Trekking Route'}</b> · Total Members: <b>${members.length}</b></p>
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 12px;">
                                            <div style="background: #F0F9FF; border: 2px dashed #0284C7; border-radius: 12px; padding: 8px 16px; display: flex; align-items: center; gap: 12px;">
                                                <div>
                                                    <div style="font-size: 10px; font-weight: 800; color: #0284C7;">TEAM CODE</div>
                                                    <div style="font-size: 18px; font-weight: 900; color: #0F172A; font-family: monospace;">${t.team_code}</div>
                                                </div>
                                                <button onclick="navigator.clipboard.writeText('${t.team_code}'); alert('Team Code ${t.team_code} copied!');" class="btn btn-primary" style="font-size: 11px; padding: 6px 10px;">
                                                    <i class="fa-solid fa-copy"></i> Copy
                                                </button>
                                            </div>
                                            <button onclick="confirmDeleteTeam(${t.id}, '${t.name.replace(/'/g, "\\'")}')" class="btn btn-danger" style="font-size: 12px; padding: 10px 14px; background: #DC2626; border: none; color: white; font-weight: 700; border-radius: 10px; cursor: pointer;">
                                                <i class="fa-solid fa-trash-can"></i> Delete Entire Team
                                            </button>
                                        </div>
                                    </div>

                                    <h5 style="font-size: 14px; font-weight: 800; color: #0F172A; margin-bottom: 10px;">👥 Joined Travelers & Roster (${members.length})</h5>
                                    <div style="margin-bottom: 18px;">
                                        ${membersHtml}
                                    </div>
                                </div>
                            `;
                        }).join('');

                        container.innerHTML = html;
                    } else {
                        container.innerHTML = `
                            <div style="text-align: center; padding: 40px; background: #F8FAFC; border-radius: 16px; border: 2px dashed #CBD5E1;">
                                <i class="fa-solid fa-people-roof" style="font-size: 40px; color: #0284C7; margin-bottom: 10px;"></i>
                                <h3 style="font-size: 16px; font-weight: 800; color: #0F172A;">No Travel Teams Created Yet</h3>
                                <p style="font-size: 12px; color: #64748B; margin-top: 4px;">Use the form above to create travel teams and generate Team Codes for your travelers.</p>
                            </div>
                        `;
                    }
                });
        }

        function confirmRemoveMember(teamId, userId, userName) {
            if (confirm(`Are you sure you want to remove traveler '${userName}' from this team?`)) {
                const formData = new FormData();
                formData.append('action', 'remove_member');
                formData.append('team_id', teamId);
                formData.append('user_id', userId);

                fetch('api/teams.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            alert(`✅ ${res.message}`);
                            loadAdminTeams();
                        } else {
                            alert(`⚠️ ${res.message}`);
                        }
                    });
            }
        }

        function confirmDeleteTeam(teamId, teamName) {
            if (confirm(`⚠️ PERMANENT DELETE WARNING:\n\nAre you sure you want to delete the team '${teamName}'?\n\nDeleting this team will immediately remove it entirely from ALL travelers' dashboards!`)) {
                const formData = new FormData();
                formData.append('action', 'delete_team');
                formData.append('team_id', teamId);

                fetch('api/teams.php', { method: 'POST', body: formData })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            alert(`🗑️ ${res.message}`);
                            loadAdminTeams();
                        } else {
                            alert(`⚠️ ${res.message}`);
                        }
                    });
            }
        }

        window.loadAdminTeams = loadAdminTeams;
    </script>
</body>
</html>

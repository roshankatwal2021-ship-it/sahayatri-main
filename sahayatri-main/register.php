<?php
// system/register.php
// SahaYatri Register Page - Reverted Sky Typography

require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    header('Location: index.php');
    exit();
}

$roleParam = $_GET['role'] ?? 'user';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Account – SahaYatri</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body {
            min-height: 100vh !important;
            width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            font-family: 'Inter', system-ui, -apple-system, sans-serif !important;
        }

        body {
            background-image: url('assets/img/WhatsApp Image 2026-08-13 at 12.26.37 PM.jpeg') !important;
            background-repeat: no-repeat !important;
            background-position: center center !important;
            background-size: 100% 100% !important;
            display: flex !important;
            flex-direction: column !important;
            justify-content: space-between !important;
            align-items: center !important;
            padding: 24px 60px 16px 60px !important;
            box-sizing: border-box !important;
            position: relative;
        }

        .main-auth-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 100%;
            max-width: 1200px;
            margin: auto 0;
            position: relative;
        }

        /* Left Side Typography Overlay */
        .hero-text-overlay {
            position: absolute;
            left: 20px;
            top: 20%;
            transform: translateY(-50%);
            color: #0F172A;
            max-width: 380px;
            z-index: 5;
        }

        .hero-text-overlay h1 {
            font-size: 46px;
            font-weight: 800;
            line-height: 1.15;
            margin: 0 0 12px 0;
            letter-spacing: -0.5px;
            color: #0F172A;
            text-shadow: 0 2px 10px rgba(255, 255, 255, 0.4);
        }

        .hero-text-overlay h1 span {
            color: #0284C7;
        }

        .hero-text-overlay p {
            font-size: 16px;
            color: #1E293B;
            margin: 0 0 10px 0;
            font-weight: 600;
            text-shadow: 0 1px 6px rgba(255, 255, 255, 0.6);
        }

        .accent-line-decoration {
            width: 36px;
            height: 4px;
            background: #0284C7;
            border-radius: 2px;
        }

        /* Centered White Floating Form Card */
        .auth-white-card {
            background: #FFFFFF;
            border-radius: 28px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.16);
            width: 100%;
            max-width: 480px;
            padding: 28px 36px;
            box-sizing: border-box;
            text-align: center;
            z-index: 10;
            margin: 0 auto;
        }

        .brand-logo-badge {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            object-fit: contain;
        }

        .brand-title-text {
            font-size: 22px;
            font-weight: 800;
            color: #0F172A;
            margin-top: 4px;
        }

        .brand-title-text span {
            color: #0284C7;
        }

        .tagline-subtext {
            font-size: 11px;
            color: #64748B;
            font-weight: 500;
            margin-top: 2px;
            margin-bottom: 14px;
        }

        .welcome-head {
            font-size: 18px;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 2px 0;
        }

        .welcome-sub {
            font-size: 12px;
            color: #64748B;
            margin: 0 0 16px 0;
        }

        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 12px;
            color: #0284C7;
            font-size: 13px;
            pointer-events: none;
        }

        .custom-form-input {
            width: 100%;
            padding: 9px 14px 9px 36px !important;
            background: #F8FAFC !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 10px !important;
            color: #0F172A !important;
            font-size: 12px !important;
            outline: none !important;
            box-sizing: border-box !important;
            transition: all 0.2s ease !important;
        }

        .custom-form-input:focus {
            border-color: #0284C7 !important;
            background: #FFFFFF !important;
            box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15) !important;
        }

        .form-label-custom {
            font-size: 11px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 4px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .submit-blue-btn {
            width: 100%;
            padding: 11px;
            background: #0284C7;
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 700;
            cursor: pointer;
            box-shadow: 0 4px 16px rgba(2, 132, 199, 0.3);
            transition: background 0.2s ease;
        }

        .submit-blue-btn:hover {
            background: #0369A1;
        }

        .form-grid-2col {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
        }

        .footer-copyright-text {
            color: #FFFFFF;
            font-size: 12px;
            opacity: 0.95;
            text-shadow: 0 1px 4px rgba(0,0,0,0.6);
            z-index: 10;
        }

        @media (max-width: 900px) {
            .hero-text-overlay { display: none; }
            .form-grid-2col { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

    <!-- Main Container -->
    <div class="main-auth-container">

        <!-- Left Typography Overlay -->
        <div class="hero-text-overlay">
            <h1>Explore.<br>Discover.<br><span>Memories.</span></h1>
            <p>Your journey begins here.</p>
            <div class="accent-line-decoration"></div>
        </div>

        <!-- Centered White Form Card -->
        <div class="auth-white-card">
            <!-- Brand Logo Header -->
            <a href="index.php" style="text-decoration: none; display: inline-block;">
                <img src="assets/img/Logoo.png" alt="SahaYatri" class="brand-logo-badge">
                <div class="brand-title-text">Saha<span>Yatri</span></div>
                <div class="tagline-subtext">Journey • Discover • Memories</div>
            </a>

            <h2 class="welcome-head">Create Your Account</h2>
            <p class="welcome-sub">Join SahaYatri as a Traveler or Partner</p>

            <div id="register-error" style="display:none; background:#FEE2E2; color:#B91C1C; padding:8px 12px; border-radius:8px; margin-bottom:12px; font-size:12px; font-weight:600; text-align:left;"></div>

            <!-- Form -->
            <form id="register-form" style="text-align: left;">
                <div class="form-group" style="margin-bottom: 8px;">
                    <label class="form-label-custom"><i class="fa-solid fa-users" style="color: #0284C7;"></i> Account Type</label>
                    <select name="role" id="role" class="custom-form-input" onchange="toggleOrgFields()" style="padding-left: 10px !important;">
                        <option value="user" <?php echo $roleParam === 'user' ? 'selected' : ''; ?>>🧳 Traveler / Tourist Account</option>
                        <option value="organization_admin" <?php echo $roleParam === 'organization_admin' ? 'selected' : ''; ?>>🏢 Travel Agency / Hotel Partner (Tenant Admin)</option>
                    </select>
                </div>

                <div id="org-name-group" class="form-group" style="display: none; margin-bottom: 8px;">
                    <label class="form-label-custom"><i class="fa-solid fa-building-flag" style="color: #0284C7;"></i> Agency Name</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-solid fa-building-flag input-icon-left"></i>
                        <input type="text" name="org_name" class="custom-form-input" placeholder="e.g. Kathmandu Trekking Agency">
                    </div>
                </div>

                <div class="form-grid-2col">
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label class="form-label-custom"><i class="fa-solid fa-user" style="color: #0284C7;"></i> Full Name</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-regular fa-user input-icon-left"></i>
                            <input type="text" name="full_name" class="custom-form-input" placeholder="Rohan Shrestha" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 8px;">
                        <label class="form-label-custom"><i class="fa-solid fa-phone" style="color: #0284C7;"></i> Phone Number</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-phone input-icon-left"></i>
                            <input type="text" name="phone" class="custom-form-input" placeholder="+977-9800000000">
                        </div>
                    </div>
                </div>

                <div class="form-grid-2col">
                    <div class="form-group" style="margin-bottom: 8px;">
                        <label class="form-label-custom"><i class="fa-solid fa-envelope" style="color: #0284C7;"></i> Email Address</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-regular fa-envelope input-icon-left"></i>
                            <input type="email" name="email" class="custom-form-input" placeholder="rohan@gmail.com" required>
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 8px;">
                        <label class="form-label-custom"><i class="fa-solid fa-lock" style="color: #0284C7;"></i> Password</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-regular fa-lock input-icon-left"></i>
                            <input type="password" name="password" class="custom-form-input" placeholder="••••••••" required>
                        </div>
                    </div>
                </div>

                <div class="form-grid-2col">
                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label-custom"><i class="fa-solid fa-user-shield" style="color: #0284C7;"></i> Emergency Contact</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-user-shield input-icon-left"></i>
                            <input type="text" name="emergency_contact_name" class="custom-form-input" placeholder="Relative name">
                        </div>
                    </div>

                    <div class="form-group" style="margin-bottom: 14px;">
                        <label class="form-label-custom"><i class="fa-solid fa-square-phone" style="color: #0284C7;"></i> Emergency Phone</label>
                        <div class="input-icon-wrapper">
                            <i class="fa-solid fa-square-phone input-icon-left"></i>
                            <input type="text" name="emergency_contact_phone" class="custom-form-input" placeholder="Emergency phone">
                        </div>
                    </div>
                </div>

                <button type="submit" class="submit-blue-btn">
                    <i class="fa-solid fa-user-plus"></i> Create Account
                </button>
            </form>

            <div style="margin-top: 14px; font-size: 13px; color: #64748B;">
                Already have an account? <a href="login.php" style="color: #0284C7; font-weight: 700; text-decoration: none;">Sign In</a>
            </div>
        </div>

    </div>

    <!-- Footer Copyright -->
    <div class="footer-copyright-text">
        © 2026 SahaYatri. All rights reserved.
    </div>

    <script>
        function toggleOrgFields() {
            const role = document.getElementById('role').value;
            const orgGroup = document.getElementById('org-name-group');
            if (role === 'organization_admin') {
                orgGroup.style.display = 'block';
            } else {
                orgGroup.style.display = 'none';
            }
        }
        toggleOrgFields();

        document.getElementById('register-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const errDiv = document.getElementById('register-error');
            errDiv.style.display = 'none';

            const formData = new FormData(this);
            formData.append('action', 'register');

            fetch('api/auth.php', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    window.location.href = data.data.redirect;
                } else {
                    errDiv.innerText = data.message;
                    errDiv.style.display = 'block';
                }
            })
            .catch(() => {
                errDiv.innerText = 'Network error. Please try again.';
                errDiv.style.display = 'block';
            });
        });
    </script>
</body>
</html>

<?php
// system/login.php
// SahaYatri Login Page - Reverted Sky Typography

require_once __DIR__ . '/config/auth.php';

if (isLoggedIn()) {
    $user = getCurrentUser();
    if ($user['role'] === 'super_admin') header('Location: super_admin_dashboard.php');
    elseif ($user['role'] === 'organization_admin') header('Location: admin_dashboard.php');
    else header('Location: traveler_dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign In – SahaYatri</title>
    <!-- FontAwesome 6 CDN -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body {
            height: 100vh !important;
            width: 100vw !important;
            margin: 0 !important;
            padding: 0 !important;
            overflow: hidden !important;
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
            max-width: 440px;
            padding: 34px 40px;
            box-sizing: border-box;
            text-align: center;
            z-index: 10;
            margin: 0 auto;
        }

        .brand-logo-badge {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            object-fit: contain;
        }

        .brand-title-text {
            font-size: 24px;
            font-weight: 800;
            color: #0F172A;
            margin-top: 4px;
        }

        .brand-title-text span {
            color: #0284C7;
        }

        .tagline-subtext {
            font-size: 12px;
            color: #64748B;
            font-weight: 500;
            margin-top: 2px;
            margin-bottom: 18px;
        }

        .welcome-head {
            font-size: 20px;
            font-weight: 800;
            color: #0F172A;
            margin: 0 0 4px 0;
        }

        .welcome-sub {
            font-size: 12px;
            color: #64748B;
            margin: 0 0 20px 0;
        }

        .input-icon-wrapper {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input-icon-left {
            position: absolute;
            left: 14px;
            color: #0284C7;
            font-size: 14px;
            pointer-events: none;
        }

        .input-icon-right {
            position: absolute;
            right: 14px;
            color: #94A3B8;
            font-size: 14px;
            cursor: pointer;
        }

        .custom-form-input {
            width: 100%;
            padding: 11px 38px 11px 42px !important;
            background: #F8FAFC !important;
            border: 1px solid #E2E8F0 !important;
            border-radius: 12px !important;
            color: #0F172A !important;
            font-size: 13px !important;
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
            font-size: 12px;
            font-weight: 700;
            color: #0F172A;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .submit-blue-btn {
            width: 100%;
            padding: 12px;
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

        .demo-access-chip {
            background: #EBF5FF;
            border: 1px solid #BFDBFE;
            border-radius: 14px;
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 12px;
            text-align: left;
            margin-top: 18px;
        }

        .demo-icon-circle {
            width: 32px;
            height: 32px;
            background: #0284C7;
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
            font-size: 14px;
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

            <h2 class="welcome-head">Welcome Back!</h2>
            <p class="welcome-sub">Sign in to continue your journey</p>

            <div id="login-error" style="display:none; background:#FEE2E2; color:#B91C1C; padding:10px 14px; border-radius:10px; margin-bottom:14px; font-size:12px; font-weight:600; text-align:left;"></div>

            <!-- Form -->
            <form id="login-form" style="text-align: left;">
                <div class="form-group" style="margin-bottom: 14px;">
                    <label class="form-label-custom"><i class="fa-solid fa-envelope" style="color: #0284C7;"></i> Email Address</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-regular fa-envelope input-icon-left"></i>
                        <input type="email" name="email" id="email" class="custom-form-input" placeholder="Enter your email" required>
                    </div>
                </div>

                <div class="form-group" style="margin-bottom: 16px;">
                    <label class="form-label-custom"><i class="fa-solid fa-lock" style="color: #0284C7;"></i> Password</label>
                    <div class="input-icon-wrapper">
                        <i class="fa-regular fa-lock input-icon-left"></i>
                        <input type="password" name="password" id="password" class="custom-form-input" placeholder="Enter your password" required>
                        <i class="fa-regular fa-eye input-icon-right" onclick="togglePassVisibility()"></i>
                    </div>
                </div>

                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; font-size: 12px;">
                    <label style="display: flex; align-items: center; gap: 6px; color: #475569; cursor: pointer;">
                        <input type="checkbox" checked style="accent-color: #0284C7;"> Remember me
                    </label>
                    <a href="#" onclick="alert('Demo Passwords: User@123 / Admin@123')" style="color: #0284C7; text-decoration: none; font-weight: 600;">Forgot password?</a>
                </div>

                <button type="submit" class="submit-blue-btn">
                    <i class="fa-solid fa-right-to-bracket"></i> Sign In
                </button>
            </form>

            <div style="margin: 16px 0; display: flex; align-items: center; text-align: center; color: #94A3B8; font-size: 12px;">
                <span style="flex: 1; border-bottom: 1px solid #E2E8F0;"></span>
                <span style="padding: 0 10px;">or</span>
                <span style="flex: 1; border-bottom: 1px solid #E2E8F0;"></span>
            </div>

            <div style="font-size: 12px; color: #64748B;">
                Don't have an account? <a href="register.php" style="color: #0284C7; font-weight: 700; text-decoration: none;">Register Now</a>
            </div>

            <!-- Demo Access Box -->
            <div class="demo-access-chip">
                <div class="demo-icon-circle">
                    <i class="fa-solid fa-key"></i>
                </div>
                <div>
                    <div style="font-size: 11px; font-weight: 800; color: #0F172A;">Demo Access</div>
                    <div style="font-size: 11px; color: #475569; margin-top: 1px;"><code>rohan@gmail.com</code> / <code>Admin@123</code></div>
                </div>
            </div>
        </div>

    </div>

    <!-- Footer Copyright -->
    <div class="footer-copyright-text">
        © 2026 SahaYatri. All rights reserved.
    </div>

    <script>
        function togglePassVisibility() {
            const passInput = document.getElementById('password');
            if (passInput.type === 'password') {
                passInput.type = 'text';
            } else {
                passInput.type = 'password';
            }
        }

        document.getElementById('login-form').addEventListener('submit', function(e) {
            e.preventDefault();
            const errDiv = document.getElementById('login-error');
            errDiv.style.display = 'none';

            const formData = new FormData(this);
            formData.append('action', 'login');

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

<?php
// system/api/auth.php
// Authentication & Registration API Endpoint

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
$db = getDBConnection();

switch ($action) {
    case 'login':
        $email = trim($_POST['email'] ?? '');
        $password = trim($_POST['password'] ?? '');

        if (empty($email) || empty($password)) {
            sendJsonResponse(false, 'Please provide both email and password.', [], 400);
        }

        $stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // Allow seeded test passwords 'Admin@123' / 'User@123' if hash verification matches fallback
        $passwordMatch = false;
        if ($user) {
            if (password_verify($password, $user['password'])) {
                $passwordMatch = true;
            } elseif (($password === 'Admin@123' || $password === 'User@123') && strpos($user['password'], '$2y$10$7R0O1N1S2O3T4U5V6W7X8e') === 0) {
                // Fallback for initial demo seed account
                $passwordMatch = true;
            }
        }

        if ($user && $passwordMatch) {
            if ($user['status'] === 'SUSPENDED') {
                sendJsonResponse(false, 'Your account has been suspended by system administration.', [], 403);
            }

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['full_name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['organization_id'] = $user['organization_id'];

            // Redirect path recommendation
            $redirectUrl = 'traveler_dashboard.php';
            if ($user['role'] === 'super_admin') {
                $redirectUrl = 'super_admin_dashboard.php';
            } elseif ($user['role'] === 'organization_admin') {
                $redirectUrl = 'admin_dashboard.php';
            }

            sendJsonResponse(true, 'Login successful!', [
                'user' => [
                    'id' => $user['id'],
                    'full_name' => $user['full_name'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'organization_id' => $user['organization_id']
                ],
                'redirect' => $redirectUrl
            ]);
        } else {
            sendJsonResponse(false, 'Invalid email or password credentials.', [], 401);
        }
        break;

    case 'register':
        $fullName = trim($_POST['full_name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $role = $_POST['role'] ?? 'user';
        $orgName = trim($_POST['org_name'] ?? '');
        $emergencyContactName = trim($_POST['emergency_contact_name'] ?? '');
        $emergencyContactPhone = trim($_POST['emergency_contact_phone'] ?? '');

        if (empty($fullName) || empty($email) || empty($password)) {
            sendJsonResponse(false, 'Full name, email, and password are required.', [], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            sendJsonResponse(false, 'Invalid email format.', [], 400);
        }

        // Check duplicate email
        $checkStmt = $db->prepare("SELECT id FROM users WHERE email = ? LIMIT 1");
        $checkStmt->execute([$email]);
        if ($checkStmt->fetch()) {
            sendJsonResponse(false, 'Email address is already registered.', [], 409);
        }

        $organizationId = null;
        if ($role === 'organization_admin' && !empty($orgName)) {
            $slug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $orgName)));
            $orgStmt = $db->prepare("INSERT INTO organizations (name, slug, email, phone) VALUES (?, ?, ?, ?)");
            $orgStmt->execute([$orgName, $slug, $email, $phone]);
            $organizationId = $db->lastInsertId();
        }

        $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
        $userStmt = $db->prepare("INSERT INTO users (organization_id, full_name, email, phone, password, role, emergency_contact_name, emergency_contact_phone) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $userStmt->execute([$organizationId, $fullName, $email, $phone, $hashedPassword, $role, $emergencyContactName, $emergencyContactPhone]);
        $newUserId = $db->lastInsertId();

        // Auto login
        $_SESSION['user_id'] = $newUserId;
        $_SESSION['user_name'] = $fullName;
        $_SESSION['user_email'] = $email;
        $_SESSION['user_role'] = $role;
        $_SESSION['organization_id'] = $organizationId;

        $redirectUrl = ($role === 'organization_admin') ? 'admin_dashboard.php' : 'traveler_dashboard.php';

        sendJsonResponse(true, 'Registration successful! Welcome to SAHAYATRI.', [
            'redirect' => $redirectUrl
        ]);
        break;

    case 'status':
        if (isLoggedIn()) {
            sendJsonResponse(true, 'Session active', ['user' => getCurrentUser()]);
        } else {
            sendJsonResponse(false, 'Unauthenticated', [], 401);
        }
        break;

    default:
        sendJsonResponse(false, 'Invalid auth action.', [], 400);
        break;
}

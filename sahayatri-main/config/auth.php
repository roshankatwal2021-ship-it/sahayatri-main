<?php
// system/config/auth.php
// Auth & Role-Based Access Control Helper

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function getCurrentUser() {
    if (!isLoggedIn()) return null;
    return [
        'id' => $_SESSION['user_id'],
        'full_name' => $_SESSION['user_name'] ?? 'User',
        'email' => $_SESSION['user_email'] ?? '',
        'role' => $_SESSION['user_role'] ?? 'user',
        'organization_id' => $_SESSION['organization_id'] ?? null,
    ];
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($allowedRoles = []) {
    requireLogin();
    $user = getCurrentUser();
    if (!in_array($user['role'], (array)$allowedRoles)) {
        http_response_code(403);
        die("403 Forbidden: You do not have permission to access this portal.");
    }
}

// Security: Tenant Scoping rule
function getTenantId() {
    $user = getCurrentUser();
    if (!$user) return null;
    if ($user['role'] === 'super_admin') {
        // Super Admin can override or access all, defaults to null for global view
        return isset($_GET['org_id']) ? intval($_GET['org_id']) : null;
    }
    return $user['organization_id'];
}

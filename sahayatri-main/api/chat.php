<?php
// system/api/chat.php
// Direct Messaging API between Travelers and Travel Agencies / Tourism Organizations

header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

$user = getCurrentUser();
if (!$user) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access. Please login.']);
    exit();
}

$db = getDBConnection();
$action = $_GET['action'] ?? $_POST['action'] ?? '';

// 1. Fetch chat history for traveler with a selected organization
if ($action === 'fetch_messages') {
    $orgId = intval($_GET['org_id'] ?? 0);
    $travelerId = intval($_GET['user_id'] ?? $user['id']);

    // If organization_admin is fetching messages, user_id is the traveler
    if ($user['role'] === 'organization_admin') {
        $orgId = $user['organization_id'];
    }

    $stmt = $db->prepare("SELECT m.*, u.full_name as sender_name, u.role as sender_role 
                          FROM messages m 
                          JOIN users u ON m.sender_id = u.id 
                          WHERE (m.organization_id = ? AND (m.sender_id = ? OR m.receiver_id = ?))
                          ORDER BY m.id ASC");
    $stmt->execute([$orgId, $travelerId, $travelerId]);
    $messages = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => ['messages' => $messages]]);
    exit();
}

// 2. Send message from Traveler to Agency, or Agency Admin to Traveler
if ($action === 'send_message') {
    $message = trim($_POST['message'] ?? '');
    $orgId = intval($_POST['org_id'] ?? 0);
    $receiverId = isset($_POST['receiver_id']) && !empty($_POST['receiver_id']) ? intval($_POST['receiver_id']) : null;

    if (empty($message)) {
        echo json_encode(['success' => false, 'message' => 'Message content cannot be empty.']);
        exit();
    }

    if ($user['role'] === 'organization_admin') {
        $orgId = $user['organization_id'];
    }

    try {
        $stmt = $db->prepare("INSERT INTO messages (sender_id, receiver_id, organization_id, message) VALUES (?, ?, ?, ?)");
        $stmt->execute([$user['id'], $receiverId, $orgId, $message]);

        echo json_encode(['success' => true, 'message' => 'Message sent successfully.']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Failed to send message: ' . $e->getMessage()]);
    }
    exit();
}

// 3. List agency conversations for agency admin
if ($action === 'list_conversations') {
    if ($user['role'] !== 'organization_admin' && $user['role'] !== 'super_admin') {
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit();
    }

    $orgId = $user['organization_id'];
    $stmt = $db->prepare("SELECT DISTINCT u.id as user_id, u.full_name, u.email, u.phone, 
                                 (SELECT message FROM messages WHERE (sender_id = u.id OR receiver_id = u.id) AND organization_id = ? ORDER BY id DESC LIMIT 1) as last_message,
                                 (SELECT created_at FROM messages WHERE (sender_id = u.id OR receiver_id = u.id) AND organization_id = ? ORDER BY id DESC LIMIT 1) as last_time
                          FROM users u 
                          JOIN messages m ON (m.sender_id = u.id OR m.receiver_id = u.id)
                          WHERE m.organization_id = ? AND u.role = 'user'
                          ORDER BY last_time DESC");
    $stmt->execute([$orgId, $orgId, $orgId]);
    $conversations = $stmt->fetchAll();

    echo json_encode(['success' => true, 'data' => ['conversations' => $conversations]]);
    exit();
}

echo json_encode(['success' => false, 'message' => 'Invalid action']);

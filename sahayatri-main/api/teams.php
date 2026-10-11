<?php
// system/api/teams.php
// Travel Team Hub API (Admin Team Creation, Traveler Join via Code, Member Removal, Team Deletion & Group Chat)

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');

$db = getDBConnection();
$action = $_GET['action'] ?? ($_POST['action'] ?? 'my_teams');

switch ($action) {
    case 'create':
        requireLogin();
        $user = getCurrentUser();

        // Enforce: Only Admins can create travel teams
        if ($user['role'] !== 'organization_admin' && $user['role'] !== 'super_admin') {
            sendJsonResponse(false, 'Only Travel Agency / Hotel Admins can create Travel Teams. Travelers can join teams using a Team Code.', [], 403);
        }

        $teamName = trim($_POST['team_name'] ?? '');
        $destinationName = trim($_POST['destination_name'] ?? 'Nepal Trekking Route');
        $startDate = $_POST['start_date'] ?? date('Y-m-d');
        $endDate = $_POST['end_date'] ?? date('Y-m-d', strtotime('+7 days'));

        if (empty($teamName)) {
            sendJsonResponse(false, 'Please provide a team name.', [], 400);
        }

        $teamCode = generateTeamCode('NP');

        $stmt = $db->prepare("INSERT INTO teams (team_code, name, leader_id, destination_name, start_date, end_date, status) VALUES (?, ?, ?, ?, ?, ?, 'ACTIVE')");
        $stmt->execute([$teamCode, $teamName, $user['id'], $destinationName, $startDate, $endDate]);
        $teamId = $db->lastInsertId();

        // Add admin creator as Team Leader member
        $memStmt = $db->prepare("INSERT INTO team_members (team_id, user_id, sharing_option, status) VALUES (?, ?, 'ALWAYS', 'ACTIVE')");
        $memStmt->execute([$teamId, $user['id']]);

        sendJsonResponse(true, "Travel Team '{$teamName}' created successfully! Share Team Code: {$teamCode}", [
            'team_id' => $teamId,
            'team_code' => $teamCode,
            'name' => $teamName
        ]);
        break;

    case 'join':
        requireLogin();
        $user = getCurrentUser();
        $code = strtoupper(trim($_POST['team_code'] ?? ''));

        if (empty($code)) {
            sendJsonResponse(false, 'Team Code is required.', [], 400);
        }

        $stmt = $db->prepare("SELECT * FROM teams WHERE team_code = ? AND status = 'ACTIVE' LIMIT 1");
        $stmt->execute([$code]);
        $team = $stmt->fetch();

        if (!$team) {
            sendJsonResponse(false, 'Invalid or expired Team Code. Please check code and try again.', [], 404);
        }

        // Check if already a member or removed
        $checkStmt = $db->prepare("SELECT status FROM team_members WHERE team_id = ? AND user_id = ? LIMIT 1");
        $checkStmt->execute([$team['id'], $user['id']]);
        $existing = $checkStmt->fetch();

        if ($existing) {
            if ($existing['status'] === 'ACTIVE') {
                sendJsonResponse(true, "You are already a member of team '{$team['name']}'.", ['team' => $team]);
            } else {
                // Re-activate member
                $updStmt = $db->prepare("UPDATE team_members SET status = 'ACTIVE', joined_at = NOW() WHERE team_id = ? AND user_id = ?");
                $updStmt->execute([$team['id'], $user['id']]);
                sendJsonResponse(true, "Re-joined team '{$team['name']}'!", ['team' => $team]);
            }
        } else {
            $joinStmt = $db->prepare("INSERT INTO team_members (team_id, user_id, sharing_option, status) VALUES (?, ?, 'ALWAYS', 'ACTIVE')");
            $joinStmt->execute([$team['id'], $user['id']]);
            sendJsonResponse(true, "Successfully joined team: {$team['name']}!", ['team' => $team]);
        }
        break;

    case 'my_teams':
        requireLogin();
        $user = getCurrentUser();

        $stmt = $db->prepare("SELECT t.*, u.full_name as leader_name, 
                                     (SELECT COUNT(*) FROM team_members tm WHERE tm.team_id = t.id AND tm.status = 'ACTIVE') as member_count
                              FROM teams t 
                              JOIN team_members tm ON t.id = tm.team_id 
                              JOIN users u ON t.leader_id = u.id 
                              WHERE tm.user_id = ? AND t.status = 'ACTIVE' AND tm.status = 'ACTIVE'
                              ORDER BY t.id DESC");
        $stmt->execute([$user['id']]);
        $teams = $stmt->fetchAll();

        foreach ($teams as &$t) {
            $memStmt = $db->prepare("
                SELECT u.id as user_id, u.full_name, u.email, u.phone, u.avatar, 
                       u.emergency_contact_name, u.emergency_contact_phone,
                       tm.sharing_option, tm.joined_at,
                       ul.latitude, ul.longitude, ul.updated_at as last_location_time
                FROM team_members tm
                JOIN users u ON tm.user_id = u.id
                LEFT JOIN user_locations ul ON u.id = ul.user_id
                WHERE tm.team_id = ? AND tm.status = 'ACTIVE'
                ORDER BY (u.id = ?) DESC, tm.joined_at ASC
            ");
            $memStmt->execute([$t['id'], $t['leader_id']]);
            $members = $memStmt->fetchAll();

            foreach ($members as &$m) {
                $m['is_leader'] = ($m['user_id'] == $t['leader_id']);
                if (!$m['last_location_time']) {
                    $m['online_status'] = '🔴 Offline';
                } else {
                    $diff = time() - strtotime($m['last_location_time']);
                    if ($diff < 180) {
                        $m['online_status'] = '🟢 Active Now';
                    } elseif ($diff < 1800) {
                        $m['online_status'] = '🟡 Last seen ' . round($diff / 60) . 'm ago';
                    } else {
                        $m['online_status'] = '🔴 Offline';
                    }
                }
            }
            $t['members'] = $members;
        }

        sendJsonResponse(true, 'User teams', ['teams' => $teams]);
        break;

    case 'leave_team':
        requireLogin();
        $user = getCurrentUser();
        $teamId = intval($_POST['team_id'] ?? ($_GET['team_id'] ?? 0));

        if (!$teamId) {
            sendJsonResponse(false, 'Team ID is required.', [], 400);
        }

        $stmt = $db->prepare("UPDATE team_members SET status = 'LEFT' WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$teamId, $user['id']]);

        sendJsonResponse(true, 'You have left the travel team successfully.');
        break;

    case 'remove_member':
        requireLogin();
        $user = getCurrentUser();
        $teamId = intval($_POST['team_id'] ?? ($_GET['team_id'] ?? 0));
        $targetUserId = intval($_POST['user_id'] ?? ($_GET['user_id'] ?? 0));

        if (!$teamId || !$targetUserId) {
            sendJsonResponse(false, 'Team ID and User ID are required.', [], 400);
        }

        if ($user['role'] !== 'organization_admin' && $user['role'] !== 'super_admin') {
            sendJsonResponse(false, 'Only Travel Agency / Admins can remove team members.', [], 403);
        }

        $stmt = $db->prepare("UPDATE team_members SET status = 'REMOVED' WHERE team_id = ? AND user_id = ?");
        $stmt->execute([$teamId, $targetUserId]);

        sendJsonResponse(true, 'Traveler removed from squad successfully.');
        break;

    case 'delete_team':
        requireLogin();
        $user = getCurrentUser();
        $teamId = intval($_POST['team_id'] ?? ($_GET['team_id'] ?? 0));

        if (!$teamId) {
            sendJsonResponse(false, 'Team ID is required.', [], 400);
        }

        if ($user['role'] !== 'organization_admin' && $user['role'] !== 'super_admin') {
            sendJsonResponse(false, 'Only Travel Agency / Admins can delete travel teams.', [], 403);
        }

        $stmt = $db->prepare("UPDATE teams SET status = 'DISBANDED' WHERE id = ?");
        $stmt->execute([$teamId]);

        $memStmt = $db->prepare("UPDATE team_members SET status = 'REMOVED' WHERE team_id = ?");
        $memStmt->execute([$teamId]);

        sendJsonResponse(true, 'Travel Team deleted successfully! It has been removed from all travelers\' dashboards.');
        break;

    case 'send_message':
        requireLogin();
        $user = getCurrentUser();
        $teamId = intval($_POST['team_id'] ?? 0);
        $message = trim($_POST['message'] ?? '');

        if (!$teamId || empty($message)) {
            sendJsonResponse(false, 'Team ID and message text are required.', [], 400);
        }

        // Verify user is a member of the team
        $checkStmt = $db->prepare("SELECT id FROM team_members WHERE team_id = ? AND user_id = ? AND status = 'ACTIVE' LIMIT 1");
        $checkStmt->execute([$teamId, $user['id']]);
        if (!$checkStmt->fetch()) {
            sendJsonResponse(false, 'You must be an active member of this team to send messages.', [], 403);
        }

        $stmt = $db->prepare("INSERT INTO team_messages (team_id, sender_id, message) VALUES (?, ?, ?)");
        $stmt->execute([$teamId, $user['id'], $message]);

        sendJsonResponse(true, 'Message sent to team chat.', ['message_id' => $db->lastInsertId()]);
        break;

    case 'get_messages':
        requireLogin();
        $user = getCurrentUser();
        $teamId = intval($_GET['team_id'] ?? 0);

        if (!$teamId) {
            sendJsonResponse(false, 'Team ID is required.', [], 400);
        }

        // Fetch team messages
        $stmt = $db->prepare("
            SELECT tm.*, u.full_name as sender_name, u.role as sender_role
            FROM team_messages tm
            JOIN users u ON tm.sender_id = u.id
            WHERE tm.team_id = ?
            ORDER BY tm.created_at ASC
        ");
        $stmt->execute([$teamId]);
        $messages = $stmt->fetchAll();

        sendJsonResponse(true, 'Team messages', ['messages' => $messages]);
        break;

    default:
        sendJsonResponse(false, 'Invalid action', [], 400);
        break;
}

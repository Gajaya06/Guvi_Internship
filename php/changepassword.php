<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db/mysql.php';
require_once __DIR__ . '/../db/redis.php';

$session_id = $_POST['session_id'] ?? '';
$currentPw = $_POST['current_password'] ?? '';
$newPw = $_POST['new_password'] ?? '';

if (!$session_id || !$currentPw || !$newPw) {
    echo json_encode(["status" => "error", "message" => "Missing fields"]);
    exit;
}

// Validate session
$uid = $redis->get("sess:" . $session_id);
if (!$uid) {
    echo json_encode(["status" => "error", "message" => "Invalid session"]);
    exit;
}

// Fetch current password
$stmt = $conn->prepare("SELECT password FROM users WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $uid);
$stmt->execute();
$stmt->bind_result($hash);
$stmt->fetch();
$stmt->close();

if (!$hash) {
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

if (!password_verify($currentPw, $hash)) {
    echo json_encode(["status" => "error", "message" => "Current password incorrect"]);
    exit;
}

// Update new password
$newHashed = password_hash($newPw, PASSWORD_DEFAULT);
$stmt2 = $conn->prepare("UPDATE users SET password = ? WHERE id = ? LIMIT 1");
$stmt2->bind_param("si", $newHashed, $uid);
$stmt2->execute();
$stmt2->close();

echo json_encode(["status" => "success", "message" => "Password updated"]);
exit;

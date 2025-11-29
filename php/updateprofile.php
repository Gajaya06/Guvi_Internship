<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db/mongo.php';
require_once __DIR__ . '/../db/redis.php';
require_once __DIR__ . '/../db/mysql.php';

$session = $_POST['session_id'] ?? '';

if (!$session) {
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

$uid = $redis->get("sess:" . $session);
if (!$uid) {
    echo json_encode(['status'=>'error','message'=>'Invalid session']);
    exit;
}

$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');

try {
    // MongoDB update
    $profiles->updateOne(
        ['uid' => (int)$uid],
        ['$set' => ['name' => $name, 'phone' => $phone]],
        ['upsert' => true]
    );

    // MySQL update
    $stmt = $conn->prepare("UPDATE users SET name = ?, phone = ? WHERE id = ? LIMIT 1");
    $stmt->bind_param("ssi", $name, $phone, $uid);
    $stmt->execute();
    $stmt->close();

    echo json_encode(['status' => 'success', 'message' => 'Profile updated']);
    exit;

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
    exit;
}

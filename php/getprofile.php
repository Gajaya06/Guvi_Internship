<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db/mysql.php';
require_once __DIR__ . '/../db/mongo.php';
require_once __DIR__ . '/../db/redis.php';

$session = $_POST['session_id'] ?? '';
if (!$session) {
    http_response_code(401);
    echo json_encode(['status' => 'error', 'message' => 'Not authenticated']);
    exit;
}

try {
    if (!isset($redis)) {
        echo json_encode(['status' => 'error', 'message' => 'Server error']);
        exit;
    }
   $uid = $redis->get("sess:" . $session);

    if (!$uid) {
        http_response_code(401);
        echo json_encode(['status' => 'error', 'message' => 'Session expired or invalid']);
        exit;
    }

    // Fetch MySQL account basic info
    $stmt = $conn->prepare("SELECT id, name, email, phone, created_at FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $uid);
    $stmt->execute();
    $res = $stmt->get_result();
    $user = $res->fetch_assoc();
    $stmt->close();

    if (!$user) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        exit;
    }

    // Fetch MongoDB profile
    $profile = $profiles->findOne(['uid' => (int) $uid]);
    $profileArray = $profile ? json_decode(json_encode($profile), true) : [];

    // Merge and return (omit sensitive fields)
    $out = [
        'status' => 'success',
        'uid' => (int) $user['id'],
        'name' => $user['name'],
        'email' => $user['email'],
        'phone' => $user['phone'],
        'created_at' => $user['created_at'],
        'profile' => $profileArray
    ];

    echo json_encode($out);
    exit;

} catch (Exception $e) {
    error_log("GetProfile error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
    exit;
}

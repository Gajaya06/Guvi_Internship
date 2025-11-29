<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db/mysql.php';
require_once __DIR__ . '/../db/redis.php';

$identifier = trim($_POST['identifier'] ?? '');
$password = $_POST['password'] ?? '';

if (!$identifier || !$password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing credentials']);
    exit;
}

try {
    // Fetch user by email OR phone
    $stmt = $conn->prepare("SELECT id, password FROM users WHERE email = ? OR phone = ? LIMIT 1");
    $stmt->bind_param("ss", $identifier, $identifier);
    $stmt->execute();
    $stmt->store_result();
    if ($stmt->num_rows === 0) {
        echo json_encode(['status' => 'error', 'message' => 'User not found']);
        $stmt->close();
        exit;
    }
    $stmt->bind_result($uid, $hashed_pw);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($password, $hashed_pw)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid password']);
        exit;
    }

    // Generate secure session id and store in Redis
    $session_id = bin2hex(random_bytes(16)); // 32 hex chars
    // store mapping session_id => uid for 7 days (example)
    $ttl_seconds = 7 * 24 * 3600;
    if (!isset($redis)) {
        // If redis unavailable, fail securely
        error_log("Redis not available for login");
        echo json_encode(['status' => 'error', 'message' => 'Server error']);
        exit;
    }
    $redis->setex("sess:" . $session_id, $ttl_seconds, (string) $uid);

    // Optionally store metadata: login time
    $meta = json_encode(['uid' => (int) $uid, 'login_at' => time()]);
    $redis->setex("sessmeta:" . $session_id, $ttl_seconds, $meta);

    echo json_encode(['status' => 'success', 'session_id' => $session_id]);
    exit;

} catch (Exception $e) {
    error_log("Login error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
    exit;
}

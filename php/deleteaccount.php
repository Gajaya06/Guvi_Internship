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
      echo json_encode(['status' => 'error', 'message' => 'Invalid session']);
      exit;
   }

   // 1) Delete from MySQL
   $stmt = $conn->prepare("DELETE FROM users WHERE id = ? LIMIT 1");
   $stmt->bind_param("i", $uid);
   $stmt->execute();
   $stmt->close();

   // 2) Delete from MongoDB
   $profiles->deleteOne(['uid' => (int) $uid]);

   // 3) Remove session(s) in Redis (all keys matching prefix could exist)
   // if you only used sess:<sessionid> then delete that specific key:
   $redis->del("sess:" . $session);
   $redis->del("sessmeta:" . $session);

   echo json_encode(['status' => 'success', 'message' => 'Account deleted']);

} catch (Exception $e) {
   error_log("DeleteAccount error: " . $e->getMessage());
   http_response_code(500);
   echo json_encode(['status' => 'error', 'message' => 'Server error']);
   exit;
}

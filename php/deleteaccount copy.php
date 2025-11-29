<?php
require "../db/mysql.php";
require "../db/mongo.php";
require "php/redis.php";

$session_id = $_POST['session_id'] ?? '';

if (!$session_id) {
    echo json_encode(["status" => "error", "message" => "Missing session"]);
    exit;
}

// Validate session
$uid = $redis->get($session_id);
if (!$uid) {
    echo json_encode(["status" => "error", "message" => "Invalid session"]);
    exit;
}

/* --------------------------
   DELETE from MySQL
---------------------------*/
$d1 = $conn->prepare("DELETE FROM users WHERE id=?");
$d1->bind_param("i", $uid);
$d1->execute();
$d1->close();

/* --------------------------
   DELETE from MongoDB
---------------------------*/
$collection->deleteOne(["uid" => intval($uid)]);

/* --------------------------
   DELETE session from Redis
---------------------------*/
$redis->del($session_id);

echo json_encode(["status" => "success", "message" => "Account deleted"]);
?>

<?php
require "../db/mysql.php";
require "redis.php";

$identifier = $_POST['identifier']; // email OR phone
$password = $_POST['password'];

$stmt = $conn->prepare("SELECT id, password FROM users WHERE email=? OR phone=?");
$stmt->bind_param("ss", $identifier, $identifier);
$stmt->execute();
$stmt->bind_result($uid, $hashed_pw);
$stmt->fetch();

if(!$uid){
    echo json_encode(["status" => "error", "message" => "User not found"]);
    exit;
}

if(password_verify($password, $hashed_pw)){

    $session_id = bin2hex(random_bytes(16));

    $redis->set($session_id, $uid);

    echo json_encode([
        "status" => "success",
        "session_id" => $session_id
    ]);

} else {
    echo json_encode(["status" => "error", "message" => "Invalid Password"]);
}
?>

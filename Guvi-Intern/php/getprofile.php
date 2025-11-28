<?php
require "../db/mongo.php";
require "redis.php";

$session_id = $_POST['session_id'];

$uid = $redis->get($session_id);

if(!$uid){
    echo json_encode(["error" => "Invalid session"]);
    exit;
}

$data = $collection->findOne(["uid" => intval($uid)]);

echo json_encode($data);
?>

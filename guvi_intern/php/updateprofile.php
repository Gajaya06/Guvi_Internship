<?php
require "../db/mongo.php";
require "redis.php";

$session_id = $_POST['session_id'];
$uid = $redis->get($session_id);

if(!$uid){
    echo "Invalid Session";
    exit;
}

$collection->updateOne(
    ["uid" => intval($uid)],
    ['$set' => [
        "email" => $_POST['email'],
        "phone" => $_POST['phone'],
        "altphone" => $_POST['altphone'],
        "age" => $_POST['age'],
        "dob" => $_POST['dob'],
        "contact" => $_POST['contact']
    ]],
    ['upsert' => true]
);

echo "Profile Updated Successfully";
?>

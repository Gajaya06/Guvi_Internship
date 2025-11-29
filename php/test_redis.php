<?php
$redis = new Redis();
$redis->connect('127.0.0.1', 6379);
echo "Connected!<br>";

$redis->set("name", "Gaja");
echo $redis->get("name");
?>

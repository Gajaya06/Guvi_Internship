<?php
$redis = null;
if (class_exists('Redis')) {
    try {
        $redis = new Redis();
        $redis->connect('127.0.0.1', 6379);
    } catch (Exception $e) {
        error_log("Redis connect failed: " . $e->getMessage());
        $redis = null;
    }
}

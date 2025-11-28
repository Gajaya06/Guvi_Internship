<?php
// backend/db/mongo.php
$config = require __DIR__ . '/../config.php';
$mongo = $config['mongo'];

require_once __DIR__ . '/vendor/autoload.php'; // Assuming you use Composer for MongoDB driver

try {
    $client = new MongoDB\Client($mongo['uri']);
    $database = $client->selectDatabase($mongo['db']);
    $collection = $database->selectCollection('profiles'); // Assuming the collection is named 'profiles'

} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'MongoDB connection failed']);
    exit;
}
?>
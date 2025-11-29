<?php
require_once __DIR__ . '/../vendor/autoload.php'; // make sure composer install was run

use MongoDB\Client as MongoClient;

// --- CONFIGURE THESE ---
$mongo_uri = 'mongodb://127.0.0.1:27017';
$mongo_dbname = 'guvi_intern';
// -----------------------

try {
    $mclient = new MongoClient($mongo_uri);
    $mdb = $mclient->selectDatabase($mongo_dbname);
    // profile collection
    $profiles = $mdb->selectCollection('profiles');
} catch (Exception $e) {
    error_log("MongoDB error: " . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'MongoDB unavailable']);
    exit;
}

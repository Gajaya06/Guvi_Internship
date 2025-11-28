<?php
// backend/db/mysql.php

// The configuration file is expected to be in a directory above the current file (e.g., ../config.php)
$config = require __DIR__ . '/../config.php';
$mysql = $config['mysql'];

// Create connection
$conn = new mysqli(
    $mysql['host'], 
    $mysql['user'], 
    $mysql['pass'], 
    $mysql['dbname']
);

// Check connection
if ($conn->connect_error) {
    // Log the error (optional, but good practice)
    // error_log("MySQL Connection Failed: " . $conn->connect_error);
    
    // Respond with a JSON error and exit
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Database connection failed']);
    exit;
}

// Set character set to ensure proper handling of data (as defined in config)
if (!$conn->set_charset($mysql['charset'])) {
    // Handle charset setting error
    error_log("Error loading character set {$mysql['charset']}: " . $conn->error);
}

// The connection object is now available as $conn for use in other scripts.
<?php
// --- CONFIGURE THESE ---
$db_host = '127.0.0.1';
$db_user = 'root';
$db_pass = 'admin';
$db_name = 'guvi_intern';
$db_port = 3306;
// -----------------------

$conn = new mysqli($db_host, $db_user, $db_pass, $db_name, $db_port);

if ($conn->connect_errno) {
    // Log real error server-side; return safe message
    error_log("MySQL connect error: " . $conn->connect_error);
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['status' => 'error', 'message' => 'Database unavailable']);
    exit;
}

// Set charset
$conn->set_charset('utf8mb4');

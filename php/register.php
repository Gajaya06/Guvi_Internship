<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../db/mysql.php';
require_once __DIR__ . '/../db/mongo.php';

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$password = $_POST['password'] ?? '';


if (!$name || !$email || !$phone || !$password) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required fields']);
    exit;
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid email']);
    exit;
}

// Ensure phone is clean (digits only)
$phone_clean = preg_replace('/\D+/', '', $phone);
if (strlen($phone_clean) < 7) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid phone number']);
    exit;
}



try {
    // 1) Check uniqueness (email or phone)
    $checkStmt = $conn->prepare("SELECT id FROM users WHERE email = ? OR phone = ? LIMIT 1");
    $checkStmt->bind_param("ss", $email, $phone_clean);
    $checkStmt->execute();
    $checkStmt->store_result();
    if ($checkStmt->num_rows > 0) {
        echo json_encode(['status' => 'error', 'message' => 'Email or phone already exists']);
        $checkStmt->close();
        exit;
    }
    $checkStmt->close();

    // 2) Insert into MySQL
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $ins = $conn->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
    $ins->bind_param("ssss", $name, $email, $phone_clean, $hashed);

    if (!$ins->execute()) {
        // duplicate or other DB error
        error_log("MySQL insert error: " . $ins->error);
        echo json_encode(['status' => 'error', 'message' => 'Registration failed']);
        $ins->close();
        exit;
    }

    $uid = $ins->insert_id;
    $ins->close();

    // 3) Insert profile into MongoDB
    $doc = [
        'uid' => (int) $uid,
        'name' => $name,
        'email' => $email,
        'phone' => $phone_clean,
        'altphone' => '',
        'age' => '',
        'dob' => '',
        'contact' => '',
        'avatar' => ''
        // 'created_at' => new MongoDB\BSON\UTCDateTime()
    ];
    $profiles->insertOne($doc);

    echo json_encode(['status' => 'success', 'message' => 'Registration successful']);

} catch (Exception $e) {
    error_log("Register error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Server error']);
    exit;
}
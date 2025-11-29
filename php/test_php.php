<?php
require "../db/mysql.php";

$name = "test";
$email = "test@mail.com";
$phone = "1234567890";
$password = password_hash("123456", PASSWORD_BCRYPT);

$stmt = $conn->prepare("INSERT INTO users (name, email, phone, password, created_at) VALUES (?, ?, ?, ?, NOW())");
$stmt->bind_param("ssss", $name, $email, $phone, $password);

if ($stmt->execute()) {
    echo "OK Inserted";
} else {
    echo "ERROR: " . $stmt->error;
}
?>

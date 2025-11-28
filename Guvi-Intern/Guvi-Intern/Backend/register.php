<?php
require "../db/mysql.php";
require "../db/mongo.php";

$name = $_POST['name'];
$email = $_POST['email'];
$phone = $_POST['phone'];
$altphone = $_POST['altphone'];
$password = password_hash($_POST['password'], PASSWORD_BCRYPT);

// Insert into MySQL (only account info)
$stmt = $conn->prepare("INSERT INTO users (name, email, phone, password) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $name, $email, $phone, $password);

if($stmt->execute()) {

    $uid = $conn->insert_id;

    // Create MongoDB Profile Document
    $collection->insertOne([
        "uid" => $uid,
        "email" => $email,
        "phone" => $phone,
        "altphone" => $altphone,
        "age" => "",
        "dob" => "",
        "contact" => ""
    ]);

    echo "Registration Successful";

} else {
    echo "Email or Phone Already Exists";
}
?>

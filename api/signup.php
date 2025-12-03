<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../core/db.php";   
require_once __DIR__ . "/../vendor/autoload.php";

$data = json_decode(file_get_contents("php://input"), true);

$name = trim($data["name"] ?? "");
$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";
$role = strtolower(trim($data["role"] ?? ""));

$allowedRoles = ["admin","agent","driver"];

if (!$name || !$email || !$password || !in_array($role, $allowedRoles)) {
    echo json_encode([
        "status" => "error",
        "message" => "All fields are required and role must be admin/agent/driver"
    ]);
    exit;
}


$stmt = $pdo->prepare("SELECT id FROM users WHERE email=?");
$stmt->execute([$email]);

if ($stmt->rowCount() > 0) {
    echo json_encode([
        "status" => "error",
        "message" => "Email already registered"
    ]);
    exit;
}


$stmt = $pdo->prepare("
    INSERT INTO users (name, email, password, role) 
    VALUES (?, ?, ?, ?)
");
$stmt->execute([
    $name,
    $email,
    password_hash($password, PASSWORD_DEFAULT),
    $role
]);

echo json_encode([
    "status" => "success",
    "message" => "Signup successful",
    "role" => $role
]);

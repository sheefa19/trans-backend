<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../core/db.php";
require_once __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;

$data = json_decode(file_get_contents("php://input"), true);

$email = trim($data["email"] ?? "");
$password = $data["password"] ?? "";

if (!$email || !$password) {
    echo json_encode(["status"=>"error","message"=>"Email and password required"]);
    exit;
}

// FETCH USER
$stmt = $pdo->prepare("SELECT * FROM users WHERE email=? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user || !password_verify($password, $user["password"])) {
    echo json_encode(["status"=>"error","message"=>"Invalid credentials"]);
    exit;
}

$secret = $_ENV["SECRET_KEY"];
$exp = time() + intval($_ENV["TOKEN_EXPIRY"]);

// JWT PAYLOAD
$payload = [
    "iss" => "localhost",
    "iat" => time(),
    "exp" => $exp,
    "data" => [
        "id"    => $user["id"],
        "email" => $user["email"],
        "name"  => $user["name"],
        "role"  => $user["role"]
    ]
];

$jwt = JWT::encode($payload, $secret, 'HS256');

echo json_encode([
    "status"=>"success",
    "message"=>"Login successful",
    "token"=>$jwt,
    "role"=>$user["role"],
    "expires_in"=>$exp
]);

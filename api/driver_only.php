<?php
require_once __DIR__ . "/verify_token.php";

$decoded = verifyJwtFromHeader("driver");

echo json_encode([
    "status" => "success",
    "message" => "Welcome Driver: " . $decoded->data->email
]);

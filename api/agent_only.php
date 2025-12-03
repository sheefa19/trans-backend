<?php
require_once __DIR__ . "/verify_token.php";

$decoded = verifyJwtFromHeader("agent");

echo json_encode([
    "status" => "success",
    "message" => "Welcome Agent: " . $decoded->data->email
]);


<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../core/db.php";
require_once __DIR__ . "/../../api/verify_token.php";


loadEnv(__DIR__ . "/../.env");

try {
    $decoded = verifyJwtFromHeader("agent");
    $userEmail = strtolower($decoded->data->email);
} catch (Exception $e) {
    echo json_encode(["status" => "error", "message" => $e->getMessage()]);
    exit;
}

$raw = file_get_contents("php://input");
$input = json_decode($raw, true);
if (!$input) $input = $_POST;

// Required fields validation
$required = ["pickup", "destination", "weight", "freight", "pickup_time", "vehicle_type"];
foreach ($required as $field) {
    if (!isset($input[$field]) || empty(trim($input[$field]))) {
        echo json_encode(["status" => "error", "message" => "$field is required"]);
        exit;
    }
}

$newLoadId = "TC" . rand(10000, 99999);

$sql = "INSERT INTO loads (load_id, pickup, destination, weight, freight, pickup_time, vehicle_type, 
        additional_charges, special_instructions, posted_by) 
        VALUES (:load_id, :pickup, :destination, :weight, :freight, :pickup_time, :vehicle_type,
        :additional_charges, :special_instructions, :posted_by)";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    ":load_id" => $newLoadId,
    ":pickup" => trim($input["pickup"]),
    ":destination" => trim($input["destination"]),
    ":weight" => trim($input["weight"]),
    ":freight" => trim($input["freight"]),
    ":pickup_time" => trim($input["pickup_time"]),
    ":vehicle_type" => trim($input["vehicle_type"]),
    ":additional_charges" => $input["additional_charges"] ?? "",
    ":special_instructions" => $input["special_instructions"] ?? "",
    ":posted_by" => $userEmail
]);

echo json_encode([
    "status" => "success",
    "message" => "Load posted successfully",
    "load_id" => $newLoadId
]);

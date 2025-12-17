<?php
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

$sql = "SELECT * FROM loads WHERE posted_by = :email ORDER BY id DESC LIMIT 1";
$stmt = $pdo->prepare($sql);
$stmt->execute([":email" => $userEmail]);
$latestLoad = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$latestLoad) {
    echo json_encode([
        "status" => "success",
        "message" => "No loads posted yet",
        "data" => []
    ]);
    exit;
}
00
$responseData = [
    "load_id" => $latestLoad["load_id"],
    "pickup" => $latestLoad["pickup"],
    "destination" => $latestLoad["destination"],
    "weight" => $latestLoad["weight"],
    "freight" => "₹" . $latestLoad["freight"],
    "pickup_time" => $latestLoad["pickup_time"],
    "vehicle_type" => $latestLoad["vehicle_type"],
    "driver_matches" => $latestLoad["driver_matches"],
    "avg_response" => $latestLoad["avg_response"],
    "tracking_ready" => (bool)$latestLoad["tracking_ready"]
];

echo json_encode([
    "status" => "success",
    "message" => "Latest load fetched",
    "data" => $responseData
], JSON_PRETTY_PRINT);

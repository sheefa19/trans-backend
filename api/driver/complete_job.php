<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../core/db.php";
require_once __DIR__ . "/../../api/verify_token.php";

$decoded = verifyJwtFromHeader("driver");
$driver_id = (int)$decoded->data->id;

$input = json_decode(file_get_contents("php://input"), true);

if (!isset($input['load_id'])) {
    echo json_encode(["status"=>"error","message"=>"load_id required"]);
    exit;
}

$load_code = $input['load_id']; // TC10798

/* Find internal ID */
$stmt = $pdo->prepare("SELECT id, status FROM loads WHERE load_id=?");
$stmt->execute([$load_code]);
$load = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$load) {
    echo json_encode(["status"=>"error","message"=>"Invalid load"]);
    exit;
}

if ($load['status'] !== 'active') {
    echo json_encode([
        "status"=>"error",
        "message"=>"Only active jobs can be completed"
    ]);
    exit;
}

/* Complete job */
$stmt = $pdo->prepare("
    UPDATE loads
    SET status='completed',
        completed_at = NOW()
    WHERE id=? AND driver_id=?
");
$stmt->execute([$load['id'], $driver_id]);

if ($stmt->rowCount() === 0) {
    echo json_encode([
        "status"=>"error",
        "message"=>"Unauthorized or already completed"
    ]);
    exit;
}

echo json_encode([
    "status"=>"success",
    "message"=>"Job completed successfully"
]);

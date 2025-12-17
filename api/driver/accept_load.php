<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../core/db.php";
require_once __DIR__ . "/../../api/verify_token.php";

$decoded = verifyJwtFromHeader("driver");
$driver_id = $decoded->data->id;

$input = json_decode(file_get_contents("php://input"), true);

if (empty($input['load_id'])) {
    echo json_encode([
        "status" => "error",
        "message" => "load_id required"
    ]);
    exit;
}

$load_id = $input['load_id']; // TC10798

try {
    $stmt = $pdo->prepare("
        UPDATE loads
        SET driver_id = ?, status = 'active'
        WHERE load_id = ? AND driver_id IS NULL
    ");
    $stmt->execute([$driver_id, $load_id]);

    if ($stmt->rowCount() === 0) {
        echo json_encode([
            "status" => "error",
            "message" => "Load already accepted or invalid"
        ]);
        exit;
    }

    echo json_encode([
        "status" => "success",
        "message" => "Load accepted successfully"
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Database error"
    ]);
}

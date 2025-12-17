<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../core/db.php";
require_once __DIR__ . "/../../api/verify_token.php";

verifyJwtFromHeader("driver");

try {
    $stmt = $pdo->prepare("
        SELECT 
            load_id,
            pickup,
            destination,
            weight,
            freight,
            pickup_time,
            vehicle_type,
            status,
            posted_by
        FROM loads
        WHERE driver_id IS NULL
          AND status = 'upcoming'
        ORDER BY created_at DESC
    ");

    $stmt->execute();
    $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "data" => $loads
    ]);

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "DB error"
    ]);
}

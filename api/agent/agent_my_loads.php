<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../core/db.php";
require_once __DIR__ . "/../../api/verify_token.php";


$decoded = verifyJwtFromHeader("agent");
$agent_email = $decoded->data->email; 

try {


       //LOAD COUNTS
 
    $countStmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(status = 'active') AS active,
            SUM(status = 'upcoming') AS pending,
            SUM(status = 'completed') AS completed
        FROM loads
        WHERE posted_by = ?
    ");
    $countStmt->execute([$agent_email]);
    $counts = $countStmt->fetch(PDO::FETCH_ASSOC);

   
//       LOAD LIST

    $stmt = $pdo->prepare("
        SELECT
            load_id,
            pickup,
            destination,
            freight,
            status,
            driver_id,
            created_at
        FROM loads
        WHERE posted_by = ?
        ORDER BY created_at DESC
    ");
    $stmt->execute([$agent_email]);
    $loads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "success",
        "summary" => [
            "total"     => (int)$counts['total'],
            "active"    => (int)$counts['active'],
            "pending"   => (int)$counts['pending'],
            "completed" => (int)$counts['completed']
        ],
        "loads" => $loads
    ], JSON_PRETTY_PRINT);

} catch (Exception $e) {
    echo json_encode([
        "status" => "error",
        "message" => $e->getMessage()
    ]);
}

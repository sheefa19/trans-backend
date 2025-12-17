<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../core/db.php";
require_once __DIR__ . "/../../api/verify_token.php";

//auth
$decoded = verifyJwtFromHeader("driver");
$driver_id = (int)$decoded->data->id;


//TOTAL EARNINGS (COMPLETED)

$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(CAST(freight AS UNSIGNED)),0)
    FROM loads
    WHERE driver_id = ? AND status = 'completed'
");
$stmt->execute([$driver_id]);
$total_earnings = (float)$stmt->fetchColumn();


   //AVERAGE RATING
$stmt = $pdo->prepare("
    SELECT ROUND(AVG(rating),1)
    FROM driver_ratings
    WHERE driver_id = ?
");
$stmt->execute([$driver_id]);
$avg_rating = (float)($stmt->fetchColumn() ?? 0);


  // ON TIME RATE
$stmt = $pdo->prepare("
    SELECT 
        COUNT(*) AS total,
        SUM(completed_on_time) AS on_time
    FROM loads
    WHERE driver_id = ? AND status = 'completed'
");
$stmt->execute([$driver_id]);
$timing = $stmt->fetch(PDO::FETCH_ASSOC);

$on_time_rate = ($timing['total'] > 0)
    ? round(($timing['on_time'] / $timing['total']) * 100) . "%"
    : "0%";


 //  COMPLETED JOBS LIST
$stmt = $pdo->prepare("
    SELECT 
        id,
        load_id,
        pickup,
        destination,
        freight,
        distance_km,
        duration_minutes,
        posted_by,
        completed_at
    FROM loads
    WHERE driver_id = ? AND status = 'completed'
    ORDER BY completed_at DESC
");
$stmt->execute([$driver_id]);
$jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);


  // TOP SHIPPER
$stmt = $pdo->prepare("
    SELECT posted_by, COUNT(*) AS total
    FROM loads
    WHERE driver_id = ? AND status = 'completed'
    GROUP BY posted_by
    ORDER BY total DESC
    LIMIT 1
");
$stmt->execute([$driver_id]);
$shipper = $stmt->fetch(PDO::FETCH_ASSOC);


 //  RESPONSE
echo json_encode([
    "status" => "success",
    "summary" => [
        "total_earnings"     => $total_earnings,
        "average_rating"     => $avg_rating,
        "on_time_rate"       => $on_time_rate,
        "top_shipper"        => $shipper['posted_by'] ?? "N/A",
        "top_shipper_loads"  => $shipper['total'] ?? 0
    ],
    "jobs" => $jobs
], JSON_PRETTY_PRINT);

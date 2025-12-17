<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../core/db.php";
require_once __DIR__ . "/../../api/verify_token.php";


//   AUTH (AGENT)

try {
    $decoded = verifyJwtFromHeader("agent");
    $agentEmail = strtolower($decoded->data->email);
} catch (Exception $e) {
    echo json_encode(["status"=>"error","message"=>"Unauthorized"]);
    exit;
}


//   TOTAL REVENUE

$stmt = $pdo->prepare("
    SELECT IFNULL(SUM(CAST(freight AS UNSIGNED)),0)
    FROM loads
    WHERE posted_by=? AND status='completed'
");
$stmt->execute([$agentEmail]);
$total_revenue = (int)$stmt->fetchColumn();


//   COMPLETED JOBS

$stmt = $pdo->prepare("
    SELECT COUNT(*)
    FROM loads
    WHERE posted_by=? AND status='completed'
");
$stmt->execute([$agentEmail]);
$completed_jobs = (int)$stmt->fetchColumn();


//   DRIVER PARTNERS

$stmt = $pdo->prepare("
    SELECT COUNT(DISTINCT driver_id)
    FROM loads
    WHERE posted_by=? AND driver_id IS NOT NULL
");
$stmt->execute([$agentEmail]);
$driver_partners = (int)$stmt->fetchColumn();


//   AVG COMMISSION (3.5%)

$avg_commission = ($completed_jobs > 0)
    ? round(($total_revenue * 0.035) / $completed_jobs)
    : 0;

//   WEEKLY REVENUE & JOBS

$stmt = $pdo->prepare("
    SELECT 
        DAYNAME(created_at) AS day,
        COUNT(*) AS jobs,
        SUM(CAST(freight AS UNSIGNED)) AS revenue
    FROM loads
    WHERE posted_by=? AND status='completed'
      AND created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
    GROUP BY DAY(created_at)
    ORDER BY created_at
");
$stmt->execute([$agentEmail]);
$weekly_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);


//   RECENT ACTIVITY (LAST 5)

$stmt = $pdo->prepare("
    SELECT 
        status,
        pickup,
        destination,
        freight,
        created_at
    FROM loads
    WHERE posted_by=?
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([$agentEmail]);
$activityRaw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$recent_activity = [];
foreach ($activityRaw as $row) {
    $recent_activity[] = [
        "message" => match($row['status']) {
            'completed' => "Job completed {$row['pickup']} → {$row['destination']}",
            'active'    => "Load is active",
            default     => "New load posted"
        },
        "amount" => "₹" . number_format((int)$row['freight']),
        "time" => $row['created_at']
    ];
}


//   FINAL RESPONSE

echo json_encode([
    "status" => "success",
    "cards" => [
        "total_revenue"    => $total_revenue,
        "completed_jobs"   => $completed_jobs,
        "driver_partners"  => $driver_partners,
        "avg_commission"   => $avg_commission
    ],
    "weekly_trend" => $weekly_trend,
    "recent_activity" => $recent_activity
], JSON_PRETTY_PRINT);

<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../../core/db.php";
require_once __DIR__ . "/../../api/verify_token.php";


$decoded = verifyJwtFromHeader("agent");
$userEmail = strtolower($decoded->data->email);

try {

    //LOAD COUNTS 

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loads WHERE posted_by=? AND status='active'");
    $stmt->execute([$userEmail]);
    $activeLoads = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loads WHERE posted_by=? AND status='completed'");
    $stmt->execute([$userEmail]);
    $completedLoads = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM loads WHERE posted_by=? AND status='upcoming'");
    $stmt->execute([$userEmail]);
    $upcomingLoads = (int)$stmt->fetchColumn();

    // REVENUE

    $stmt = $pdo->prepare("
        SELECT COALESCE(SUM(freight),0)
        FROM loads
        WHERE posted_by=? AND status='completed'
    ");
    $stmt->execute([$userEmail]);
    $revenueTotal = (float)$stmt->fetchColumn();
    $revenueFormatted = "₹" . number_format($revenueTotal, 2);

    //ON-TIME RATE 
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(*) AS total,
            SUM(completed_on_time) AS on_time
        FROM loads
        WHERE posted_by=? AND status='completed'
    ");
    $stmt->execute([$userEmail]);
    $onTime = $stmt->fetch(PDO::FETCH_ASSOC);

    $onTimeRate = ($onTime['total'] > 0)
        ? round(($onTime['on_time'] / $onTime['total']) * 100, 1) . "%"
        : "0%";

    //TOP DRIVERS
    $stmt = $pdo->prepare("
        SELECT 
            u.name,
            ROUND(AVG(dr.rating),1) AS avg_rating,
            COUNT(l.id) AS total_loads,
            ROUND((SUM(l.completed_on_time)/COUNT(l.id))*100,1) AS on_time_rate
        FROM loads l
        JOIN users u ON u.id = l.driver_id
        LEFT JOIN driver_ratings dr ON dr.driver_id = u.id
        WHERE l.posted_by=? AND l.status='completed'
        GROUP BY u.id
        ORDER BY avg_rating DESC, total_loads DESC
        LIMIT 5
    ");
    $stmt->execute([$userEmail]);
    $topDrivers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    //RECENT ACTIVITY 
    $stmt = $pdo->prepare("
        SELECT 
            status,
            created_at,
            freight
        FROM loads
        WHERE posted_by=?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmt->execute([$userEmail]);
    $activities = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $recentActivity = [];
    foreach ($activities as $a) {
        $msg = match($a['status']) {
            'completed' => "Job completed – ₹{$a['freight']}",
            'active'    => "Load in transit",
            'upcoming'  => "New load posted",
            default     => "Load updated"
        };

        $recentActivity[] = [
            "message" => $msg,
            "time" => date("d M, h:i A", strtotime($a['created_at'])),
            "type" => $a['status']
        ];
    }

    //UPCOMING LOADS 
    $stmt = $pdo->prepare("
        SELECT id, pickup, destination, freight, vehicle_type, pickup_time, status
        FROM loads
        WHERE posted_by=? AND status='upcoming'
        ORDER BY created_at DESC
    ");
    $stmt->execute([$userEmail]);
    $upcomingLoadsList = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    echo json_encode(["status" => "error", "message" => "Dashboard fetch failed"]);
    exit;
}

//response
$response = [
    "status" => "success",
    "message" => "Dashboard data fetched",
    "data" => [
        "summary" => [
            "active_loads" => $activeLoads,
            "completed_jobs" => $completedLoads,
            "revenue_month" => $revenueFormatted,
            "on_time_rate" => $onTimeRate
        ],
        "charts" => [
            "load_performance" => [
                "active" => $activeLoads,
                "completed" => $completedLoads
            ]
        ],
        "distribution" => [
            "active" => $activeLoads,
            "completed" => $completedLoads,
            "upcoming" => $upcomingLoads
        ],
        "top_drivers" => $topDrivers,
        "recent_activity" => $recentActivity,
        "upcoming_loads" => $upcomingLoadsList
    ]
];

echo json_encode($response, JSON_PRETTY_PRINT);

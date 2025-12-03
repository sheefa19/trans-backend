<?php
header("Content-Type: application/json");

// Load all JSON files
$loads = json_decode(file_get_contents("../data/loads.json"), true);
$drivers = json_decode(file_get_contents("../data/drivers.json"), true);
$revenue = json_decode(file_get_contents("../data/revenue.json"), true);
$activity = json_decode(file_get_contents("../data/activity.json"), true);

// SUMMARY COUNTS
$activeLoads = array_filter($loads, fn($l) => $l['status'] === "active");
$completedLoads = array_filter($loads, fn($l) => $l['status'] === "completed");
$upcomingLoads = array_filter($loads, fn($l) => $l['status'] === "upcoming");

// FINAL DASHBOARD RESPONSE
$response = [
    "welcome" => "Welcome back, Agent!",
    "summary" => [
        "active_loads" => count($activeLoads),
        "completed_jobs" => count($completedLoads),
        "revenue_month" => "₹1,45,500", // static or from formula
        "on_time_rate" => "94.2%"
    ],
    "charts" => [
        "revenue" => $revenue["monthly"],
        "load_performance" => [
            "active" => count($activeLoads),
            "completed" => count($completedLoads)
        ]
    ],
    "distribution" => [
        "pie" => [
            "active" => count($activeLoads),
            "completed" => count($completedLoads),
            "upcoming" => count($upcomingLoads)
        ]
    ],
    "top_drivers" => $drivers,
    "recent_activity" => $activity,
    "upcoming_loads" => array_values($upcomingLoads)
];

echo json_encode($response, JSON_PRETTY_PRINT);

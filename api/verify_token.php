<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

require_once __DIR__ . "/../core/env.php";
loadEnv(__DIR__ . "/../.env");

require_once __DIR__ . "/../vendor/autoload.php";

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

function verifyJwtFromHeader($requiredRole = null) {

   
    $headers = null;
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
    }

    if (!$headers && isset($_SERVER)) {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))));
                $headers[$key] = $value;
            }
        }
    }

    
    $auth = $headers['Authorization'] ?? ($headers['authorization'] ?? null);

    if (!$auth) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Authorization header missing"]);
        exit;
    }

    
    if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Invalid Authorization header format"]);
        exit;
    }

    $token = $matches[1];
    $secret = $_ENV["SECRET_KEY"] ?? "";

    try {
        // Decode JWT
        $decoded = JWT::decode($token, new Key($secret, 'HS256'));

    
        $role = $decoded->data->role ?? null;
        if ($requiredRole && $role !== $requiredRole) {
            http_response_code(403);
            echo json_encode(["status"=>"error","message"=>"Access denied for role: $role"]);
            exit;
        }

        return $decoded;

    } catch (\Firebase\JWT\ExpiredException $e) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Token expired"]);
        exit;
    } catch (Exception $e) {
        http_response_code(401);
        echo json_encode(["status"=>"error","message"=>"Invalid token"]);
        exit;
    }
}

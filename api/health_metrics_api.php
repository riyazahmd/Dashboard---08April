<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
global $pdo;

// Import Database Connection
require_once 'db_connection.php';
// Include the JWT Library
require_once 'libs/JWT.php';
require_once 'libs/Key.php';

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// Define JWT Secret Key
const JWT_SECRET = "your_jwt_secret_key";

// Set CORS Headers
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

// Utility Functions
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Password Hashing and Verification
function hash_password($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verify_password($provided_password, $stored_password) {
    return password_verify($provided_password, $stored_password);
}

// Geocoding Utility
function geocode_address($address) {
    $url = "https://nominatim.openstreetmap.org/search?format=json&q=" . urlencode($address);
    $response = file_get_contents($url);
    return json_decode($response, true);
}

// API Functions
function get_user_info($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE UserID = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user) {
        respond($user);
    } else {
        respond(["error" => "User not found"], 404);
    }
}

function get_all_users() {
    global $pdo;
    $stmt = $pdo->query("SELECT * FROM users");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    respond($users);
}

function create_user($data) {
    global $pdo;
    $stmt = $pdo->prepare("INSERT INTO users (Name, DeviceID, Gender, DateOfBirth, Phone, Address, Email, SubscriptionStatus, PlanName, BloodGroup, Age) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $data['Name'], $data['DeviceID'], $data['Gender'], $data['DateOfBirth'], $data['Phone'], $data['Address'],
        $data['Email'], $data['SubscriptionStatus'], $data['PlanName'], $data['BloodGroup'], $data['Age']
    ]);
    respond(["message" => "User created successfully", "UserID" => $pdo->lastInsertId()]);
}

function update_user($user_id, $data) {
    global $pdo;
    $stmt = $pdo->prepare("UPDATE users SET Name = ?, DeviceID = ?, Gender = ?, DateOfBirth = ?, Phone = ?, Address = ?, Email = ?, SubscriptionStatus = ?, PlanName = ?, BloodGroup = ?, Age = ? WHERE UserID = ?");
    $result = $stmt->execute([
        $data['Name'], $data['DeviceID'], $data['Gender'], $data['DateOfBirth'], $data['Phone'], $data['Address'],
        $data['Email'], $data['SubscriptionStatus'], $data['PlanName'], $data['BloodGroup'], $data['Age'], $user_id
    ]);
    if ($result) {
        respond(["message" => "User updated successfully"]);
    } else {
        respond(["error" => "Failed to update user"], 500);
    }
}

function delete_user($user_id) {
    global $pdo;
    $stmt = $pdo->prepare("DELETE FROM users WHERE UserID = ?");
    $result = $stmt->execute([$user_id]);
    if ($result) {
        respond(["message" => "User deleted successfully"]);
    } else {
        respond(["error" => "Failed to delete user"], 500);
    }
}

// JWT Authentication
function generate_jwt($user_id) {
    $payload = [
        "iss" => "your_issuer",
        "iat" => time(),
        "exp" => time() + (60 * 60), // 1 hour expiration
        "user_id" => $user_id
    ];
    return JWT::encode($payload, JWT_SECRET, 'HS256');
}

function verify_jwt($token) {
    try {
        $decoded = JWT::decode($token, new Key(JWT_SECRET, 'HS256'));
        return (array) $decoded;
    } catch (Exception $e) {
        respond(["error" => "Invalid token"], 401);
    }
}


// Get Weekly Health Metrics
function get_weekly_health_metrics($user_id) {
    global $pdo;

    // Query to fetch health metrics for the last 7 days
    $stmt = $pdo->prepare("
        SELECT 
            Date, BloodPressure, HeartRate, BloodGlucose, OxygenLevel, StressLevel 
        FROM health_metrics 
        WHERE UserID = ? 
          AND Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY Date ASC
    ");
    $stmt->execute([$user_id]);
    $metrics = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($metrics) {
        respond($metrics);
    } else {
        respond(["error" => "No health metrics found for the last 7 days"], 404);
    }
}



// Routing Logic
if (isset($_GET['action'])) {
    $action = $_GET['action'];
    switch ($action) {
        case 'get_user_info':
            $user_id = $_GET['user_id'] ?? null;
            if ($user_id) {
                get_user_info($user_id);
            } else {
                respond(["error" => "User ID is required"], 400);
            }
            break;

        case 'get_all_users':
            get_all_users();
            break;

        case 'create_user':
            $data = json_decode(file_get_contents("php://input"), true);
            create_user($data);
            break;

        case 'update_user':
            $user_id = $_GET['user_id'] ?? null;
            if ($user_id) {
                $data = json_decode(file_get_contents("php://input"), true);
                update_user($user_id, $data);
            } else {
                respond(["error" => "User ID is required"], 400);
            }
            break;

        case 'delete_user':
            $user_id = $_GET['user_id'] ?? null;
            if ($user_id) {
                delete_user($user_id);
            } else {
                respond(["error" => "User ID is required"], 400);
            }
            break;

        default:
            respond(["error" => "Unknown action"], 400);
    }
} else {
    respond(["error" => "No action specified"], 400);
}

/**
 * Fetch weekly blood oxygen data.
 */
function getWeeklyBloodOxygen($userId, $db) {
    $query = "SELECT timestamp, saturation, scd_state FROM blood_oxygen 
              WHERE user_id = ? AND timestamp >= NOW() - INTERVAL 7 DAY ORDER BY timestamp DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    return $data;
}

/**
 * Fetch weekly heart rate data.
 */
function getWeeklyHeartRate($userId, $db) {
    $query = "SELECT ReadingTimestamp AS timestamp, HeartRate FROM optical_hrm 
              WHERE UserID = ? AND ReadingTimestamp >= NOW() - INTERVAL 7 DAY ORDER BY ReadingTimestamp DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    return $data;
}

/**
 * Fetch weekly sleep data.
 */
function getWeeklySleepData($userId, $db) {
    $query = "SELECT timestamp, duration, sleep_quality FROM sleep_data 
              WHERE user_id = ? AND timestamp >= NOW() - INTERVAL 7 DAY ORDER BY timestamp DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    return $data;
}

/**
 * Fetch weekly stress data.
 */
function getWeeklyStressData($userId, $db) {
    $query = "SELECT timestamp, stress_level FROM stress 
              WHERE user_id = ? AND timestamp >= NOW() - INTERVAL 7 DAY ORDER BY timestamp DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    return $data;
}

/**
 * Fetch weekly blood glucose data.
 */
function getWeeklyBloodGlucose($userId, $db) {
    $query = "SELECT Timestamp AS timestamp, BloodGlucoseLevel FROM BloodGlucoseData 
              WHERE UserID = ? AND Timestamp >= NOW() - INTERVAL 7 DAY ORDER BY Timestamp DESC";
    $stmt = $db->prepare($query);
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);
    return $data;
}

// Main API handler
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_weekly_health_metrics') {
    $userId = $_GET['user_id'] ?? null;

    if (!$userId) {
        echo json_encode(['error' => 'User ID is required']);
        exit;
    }

    $response = [
        'blood_oxygen' => getWeeklyBloodOxygen($userId, $db),
        'heart_rate' => getWeeklyHeartRate($userId, $db),
        'sleep_data' => getWeeklySleepData($userId, $db),
        'stress' => getWeeklyStressData($userId, $db),
        'blood_glucose' => getWeeklyBloodGlucose($userId, $db),
    ];

    echo json_encode($response);
} else {
    echo json_encode(['error' => 'Invalid action or request method']);
}

<?php
header("Content-Type: application/json");

// Database connection
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hfbmbhmy_aurmadb", "hfbmbhmy_aurm", "kcry2gb79ff250");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode(["error" => "Could not connect to the database: " . $e->getMessage()]));
}

// Helper function to respond with JSON
function respond($data, $status = 200) {
    http_response_code($status);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

// Functions to handle different actions
function get_user_info($user_id) {
    global $pdo;

    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        respond($user);
    } else {
        respond(["error" => "User not found"], 404);
    }
}

function get_weekly_health_metrics($user_id) {
    global $pdo;

    // Fetch Blood Oxygen Data
    $blood_oxygen_stmt = $pdo->prepare("
        SELECT Date, OxygenLevel 
        FROM blood_oxygen 
        WHERE UserID = ? 
          AND Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY Date ASC
    ");
    $blood_oxygen_stmt->execute([$user_id]);
    $blood_oxygen_data = $blood_oxygen_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Heart Rate Data
    $heart_rate_stmt = $pdo->prepare("
        SELECT Date, HeartRate 
        FROM optical_hrm 
        WHERE UserID = ? 
          AND Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY Date ASC
    ");
    $heart_rate_stmt->execute([$user_id]);
    $heart_rate_data = $heart_rate_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Sleep Data
    $sleep_data_stmt = $pdo->prepare("
        SELECT Date, SleepDuration 
        FROM sleep_data 
        WHERE UserID = ? 
          AND Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY Date ASC
    ");
    $sleep_data_stmt->execute([$user_id]);
    $sleep_data = $sleep_data_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Stress Levels
    $stress_stmt = $pdo->prepare("
        SELECT Date, StressLevel 
        FROM stress 
        WHERE UserID = ? 
          AND Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY Date ASC
    ");
    $stress_stmt->execute([$user_id]);
    $stress_data = $stress_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch Blood Glucose Levels
    $blood_glucose_stmt = $pdo->prepare("
        SELECT Date, GlucoseLevel 
        FROM BloodGlucoseData 
        WHERE UserID = ? 
          AND Date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
        ORDER BY Date ASC
    ");
    $blood_glucose_stmt->execute([$user_id]);
    $blood_glucose_data = $blood_glucose_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Consolidate Data
    $response = [
        "blood_oxygen" => $blood_oxygen_data,
        "heart_rate" => $heart_rate_data,
        "sleep_data" => $sleep_data,
        "stress_levels" => $stress_data,
        "blood_glucose" => $blood_glucose_data
    ];

    // Return Response
    if (!empty($response)) {
        respond($response);
    } else {
        respond(["error" => "No health metrics found for the last 7 days"], 404);
    }
}

// Route the actions
$action = $_GET['action'] ?? null;

switch ($action) {
    case 'get_user_info':
        $user_id = $_GET['user_id'] ?? null;
        if ($user_id) {
            get_user_info($user_id);
        } else {
            respond(["error" => "User ID is required"], 400);
        }
        break;

    case 'get_weekly_health_metrics':
        $user_id = $_GET['user_id'] ?? null;
        if ($user_id) {
            get_weekly_health_metrics($user_id);
        } else {
            respond(["error" => "User ID is required"], 400);
        }
        break;

    default:
        respond(["error" => "Unknown action"], 400);
        break;
}
?>

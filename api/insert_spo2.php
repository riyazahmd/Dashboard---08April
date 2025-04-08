<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Max-Age: 3600");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

include_once "db_connection.php";  // Ensure this file contains your database connection
include_once "jwt/validate_token.php";  // Include JWT validation

// Verify JWT token
$jwt = getBearerToken();
$userID = validateToken($jwt);  // Returns user ID if valid, otherwise false

if (!$userID) {
    http_response_code(401);
    echo json_encode(["status" => "error", "message" => "Unauthorized access."]);
    exit;
}

// Read JSON input
$data = json_decode(file_get_contents("php://input"), true);

// Validate required fields
if (
    empty($data['SpO2']) || 
    empty($data['HR']) || 
    empty($data['DeviceID'])
) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Missing required fields."]);
    exit;
}

// Assign values after sanitization
$SpO2 = filter_var($data['SpO2'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 0, "max_range" => 100]]);
$HR = filter_var($data['HR'], FILTER_VALIDATE_INT, ["options" => ["min_range" => 30, "max_range" => 250]]);
$DeviceID = htmlspecialchars(strip_tags($data['DeviceID']));
$timestamp = date("Y-m-d H:i:s");

// Ensure valid data
if ($SpO2 === false || $HR === false) {
    http_response_code(400);
    echo json_encode(["status" => "error", "message" => "Invalid SpO2 or HR values."]);
    exit;
}

// Insert into database using prepared statements
$query = "INSERT INTO spo2 (Timestamp, SpO2, HR, UserID, DeviceID, UpdatedTimestamp) 
          VALUES (:Timestamp, :SpO2, :HR, :UserID, :DeviceID, :UpdatedTimestamp)";

$stmt = $conn->prepare($query);
$stmt->bindParam(":Timestamp", $timestamp);
$stmt->bindParam(":SpO2", $SpO2);
$stmt->bindParam(":HR", $HR);
$stmt->bindParam(":UserID", $userID);
$stmt->bindParam(":DeviceID", $DeviceID);
$stmt->bindParam(":UpdatedTimestamp", $timestamp);

if ($stmt->execute()) {
    http_response_code(201);
    echo json_encode(["status" => "success", "message" => "SpO2 data inserted successfully."]);
} else {
    http_response_code(500);
    echo json_encode(["status" => "error", "message" => "Database insertion failed."]);
}

?>

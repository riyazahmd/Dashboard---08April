<?php
require 'db_connection.php'; // Include the database connection

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];

    if ($action === 'get_users') {
        fetchUsers();
    } elseif ($action === 'get_user_by_id' && isset($_GET['user_id'])) {
        fetchUserById($_GET['user_id']);
    } else {
        jsonResponse(400, "Invalid API action");
    }
}

function fetchUsers() {
    $conn = getDatabaseConnection();
    $sql = "SELECT UserID, Name, Age, Address, ContactInfo FROM users";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $users = [];
        while ($row = $result->fetch_assoc()) {
            $users[] = $row;
        }
        jsonResponse(200, "Users fetched successfully", $users);
    } else {
        jsonResponse(404, "No users found");
    }

    $conn->close();
}

function fetchUserById($userId) {
    $conn = getDatabaseConnection();
    $stmt = $conn->prepare("SELECT * FROM users WHERE UserID = ?");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        jsonResponse(200, "User fetched successfully", $user);
    } else {
        jsonResponse(404, "User not found");
    }

    $stmt->close();
    $conn->close();
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $action = $_GET['action'];

    switch ($action) {
        case 'get_users':
            fetchUsers();
            break;

        case 'get_user_by_id':
            if (isset($_GET['user_id'])) {
                fetchUserById($_GET['user_id']);
            } else {
                jsonResponse(400, "User ID is required");
            }
            break;

        case 'get_weekly_health_metrics':
            if (isset($_GET['user_id'])) {
                get_weekly_health_metrics($_GET['user_id']);
            } else {
                jsonResponse(400, "User ID is required");
            }
            break;

        default:
            jsonResponse(400, "Unknown action");
            break;
    }
}




?>

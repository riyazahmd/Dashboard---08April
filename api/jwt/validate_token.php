<?php
include_once('/home3/hfbmbhmy/public_html/aurma/dashboard/libs/JWT.php');  // Make sure you have Firebase PHP JWT Library

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

define("SECRET_KEY", "your_secret_key");  // Change this to your secure secret key

function getBearerToken() {
    $headers = getallheaders();
    if (isset($headers["Authorization"])) {
        $matches = [];
        if (preg_match('/Bearer\s(\S+)/', $headers["Authorization"], $matches)) {
            return $matches[1];
        }
    }
    return null;
}

function validateToken($jwt) {
    try {
        if (!$jwt) return false;
        $decoded = JWT::decode($jwt, new Key(SECRET_KEY, "HS256"));
        return $decoded->user_id;
    } catch (Exception $e) {
        return false;
    }
}
?>

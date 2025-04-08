<?php
try {
    $pdo = new PDO("mysql:host=localhost;dbname=hfbmbhmy_aurmadb", "hfbmbhmy_aurm", "kcry2gb79ff250");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	
	// Alias $pdo as $db for compatibility
    $db = $pdo;
	
} catch (PDOException $e) {
    die("Could not connect to the database: " . $e->getMessage());
}
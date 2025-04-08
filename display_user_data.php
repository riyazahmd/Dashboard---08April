<?php
// API endpoint
$apiEndpoint = "https://aurma.health/dashboard/api/health_metrics_api.php?action=get_user_info&user_id=1";

// Fetch data from the API
$response = file_get_contents($apiEndpoint);

// Check if the response is valid
if ($response === FALSE) {
    die("Error fetching data from API.");
}

// Decode the JSON response
$data = json_decode($response, true);

if (!$data || isset($data['error'])) {
    echo "<h2>Individual User Data</h2>";
    echo "<p>No user information found.</p>";
} else {
    echo "<h2>Individual User Data</h2>";

    // Display User Personal Information
    echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 20px;'>";
    echo "<h3>User Information</h3>";
    echo "<p><strong>Name:</strong> " . htmlspecialchars($data['Name']) . "</p>";
    echo "<p><strong>Gender:</strong> " . htmlspecialchars($data['Gender']) . "</p>";
    echo "<p><strong>Date of Birth:</strong> " . htmlspecialchars($data['DateOfBirth']) . "</p>";
    echo "<p><strong>Phone:</strong> " . htmlspecialchars($data['Phone']) . "</p>";
    echo "<p><strong>Address:</strong> " . htmlspecialchars($data['Address']) . "</p>";
    echo "<p><strong>Email:</strong> " . htmlspecialchars($data['Email']) . "</p>";
    echo "<p><strong>Subscription:</strong> " . htmlspecialchars($data['PlanName']) . " (" . htmlspecialchars($data['SubscriptionStatus']) . ")</p>";
    echo "</div>";

    // Health Metrics (static placeholders for now)
    echo "<h3>Health Metrics</h3>";
    echo "<div style='display: flex; flex-wrap: wrap; gap: 10px;'>";

    // Simulated health metrics - Update with real API calls later
    echo "<div style='flex: 1; min-width: 200px; border: 1px solid #ccc; padding: 10px; text-align: center;'>";
    echo "<h4>Blood Pressure</h4>";
    echo "<p>120/80 mmHg</p>";
    echo "</div>";

    echo "<div style='flex: 1; min-width: 200px; border: 1px solid #ccc; padding: 10px; text-align: center;'>";
    echo "<h4>Heart Rate</h4>";
    echo "<p>75 bpm</p>";
    echo "</div>";

    echo "<div style='flex: 1; min-width: 200px; border: 1px solid #ccc; padding: 10px; text-align: center;'>";
    echo "<h4>Blood Glucose</h4>";
    echo "<p>110 mg/dL</p>";
    echo "</div>";

    echo "<div style='flex: 1; min-width: 200px; border: 1px solid #ccc; padding: 10px; text-align: center;'>";
    echo "<h4>Oxygen Level</h4>";
    echo "<p>98%</p>";
    echo "</div>";

    echo "<div style='flex: 1; min-width: 200px; border: 1px solid #ccc; padding: 10px; text-align: center;'>";
    echo "<h4>Stress Level</h4>";
    echo "<p>Low</p>";
    echo "</div>";

    echo "</div>";
}
?>

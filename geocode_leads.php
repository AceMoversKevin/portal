<?php
session_start();
include 'db.php'; // Include database connection

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit;
}
set_time_limit(0); // Sets the maximum execution time to unlimited

function geocode($location) {
    $query = urlencode($location . ', Australia');
    $url = "https://nominatim.openstreetmap.org/search?format=json&countrycodes=au&q=" . $query;

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_USERAGENT, 'ACELeads/1.0'); // Set a custom user agent
    $response = curl_exec($ch);
    curl_close($ch);

    $data = json_decode($response, true);
    return $data[0] ?? null;
}

function geocodeAndSave($lead_id, $pickup, $dropoff) {
    global $conn;

    $pickupResult = geocode($pickup);
    $pickupLatitude = $pickupResult['lat'] ?? null;
    $pickupLongitude = $pickupResult['lon'] ?? null;

    $dropoffResult = geocode($dropoff);
    $dropoffLatitude = $dropoffResult['lat'] ?? null;
    $dropoffLongitude = $dropoffResult['lon'] ?? null;

    $sql = "INSERT INTO lead_geocodes (lead_id, pickup_latitude, pickup_longitude, dropoff_latitude, dropoff_longitude)
            VALUES (?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("idddd", $lead_id, $pickupLatitude, $pickupLongitude, $dropoffLatitude, $dropoffLongitude);
    $stmt->execute();
    $stmt->close();
}

$sql = "SELECT lead_id, pickup, dropoff FROM leads";
$result = $conn->query($sql);

$totalLeads = $result->num_rows;
$processedLeads = 0;

if ($totalLeads > 0) {
    while ($row = $result->fetch_assoc()) {
        $lead_id = $row['lead_id'];
        $pickup = $row['pickup'];
        $dropoff = $row['dropoff'];

        $check_sql = "SELECT 1 FROM lead_geocodes WHERE lead_id = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("i", $lead_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        $check_stmt->close();

        if ($check_result->num_rows == 0) {
            geocodeAndSave($lead_id, $pickup, $dropoff);
        }

        $processedLeads++;
        echo "Processed $processedLeads of $totalLeads leads.<br>";
        flush();
    }
    echo "Geocoding completed for all leads.";
} else {
    echo "No leads found.";
}

$conn->close();
?>
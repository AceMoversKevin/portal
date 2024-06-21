<?php
session_start();
include 'db.php'; // Adjust the path to your database connection file as necessary

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo 'Unauthorized';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $lead_name = $_POST['lead_name'];
    $bedrooms = $_POST['bedrooms'];
    $pickup = $_POST['pickup'];
    $dropoff = $_POST['dropoff'];
    $lead_date = $_POST['lead_date'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $details = $_POST['details'];
    $acceptanceLimit = isset($_POST['acceptanceLimit']) ? $_POST['acceptanceLimit'] : 3;
    $booking_status = isset($_POST['booking_status']) ? $_POST['booking_status'] : 0;
    $isReleased = isset($_POST['isReleased']) ? $_POST['isReleased'] : 0;

    // Validate and sanitize input
    $lead_name = mysqli_real_escape_string($conn, $lead_name);
    $bedrooms = intval($bedrooms);
    $pickup = mysqli_real_escape_string($conn, $pickup);
    $dropoff = mysqli_real_escape_string($conn, $dropoff);
    $lead_date = mysqli_real_escape_string($conn, $lead_date);
    $phone = mysqli_real_escape_string($conn, $phone);
    $email = mysqli_real_escape_string($conn, $email);
    $details = mysqli_real_escape_string($conn, $details);
    $acceptanceLimit = intval($acceptanceLimit);
    $booking_status = intval($booking_status);
    $isReleased = intval($isReleased);

    // Insert the new lead into the database
    $sql = "INSERT INTO leads (lead_name, bedrooms, pickup, dropoff, lead_date, phone, email, details, acceptanceLimit, booking_status, isReleased)
            VALUES ('$lead_name', $bedrooms, '$pickup', '$dropoff', '$lead_date', '$phone', '$email', '$details', $acceptanceLimit, $booking_status, $isReleased)";
    if ($conn->query($sql) === TRUE) {
        echo 'New lead added successfully';
    } else {
        echo 'Error adding lead: ' . $conn->error;
    }
}
?>

<?php
session_start();
require 'db.php'; // Adjust this as necessary

// Ensure the user is logged in and the request is legitimate
if (!isset($_SESSION['user_id']) || $_SESSION['user_id'] != $_POST['user_id']) {
    die("Unauthorized access");
}

$lead_id = $_POST['lead_id'];
$user_id = $_POST['user_id']; // Ensure this matches your session or form
$notes = $_POST['notes'];

// Prepare your SQL statement
$stmt = $conn->prepare("UPDATE user_quotations SET notes = ? WHERE lead_id = ? AND user_id = ?");
$stmt->bind_param("sis", $notes, $lead_id, $user_id);

if ($stmt->execute()) {
    echo "Note saved successfully";
} else {
    echo "An error occurred";
}

$stmt->close();
$conn->close();
?>

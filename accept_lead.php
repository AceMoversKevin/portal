<?php
session_start();
include 'db.php'; // Include your database connection

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// First, check if the user has enough credits
$creditCheckSql = "SELECT credits FROM users WHERE user_id = ?";
$stmt = $conn->prepare($creditCheckSql);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$creditResult = $stmt->get_result();
$userCredits = 0;

if ($row = $creditResult->fetch_assoc()) {
    $userCredits = $row['credits'];
}

if ($userCredits < 10) {
    // Not enough credits, redirect with an error query parameter
    header("Location: index.php?error=notenoughcredits");
    exit;
}


// User has enough credits, proceed to accept the lead
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lead_id'])) {
    $lead_id = $conn->real_escape_string($_POST['lead_id']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        // Deduct credits
        $deductSql = "UPDATE users SET credits = credits - 10 WHERE user_id = ?";
        $deductStmt = $conn->prepare($deductSql);
        $deductStmt->bind_param("s", $user_id);
        $deductStmt->execute();

        // Insert into user_quotations table
        $insertSql = "INSERT INTO user_quotations (user_id, lead_id, accepted_at) VALUES (?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("si", $user_id, $lead_id);

        if (!$insertStmt->execute()) {
            // Handle insertion error
            throw new Exception("Error accepting lead.");
        }

        // Commit transaction
        $conn->commit();

        // Update session credits
        $_SESSION['credits'] -= 10;

        // Redirect back to the leads page with a success message
        header("Location: userleads.php?success=1");
    } catch (Exception $e) {
        $conn->rollback();
        echo "An error occurred: " . $e->getMessage();
    }
} else {
    // Redirect if the request method is not POST or lead_id is not set
    header("Location: index.php");
}
?>

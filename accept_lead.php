<?php
session_start();
require 'db.php'; // Adjust this as necessary
require 'PHPMailer-master/src/Exception.php';
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email']; // Assuming email is stored in the session

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
    header("Location: index.php?error=notenoughcredits");
    exit;
}

// User has enough credits, proceed to accept the lead
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lead_id'])) {
    $lead_id = $conn->real_escape_string($_POST['lead_id']);

    // Begin transaction
    $conn->begin_transaction();

    try {
        $deductSql = "UPDATE users SET credits = credits - 10 WHERE user_id = ?";
        $deductStmt = $conn->prepare($deductSql);
        $deductStmt->bind_param("s", $user_id);
        $deductStmt->execute();

        $insertSql = "INSERT INTO user_quotations (user_id, lead_id, accepted_at) VALUES (?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("si", $user_id, $lead_id);
        $insertStmt->execute();

        // Get the lead details for the email
        $leadDetailsQuery = "SELECT * FROM leads WHERE lead_id = ?";
        $leadDetailsStmt = $conn->prepare($leadDetailsQuery);
        $leadDetailsStmt->bind_param("i", $lead_id);
        $leadDetailsStmt->execute();
        $leadDetailsResult = $leadDetailsStmt->get_result();
        $leadDetails = $leadDetailsResult->fetch_assoc();

        // Assuming you have a function to format the lead details into a string for the email
        $emailBody = "Here are the details of the lead you accepted: " . formatLeadDetails($leadDetails);

        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.elasticemail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aaron@acemovers.com.au';
        $mail->Password = '8F1E23DEE343B60A0336456A6944E7B4F7DA';
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        $mail->setFrom('aaron@acemovers.com.au', 'Ace Movers');
        $mail->addAddress($user_email); // Add a recipient
        $mail->isHTML(true); // Set email format to HTML
        $mail->Subject = 'Your accepted lead';
        $mail->Body = $emailBody;
        $mail->AltBody = strip_tags($emailBody);

        $mail->send();

        $_SESSION['credits'] -= 10;
        $conn->commit();
        header("Location: userleads.php?success=1");
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        header("Location: index.php?error=leadaccept");
        exit;
    }
} else {
    header("Location: index.php");
    exit;
}

// Placeholder for the formatLeadDetails function
// You will need to implement this function to format the lead details into a string for the email
function formatLeadDetails($details) {
    // Initialize the details string
    $detailsString = "<h2>Lead Details</h2>";

    // Add each detail by checking if it exists and is not null
    $detailsString .= "<strong>Lead Name:</strong> " . htmlspecialchars($details['lead_name']) . "<br>";
    if (!empty($details['bedrooms'])) {
        $detailsString .= "<strong>Bedrooms:</strong> " . htmlspecialchars($details['bedrooms']) . "<br>";
    }
    if (!empty($details['pickup'])) {
        $detailsString .= "<strong>Pick Up:</strong> " . htmlspecialchars($details['pickup']) . "<br>";
    }
    if (!empty($details['dropoff'])) {
        $detailsString .= "<strong>Drop Off:</strong> " . htmlspecialchars($details['dropoff']) . "<br>";
    }
    if (!empty($details['lead_date'])) {
        $detailsString .= "<strong>Date:</strong> " . htmlspecialchars($details['lead_date']) . "<br>";
    }
    if (!empty($details['phone'])) {
        $detailsString .= "<strong>Phone:</strong> " . htmlspecialchars($details['phone']) . "<br>";
    }
    if (!empty($details['email'])) {
        $detailsString .= "<strong>Email:</strong> " . htmlspecialchars($details['email']) . "<br>";
    }
    if (!empty($details['details'])) {
        $detailsString .= "<strong>Details:</strong> " . htmlspecialchars($details['details']) . "<br>";
    }

    // Assuming `created_at` is the timestamp when the lead was created/registered
    if (!empty($details['created_at'])) {
        $createdDate = new DateTime($details['created_at']);
        $detailsString .= "<strong>Registered on:</strong> " . $createdDate->format('F j, Y, g:i a') . "<br>";
    }

    // Add any additional details you need here using the same structure
    // ...

    return $detailsString;
}

?>

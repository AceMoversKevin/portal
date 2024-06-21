<?php
session_start();
require 'db.php'; // Adjust this as necessary
require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email']; // Assuming email is stored in the session

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['lead_id'], $_POST['lead_type'])) {
    $lead_id = $conn->real_escape_string($_POST['lead_id']);
    $lead_type = $conn->real_escape_string($_POST['lead_type']);

    // Fetch lead details including bedrooms
    $leadDetailsQuery = "SELECT acceptanceLimit, bedrooms FROM leads WHERE lead_id = ?";
    $leadDetailsStmt = $conn->prepare($leadDetailsQuery);
    $leadDetailsStmt->bind_param("i", $lead_id);
    $leadDetailsStmt->execute();
    $leadDetailsResult = $leadDetailsStmt->get_result();
    $leadDetails = $leadDetailsResult->fetch_assoc();

    if (!$leadDetails || $leadDetails['acceptanceLimit'] <= 0) {
        header("Location: index.php?error=leadnotavailable");
        exit;
    }

    $bedrooms = intval($leadDetails['bedrooms']);
    $creditDeduction = 0;
    $newAcceptanceLimit = 0; // To store the updated acceptance limit

    // Deduct credits based on the number of bedrooms
    if ($bedrooms == 0 || $bedrooms == 1) {
        $creditDeduction = 15;
    } else {
        $creditDeduction = 25;
    }

    // Check if user has enough credits
    $creditCheckSql = "SELECT credits FROM users WHERE user_id = ?";
    $creditCheckStmt = $conn->prepare($creditCheckSql);
    $creditCheckStmt->bind_param("s", $user_id);
    $creditCheckStmt->execute();
    $creditResult = $creditCheckStmt->get_result()->fetch_assoc();

    if ($creditResult['credits'] < $creditDeduction) {
        header("Location: index.php?error=notenoughcredits");
        exit;
    }

    // Begin transaction for updating user credits, lead acceptance limit, and adding entry to user_quotations
    $conn->begin_transaction();

    try {
        // Deduct credits from user
        $deductSql = "UPDATE users SET credits = credits - ? WHERE user_id = ?";
        $deductStmt = $conn->prepare($deductSql);
        $deductStmt->bind_param("is", $creditDeduction, $user_id);
        $deductStmt->execute();

        // Update lead acceptance limit
        $acceptanceUpdateSql = "UPDATE leads SET acceptanceLimit = ? WHERE lead_id = ?";
        $acceptanceUpdateStmt = $conn->prepare($acceptanceUpdateSql);
        $acceptanceUpdateStmt->bind_param("ii", $newAcceptanceLimit, $lead_id);
        $acceptanceUpdateStmt->execute();

        // Insert lead acceptance record
        $insertSql = "INSERT INTO user_quotations (user_id, lead_id, accepted_at) VALUES (?, ?, NOW())";
        $insertStmt = $conn->prepare($insertSql);
        $insertStmt->bind_param("si", $user_id, $lead_id);
        $insertStmt->execute();

        $conn->commit();

        // Prepare email to send to user
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

        // Update session credits
        $_SESSION['credits'] -= $creditDeduction;
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

function formatLeadDetails($details)
{
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

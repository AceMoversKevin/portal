<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

require_once 'db.php'; // Ensure you have your database connection set up

// Check if there's a logged-in session
if (isset($_SESSION['user_id'])) {
    $userId = $_SESSION['user_id'];
    
    // Query the database for the user's active status
    $stmt = $conn->prepare("SELECT isActive FROM users WHERE user_id = ?");
    $stmt->bind_param("s", $userId);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    // If the user is not active, log them out
    if ($user && $user['isActive'] == 0) {
        // Perform logout operations
        $_SESSION = array(); // Clear the session array
        session_destroy(); // Destroy the session
        
        // Redirect to the login page with a message, if necessary
        header("Location: login.php?message=Account deactivated. Please contact admin.");
        exit;
    }
}
?>

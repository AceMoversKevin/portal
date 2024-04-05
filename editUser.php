<?php
session_start();
require_once 'db.php'; // Adjust this path as needed

// Security check: Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "You do not have permission to perform this action.";
    exit;
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $userId = $conn->real_escape_string($_POST['user_id']);
    $action = $_POST['action'];

    switch ($action) {
        case 'edit_credits':
            $credits = filter_input(INPUT_POST, 'credits', FILTER_VALIDATE_INT);
            if ($credits === null || $credits === false) {
                // Handle invalid credits input
                echo "Invalid credits input.";
                exit;
            }
            $query = "UPDATE users SET credits = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('is', $credits, $userId);
            break;
        case 'deactivate':
            $query = "UPDATE users SET isActive = 0 WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('s', $userId);
            break;
        case 'change_username':
            $username = $conn->real_escape_string($_POST['username']);
            $query = "UPDATE users SET username = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $username, $userId);
            break;
        case 'change_password':
            $password = $conn->real_escape_string($_POST['password']);
            // Remember to use password hashing in real applications
            $query = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param('ss', $password, $userId);
            break;
            case 'remove':
                if (isset($_POST['confirmation']) && $_POST['confirmation'] === 'remove user') {
                    // Begin transaction
                    $conn->begin_transaction();
                    try {
                        // Delete related records in user_quotations first
                        $deleteRelated = "DELETE FROM user_quotations WHERE user_id = ?";
                        $stmtRelated = $conn->prepare($deleteRelated);
                        $stmtRelated->bind_param('s', $userId);
                        $stmtRelated->execute();
                        $stmtRelated->close();
                        
                        // Then delete the user
                        $query = "DELETE FROM users WHERE user_id = ?";
                        $stmt = $conn->prepare($query);
                        $stmt->bind_param('s', $userId);
                        $stmt->execute();
                        $stmt->close();
                        
                        // Commit transaction
                        $conn->commit();
                    } catch (mysqli_sql_exception $exception) {
                        // Rollback transaction on error
                        $conn->rollback();
                        throw $exception;
                    }
                } else {
                    $_SESSION['flash_message'] = "Confirmation to remove user failed.";
                    $_SESSION['flash_message_type'] = 'error';
                    header("Location: users.php");
                    exit;
                }
                break;
        default:
            echo "Invalid action.";
            exit;
    }

    if (isset($stmt) && $stmt->execute()) {
        // If you want to provide a success message, store it in a session variable
        $_SESSION['flash_message'] = "Action successfully executed.";
    } else {
        // If you want to provide an error message, store it in a session variable
        $_SESSION['flash_message'] = "An error occurred.";
    }

    // Close statement and connection if they were opened
    if (isset($stmt)) {
        $stmt->close();
    }
    $conn->close();

    // Redirect back to users.php or provide a link for the admin to go back
    header("Location: users.php");
    exit;
}
?>

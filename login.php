<?php
session_start();
include 'db.php'; // Ensure this points to your database connection file

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['username']) && isset($_POST['password'])) {
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']); // Remember to hash passwords in real applications

    // Adjusted SQL query to also check if the user account is active
    $sql = "SELECT user_id, username, role, credits FROM users WHERE username = ? AND password = ? AND isActive = 1";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['credits'] = $user['credits'];
        // Redirect user to the index page or dashboard
        if ($user['role'] === 'admin') {
            header("Location: admin.php"); // Redirect to admin dashboard
        } else {
            header("Location: index.php"); // Redirect to standard user page
        }
        exit;
    } else {
        // Check if the user exists but is not active
        $checkActiveSql = "SELECT user_id FROM users WHERE username = ? AND password = ? AND isActive = 0";
        $checkActiveStmt = $conn->prepare($checkActiveSql);
        $checkActiveStmt->bind_param("ss", $username, $password);
        $checkActiveStmt->execute();
        $checkActiveResult = $checkActiveStmt->get_result();

        if ($checkActiveResult->num_rows > 0) {
            $error_message = "Your account is not activated. Please contact the administrator.";
        } else {
            $error_message = "Invalid username or password.";
        }
    }
}
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records Portal</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>

<body>
    <div class="container mt-5">

        <h2>Login</h2>
        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary">Login</button>
        </form>

        <!-- Sign Up Button -->
        <a href="signup.php" class="btn btn-primary">Sign Up</a>
    </div>
</body>

</html>
<?php
session_start();
include 'db.php'; // Include your database connection

$error_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $phone = $conn->real_escape_string($_POST['phone']);
    $username = $conn->real_escape_string($_POST['username']);
    $password = $conn->real_escape_string($_POST['password']); // Directly using the password without hashing

    // Validate phone format
    if (!preg_match("/^04\d{8}$/", $phone)) {
        $error_message = "Phone number must be in the format 0412345678.";
    } else {
        // SQL to insert new user using phone as user_id
        $sql = "INSERT INTO users (user_id, username, password, role, created_at, updated_at, credits, isActive) VALUES (?, ?, ?, 'user', NOW(), NOW(), 0, 0)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sss", $phone, $username, $password);

        if ($stmt->execute()) {
            // Instead of echoing the script directly, set a flag
            $registrationSuccess = true;
        } else {
            $error_message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h2 class="text-center">Sign Up</h2>
        <p class="text-center">Please enter your phone number. It will be your User ID and must be in the format 0412345678 to ensure verification.</p>
        <?php if (!empty($error_message)) { echo "<div class='alert alert-danger'>$error_message</div>"; } ?>
        <form action="signup.php" method="post">
            <div class="form-group">
                <label for="phone">Phone Number (User ID)</label>
                <input type="text" class="form-control" id="phone" name="phone" pattern="^04\d{8}$" title="Phone number must be in the format 0412345678" required>
            </div>
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Register</button>
        </form>
    </div>

    <!-- Check for successful registration and show popup -->
    <?php if (isset($registrationSuccess) && $registrationSuccess): ?>
    <script>
        alert('Registration successful. Your phone number is your User ID.');
        window.location.href = 'login.php';
    </script>
    <?php endif; ?>
</body>
</html>

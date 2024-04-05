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

    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f7f7f7;
            padding-top: 5%;
        }

        .container {
            max-width: 400px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
        }

        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }

        .form-control {
            border-radius: 20px;
        }

        label {
            font-weight: 600;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        h2 {
            text-align: center;
            margin-bottom: 2rem;
        }

        .buttons-container {
            display: flex;
            justify-content: space-between;
            /* Adjusts buttons to each end */
            align-items: center;
            gap: 10px;
            /* Adds a small gap between buttons */
        }

        .btn-primary,
        .btn-secondary {
            flex-grow: 1;
            /* Allows buttons to expand and fill available space */
            margin: 5px;
            /* Adds a small margin around buttons */
        }
    </style>

</head>

<body>
    <!-- Form and Buttons Container -->
    <div class="container">
        <h2>Login</h2>
        <?php if (!empty($error_message)) : ?>
            <div class="alert alert-danger"><?php echo $error_message; ?></div>
        <?php endif; ?>

        <!-- Login Form -->
        <form action="login.php" method="post">
            <div class="form-group">
                <label for="username">Username:</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="password">Password:</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>

            <!-- Buttons Container -->
            <div class="buttons-container">
                <button type="submit" class="btn btn-primary">Login</button>
                <a href="signup.php" class="btn btn-secondary">Sign Up</a>
            </div>
        </form>
    </div>

</body>

</html>
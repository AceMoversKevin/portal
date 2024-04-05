<?php
session_start();
require_once 'db.php'; // Adjust the path to your actual database connection file

// Security check: Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect non-admins to the login page
    exit;
}

// Query to fetch active users
$query = "SELECT user_id, username, role, created_at, credits FROM users WHERE isActive = 1";
$activeUsers = $conn->query($query);
?>


<script>
    document.addEventListener('click', function(event) {
        var detailsElement = event.target.closest('.user-details');
        if (detailsElement) {
            event.stopPropagation();
        }
    }, true);

    function toggleUserDetails(userId) {
        var x = document.getElementById("details-" + userId);
        if (x.style.display === "none") {
            x.style.display = "block";
        } else {
            x.style.display = "none";
        }
    }

    function confirmRemoveUser() {
        var userInput = prompt("This is serious. Are you sure? Type 'remove user' to confirm.");
        if (userInput === "remove user") {
            return true;
        }
        alert("Action cancelled.");
        return false;
    }
</script>



<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Active Users</title>
    <!-- Link to Bootstrap CSS for styling, optional -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">

    <style>
        .user-container {
            background-color: #fff;
            border-radius: 0.25rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 1rem;
            margin-bottom: 1rem;
            cursor: pointer;
            transition: background-color 0.15s;
        }

        .user-container:hover {
            background-color: #f8f9fa;
        }

        .user-details {
            padding-top: 0.5rem;
        }

        .user-details input,
        .user-details button {
            margin-bottom: 0.5rem;
        }
    </style>

</head>

<body>
    <header class="mb-3 py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <a href="admin.php" class="btn btn-outline-secondary">Dashboard</a>
                <a href="users.php" class="btn btn-outline-primary">Active Users</a>
                <a href="activation.php" class="btn btn-outline-info">Activation Requests</a>
            </div>
            <div>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </header>

    <div class="container mt-4">
        <h2 class="mb-4">Active Users</h2>
        <?php foreach ($activeUsers as $user) : ?>
            <div class="user-container" onclick="toggleUserDetails('<?= $user['user_id']; ?>')">
                <p class="font-weight-bold"><?= htmlspecialchars($user['user_id']); ?></p>
                <p class="font-weight-bold"><?= htmlspecialchars($user['username']); ?></p>
                <div id="details-<?= $user['user_id']; ?>" class="user-details" style="display: none;">
                    <form action="editUser.php" method="POST">
                        <input type="hidden" name="user_id" value="<?= $user['user_id']; ?>">

                        <!-- Edit credits -->
                        <p>Edit Credits: </p>
                        <div class="input-group mb-2">
                            
                            <input type="number" class="form-control" name="credits" placeholder="Credits" value="<?= $user['credits']; ?>">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-info" name="action" value="edit_credits">Update Credits</button>
                            </div>
                        </div>

                        <!-- Change username -->
                        <p>Change Username: </p>
                        <div class="input-group mb-2">
                            
                            <input type="text" class="form-control" name="username" placeholder="New username">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-secondary" name="action" value="change_username">Change Username</button>
                            </div>
                        </div>

                        <!-- Change password -->
                        <p>Change Password: </p>
                        <div class="input-group mb-2">
                            
                            <input type="password" class="form-control" name="password" placeholder="New password">
                            <div class="input-group-append">
                                <button type="submit" class="btn btn-secondary" name="action" value="change_password">Change Password</button>
                            </div>
                        </div>

                        <!-- Deactivate user -->
                        <button type="submit" class="btn btn-warning mb-2" name="action" value="deactivate">Deactivate User</button>
                    </form>
                </div>
            </div>
        <?php endforeach; ?>
    </div>


    <!-- Optional: Link to Bootstrap JS and dependencies -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
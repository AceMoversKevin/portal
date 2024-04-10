<?php
session_start();
require_once 'db.php'; // Adjust the path to your actual database connection file

// Security check: Only allow admins
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect non-admins to the login page
    exit;
}

// Check if an activation request was submitted
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['activateUserId'])) {
    $activateUserId = $conn->real_escape_string($_POST['activateUserId']);
    $updateQuery = "UPDATE users SET isActive = 1 WHERE user_id = ?";
    $stmt = $conn->prepare($updateQuery);
    $stmt->bind_param('s', $activateUserId);
    $stmt->execute();
    $stmt->close();
    // Optionally, add a success message or redirect
}

// Query to fetch users needing activation
$query = "SELECT user_id, username, role, created_at FROM users WHERE isActive = 0";
$inactiveUsers = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Activation</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

<header class="mb-3 py-3">
    <div class="container-fluid d-flex justify-content-between align-items-center">
        <div class="d-flex flex-wrap">
            <a href="admin.php" class="btn btn-outline-secondary mb-2 mb-md-0 mr-md-2">Dashboard</a>
            <a href="users.php" class="btn btn-outline-primary mb-2 mb-md-0 mr-md-2">Active Users</a>
            <a href="activation.php" class="btn btn-outline-info mb-2 mb-md-0 mr-md-2">Activation Requests</a>
        </div>
        <div>
            <a href="logout.php" class="btn btn-outline-danger">Logout</a>
        </div>
    </div>
</header>

    <div class="container mt-4">
        <h2>Users Needing Activation</h2>
        <?php if ($inactiveUsers->num_rows > 0) : ?>
            <?php while ($user = $inactiveUsers->fetch_assoc()) : ?>
                <div class="card mb-3">
                    <div class="card-body">
                        <h5 class="card-title"><?= htmlspecialchars($user['username']); ?> (<?= htmlspecialchars($user['role']); ?>)</h5>
                        <h6 class="card-subtitle mb-2 text-muted">User ID: <?= htmlspecialchars($user['user_id']); ?></h6>
                        <p class="card-text">Created at: <?= htmlspecialchars($user['created_at']); ?></p>
                        <form method="post" action="activation.php">
                            <input type="hidden" name="activateUserId" value="<?= htmlspecialchars($user['user_id']); ?>">
                            <button type="submit" class="btn btn-primary">Activate User</button>
                        </form>
                    </div>
                </div>
            <?php endwhile; ?>
        <?php else : ?>
            <p>No users need activation at the moment.</p>
        <?php endif; ?>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>

</html>
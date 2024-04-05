<?php
session_start();
include 'db.php'; // Adjust the path to your database connection file as necessary

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect non-admins back to login page
    exit;
}

// Total number of available leads
$totalLeadsQuery = "SELECT COUNT(*) AS total FROM leads WHERE booking_status = 0"; // Assuming booking_status = 0 means available
$totalLeadsResult = $conn->query($totalLeadsQuery);
$totalLeadsRow = $totalLeadsResult->fetch_assoc();
$totalAvailableLeads = $totalLeadsRow['total'];

// How many leads each user has accepted
$leadsPerUserQuery = "SELECT users.username, COUNT(user_quotations.lead_id) AS leads_accepted 
                      FROM users
                      JOIN user_quotations ON users.user_id = user_quotations.user_id
                      GROUP BY users.username";
$leadsPerUserResult = $conn->query($leadsPerUserQuery);

// How many users pending activation
$pendingUsersQuery = "SELECT COUNT(*) AS pending FROM users WHERE isActive = 0";
$pendingUsersResult = $conn->query($pendingUsersQuery);
$pendingUsersRow = $pendingUsersResult->fetch_assoc();
$pendingUsersCount = $pendingUsersRow['pending'];


// Fetch all users from the database
$sql = "SELECT user_id, username, role, created_at, credits, isActive FROM users";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
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

    <div class="container mt-5">
        <h1>Welcome to the Admin Dashboard, <?= htmlspecialchars($_SESSION['username']); ?>!</h1>
        <br>
        <h5>Users Overview</h5>
        <div class="row">
            <?php if ($result->num_rows > 0) : ?>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <div class="col-md-4 mb-4">
                        <div class="card" style="width: 18rem;">
                            <div class="card-header">
                                <?= htmlspecialchars($row["username"]); ?>
                            </div>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">User ID: <?= htmlspecialchars($row["user_id"]); ?></li>
                                <li class="list-group-item">Role: <?= htmlspecialchars($row["role"]); ?></li>
                                <li class="list-group-item">Credits: <?= htmlspecialchars($row["credits"]); ?></li>
                                <li class="list-group-item">Account Active: <?= $row["isActive"] ? 'Yes' : 'No'; ?></li>
                                <li class="list-group-item">Created at: <?= htmlspecialchars($row["created_at"]); ?></li>
                            </ul>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <p>No users found.</p>
            <?php endif; ?>
        </div>
    </div>

    <!-- Statistics Sections -->
    <div class="container mt-5">
        <h2>Statistics</h2>
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Total Available Leads</h5>
                        <p class="card-text"><?= $totalAvailableLeads ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Leads Accepted by Each User</h5>
                        <?php while ($row = $leadsPerUserResult->fetch_assoc()) : ?>
                            <p class="card-text"><?= htmlspecialchars($row['username']) . ": " . $row['leads_accepted'] ?></p>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Users Pending Activation</h5>
                        <p class="card-text"><?= $pendingUsersCount ?></p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

    <footer>
        <br>
        <br>
    </footer>
</body>

</html>
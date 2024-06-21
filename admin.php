<?php
session_start();
include 'db.php'; // Adjust the path to your database connection file as necessary

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect non-admins back to login page
    exit;
}

// Leads grouped by number of bedrooms
$leadsByBedroomsQuery = "SELECT bedrooms, COUNT(*) AS total FROM leads GROUP BY bedrooms ORDER BY bedrooms ASC";
$bedroomsDataArray = []; // PHP array
$leadsByBedroomsResult = $conn->query($leadsByBedroomsQuery);
while ($row = $leadsByBedroomsResult->fetch_assoc()) {
    $bedroomsDataArray[] = $row;
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
$sql = "SELECT user_id, username, role, created_at, credits, isActive FROM users WHERE role = 'user'";

$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Admin Dashboard</title>
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
                <a href="leadsOverview.php" class="btn btn-outline-dark mb-2 mb-md-0 mr-md-2">Leads</a>
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

    <!-- Container for Charts -->
    <div class="container mt-5">
        <h2>Charts</h2>
        <div class="row">
            <div class="col-md-6">
                <h3>Leads by Bedrooms</h3>
                <canvas id="leadsByBedroomsChart" style="height:400px; width:400px;"></canvas>
            </div>
            <div class="col-md-6">
                <h3>Leads by Day of Week</h3>
                <canvas id="leadsByDayOfWeekChart" style="height:400px; width:400px;"></canvas>
            </div>
        </div>
    </div>

    <div class="container mt-5">
        <h2 class="mb-4">Geocode All Leads</h2>
        <form action="geocode_leads.php" method="post" target="_blank">
            <button type="submit" class="btn btn-primary">Geocode All Leads</button>
        </form>
    </div>

    <style>
        canvas {
            -moz-user-select: none;
            -webkit-user-select: none;
            -ms-user-select: none;
            max-height: 400px;
            /* Set a maximum height */
            max-width: 100%;
            /* Use a percentage to be responsive */
        }
    </style>


    <script>
        // Chart rendering script
        // Prepare data for Leads by Bedrooms Chart
        var bedroomsLabels = [];
        var bedroomsData = [];

        <?php foreach ($bedroomsDataArray as $row) : ?>
            var label = <?= json_encode($row['bedrooms']) ?> || 'Unspecified';
            bedroomsLabels.push(label + ' Bedroom' + (label !== '1' ? 's' : ''));
            bedroomsData.push(<?= $row['total'] ?>);
        <?php endforeach; ?>

        console.log(bedroomsLabels);
        console.log(bedroomsData);

        // Prepare data for Leads by Day of Week Chart
        var dayOfWeekLabels = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
        var dayOfWeekData = [0, 0, 0, 0, 0, 0, 0]; // Initialize with 0s

        // Fetch data from database for each day of the week
        <?php
        for ($i = 0; $i < 7; $i++) {
            $dayOfWeekQuery = "SELECT COUNT(*) AS total FROM leads WHERE DAYOFWEEK(lead_date) = ?";
            $stmt = $conn->prepare($dayOfWeekQuery);
            $dayIndex = $i + 1; // DAYOFWEEK() function returns values from 1 (Sunday) to 7 (Saturday)
            $stmt->bind_param("i", $dayIndex);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            echo "dayOfWeekData[$i] = " . $row['total'] . ";\n";
        }
        ?>

        // Render Charts
        window.onload = function() {
            var ctx1 = document.getElementById('leadsByBedroomsChart').getContext('2d');
            new Chart(ctx1, {
                type: 'pie',
                data: {
                    labels: bedroomsLabels,
                    datasets: [{
                        data: bedroomsData,
                        backgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40'],
                        hoverBackgroundColor: ['#FF6384', '#36A2EB', '#FFCE56', '#4BC0C0', '#9966FF', '#FF9F40']
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false
                }
            });

            var ctx2 = document.getElementById('leadsByDayOfWeekChart').getContext('2d');
            new Chart(ctx2, {
                type: 'bar',
                data: {
                    labels: dayOfWeekLabels,
                    datasets: [{
                        label: 'Leads',
                        data: dayOfWeekData,
                        backgroundColor: 'rgba(54, 162, 235, 0.2)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true
                        }
                    }
                }
            });
        };
    </script>
    </div>



    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <footer>
        <br>
        <br>
    </footer>
</body>

</html>
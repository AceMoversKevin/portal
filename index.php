<?php
session_start();
require 'session_check.php';
include 'db.php'; // Ensure this points to your database connection file

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Adjusted query to exclude leads accepted by the user
$sql = "SELECT leads.* FROM leads
        LEFT JOIN user_quotations ON leads.lead_id = user_quotations.lead_id AND user_quotations.user_id = ?
        WHERE user_quotations.lead_id IS NULL AND leads.booking_status = 0";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id); // Assuming user_id is stored as a string in your session
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Records Portal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header class="mb-3 py-3">
        <div class="container d-flex justify-content-between align-items-center">
            <div class="user-info">
                <img src="user.svg" alt="User icon">
                <!-- Display the username and credits from the session -->
                <span><?= htmlspecialchars($_SESSION['username']); ?> (Credits: <?= htmlspecialchars($_SESSION['credits']); ?>)</span>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-secondary">All Leads</a>
                <a href="userleads.php" class="btn btn-outline-primary">Accepted Leads</a>
            </div>
            <div>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </header>

    </div>
    </header>

    <div class="container mt-5">
        <h2 class="mb-4">Available Leads</h2>
        <div class="row">
            <?php
            if ($result->num_rows > 0) {
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    echo '<div class="col-md-4 mb-4">';
                    echo '<div class="card"style="width: 18rem;"> ';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">' . htmlspecialchars($row["lead_name"]) . '</h5>';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Available lead</h6>';
                    echo '<li class="list-group-item">Bedrooms: ' . htmlspecialchars($row["bedrooms"]) . '</li>';
                    echo '<li class="list-group-item">Pick Up: ' . htmlspecialchars($row["pickup"]) . '</li>';
                    echo '<li class="list-group-item">Drop Off: ' . htmlspecialchars($row["dropoff"]) . '</li>';
                    // Form for accepting a lead
                    echo '<form action="accept_lead.php" method="post">';
                    echo '<input type="hidden" name="lead_id" value="' . htmlspecialchars($row['lead_id']) . '">';
                    echo '<button type="submit" class="btn btn-success">Accept Lead</button>';
                    echo '</form>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
                }
            } else {
                echo "0 results found.";
            }
            ?>
        </div>
    </div>

    <script>
        // Check for the 'error' query parameter in the URL
        if (new URLSearchParams(window.location.search).has('error')) {
            const errorMessage = new URLSearchParams(window.location.search).get('error');
            // Check specific error message
            if (errorMessage === 'notenoughcredits') {
                alert('Not enough credits. Please call or email the administrator to add more credits.');
            }
        }
    </script>


    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>
<?php
session_start();
require 'session_check.php';
include 'db.php'; // Ensure this points to your database connection file

if (!isset($_SESSION['user_id'])) {  //if it isnt set redirect to the login page
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id']; //user id from the session

$startDate = isset($_GET['start_date']) ? $_GET['start_date'] : null;
$endDate = isset($_GET['end_date']) ? $_GET['end_date'] : null;

if ($startDate && $endDate) {
    $startDate = date('Y-m-d', strtotime($startDate));
    $endDate = date('Y-m-d', strtotime($endDate));

    // Updated SQL query to filter based on the date range
    $sql = "SELECT leads.* FROM leads
            LEFT JOIN user_quotations ON leads.lead_id = user_quotations.lead_id AND user_quotations.user_id = ?
            WHERE user_quotations.lead_id IS NULL
            AND leads.booking_status = 0
            AND leads.acceptanceLimit > 0
            AND DATE(leads.created_at) BETWEEN ? AND ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("sss", $user_id, $startDate, $endDate); // Bind parameters
} else {
    // Original SQL query if no dates are selected
    $sql = "SELECT leads.* FROM leads
            LEFT JOIN user_quotations ON leads.lead_id = user_quotations.lead_id AND user_quotations.user_id = ?
            WHERE user_quotations.lead_id IS NULL
            AND leads.booking_status = 0
            AND leads.acceptanceLimit > 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $user_id); // user_id is a session variable
}

$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Records Portal</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- Include Date Range Picker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <!-- Include Date Range Picker JavaScript and its dependencies -->
    <script src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

</head>

<body>

    <header class="mb-3 py-3">
        <div class="container-fluid">
            <div class="row justify-content-between align-items-center">
                <div class="col-md-6 col-lg-4 user-info">
                    <img src="user.svg" alt="User icon">
                    <span><?= htmlspecialchars($_SESSION['username']); ?> (Credits: <?= htmlspecialchars($_SESSION['credits']); ?>)</span>
                </div>
                <!-- Date Range Selector should be within its own column -->
                <div class="col-lg-4">
                    <form id="dateRangeForm" action="index.php" method="GET" class="form-inline">
                        <input type="date" name="start_date" id="startDatePicker" placeholder="Start Date" class="form-control mr-2">
                        <input type="date" name="end_date" id="endDatePicker" placeholder="End Date" class="form-control mr-2">
                        <button type="submit" class="btn btn-primary">Filter</button>
                    </form>

                </div>
                <div class="col-md-6 col-lg-4 text-md-right">
                    <a href="index.php" class="btn btn-outline-secondary">All Leads</a>
                    <a href="userleads.php" class="btn btn-outline-primary">Accepted Leads</a>
                    <a href="logout.php" class="btn btn-outline-danger">Logout</a>
                </div>
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
            ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card">
                            <div class="card-body">
                                <h5 class="card-title"><?= htmlspecialchars($row["lead_name"]) ?></h5>
                                <h6 class="card-subtitle mb-2 text-muted">Available lead</h6>
                                <ul class="list-group list-group-flush">
                                    <li class="list-group-item">Bedrooms: <?= htmlspecialchars($row["bedrooms"]) ?></li>
                                    <li class="list-group-item">Pick Up: <?= htmlspecialchars($row["pickup"]) ?></li>
                                    <li class="list-group-item">Drop Off: <?= htmlspecialchars($row["dropoff"]) ?></li>
                                </ul>
                                <!-- Dropdown for accepting a lead -->
                                <div id="lead-action-container-<?= $row['lead_id'] ?>" class="mt-3">
                                    <button class="btn btn-success" type="button" onclick="showLeadOptions(<?= $row['lead_id'] ?>)">Accept Lead</button>
                                    <script>
                                        function showLeadOptions(leadId) {
                                            const container = document.getElementById('lead-action-container-' + leadId);
                                            container.innerHTML = `
        <button class="btn btn-primary" type="button" onclick="acceptLead(${leadId}, 'premium')">Premium Lead</button>
        <button class="btn btn-secondary" type="button" onclick="acceptLead(${leadId}, 'normal')">Normal Lead</button>
    `;
                                        }

                                        function acceptLead(leadId, type) {
                                            const form = document.createElement('form');
                                            form.action = 'accept_lead.php';
                                            form.method = 'post';

                                            const leadIdInput = document.createElement('input');
                                            leadIdInput.type = 'hidden';
                                            leadIdInput.name = 'lead_id';
                                            leadIdInput.value = leadId;

                                            const typeInput = document.createElement('input');
                                            typeInput.type = 'hidden';
                                            typeInput.name = 'lead_type';
                                            typeInput.value = type;

                                            form.appendChild(leadIdInput);
                                            form.appendChild(typeInput);
                                            document.body.appendChild(form);
                                            form.submit();
                                        }
                                    </script>

                                </div>


                            </div>
                        </div>
                    </div>
            <?php
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
    <script>
        $(function() {
            $('#startDatePicker').daterangepicker({
                singleDatePicker: true,
                locale: {
                    format: 'YYYY-MM-DD' // Use the format that matches your database date format
                }
            });

            $('#endDatePicker').daterangepicker({
                singleDatePicker: true,
                locale: {
                    format: 'YYYY-MM-DD' // Use the format that matches your database date format
                }
            });
        });
    </script>



    <!-- Bootstrap JS, Popper.js, and jQuery -->
    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>

</body>

</html>
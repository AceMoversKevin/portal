<?php
session_start();
require 'session_check.php';
include 'db.php'; // Include database connection

if (!isset($_SESSION['user_id'])) {
    // Redirect to login page if not logged in
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// SQL query to fetch leads for the logged-in user along with geocoded data
$sql = "SELECT l.*, uq.accepted_at, uq.notes, lg.pickup_latitude, lg.pickup_longitude, lg.dropoff_latitude, lg.dropoff_longitude 
        FROM leads l
        INNER JOIN user_quotations uq ON l.lead_id = uq.lead_id
        LEFT JOIN lead_geocodes lg ON l.lead_id = lg.lead_id
        WHERE uq.user_id = ?";

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
    <meta name="robots" content="noindex, nofollow">
    <title>User Leads</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
    <style>
        .map-container {
            position: relative;
            width: 100%;
            height: 200px;
            background-color: #f0f0f0;
        }
    </style>
</head>

<body>

<header class="mb-3 py-3">
    <div class="container-fluid">
        <div class="row justify-content-between align-items-center">
            <div class="col-md-6 col-lg-4 user-info">
                <img src="user.svg" alt="User icon">
                <!-- Display the username and credits from the session -->
                <span>
                    <?php
                    if (isset($_SESSION['username']) && isset($_SESSION['credits'])) {
                        echo htmlspecialchars($_SESSION['username']) . ' (Credits: ' . htmlspecialchars($_SESSION['credits']) . ')';
                    }
                    ?>
                </span>
            </div>
            <div class="col-md-6 col-lg-4 text-md-right">
                <a href="index.php" class="btn btn-outline-secondary">All Leads</a>
                <a href="userleads.php" class="btn btn-outline-primary">Accepted Leads</a>
            </div>
            <div class="col-lg-4 text-lg-right mt-3 mt-md-0">
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </div>
</header>
<div class="container mt-5">
    <h2 class="mb-4">Your Accepted Leads</h2>
    <div class="row">
        <?php
        if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                $pickupLatitude = $row['pickup_latitude'];
                $pickupLongitude = $row['pickup_longitude'];
                $dropoffLatitude = $row['dropoff_latitude'];
                $dropoffLongitude = $row['dropoff_longitude'];

                $acceptedDate = date("F j, Y, g:i a", strtotime($row['accepted_at']));
                $notes = htmlspecialchars($row['notes'] ?? '');
        ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?= htmlspecialchars($row['lead_name']) ?></h5>
                            <h6 class="card-subtitle mb-2 text-muted">Accepted Lead</h6>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item">Bedrooms: <?= htmlspecialchars($row['bedrooms']) ?></li>
                                <li class="list-group-item">Pick Up: <?= htmlspecialchars($row['pickup']) ?></li>
                                <li class="list-group-item">Drop Off: <?= htmlspecialchars($row['dropoff']) ?></li>
                                <li class="list-group-item">Phone: <?= htmlspecialchars($row['phone']) ?></li>
                                <li class="list-group-item">Email: <?= htmlspecialchars($row['email']) ?></li>
                                <li class="list-group-item">Date: <?= htmlspecialchars($row['lead_date']) ?></li>
                                <li class="list-group-item"><small>Accepted on: <?= $acceptedDate ?></small></li>
                            </ul>
                            <!-- Form for notes -->
                            <form class="p-2">
                                <input type="hidden" name="lead_id" value="<?= $row['lead_id'] ?>">
                                <input type="hidden" name="user_id" value="<?= $_SESSION['user_id'] ?>">
                                <textarea class="form-control mb-2" name="notes" placeholder="Enter your notes here"><?= $notes ?></textarea>
                                <button type="button" class="btn btn-primary btn-sm" onclick="saveNotes('<?= $row['lead_id'] ?>')">Save Note</button>
                            </form>
                            <div id="mapid-<?= htmlspecialchars($row['lead_id']) ?>" class="map-container" data-pickup-lat="<?= htmlspecialchars($pickupLatitude) ?>" data-pickup-lng="<?= htmlspecialchars($pickupLongitude) ?>" data-dropoff-lat="<?= htmlspecialchars($dropoffLatitude) ?>" data-dropoff-lng="<?= htmlspecialchars($dropoffLongitude) ?>"></div>
                        </div>
                    </div>
                </div>
        <?php
            }
        } else {
            echo '<div class="col">No leads found.</div>';
        }
        ?>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
<script>
    var mapContainers = document.querySelectorAll('.map-container');

    mapContainers.forEach(function(container) {
        var pickupLat = parseFloat(container.getAttribute('data-pickup-lat'));
        var pickupLng = parseFloat(container.getAttribute('data-pickup-lng'));
        var dropoffLat = parseFloat(container.getAttribute('data-dropoff-lat'));
        var dropoffLng = parseFloat(container.getAttribute('data-dropoff-lng'));
        
        if (isNaN(pickupLat) || isNaN(pickupLng) || isNaN(dropoffLat) || isNaN(dropoffLng)) {
            console.error('Invalid coordinates:', pickupLat, pickupLng, dropoffLat, dropoffLng);
            return;
        }

        var mapId = container.getAttribute('id');

        var map = L.map(mapId, {
            zoomControl: false,
            scrollWheelZoom: false,
            dragging: false,
            doubleClickZoom: false,
            touchZoom: false
        }).setView([pickupLat, pickupLng], 13);

        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            attribution: '© AceMovers Map Parsing'
        }).addTo(map);

        var pickupLatLng = L.latLng(pickupLat, pickupLng);
        var dropoffLatLng = L.latLng(dropoffLat, dropoffLng);

        L.marker(pickupLatLng).addTo(map).bindPopup('Pickup Location');
        L.marker(dropoffLatLng).addTo(map).bindPopup('Dropoff Location');

        var bounds = L.latLngBounds([pickupLatLng, dropoffLatLng]);
        map.fitBounds(bounds, {
            padding: [50, 50]
        });

        map.setMaxBounds(bounds.pad(0.1));
    });

    function saveNotes(leadId) {
        var notes = document.getElementById("note-" + leadId).value;

        var dataToSend = {
            lead_id: leadId,
            user_id: '<?= $_SESSION['user_id']; ?>',
            notes: notes
        };

        $.ajax({
            type: "POST",
            url: "save_notes.php",
            data: dataToSend,
            success: function(response) {
                alert("Note saved successfully.");
            },
            error: function(xhr, status, error) {
                console.error("Error saving note:", xhr, status, error);
                alert("Error saving note. Please try again.");
            }
        });
    }
</script>
</body>
</html>

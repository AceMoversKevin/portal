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


// SQL query to fetch leads for the logged-in user
$sql = "SELECT l.*, uq.accepted_at, uq.notes FROM leads l
        INNER JOIN user_quotations uq ON l.lead_id = uq.lead_id
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
    <title>User Leads</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <script>
        function saveNotes(leadId) {
            // Grab the note content from the textarea that has an ID formatted as "note-<leadId>"
            var notes = document.getElementById("note-" + leadId).value;

            // Prepare the data to send in the AJAX request
            var dataToSend = {
                lead_id: leadId,
                user_id: '<?= $_SESSION['user_id']; ?>', // Include the user ID from the session
                notes: notes // Include the note content
            };

            // Perform the AJAX request to "save_notes.php"
            $.ajax({
                type: "POST",
                url: "save_notes.php",
                data: dataToSend,
                success: function(response) {
                    // Handle a successful response (note saved successfully)
                    alert("Note saved successfully.");
                },
                error: function(xhr, status, error) {
                    // Handle any errors that occurred during the request
                    console.error("Error saving note:", xhr, status, error);
                    alert("Error saving note. Please try again.");
                }
            });
        }
    </script>
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


    <div class="container mt-5">
        <h2 class="mb-4">Your Accepted Leads</h2>
        <div class="row">
            <?php
            if ($result->num_rows > 0) {
                // Output data of each row
                while ($row = $result->fetch_assoc()) {
                    $acceptedDate = date("F j, Y, g:i a", strtotime($row['accepted_at']));
                    $notes = htmlspecialchars($row['notes'] ?? ''); // Use null coalescing operator to handle if notes are not set

                    echo '<div class="col-md-4 mb-4">';
                    echo '<div class="card" style="width: 18rem;">';
                    echo '<div class="card-body">';
                    echo '<h5 class="card-title">' . htmlspecialchars($row['lead_name']) . '</h5>';
                    echo '<h6 class="card-subtitle mb-2 text-muted">Accepted Lead</h6>';
                    echo '<ul class="list-group list-group-flush">';
                    echo '<li class="list-group-item">Bedrooms: ' . htmlspecialchars($row['bedrooms']) . '</li>';
                    echo '<li class="list-group-item">Pick Up: ' . htmlspecialchars($row['pickup']) . '</li>';
                    echo '<li class="list-group-item">Drop Off: ' . htmlspecialchars($row['dropoff']) . '</li>';
                    echo '<li class="list-group-item">Phone: ' . htmlspecialchars($row['phone']) . '</li>';
                    echo '<li class="list-group-item">Email: ' . htmlspecialchars($row['email']) . '</li>';
                    echo '<li class="list-group-item">Date: ' . htmlspecialchars($row['lead_date']) . '</li>';
                    echo '<li class="list-group-item"><small>Accepted on: ' . $acceptedDate . '</small></li>';

                    // Form for notes
                    $formId = 'form-' . $row['lead_id'];
                    $textareaId = 'note-' . $row['lead_id'];
                    echo "<form id='$formId' onsubmit='return false;' class='p-2'>";
                    echo '<input type="hidden" name="lead_id" value="' . $row['lead_id'] . '">';
                    echo '<input type="hidden" name="user_id" value="' . $_SESSION['user_id'] . '">';
                    // Pre-populate textarea with notes if they exist
                    echo "<textarea id='$textareaId' class='form-control mb-2' name='notes' placeholder='Enter your notes here'>$notes</textarea>";
                    echo "<button type='button' class='btn btn-primary btn-sm' onclick='saveNotes(\"$row[lead_id]\")'>Save Note</button>";
                    echo '</form>';
                    echo '</ul>';
                    echo '</div>';
                    echo '</div>';
                    echo '</div>';
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


</body>

</html>
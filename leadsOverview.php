<?php
session_start();
include 'db.php'; // Adjust the path to your database connection file as necessary

// Check if the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php"); // Redirect non-admins back to login page
    exit;
}

// Determine the sorting column and order
$sortColumn = isset($_GET['sort']) ? $_GET['sort'] : 'lead_id';
$sortOrder = isset($_GET['order']) && $_GET['order'] === 'desc' ? 'desc' : 'asc';

// Handle search term and date filter
$searchTerm = isset($_GET['search']) ? $_GET['search'] : '';
$dateFilter = isset($_GET['date_filter']) ? $_GET['date_filter'] : '';

// Handle column visibility
$visibleColumns = isset($_GET['visible_columns']) ? explode(',', $_GET['visible_columns']) : ['lead_id', 'lead_name', 'bedrooms', 'pickup', 'dropoff', 'lead_date', 'phone', 'email', 'details', 'acceptanceLimit', 'booking_status', 'created_at', 'isReleased'];

// Construct SQL query with search and date filter
$sql = "SELECT * FROM leads WHERE 
    (lead_id LIKE '%$searchTerm%' OR 
    lead_name LIKE '%$searchTerm%' OR 
    bedrooms LIKE '%$searchTerm%' OR 
    pickup LIKE '%$searchTerm%' OR 
    dropoff LIKE '%$searchTerm%' OR 
    lead_date LIKE '%$searchTerm%' OR 
    phone LIKE '%$searchTerm%' OR 
    email LIKE '%$searchTerm%' OR 
    details LIKE '%$searchTerm%' OR 
    acceptanceLimit LIKE '%$searchTerm%' OR 
    booking_status LIKE '%$searchTerm%' OR 
    created_at LIKE '%$searchTerm%' OR 
    isReleased LIKE '%$searchTerm%')";

if ($dateFilter) {
    switch ($dateFilter) {
        case 'current_month':
            $sql .= " AND MONTH(lead_date) = MONTH(CURRENT_DATE()) AND YEAR(lead_date) = YEAR(CURRENT_DATE())";
            break;
        case 'last_month':
            $sql .= " AND MONTH(lead_date) = MONTH(CURRENT_DATE() - INTERVAL 1 MONTH) AND YEAR(lead_date) = YEAR(CURRENT_DATE() - INTERVAL 1 MONTH)";
            break;
        case 'current_year':
            $sql .= " AND YEAR(lead_date) = YEAR(CURRENT_DATE())";
            break;
        case 'last_year':
            $sql .= " AND YEAR(lead_date) = YEAR(CURRENT_DATE() - INTERVAL 1 YEAR)";
            break;
        case 'date_range':
            if (isset($_GET['start_date']) && isset($_GET['end_date'])) {
                $startDate = $_GET['start_date'];
                $endDate = $_GET['end_date'];
                $sql .= " AND lead_date BETWEEN '$startDate' AND '$endDate'";
            }
            break;
        default:
            break;
    }
}

$sql .= " ORDER BY $sortColumn $sortOrder";
$result = $conn->query($sql);
$leads = $result->fetch_all(MYSQLI_ASSOC);

// Determine the next order direction for each column
$nextOrder = $sortOrder === 'asc' ? 'desc' : 'asc';
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex, nofollow">
    <title>Leads Overview</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jquery-resizable-columns/0.2.3/css/jquery.resizableColumns.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.2/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-resizable-columns/0.2.3/jquery.resizableColumns.min.js"></script>
    <style>
        .editable {
            cursor: pointer;
        }

        .editable:hover {
            background-color: #f0f0f0;
        }

        .sortable:hover {
            cursor: pointer;
            text-decoration: underline;
        }

        table tbody tr {
            resize: vertical;
            overflow: hidden;
        }
    </style>
</head>

<body>
    <header class="mb-3 py-3">
        <div class="container-fluid d-flex justify-content-between align-items-center">
            <div class="d-flex flex-wrap">
                <a href="admin.php" class="btn btn-outline-secondary mb-2 mb-md-0 mr-md-2">Dashboard</a>
                <a href="users.php" class="btn btn-outline-primary mb-2 mb-md-0 mr-md-2">Active Users</a>
                <a href="activation.php" class="btn btn-outline-info mb-2 mb-md-0 mr-md-2">Activation Requests</a>
                <a href="#" class="btn btn-outline-dark mb-2 mb-md-0 mr-md-2">Leads</a>
            </div>
            <div>
                <a href="logout.php" class="btn btn-outline-danger">Logout</a>
            </div>
        </div>
    </header>

    <h1>Leads Overview</h1>
    <br>
    <form method="GET" action="leadsOverview.php" class="mb-3">
        <input type="text" name="search" value="<?php echo htmlspecialchars($searchTerm); ?>" placeholder="Search Leads" class="form-control" style="display:inline-block; width: auto;">
        <select name="date_filter" id="date_filter" class="form-control" style="display:inline-block; width: auto;">
            <option value="">Select Date Filter</option>
            <option value="current_month">Current Month</option>
            <option value="last_month">Last Month</option>
            <option value="current_year">Current Year</option>
            <option value="last_year">Last Year</option>
            <option value="date_range">Date Range</option>
        </select>
        <input type="date" name="start_date" id="start_date" class="form-control" style="display:inline-block; width: auto;">
        <input type="date" name="end_date" id="end_date" class="form-control" style="display:inline-block; width: auto;">
        <button type="submit" class="btn btn-primary">Filter</button>
        <button type="button" onclick="window.location.href='leadsOverview.php'" class="btn btn-outline-secondary">Reset</button>
    </form>

    <table class="table table-bordered resizable" data-resizable-columns-id="leads-table">
        <thead>
            <tr>
                <?php if (in_array('lead_id', $visibleColumns)) : ?><th class="sortable" data-sort="lead_id">Lead ID</th><?php endif; ?>
                <?php if (in_array('lead_name', $visibleColumns)) : ?><th class="sortable" data-sort="lead_name">Name</th><?php endif; ?>
                <?php if (in_array('bedrooms', $visibleColumns)) : ?><th class="sortable" data-sort="bedrooms">Bedrooms</th><?php endif; ?>
                <?php if (in_array('pickup', $visibleColumns)) : ?><th class="sortable" data-sort="pickup">Pickup</th><?php endif; ?>
                <?php if (in_array('dropoff', $visibleColumns)) : ?><th class="sortable" data-sort="dropoff">Dropoff</th><?php endif; ?>
                <?php if (in_array('lead_date', $visibleColumns)) : ?><th class="sortable" data-sort="lead_date">Date</th><?php endif; ?>
                <?php if (in_array('phone', $visibleColumns)) : ?><th class="sortable" data-sort="phone">Phone</th><?php endif; ?>
                <?php if (in_array('email', $visibleColumns)) : ?><th class="sortable" data-sort="email">Email</th><?php endif; ?>
                <?php if (in_array('details', $visibleColumns)) : ?><th class="sortable" data-sort="details">Details</th><?php endif; ?>
                <?php if (in_array('acceptanceLimit', $visibleColumns)) : ?><th class="sortable" data-sort="acceptanceLimit">Acceptance Limit</th><?php endif; ?>
                <?php if (in_array('booking_status', $visibleColumns)) : ?><th class="sortable" data-sort="booking_status">Booking Status</th><?php endif; ?>
                <?php if (in_array('created_at', $visibleColumns)) : ?><th class="sortable" data-sort="created_at">Created At</th><?php endif; ?>
                <?php if (in_array('isReleased', $visibleColumns)) : ?><th class="sortable" data-sort="isReleased">Released</th><?php endif; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($leads as $lead) : ?>
                <tr data-id="<?php echo $lead['lead_id']; ?>">
                    <?php if (in_array('lead_id', $visibleColumns)) : ?><td><?php echo $lead['lead_id']; ?></td><?php endif; ?>
                    <?php if (in_array('lead_name', $visibleColumns)) : ?><td class="editable" data-field="lead_name"><?php echo $lead['lead_name']; ?></td><?php endif; ?>
                    <?php if (in_array('bedrooms', $visibleColumns)) : ?><td class="editable" data-field="bedrooms"><?php echo $lead['bedrooms']; ?></td><?php endif; ?>
                    <?php if (in_array('pickup', $visibleColumns)) : ?><td class="editable" data-field="pickup"><?php echo $lead['pickup']; ?></td><?php endif; ?>
                    <?php if (in_array('dropoff', $visibleColumns)) : ?><td class="editable" data-field="dropoff"><?php echo $lead['dropoff']; ?></td><?php endif; ?>
                    <?php if (in_array('lead_date', $visibleColumns)) : ?><td class="editable" data-field="lead_date"><?php echo $lead['lead_date']; ?></td><?php endif; ?>
                    <?php if (in_array('phone', $visibleColumns)) : ?><td class="editable" data-field="phone"><?php echo $lead['phone']; ?></td><?php endif; ?>
                    <?php if (in_array('email', $visibleColumns)) : ?><td class="editable" data-field="email"><?php echo $lead['email']; ?></td><?php endif; ?>
                    <?php if (in_array('details', $visibleColumns)) : ?><td class="editable" data-field="details"><?php echo $lead['details']; ?></td><?php endif; ?>
                    <?php if (in_array('acceptanceLimit', $visibleColumns)) : ?><td class="editable" data-field="acceptanceLimit"><?php echo $lead['acceptanceLimit']; ?></td><?php endif; ?>
                    <?php if (in_array('booking_status', $visibleColumns)) : ?><td class="editable" data-field="booking_status"><?php echo $lead['booking_status']; ?></td><?php endif; ?>
                    <?php if (in_array('created_at', $visibleColumns)) : ?><td><?php echo $lead['created_at']; ?></td><?php endif; ?>
                    <?php if (in_array('isReleased', $visibleColumns)) : ?><td class="editable" data-field="isReleased"><?php echo $lead['isReleased']; ?></td><?php endif; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        $(document).ready(function() {
            // Date filter logic
            $('#date_filter').on('change', function() {
                var filter = $(this).val();
                if (filter === 'date_range') {
                    $('#start_date, #end_date').show();
                } else {
                    $('#start_date, #end_date').hide();
                }
            }).trigger('change');

            // Column selector logic
            $('#columnSelectorDropdown .dropdown-menu input[type="checkbox"]').on('change', function() {
                var selectedColumns = [];
                $('#columnSelectorDropdown .dropdown-menu input[type="checkbox"]:checked').each(function() {
                    selectedColumns.push($(this).val());
                });
                var url = new URL(window.location.href);
                url.searchParams.set('visible_columns', selectedColumns.join(','));
                window.location.href = url.href;
            });

            $('.editable').on('dblclick', function() {
                var $td = $(this);
                var originalValue = $td.text();
                var field = $td.data('field');
                var leadId = $td.closest('tr').data('id');

                var $input = $('<input>', {
                    type: 'text',
                    value: originalValue,
                    blur: function() {
                        var newValue = $input.val();
                        $td.text(newValue);

                        // Update the database with the new value
                        $.ajax({
                            url: 'update_lead.php',
                            method: 'POST',
                            data: {
                                lead_id: leadId,
                                field: field,
                                value: newValue
                            },
                            success: function(response) {
                                // Handle success response
                                console.log(response);
                            },
                            error: function(xhr, status, error) {
                                // Handle error response
                                console.error(xhr.responseText);
                            }
                        });
                    },
                    keyup: function(e) {
                        if (e.which === 13) { // Enter key
                            $input.blur();
                        }
                    }
                }).appendTo($td.empty()).focus();
            });

            $('.sortable').on('click', function() {
                var column = $(this).data('sort');
                var currentUrl = window.location.href.split('?')[0];
                var newUrl = currentUrl + '?sort=' + column + '&order=' + (column === '<?php echo $sortColumn; ?>' && '<?php echo $sortOrder; ?>' === 'asc' ? 'desc' : 'asc') + '&search=<?php echo urlencode($searchTerm); ?>' + '&date_filter=<?php echo urlencode($dateFilter); ?>' + '&visible_columns=<?php echo urlencode(implode(',', $visibleColumns)); ?>';
                window.location.href = newUrl;
            });

            // Initialize resizable columns if needed
            if ($.fn.resizableColumns) {
                $('.resizable').resizableColumns();
            }
        });
    </script>

    <footer>
        <br>
        <br>
    </footer>
</body>

</html>
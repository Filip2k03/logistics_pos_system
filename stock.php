<?php
// mb_logistics/stock.php

session_start(); // Start the session to access session variables

// Include necessary files
require_once 'config/config.php'; // For database connection and get_regions()
require_once 'includes/functions.php'; // For isLoggedIn(), redirectToLogin(), and sanitize_input()

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region']; // Get the logged-in user's region
$stocks = []; // Initialize an empty array to store fetched stock items
$filter_status = isset($_GET['status']) ? sanitize_input($_GET['status']) : ''; // Get status filter from GET, sanitize it
$filter_voucher = isset($_GET['voucher_number']) ? sanitize_input($_GET['voucher_number']) : ''; // Get voucher filter from GET, sanitize it


// Construct the base SQL query to fetch stock items
$sql = "SELECT
            s.id AS stock_id,
            v.id AS voucher_db_id, /* Add voucher DB ID for linking to view_voucher.php */
            v.voucher_number,
            v.origin_region,
            v.destination_region,
            s.current_location_region,
            s.status,
            s.last_status_update_at,
            v.sender_name,
            v.receiver_name,
            v.weight_kg,
            v.total_amount
        FROM
            stock s
        JOIN
            vouchers v ON s.voucher_id = v.id";

$params = [];
$types = "";
$where_clauses = [];

// Apply role-based filtering:
// If the user is NOT an admin, restrict the view to items relevant to their region.
if ($user_region !== 'ADMIN') {
    // A regular user can see items that originated from their region OR are currently located in their region.
    $where_clauses[] = "(v.origin_region = ? OR s.current_location_region = ?)";
    $params[] = $user_region;
    $params[] = $user_region;
    $types .= "ss";
}
// If user is ADMIN, no region-specific filtering is applied at this stage, they see all stock.

// Add status filter if a status is provided in the GET request
if (!empty($filter_status)) {
    $where_clauses[] = "s.status = ?";
    $params[] = $filter_status; // Add status to parameters
    $types .= "s"; // Add 's' for string type
}

// Add voucher number filter if a voucher number is provided
// Using LIKE for partial matching for better user experience
if (!empty($filter_voucher)) {
    $where_clauses[] = "v.voucher_number LIKE ?";
    $params[] = '%' . $filter_voucher . '%'; // Add wildcard characters for LIKE comparison
    $types .= "s";
}

// Assemble the WHERE clause
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY s.last_status_update_at DESC"; // Order results by last update time, newest first

// Prepare and execute the SQL query
if ($stmt = mysqli_prepare($conn, $sql)) {
    // Dynamically bind parameters based on whether filters are present
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt); // Execute the prepared statement
    $result = mysqli_stmt_get_result($stmt); // Get the result set

    // Fetch all rows and store them in the $stocks array
    while ($row = mysqli_fetch_assoc($result)) {
        $stocks[] = $row;
    }
    mysqli_stmt_close($stmt); // Close the statement
} else {
    // If statement preparation fails, set an error message
    $_SESSION['error_message'] = "Database query failed: " . mysqli_error($conn);
}

// Re-establish connection for modal dropdown if it was closed by stock.php
// This is a workaround if get_regions() is called after mysqli_close($conn)
// A better practice might be to keep $conn open until the end of the script or pass it around
if (!isset($conn) || !$conn) {
    require 'config/config.php'; // Reconnect if necessary
}

mysqli_close($conn); // Close the database connection
?>

<?php include 'includes/header.php'; // Include the common header HTML ?>

<div class="container">
    <h1 class="mb-4 text-center">Stock Overview
        <?php if ($user_region !== 'ADMIN'): ?>
            (Your Region: <?php echo htmlspecialchars($user_region); ?>)
        <?php else: ?>
            (All Regions - Admin View)
        <?php endif; ?>
    </h1>

    <?php if (isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show alert-fixed" role="alert">
            <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show alert-fixed" role="alert">
            <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card p-4 mb-4">
        <h4 class="mb-3">Filter Stock</h4>
        <form action="stock.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="filter_status" class="form-label">Status</label>
                <select class="form-select" id="filter_status" name="status">
                    <option value="">All Statuses</option>
                    <option value="PENDING_ORIGIN_PICKUP" <?php echo ($filter_status == 'PENDING_ORIGIN_PICKUP') ? 'selected' : ''; ?>>Pending Origin Pickup</option>
                    <option value="IN_TRANSIT" <?php echo ($filter_status == 'IN_TRANSIT') ? 'selected' : ''; ?>>In Transit</option>
                    <option value="ARRIVED_PENDING_RECEIVE" <?php echo ($filter_status == 'ARRIVED_PENDING_RECEIVE') ? 'selected' : ''; ?>>Arrived - Pending Receive</option>
                    <option value="DELIVERED" <?php echo ($filter_status == 'DELIVERED') ? 'selected' : ''; ?>>Delivered</option>
                    <option value="RETURNED" <?php echo ($filter_status == 'RETURNED') ? 'selected' : ''; ?>>Returned</option>
                </select>
            </div>
            <div class="col-md-4">
                <label for="filter_voucher" class="form-label">Voucher Number</label>
                <input type="text" class="form-control" id="filter_voucher" name="voucher_number" value="<?php echo htmlspecialchars($filter_voucher); ?>" placeholder="e.g., MD000001">
            </div>
            <div class="col-md-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-2">Apply Filter</button>
                <a href="stock.php" class="btn btn-secondary">Clear Filter</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Voucher No.</th>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Current Loc.</th>
                    <th>Status</th>
                    <th>Last Update</th>
                    <th>Sender</th>
                    <th>Receiver</th>
                    <th>Weight (KG)</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($stocks)): ?>
                    <tr>
                        <td colspan="11" class="text-center">No stock items found for your region or matching filters.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($stocks as $stock): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($stock['voucher_number']); ?></td>
                            <td><?php echo htmlspecialchars($stock['origin_region']); ?></td>
                            <td><?php echo htmlspecialchars($stock['destination_region']); ?></td>
                            <td><?php echo htmlspecialchars($stock['current_location_region']); ?></td>
                            <td><span class="badge <?php
                                // Apply Bootstrap badge classes based on status for visual differentiation
                                switch ($stock['status']) {
                                    case 'PENDING_ORIGIN_PICKUP': echo 'bg-warning text-dark'; break;
                                    case 'IN_TRANSIT': echo 'bg-info'; break;
                                    case 'ARRIVED_PENDING_RECEIVE': echo 'bg-primary'; break;
                                    case 'DELIVERED': echo 'bg-success'; break;
                                    case 'RETURNED': echo 'bg-secondary'; break;
                                    default: echo 'bg-light text-dark'; break; // Default style
                                }
                            ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $stock['status'])); ?></span></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($stock['last_status_update_at'])); ?></td>
                            <td><?php echo htmlspecialchars($stock['sender_name']); ?></td>
                            <td><?php echo htmlspecialchars($stock['receiver_name']); ?></td>
                            <td><?php echo htmlspecialchars($stock['weight_kg']); ?></td>
                            <td>$<?php echo number_format($stock['total_amount'], 2); ?></td>
                            <td class="d-flex flex-column flex-md-row gap-1">
                                <a href="view_voucher.php?voucher_id=<?php echo $stock['voucher_db_id']; ?>" class="btn btn-sm btn-secondary">View Voucher</a>
                                <?php
                                $can_update = false;
                                // Authorization logic for updating status:
                                // If admin, they can update anything (valid transitions still apply)
                                if ($user_region === 'ADMIN') {
                                    $can_update = true;
                                } else {
                                    // Regular user logic:
                                    // 1. The item's current location is the user's region AND it's not already delivered or returned.
                                    if ($stock['current_location_region'] == $user_region &&
                                        $stock['status'] != 'DELIVERED' && $stock['status'] != 'RETURNED') {
                                        $can_update = true;
                                    }
                                    // 2. The item originated from the user's region AND its status is PENDING_ORIGIN_PICKUP.
                                    if ($stock['origin_region'] == $user_region && $stock['status'] == 'PENDING_ORIGIN_PICKUP') {
                                        $can_update = true;
                                    }
                                    // 3. The user is the destination region and the item is in transit.
                                    if ($stock['destination_region'] == $user_region && $stock['status'] == 'IN_TRANSIT') {
                                        $can_update = true;
                                    }
                                }
                                ?>
                                <?php if ($can_update): ?>
                                    <a href="update_status.php?stock_id=<?php echo $stock['stock_id']; ?>" class="btn btn-sm btn-primary">Update Status</a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Update Stock Status Modal -->
<div class="modal fade" id="updateStockModal" tabindex="-1" aria-labelledby="updateStockModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content rounded-3 shadow">
            <div class="modal-header bg-primary text-white border-bottom-0 rounded-top-3">
                <h5 class="modal-title" id="updateStockModalLabel">Update Stock Status</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="updateStockForm" action="update_stock.php" method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="stock_id" id="modal_stock_id">
                    <input type="hidden" name="user_region" value="<?php echo htmlspecialchars($user_region); ?>">
                    <div class="mb-3">
                        <label for="modal_current_status" class="form-label">Current Status</label>
                        <input type="text" class="form-control" id="modal_current_status" readonly>
                    </div>
                    <div class="mb-3">
                        <label for="new_status" class="form-label">Select New Status</label>
                        <select class="form-select" id="new_status" name="new_status" required>
                            <!-- Options will be dynamically populated by JS based on current status and user region -->
                        </select>
                    </div>
                    <div class="mb-3">
                         <label for="modal_current_location" class="form-label">Current Location (for status update)</label>
                        <select class="form-select" id="modal_current_location" name="new_location_region" required>
                             <?php
                             // Get all regions for the modal dropdown. Re-fetching them to ensure $conn is active here.
                             // Need to reopen connection temporarily or pass it if it was closed by stock.php
                             // A better practice might be to keep $conn open until the end of the script or pass it around
                             if (!isset($conn) || !$conn) {
                                require 'config/config.php'; // Reconnect if necessary
                             }
                             $all_regions_for_modal = get_regions($conn); // Re-fetch or reuse if available from earlier scope
                             foreach ($all_regions_for_modal as $region_mod) {
                                 // Exclude 'ADMIN' as a physical location
                                 if ($region_mod['region_code'] !== 'ADMIN') {
                                     echo '<option value="' . htmlspecialchars($region_mod['region_code']) . '">' . htmlspecialchars($region_mod['region_name']) . '</option>';
                                 }
                             }
                             ?>
                        </select>
                    </div>
                </div>
                <div class="modal-footer flex-column border-top-0 px-4 pb-3">
                    <button type="submit" class="btn btn-lg btn-primary w-100 mx-0 mb-2 rounded-pill">Update Status</button>
                    <button type="button" class="btn btn-lg btn-light w-100 mx-0 rounded-pill" data-bs-dismiss="modal">Cancel</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const updateStockModal = document.getElementById('updateStockModal');
    updateStockModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget; // Button that triggered the modal
        const stockId = button.getAttribute('data-stock-id');
        const currentStatus = button.getAttribute('data-current-status');
        const originRegion = button.getAttribute('data-origin-region');
        const destinationRegion = button.getAttribute('data-destination-region');
        const currentLocation = button.getAttribute('data-current-location');
        const userRegion = "<?php echo $_SESSION['region']; ?>"; // Get user's region from PHP session

        const modalStockId = updateStockModal.querySelector('#modal_stock_id');
        const modalCurrentStatus = updateStockModal.querySelector('#modal_current_status');
        const newStatusSelect = updateStockModal.querySelector('#new_status');
        const modalCurrentLocationSelect = updateStockModal.querySelector('#modal_current_location');

        modalStockId.value = stockId;
        modalCurrentStatus.value = currentStatus.replace(/_/g, ' '); // Display nicely with spaces
        modalCurrentLocationSelect.value = currentLocation; // Set initial location based on stock data

        // Clear previous options in the new status dropdown
        newStatusSelect.innerHTML = '';

        // Dynamically populate new status options based on current status and user's region permissions
        let availableStatuses = [];

        // Admin can perform any valid transition regardless of location
        if (userRegion === 'ADMIN') {
            switch (currentStatus) {
                case 'PENDING_ORIGIN_PICKUP':
                    availableStatuses.push({value: 'IN_TRANSIT', text: 'In Transit'});
                    availableStatuses.push({value: 'ARRIVED_PENDING_RECEIVE', text: 'Arrived - Pending Receive'}); // Admin can force arrival
                    availableStatuses.push({value: 'DELIVERED', text: 'Delivered'}); // Admin can force delivered
                    availableStatuses.push({value: 'RETURNED', text: 'Returned'});
                    break;
                case 'IN_TRANSIT':
                    availableStatuses.push({value: 'ARRIVED_PENDING_RECEIVE', text: 'Arrived - Pending Receive'});
                    availableStatuses.push({value: 'DELIVERED', text: 'Delivered'}); // Admin can force delivered
                    availableStatuses.push({value: 'RETURNED', text: 'Returned'}); // Admin can force returned
                    break;
                case 'ARRIVED_PENDING_RECEIVE':
                    availableStatuses.push({value: 'DELIVERED', text: 'Delivered'});
                    availableStatuses.push({value: 'RETURNED', text: 'Returned'});
                    break;
                // DELIVERED and RETURNED are final states, no further transitions are allowed
            }
        } else {
            // Regular user logic for valid transitions and who can perform them
            switch (currentStatus) {
                case 'PENDING_ORIGIN_PICKUP':
                    // Only the origin region user can mark it IN_TRANSIT or RETURNED (if not picked up)
                    if (originRegion === userRegion) {
                        availableStatuses.push({value: 'IN_TRANSIT', text: 'In Transit'});
                        availableStatuses.push({value: 'RETURNED', text: 'Returned to Sender (Not picked up)'});
                    }
                    break;
                case 'IN_TRANSIT':
                     // If the item is in transit and the user is the destination region, they can mark it as arrived.
                     if (destinationRegion === userRegion) {
                         availableStatuses.push({value: 'ARRIVED_PENDING_RECEIVE', text: 'Arrived - Pending Receive'});
                     }
                    break;
                case 'ARRIVED_PENDING_RECEIVE':
                    // Only the current location (which should be the destination for this status) can mark as delivered/returned
                    if (currentLocation === userRegion) {
                        availableStatuses.push({value: 'DELIVERED', text: 'Delivered'});
                        availableStatuses.push({value: 'RETURNED', text: 'Returned (Not picked up by Receiver)'});
                    }
                    break;
                // DELIVERED and RETURNED are final states, no further transitions are allowed
            }
        }


        // Add generated options to the select element
        if (availableStatuses.length > 0) {
            availableStatuses.forEach(status => {
                const option = document.createElement('option');
                option.value = status.value;
                option.textContent = status.text;
                newStatusSelect.appendChild(option);
            });
            // Enable the select and submit button if there are valid options
            newStatusSelect.disabled = false;
            updateStockModal.querySelector('button[type="submit"]').disabled = false;
        } else {
            // If no valid transitions, display a message and disable controls
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No further updates possible';
            newStatusSelect.appendChild(option);
            newStatusSelect.disabled = true; // Disable the dropdown
            updateStockModal.querySelector('button[type="submit"]').disabled = true; // Disable the submit button
        }
    });

    // Handle form submission via browser confirmation
    document.getElementById('updateStockForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission initially

        // Use browser's confirm dialog
        if (window.confirm('Are you sure you want to update the status of this stock item?')) {
            e.target.submit();
        }
    });
});
</script>

<?php include 'includes/footer.php'; // Include the common footer HTML ?>
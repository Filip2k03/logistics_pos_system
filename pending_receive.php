<?php
// mb_logistics/pending_receive.php

session_start(); // Start the session to access session variables

// Include necessary files
require_once 'config/config.php'; // For database connection and get_regions()
require_once 'includes/functions.php'; // For isLoggedIn(), redirectToLogin(), and sanitize_input()

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region']; // Get the logged-in user's region
$pending_items = []; // Initialize an empty array to store pending items
$months_threshold = 9; // Define the threshold for "long pending" items (e.g., 9 months)

// SQL query to retrieve items that are:
// 1. In 'ARRIVED_PENDING_RECEIVE' status.
// 2. Have been in this status for longer than the defined months_threshold.
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
            v.receiver_phone,
            v.receiver_address,
            v.weight_kg,
            v.total_amount
        FROM
            stock s
        JOIN
            vouchers v ON s.voucher_id = v.id
        WHERE
            s.status = 'ARRIVED_PENDING_RECEIVE'
            AND s.last_status_update_at < DATE_SUB(NOW(), INTERVAL ? MONTH)";

$params = [$months_threshold]; // Initialize parameters for the prepared statement
$types = "i"; // Initialize parameter types string

// If the user is NOT an admin, apply region-specific filtering for current location
if ($user_region !== 'ADMIN') {
    $sql .= " AND s.current_location_region = ?";
    $params[] = $user_region;
    $types .= "s";
}
// If user is ADMIN, no location filtering is applied, they see all long-pending items regardless of location.

$sql .= " ORDER BY s.last_status_update_at ASC"; // Order by oldest items first

// Prepare and execute the SQL query
if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params); // Bind user region and months threshold
    mysqli_stmt_execute($stmt); // Execute the prepared statement
    $result = mysqli_stmt_get_result($stmt); // Get the result set

    // Fetch all rows and store them in the $pending_items array
    while ($row = mysqli_fetch_assoc($result)) {
        $pending_items[] = $row;
    }
    mysqli_stmt_close($stmt); // Close the statement
} else {
    // If statement preparation fails, set an error message
    $_SESSION['error_message'] = "Database query failed: " . mysqli_error($conn);
}

// Re-establish connection for modal dropdown if it was closed by pending_receive.php
// This is a workaround if get_regions() is called after mysqli_close($conn)
if (!isset($conn) || !$conn) {
    require 'config/config.php'; // Reconnect if necessary
}

mysqli_close($conn); // Close the database connection
?>

<?php include 'includes/header.php'; // Include the common header HTML ?>

<div class="container">
    <h1 class="mb-4 text-center">Delivery Pending Receive
        <?php if ($user_region !== 'ADMIN'): ?>
            (Your Region: <?php echo htmlspecialchars($user_region); ?>)
        <?php else: ?>
            (All Regions - Admin View)
        <?php endif; ?>
    </h1>
    <p class="text-muted text-center">Items that have been in "Arrived - Pending Receive" status at their current location for more than <?php echo $months_threshold; ?> months.</p>

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

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Voucher No.</th>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Current Loc.</th> <!-- Added Current Location for Admin View -->
                    <th>Last Update</th>
                    <th>Receiver Name</th>
                    <th>Receiver Phone</th>
                    <th>Receiver Address</th>
                    <th>Weight (KG)</th>
                    <th>Amount</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($pending_items)): ?>
                    <tr>
                        <td colspan="11" class="text-center">No items currently pending receive for more than <?php echo $months_threshold; ?> months in <?php echo ($user_region !== 'ADMIN') ? 'your region' : 'all regions'; ?>.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pending_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['voucher_number']); ?></td>
                            <td><?php echo htmlspecialchars($item['origin_region']); ?></td>
                            <td><?php echo htmlspecialchars($item['destination_region']); ?></td>
                            <td><?php echo htmlspecialchars($item['current_location_region']); ?></td> <!-- Display current location -->
                            <td><?php echo date('Y-m-d H:i', strtotime($item['last_status_update_at'])); ?></td>
                            <td><?php echo htmlspecialchars($item['receiver_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['receiver_phone']); ?></td>
                            <td><?php echo htmlspecialchars($item['receiver_address']); ?></td>
                            <td><?php echo htmlspecialchars($item['weight_kg']); ?></td>
                            <td>$<?php echo number_format($item['total_amount'], 2); ?></td>
                            <td class="d-flex flex-column flex-md-row gap-1">
                                <a href="view_voucher.php?voucher_id=<?php echo $item['voucher_db_id']; ?>" class="btn btn-sm btn-secondary">View Voucher</a>
                                <!-- Button to trigger the update status modal -->
                                <button type="button" class="btn btn-sm btn-warning"
                                        data-bs-toggle="modal"
                                        data-bs-target="#updateStockModal"
                                        data-stock-id="<?php echo $item['stock_id']; ?>"
                                        data-current-status="<?php echo $item['status']; ?>"
                                        data-origin-region="<?php echo $item['origin_region']; ?>"
                                        data-destination-region="<?php echo $item['destination_region']; ?>"
                                        data-current-location="<?php echo $item['current_location_region']; ?>">
                                    Mark as Delivered/Returned
                                </button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Update Stock Status Modal (reused from stock.php but needs local data for regions) -->
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
                             // Get all regions for the modal dropdown. This ensures the dropdown is populated.
                             // Re-fetching them to ensure $conn is active within this scope.
                             if (!isset($conn) || !$conn) {
                                require 'config/config.php'; // Reconnect if necessary
                             }
                             $all_regions_for_modal = get_regions($conn);
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

    // Handle form submission via custom modal confirmation
    document.getElementById('updateStockForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission initially

        // Show the custom confirmation modal
        showConfirmModal(function(confirmed) {
            if (confirmed) {
                // If user confirms, programmatically submit the form
                e.target.submit();
            } else {
                // If user cancels, do nothing or close the modal if it's still open
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; // Include the common footer HTML ?>

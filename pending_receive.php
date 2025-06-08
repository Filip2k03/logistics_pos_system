<?php
// mb_logistics/pending_receive.php

session_start();

require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region'];
$pending_items = [];
$months_threshold = 9; // Define the threshold for "long pending" items

$sql = "SELECT
            s.id AS stock_id,
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
            AND s.current_location_region = ?
            AND s.last_status_update_at < DATE_SUB(NOW(), INTERVAL ? MONTH)
        ORDER BY s.last_status_update_at ASC"; // Order by oldest first

if ($stmt = mysqli_prepare($conn, $sql)) {
    mysqli_stmt_bind_param($stmt, "si", $user_region, $months_threshold);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    while ($row = mysqli_fetch_assoc($result)) {
        $pending_items[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error_message'] = "Database query failed: " . mysqli_error($conn);
}

mysqli_close($conn);
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h1 class="mb-4 text-center">Delivery Pending Receive (Your Region: <?php echo htmlspecialchars($user_region); ?>)</h1>
    <p class="text-muted text-center">Items that have been in "Arrived - Pending Receive" status at your location for more than <?php echo $months_threshold; ?> months.</p>

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
                        <td colspan="10" class="text-center">No items currently pending receive for more than <?php echo $months_threshold; ?> months in your region.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pending_items as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['voucher_number']); ?></td>
                            <td><?php echo htmlspecialchars($item['origin_region']); ?></td>
                            <td><?php echo htmlspecialchars($item['destination_region']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($item['last_status_update_at'])); ?></td>
                            <td><?php echo htmlspecialchars($item['receiver_name']); ?></td>
                            <td><?php echo htmlspecialchars($item['receiver_phone']); ?></td>
                            <td><?php echo htmlspecialchars($item['receiver_address']); ?></td>
                            <td><?php echo htmlspecialchars($item['weight_kg']); ?></td>
                            <td>$<?php echo number_format($item['total_amount'], 2); ?></td>
                            <td>
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

<!-- Update Stock Status Modal (reused from stock.php) -->
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
                            <!-- Options will be dynamically populated by JS -->
                        </select>
                    </div>
                    <div class="mb-3">
                         <label for="modal_current_location" class="form-label">Current Location (for status update)</label>
                        <select class="form-select" id="modal_current_location" name="new_location_region" required>
                             <?php
                             // Get all regions for the modal dropdown
                             $all_regions_for_modal = get_regions($conn); // Re-fetch all regions for the modal dropdown
                             foreach ($all_regions_for_modal as $region_mod) {
                                 echo '<option value="' . htmlspecialchars($region_mod['region_code']) . '">' . htmlspecialchars($region_mod['region_name']) . '</option>';
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
        modalCurrentStatus.value = currentStatus.replace(/_/g, ' '); // Display nicely
        modalCurrentLocationSelect.value = currentLocation; // Set initial location

        // Clear previous options
        newStatusSelect.innerHTML = '';

        // Dynamically populate new status options based on current status and user's region
        let availableStatuses = [];

        // Define valid transitions for items in "ARRIVED_PENDING_RECEIVE" for this page
        if (currentStatus === 'ARRIVED_PENDING_RECEIVE' && currentLocation === userRegion) {
            availableStatuses.push({value: 'DELIVERED', text: 'Delivered'});
            availableStatuses.push({value: 'RETURNED', text: 'Returned (Not picked up by Receiver)'});
        }

        // Add options to the select element
        if (availableStatuses.length > 0) {
            availableStatuses.forEach(status => {
                const option = document.createElement('option');
                option.value = status.value;
                option.textContent = status.text;
                newStatusSelect.appendChild(option);
            });
            newStatusSelect.disabled = false;
            updateStockModal.querySelector('button[type="submit"]').disabled = false;
        } else {
            const option = document.createElement('option');
            option.value = '';
            option.textContent = 'No further updates possible';
            newStatusSelect.appendChild(option);
            newStatusSelect.disabled = true;
            updateStockModal.querySelector('button[type="submit"]').disabled = true;
        }
    });

    // Handle form submission via custom modal
    document.getElementById('updateStockForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent default form submission

        showConfirmModal(function(confirmed) {
            if (confirmed) {
                e.target.submit(); // If confirmed, submit the form
            } else {
                // User cancelled, do nothing or close modal
            }
        });
    });
});
</script>

<?php include 'includes/footer.php'; ?>

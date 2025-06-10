<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

if (!isset($_GET['stock_id'])) {
    $_SESSION['error_message'] = "No stock item selected.";
    header("Location: stock.php");
    exit;
}

$stock_id = intval($_GET['stock_id']);
$result = mysqli_query($conn, "SELECT * FROM stock WHERE id = $stock_id");

if (!$result || mysqli_num_rows($result) === 0) {
    $_SESSION['error_message'] = "Stock item not found.";
    header("Location: stock.php");
    exit;
}

$stock = mysqli_fetch_assoc($result);

// Fetch only origin and destination regions for the dropdown
$regions = [];
if (!empty($stock['voucher_id'])) {
    $voucher_result = mysqli_query($conn, "SELECT origin_region, destination_region FROM vouchers WHERE id = " . intval($stock['voucher_id']));
    if ($voucher_result && $voucher_row = mysqli_fetch_assoc($voucher_result)) {
        $origin_region = $voucher_row['origin_region'];
        $destination_region = $voucher_row['destination_region'];
        // Get region names
        $region_query = mysqli_query($conn, "SELECT region_code, region_name FROM regions WHERE region_code IN ('$origin_region', '$destination_region')");
        if ($region_query) {
            while ($row = mysqli_fetch_assoc($region_query)) {
                $regions[] = $row;
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = sanitize_input($_POST['status']);
    $new_location = sanitize_input($_POST['current_location_region']);
    $update = mysqli_query($conn, "UPDATE stock SET status = '$new_status', current_location_region = '$new_location', last_status_update_at = NOW() WHERE id = $stock_id");

    if ($update) {
        $_SESSION['success_message'] = "Status and location updated successfully!";
    } else {
        $_SESSION['error_message'] = "Failed to update status.";
    }

    header("Location: stock.php");
    exit;
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h2>Update Status for Voucher #<?php echo htmlspecialchars($stock['voucher_id']); ?></h2>
    <form method="POST">
        <div class="mb-3">
            <label for="status" class="form-label">New Status</label>
            <select name="status" id="status" class="form-select" required>
                <option value="IN_TRANSIT">In Transit</option>
                <option value="ARRIVED_PENDING_RECEIVE">Arrived - Pending Receive</option>
                <option value="DELIVERED">Delivered</option>
                <option value="RETURNED">Returned</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="current_location_region" class="form-label">Current Location (Region)</label>
            <select name="current_location_region" id="current_location_region" class="form-select" required>
                <?php foreach ($regions as $region): ?>
                    <option value="<?php echo htmlspecialchars($region['region_code']); ?>" <?php if ($stock['current_location_region'] == $region['region_code']) echo 'selected'; ?>>
                        <?php echo htmlspecialchars($region['region_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="stock.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

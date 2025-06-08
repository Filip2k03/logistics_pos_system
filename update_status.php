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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $new_status = sanitize_input($_POST['status']);
    $update = mysqli_query($conn, "UPDATE stock SET status = '$new_status', last_status_update_at = NOW() WHERE id = $stock_id");

    if ($update) {
        $_SESSION['success_message'] = "Status updated successfully!";
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
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="stock.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>

<?php include 'includes/footer.php'; ?>

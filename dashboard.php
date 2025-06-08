<?php
// mb_logistics/dashboard.php

session_start(); // Start the session to access session variables

// Include necessary files
require_once 'config/config.php'; // For database connection
require_once 'includes/functions.php'; // For isLoggedIn(), redirectToLogin(), get_daily_voucher_count(), get_monthly_profit()

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

// Get the logged-in user's region from the session
$user_region = $_SESSION['region'];

// Fetch daily voucher count and monthly profit
// $daily_vouchers = get_daily_voucher_count($conn, $user_region);
// $monthly_profit = get_monthly_profit($conn, $user_region);

// Close connection for dashboard page (will be reopened by other pages if needed)
mysqli_close($conn);
?>

<?php include 'includes/header.php'; // Include the common header HTML ?>

<div class="container">
    <h1 class="mb-4 text-center">Dashboard for <?php echo htmlspecialchars($user_region); ?> Region</h1>

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

    <div class="row g-4 mb-4">
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 p-3 bg-light">
                <div class="card-body">
                    <h5 class="card-title text-primary">Vouchers Created Today</h5>
                    <p class="display-4 text-dark"><?php echo $daily_vouchers; ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 p-3 bg-light">
                <div class="card-body">
                    <h5 class="card-title text-primary">Monthly Profit (Current Month)</h5>
                    <p class="display-4 text-dark">$<?php echo number_format($monthly_profit, 2); ?></p>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Create New Voucher</h5>
                    <p class="card-text">Generate a new shipment voucher and track an item.</p>
                    <a href="create_voucher.php" class="btn btn-primary">Go to Voucher Creation</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">View Stock</h5>
                    <p class="card-text">Check the current status and location of all items.</p>
                    <a href="stock.php" class="btn btn-primary">View All Stock</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Delivery Pending Receive</h5>
                    <p class="card-text">See items waiting for pickup for an extended period.</p>
                    <a href="pending_receive.php" class="btn btn-primary">View Pending Items</a>
                </div>
            </div>
        </div>
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">View All Vouchers</h5>
                    <p class="card-text">Access a comprehensive list of all created vouchers.</p>
                    <a href="voucher_list.php" class="btn btn-primary">Go to Voucher List</a>
                </div>
            </div>
        </div>
        <!-- Add more cards for other functionalities if needed in the future -->
    </div>
</div>

<?php include 'includes/footer.php'; // Include the common footer HTML ?>

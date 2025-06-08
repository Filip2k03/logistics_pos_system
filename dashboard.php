<?php
// mb_logistics/dashboard.php

session_start();

require_once 'includes/functions.php';

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

$region = $_SESSION['region'];
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h1 class="mb-4 text-center">Dashboard for <?php echo htmlspecialchars($region); ?> Region</h1>

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

    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <div class="col">
            <div class="card text-center h-100 dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Create New Voucher</h5>
                    <p class="card-text">Generate a new shipment voucher and track an item.</p>
                    <a href="create_voucher.php" class="btn btn-primary">Go to Voucher Creation</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center h-100 dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">View Stock</h5>
                    <p class="card-text">Check the current status and location of all items.</p>
                    <a href="stock.php" class="btn btn-primary">View All Stock</a>
                </div>
            </div>
        </div>
        <div class="col">
            <div class="card text-center h-100 dashboard-card">
                <div class="card-body">
                    <h5 class="card-title">Delivery Pending Receive</h5>
                    <p class="card-text">See items waiting for pickup for an extended period.</p>
                    <a href="pending_receive.php" class="btn btn-primary">View Pending Items</a>
                </div>
            </div>
        </div>
        <!-- Add more cards for other functionalities if needed in the future -->
    </div>
</div>

<?php include 'includes/footer.php'; ?>

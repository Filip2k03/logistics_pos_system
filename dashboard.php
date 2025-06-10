<?php
session_start();

require_once 'config/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region'];

// Fetch daily voucher count (filtered by region)
$daily_vouchers = get_daily_voucher_count($conn, $user_region);

// Fetch profits by currency (only for ADMIN)
$profits_by_currency = [];
if ($user_region === 'ADMIN') {
    $sql = "SELECT currency, SUM(total_amount) as profit
            FROM vouchers
            WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
              AND YEAR(created_at) = YEAR(CURRENT_DATE())
            GROUP BY currency";
    $result = mysqli_query($conn, $sql);
    while ($row = mysqli_fetch_assoc($result)) {
        $profits_by_currency[$row['currency']] = $row['profit'];
    }
} else {
    // For non-admin, show only their region's profit in their currency
    $sql = "SELECT currency, SUM(total_amount) as profit
            FROM vouchers
            WHERE (origin_region = ? OR destination_region = ?)
              AND MONTH(created_at) = MONTH(CURRENT_DATE())
              AND YEAR(created_at) = YEAR(CURRENT_DATE())
            GROUP BY currency";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ss", $user_region, $user_region);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $profits_by_currency[$row['currency']] = $row['profit'];
    }
    mysqli_stmt_close($stmt);
}

mysqli_close($conn);
?>

<?php include 'includes/header.php'; ?>

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
        <!-- Daily Vouchers Card -->
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 p-3 bg-light">
                <div class="card-body">
                    <h5 class="card-title text-primary">Vouchers Created Today</h5>
                    <p class="display-4 text-dark"><?php echo $daily_vouchers; ?></p>
                </div>
            </div>
        </div>
        <!-- Profits by Currency Card (ADMIN only) -->
        <?php if ($user_region === 'ADMIN'): ?>
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 p-3 bg-light">
                <div class="card-body">
                    <h5 class="card-title text-primary">Monthly Profit (Current Month)</h5>
                    <?php if (empty($profits_by_currency)): ?>
                        <p class="text-muted">No data</p>
                    <?php else: ?>
                        <?php foreach ($profits_by_currency as $currency => $profit): ?>
                            <p class="display-6 text-dark"><?php echo htmlspecialchars($currency) . ' ' . number_format($profit, 2); ?></p>
                        <?php endforeach; ?>
                        <a href="profits.php" class="btn btn-outline-primary btn-sm mt-2">View All Profits</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
        <!-- Expenses Card (Visible to ALL users) -->
        <div class="col-md-6 col-lg-4">
            <div class="card text-center h-100 p-3 bg-light">
                <div class="card-body">
                    <h5 class="card-title text-danger">Add Expense</h5>
                    <p class="card-text">Record a new delivery or business expense.</p>
                    <a href="expenses.php" class="btn btn-danger">
                        <i class="bi bi-cash-stack"></i> Add Expense
                    </a>
                    <?php if ($user_region === 'ADMIN'): ?>
                    <hr>
                    <a href="expense_list.php" class="btn btn-outline-danger btn-sm mt-2">
                        <i class="bi bi-list-ul"></i> View Expense List
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <!-- Other dashboard cards ... -->
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
    </div>
</div>

<?php include 'includes/footer.php'; ?>
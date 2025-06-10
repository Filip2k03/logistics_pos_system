<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || $_SESSION['region'] !== 'ADMIN') {
    redirectToLogin();
}

// Get profits by currency
$sql = "SELECT currency, SUM(total_amount) as profit
        FROM vouchers
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
        GROUP BY currency";
$result = mysqli_query($conn, $sql);

$profits = [];
while ($row = mysqli_fetch_assoc($result)) {
    $profits[$row['currency']] = $row['profit'];
}

// Get expenses by currency (assuming you have an 'expenses' table with 'amount', 'currency', 'created_at')
$expenses = [];
if (mysqli_query($conn, "SHOW TABLES LIKE 'expenses'")->num_rows > 0) {
    $sql_exp = "SELECT currency, SUM(amount) as expense
                FROM expenses
                WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
                  AND YEAR(created_at) = YEAR(CURRENT_DATE())
                GROUP BY currency";
    $result_exp = mysqli_query($conn, $sql_exp);
    while ($row = mysqli_fetch_assoc($result_exp)) {
        $expenses[$row['currency']] = $row['expense'];
    }
}
mysqli_close($conn);
?>

<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <h1 class="mb-4 text-center">Monthly Profits by Currency</h1>
    <table class="table table-bordered table-striped">
        <thead>
            <tr>
                <th>Currency</th>
                <th>Total Profit</th>
                <th>Total Expense</th>
                <th>Net Profit</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $all_currencies = array_unique(array_merge(array_keys($profits), array_keys($expenses)));
            foreach ($all_currencies as $currency):
                $profit = isset($profits[$currency]) ? $profits[$currency] : 0;
                $expense = isset($expenses[$currency]) ? $expenses[$currency] : 0;
                $net = $profit - $expense;
            ?>
                <tr>
                    <td><?php echo htmlspecialchars($currency); ?></td>
                    <td><?php echo number_format($profit, 2); ?></td>
                    <td><?php echo number_format($expense, 2); ?></td>
                    <td><?php echo number_format($net, 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
<?php include 'includes/footer.php'; ?>
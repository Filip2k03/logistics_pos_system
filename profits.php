<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn() || $_SESSION['region'] !== 'ADMIN') {
    redirectToLogin();
}

$sql = "SELECT currency, SUM(total_amount) as profit
        FROM vouchers
        WHERE MONTH(created_at) = MONTH(CURRENT_DATE())
          AND YEAR(created_at) = YEAR(CURRENT_DATE())
        GROUP BY currency";
$result = mysqli_query($conn, $sql);

$profits = [];
while ($row = mysqli_fetch_assoc($result)) {
    $profits[] = $row;
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
            </tr>
        </thead>
        <tbody>
            <?php foreach ($profits as $row): ?>
                <tr>
                    <td><?php echo htmlspecialchars($row['currency']); ?></td>
                    <td><?php echo number_format($row['profit'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <a href="dashboard.php" class="btn btn-secondary mt-3">Back to Dashboard</a>
</div>
<?php include 'includes/footer.php'; ?>
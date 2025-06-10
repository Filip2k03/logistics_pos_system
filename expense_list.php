<?php
// mb_logistics/expense_list.php

session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region'];
$expenses = [];
$filter_region = isset($_GET['region']) ? sanitize_input($_GET['region']) : '';
$filter_date = isset($_GET['date']) ? sanitize_input($_GET['date']) : ''; // This now correctly refers to the 'date' column

// Build SQL
// Select 'e.date' which is the new column for the expense date
// Select 'e.created_at' as created_at for the record creation timestamp
$sql = "SELECT e.id, e.date, e.region, e.amount, e.description, e.created_at, u.username
        FROM expenses e
        LEFT JOIN users u ON e.created_by = u.id";
$where = [];
$params = [];
$types = "";

if ($user_region !== 'ADMIN') {
    $where[] = "e.region = ?";
    $params[] = $user_region;
    $types .= "s";
} elseif ($filter_region) {
    $where[] = "e.region = ?";
    $params[] = $filter_region;
    $types .= "s";
}

if ($filter_date) {
    $where[] = "e.date = ?"; // Filter by the new 'date' column
    $params[] = $filter_date;
    $types .= "s";
}

if ($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY e.date DESC, e.id DESC"; // Order by the new 'date' column

$stmt = mysqli_prepare($conn, $sql);
if ($stmt) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $expenses[] = $row;
    }
    mysqli_stmt_close($stmt);
} else {
    $_SESSION['error_message'] = "Database error: " . mysqli_error($conn);
}

$regions = get_regions($conn);
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <h1 class="mb-4 text-center">Expense List</h1>

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
        <form method="get" class="row g-3 align-items-end">
            <?php if ($user_region === 'ADMIN'): ?>
            <div class="col-md-4">
                <label for="region" class="form-label">Region</label>
                <select name="region" id="region" class="form-select">
                    <option value="">All Regions</option>
                    <?php foreach ($regions as $region): ?>
                        <?php if ($region['region_code'] !== 'ADMIN'): ?>
                        <option value="<?php echo htmlspecialchars($region['region_code']); ?>" <?php if ($filter_region == $region['region_code']) echo 'selected'; ?>>
                            <?php echo htmlspecialchars($region['region_name']); ?>
                        </option>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>
            <div class="col-md-4">
                <label for="date" class="form-label">Expense Date</label>
                <input type="date" name="date" id="date" class="form-control" value="<?php echo htmlspecialchars($filter_date); ?>">
            </div>
            <div class="col-md-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-2">Filter</button>
                <a href="expense_list.php" class="btn btn-secondary">Clear</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
    <table class="table table-striped table-hover">
        <thead class="table-dark">
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Region</th>
                <th>Amount</th>
                <th>Description</th>
                <th>Created By</th>
                <th>Created At</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($expenses)): ?>
                <tr>
                    <td colspan="7" class="text-center">No expenses found.</td>
                </tr>
            <?php else: ?>
                <?php foreach ($expenses as $i => $exp): ?>
                    <tr>
                        <td><?php echo $i + 1; ?></td>
                        <td><?php echo htmlspecialchars($exp['date'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($exp['region'] ?? ''); ?></td>
                        <td>$<?php echo number_format($exp['amount'] ?? 0, 2); ?></td>
                        <td><?php echo htmlspecialchars($exp['description'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($exp['username'] ?? 'N/A'); ?></td>
                        <td><?php echo htmlspecialchars($exp['created_at'] ?? ''); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</div>

<?php include 'includes/footer.php'; ?>
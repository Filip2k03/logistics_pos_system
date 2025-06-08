<?php
// mb_logistics/voucher_list.php

session_start(); // Start the session to access session variables

// Include necessary files
require_once 'config/config.php'; // For database connection and get_regions()
require_once 'includes/functions.php'; // For isLoggedIn(), redirectToLogin(), and sanitize_input()

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region']; // Get the logged-in user's region
$vouchers = []; // Initialize an empty array to store fetched vouchers

// Filters from GET request
$filter_voucher_number = isset($_GET['voucher_number']) ? sanitize_input($_GET['voucher_number']) : '';
$filter_origin_region = isset($_GET['origin_region']) ? sanitize_input($_GET['origin_region']) : '';
$filter_destination_region = isset($_GET['destination_region']) ? sanitize_input($_GET['destination_region']) : '';
$filter_start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$filter_end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';

$all_regions_for_filter = get_regions($conn); // Fetch all regions for filter dropdowns

// Construct the base SQL query to fetch voucher items
$sql = "SELECT
            v.id AS voucher_db_id,
            v.voucher_number,
            v.origin_region,
            v.destination_region,
            v.sender_name,
            v.receiver_name,
            v.weight_kg,
            v.total_amount,
            v.currency, /* Fetch currency from vouchers table */
            v.delivery_type, /* Fetch delivery_type */
            v.created_at,
            s.status,
            s.current_location_region,
            u.username AS created_by_username
        FROM
            vouchers v
        JOIN
            stock s ON v.id = s.voucher_id
        JOIN
            users u ON v.created_by_user_id = u.id";

$params = [];
$types = "";
$where_clauses = [];

// Authorization Filter: Restrict non-admin users to their relevant vouchers
if ($user_region !== 'ADMIN') {
    // Regular user can see vouchers where they are the origin, destination, or current location of the stock
    // This uses created_by_user_id as a proxy for origin, or checks destination/current location
    $where_clauses[] = "(v.origin_region = ? OR v.destination_region = ? OR s.current_location_region = ?)";
    $params[] = $user_region;
    $params[] = $user_region;
    $params[] = $user_region;
    $types .= "sss";
}

// Apply Filters
if (!empty($filter_voucher_number)) {
    $where_clauses[] = "v.voucher_number LIKE ?";
    $params[] = '%' . $filter_voucher_number . '%';
    $types .= "s";
}
if (!empty($filter_origin_region)) {
    $where_clauses[] = "v.origin_region = ?";
    $params[] = $filter_origin_region;
    $types .= "s";
}
if (!empty($filter_destination_region)) {
    $where_clauses[] = "v.destination_region = ?";
    $params[] = $filter_destination_region;
    $types .= "s";
}
if (!empty($filter_start_date)) {
    $where_clauses[] = "DATE(v.created_at) >= ?";
    $params[] = $filter_start_date;
    $types .= "s";
}
if (!empty($filter_end_date)) {
    $where_clauses[] = "DATE(v.created_at) <= ?";
    $params[] = $filter_end_date;
    $types .= "s";
}

// Assemble the WHERE clause
if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}

$sql .= " ORDER BY v.created_at DESC"; // Order results by creation time, newest first

// Prepare and execute the SQL query
if ($stmt = mysqli_prepare($conn, $sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt); // Execute the prepared statement
    $result = mysqli_stmt_get_result($stmt); // Get the result set

    // Fetch all rows and store them in the $vouchers array
    while ($row = mysqli_fetch_assoc($result)) {
        $vouchers[] = $row;
    }
    mysqli_stmt_close($stmt); // Close the statement
} else {
    // If statement preparation fails, set an error message
    $_SESSION['error_message'] = "Database query failed: " . mysqli_error($conn);
}

mysqli_close($conn); // Close the database connection
?>

<?php include 'includes/header.php'; // Include the common header HTML ?>

<div class="container">
    <h1 class="mb-4 text-center">Voucher List
        <?php if ($user_region !== 'ADMIN'): ?>
            (Your Vouchers)
        <?php else: ?>
            (All Vouchers - Admin View)
        <?php endif; ?>
    </h1>

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
        <h4 class="mb-3">Filter Vouchers</h4>
        <form action="voucher_list.php" method="GET" class="row g-3 align-items-end">
            <div class="col-md-4">
                <label for="filter_voucher_number" class="form-label">Voucher Number</label>
                <input type="text" class="form-control" id="filter_voucher_number" name="voucher_number" value="<?php echo htmlspecialchars($filter_voucher_number); ?>" placeholder="e.g., MD000001">
            </div>
            <div class="col-md-4">
                <label for="filter_origin_region" class="form-label">Origin Region</label>
                <select class="form-select" id="filter_origin_region" name="origin_region">
                    <option value="">All Origins</option>
                    <?php foreach ($all_regions_for_filter as $region_filter):
                        if ($region_filter['region_code'] !== 'ADMIN'): ?>
                        <option value="<?php echo htmlspecialchars($region_filter['region_code']); ?>"
                            <?php echo ($filter_origin_region == $region_filter['region_code']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($region_filter['region_name']); ?> (<?php echo htmlspecialchars($region_filter['region_code']); ?>)
                        </option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="filter_destination_region" class="form-label">Destination Region</label>
                <select class="form-select" id="filter_destination_region" name="destination_region">
                    <option value="">All Destinations</option>
                    <?php foreach ($all_regions_for_filter as $region_filter):
                        if ($region_filter['region_code'] !== 'ADMIN'): ?>
                        <option value="<?php echo htmlspecialchars($region_filter['region_code']); ?>"
                            <?php echo ($filter_destination_region == $region_filter['region_code']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($region_filter['region_name']); ?> (<?php echo htmlspecialchars($region_filter['region_code']); ?>)
                        </option>
                    <?php endif; endforeach; ?>
                </select>
            </div>
            <div class="col-md-4">
                <label for="filter_start_date" class="form-label">Start Date</label>
                <input type="date" class="form-control" id="filter_start_date" name="start_date" value="<?php echo htmlspecialchars($filter_start_date); ?>">
            </div>
            <div class="col-md-4">
                <label for="filter_end_date" class="form-label">End Date</label>
                <input type="date" class="form-control" id="filter_end_date" name="end_date" value="<?php echo htmlspecialchars($filter_end_date); ?>">
            </div>
            <div class="col-md-4 d-flex justify-content-end">
                <button type="submit" class="btn btn-primary me-2">Apply Filter</button>
                <a href="voucher_list.php" class="btn btn-secondary">Clear Filter</a>
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-hover">
            <thead class="table-dark">
                <tr>
                    <th>Voucher No.</th>
                    <th>Origin</th>
                    <th>Destination</th>
                    <th>Sender Name</th>
                    <th>Receiver Name</th>
                    <th>Weight (KG)</th>
                    <th>Total Amount</th>
                    <th>Currency</th> <!-- Added Currency column -->
                    <th>Delivery Type</th> <!-- Added Delivery Type column -->
                    <th>Current Status</th>
                    <th>Current Location</th>
                    <th>Created By</th>
                    <th>Created At</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($vouchers)): ?>
                    <tr>
                        <td colspan="14" class="text-center">No vouchers found matching your criteria.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($vouchers as $voucher): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($voucher['voucher_number']); ?></td>
                            <td><?php echo htmlspecialchars($voucher['origin_region']); ?></td>
                            <td><?php echo htmlspecialchars($voucher['destination_region']); ?></td>
                            <td><?php echo htmlspecialchars($voucher['sender_name']); ?></td>
                            <td><?php echo htmlspecialchars($voucher['receiver_name']); ?></td>
                            <td><?php echo htmlspecialchars($voucher['weight_kg']); ?></td>
                            <td><?php echo number_format($voucher['total_amount'], 2); ?></td>
                            <td><?php echo htmlspecialchars($voucher['currency']); ?></td> <!-- Display currency -->
                            <td><?php echo htmlspecialchars($voucher['delivery_type']); ?></td> <!-- Display delivery type -->
                            <td><span class="badge <?php
                                switch ($voucher['status']) {
                                    case 'PENDING_ORIGIN_PICKUP': echo 'bg-warning text-dark'; break;
                                    case 'IN_TRANSIT': echo 'bg-info'; break;
                                    case 'ARRIVED_PENDING_RECEIVE': echo 'bg-primary'; break;
                                    case 'DELIVERED': echo 'bg-success'; break;
                                    case 'RETURNED': echo 'bg-secondary'; break;
                                    default: echo 'bg-light text-dark'; break;
                                }
                            ?>"><?php echo htmlspecialchars(str_replace('_', ' ', $voucher['status'])); ?></span></td>
                            <td><?php echo htmlspecialchars($voucher['current_location_region']); ?></td>
                            <td><?php echo htmlspecialchars($voucher['created_by_username']); ?></td>
                            <td><?php echo date('Y-m-d H:i', strtotime($voucher['created_at'])); ?></td>
                            <td>
                                <a href="view_voucher.php?voucher_id=<?php echo $voucher['voucher_db_id']; ?>" class="btn btn-sm btn-info">View</a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; // Include the common footer HTML ?>

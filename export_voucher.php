<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region'];

// Get filters from GET
$filter_voucher_number = isset($_GET['voucher_number']) ? sanitize_input($_GET['voucher_number']) : '';
$filter_origin_region = isset($_GET['origin_region']) ? sanitize_input($_GET['origin_region']) : '';
$filter_destination_region = isset($_GET['destination_region']) ? sanitize_input($_GET['destination_region']) : '';
$filter_start_date = isset($_GET['start_date']) ? sanitize_input($_GET['start_date']) : '';
$filter_end_date = isset($_GET['end_date']) ? sanitize_input($_GET['end_date']) : '';

// Build SQL
$sql = "SELECT
            v.voucher_number,
            v.origin_region,
            v.destination_region,
            v.sender_name,
            v.receiver_name,
            v.weight_kg,
            v.total_amount,
            v.currency,
            v.delivery_type,
            s.status,
            s.current_location_region,
            u.username AS created_by_username,
            v.created_at
        FROM vouchers v
        JOIN stock s ON v.id = s.voucher_id
        JOIN users u ON v.created_by_user_id = u.id";

$params = [];
$types = "";
$where_clauses = [];

// Region filter for non-admin
if ($user_region !== 'ADMIN') {
    $where_clauses[] = "(v.origin_region = ? OR v.destination_region = ? OR s.current_location_region = ?)";
    $params[] = $user_region;
    $params[] = $user_region;
    $params[] = $user_region;
    $types .= "sss";
}

// Apply filters
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

if (!empty($where_clauses)) {
    $sql .= " WHERE " . implode(" AND ", $where_clauses);
}
$sql .= " ORDER BY v.created_at DESC";

// Prepare and execute
if ($stmt = mysqli_prepare($conn, $sql)) {
    if (!empty($params)) {
        mysqli_stmt_bind_param($stmt, $types, ...$params);
    }
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    // Output headers for Excel
    header('Content-Type: application/vnd.ms-excel');
    header('Content-Disposition: attachment;filename="vouchers_export_' . date('Ymd_His') . '.xls"');
    header('Cache-Control: max-age=0');

    // Output column headers
    echo "Voucher No.\tOrigin\tDestination\tSender Name\tReceiver Name\tWeight (KG)\tTotal Amount\tCurrency\tDelivery Type\tStatus\tCurrent Location\tCreated By\tCreated At\n";

    // Output data rows
    while ($row = mysqli_fetch_assoc($result)) {
        echo
            $row['voucher_number'] . "\t" .
            $row['origin_region'] . "\t" .
            $row['destination_region'] . "\t" .
            $row['sender_name'] . "\t" .
            $row['receiver_name'] . "\t" .
            $row['weight_kg'] . "\t" .
            number_format($row['total_amount'], 2) . "\t" .
            $row['currency'] . "\t" .
            $row['delivery_type'] . "\t" .
            str_replace('_', ' ', $row['status']) . "\t" .
            $row['current_location_region'] . "\t" .
            $row['created_by_username'] . "\t" .
            date('Y-m-d H:i', strtotime($row['created_at'])) . "\n";
    }
    mysqli_stmt_close($stmt);
}
mysqli_close($conn);
exit;

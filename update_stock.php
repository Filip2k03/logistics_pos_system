<?php
// mb_logistics/update_stock.php

session_start(); // Start session to access session variables

// Include necessary files
require_once 'config/config.php';
require_once 'includes/functions.php';

// Redirect if not logged in
if (!isLoggedIn()) {
    redirectToLogin();
}

// Only handle POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize inputs
    $stock_id = sanitize_input($_POST['stock_id']);
    $new_status = sanitize_input($_POST['new_status']);
    $new_location_region = sanitize_input($_POST['new_location_region']);
    $user_region = $_SESSION['region'];

    // Check required fields
    if (empty($stock_id) || empty($new_status) || empty($new_location_region)) {
        $_SESSION['error_message'] = "Missing stock update data.";
        header("Location: stock.php");
        exit;
    }

    // Fetch stock info
    $sql = "SELECT s.status, s.current_location_region, v.origin_region, v.destination_region
            FROM stock s
            JOIN vouchers v ON s.voucher_id = v.id
            WHERE s.id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $stock_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $stock = mysqli_fetch_assoc($result);
    mysqli_stmt_close($stmt);

    if (!$stock) {
        $_SESSION['error_message'] = "Stock not found.";
        header("Location: stock.php");
        exit;
    }

    // Extract stock info
    $old_status = $stock['status'];
    $current_location = $stock['current_location_region'];
    $origin = $stock['origin_region'];
    $destination = $stock['destination_region'];

    // Permission check
    $is_authorized = false;
    if ($user_region === 'ADMIN') {
        $is_authorized = true;
    } else {
        if ($current_location === $user_region && !in_array($old_status, ['DELIVERED', 'RETURNED'])) {
            $is_authorized = true;
        } elseif ($user_region === $origin && $old_status === 'PENDING_ORIGIN_PICKUP' && in_array($new_status, ['IN_TRANSIT', 'RETURNED'])) {
            $is_authorized = true;
        } elseif ($user_region === $destination && $old_status === 'IN_TRANSIT' && $new_status === 'ARRIVED_PENDING_RECEIVE') {
            $is_authorized = true;
        } elseif ($user_region === $destination && $old_status === 'ARRIVED_PENDING_RECEIVE' && in_array($new_status, ['DELIVERED', 'RETURNED'])) {
            $is_authorized = true;
        }
    }

    if (!$is_authorized) {
        $_SESSION['error_message'] = "Not authorized to change this stock.";
        header("Location: stock.php");
        exit;
    }

    // Validate status transition
    $valid = false;
    if ($old_status === 'PENDING_ORIGIN_PICKUP' && in_array($new_status, ['IN_TRANSIT', 'RETURNED'])) {
        $valid = true;
    } elseif ($old_status === 'IN_TRANSIT' && $new_status === 'ARRIVED_PENDING_RECEIVE') {
        $valid = true;
    } elseif ($old_status === 'ARRIVED_PENDING_RECEIVE' && in_array($new_status, ['DELIVERED', 'RETURNED'])) {
        $valid = true;
    }

    // Admin additional transitions
    if ($user_region === 'ADMIN') {
        if ($old_status === 'PENDING_ORIGIN_PICKUP' && in_array($new_status, ['ARRIVED_PENDING_RECEIVE', 'DELIVERED'])) {
            $valid = true;
        } elseif ($old_status === 'IN_TRANSIT' && in_array($new_status, ['DELIVERED', 'RETURNED'])) {
            $valid = true;
        }
    }

    if (!$valid) {
        $_SESSION['error_message'] = "Invalid status transition: $old_status → $new_status.";
        header("Location: stock.php");
        exit;
    }

    // Determine new location
    $final_location = $current_location;
    if ($new_status === 'IN_TRANSIT') {
        $final_location = $origin;
    } elseif ($new_status === 'ARRIVED_PENDING_RECEIVE') {
        $final_location = $destination;
    } elseif (in_array($new_status, ['DELIVERED', 'RETURNED'])) {
        $final_location = $new_location_region;
    }

    // Transaction
    mysqli_begin_transaction($conn);
    try {
        $update_sql = "UPDATE stock SET status = ?, current_location_region = ?, last_status_update_at = NOW() WHERE id = ?";
        $stmt = mysqli_prepare($conn, $update_sql);
        mysqli_stmt_bind_param($stmt, "ssi", $new_status, $final_location, $stock_id);
        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['success_message'] = "Stock updated to " . str_replace('_', ' ', $new_status) . ".";
        } else {
            throw new Exception("Execution failed: " . mysqli_stmt_error($stmt));
        }
        mysqli_stmt_close($stmt);
        mysqli_commit($conn);
    } catch (Exception $e) {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Update failed: " . $e->getMessage();
        error_log("Update error: " . $e->getMessage());
    }

    mysqli_close($conn);
    header("Location: stock.php");
    exit;
} else {
    header("Location: stock.php");
    exit;
}
?>

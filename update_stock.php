<?php
// mb_logistics/update_stock.php

session_start(); // Start the session to access session variables

// Include necessary files
require_once 'config/config.php'; // For database connection and configuration
require_once 'includes/functions.php'; // For isLoggedIn(), redirectToLogin(), and sanitize_input()

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

// Process form data only if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $stock_id = sanitize_input($_POST['stock_id']);
    $new_status = sanitize_input($_POST['new_status']);
    // new_location_region indicates where the item is now after the status update.
    // This value is passed from the modal select.
    $new_location_region = sanitize_input($_POST['new_location_region']);
    $user_region = $_SESSION['region']; // Get the region of the logged-in user

    // Input validation: ensure critical data is not empty
    if (empty($stock_id) || empty($new_status) || empty($new_location_region)) {
        $_SESSION['error_message'] = "Invalid request. Missing data for stock update.";
        header("location: stock.php"); // Redirect back with an error message
        exit;
    }

    // Fetch current stock details to validate transition and user permissions
    $current_stock = null;
    $sql_fetch = "SELECT s.status, s.current_location_region, v.origin_region, v.destination_region
                  FROM stock s JOIN vouchers v ON s.voucher_id = v.id WHERE s.id = ?";
    if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch)) {
        mysqli_stmt_bind_param($stmt_fetch, "i", $stock_id); // Bind stock ID
        mysqli_stmt_execute($stmt_fetch); // Execute statement
        $result_fetch = mysqli_stmt_get_result($stmt_fetch); // Get result set
        $current_stock = mysqli_fetch_assoc($result_fetch); // Fetch the associative array
        mysqli_stmt_close($stmt_fetch); // Close the statement
    }

    // If stock item not found or no data fetched, it's an invalid ID or permission issue
    if (!$current_stock) {
        $_SESSION['error_message'] = "Stock item not found or you do not have permission to access it.";
        header("location: stock.php");
        exit;
    }

    $old_status = $current_stock['status']; // Current status from DB
    $current_location = $current_stock['current_location_region']; // Current physical location from DB
    $origin_region = $current_stock['origin_region']; // Original region of the voucher
    $destination_region = $current_stock['destination_region']; // Destination region of the voucher

    $is_authorized = false; // Flag to check if the current user is authorized to perform this update

    // Authorization logic:
    if ($user_region === 'ADMIN') {
        // Admin user can update any stock item, given it's a valid status transition
        $is_authorized = true;
    } else {
        // Regular user authorization:
        // 1. User can update if the item is currently in their region AND it's not already delivered or returned.
        if ($current_location == $user_region && $old_status != 'DELIVERED' && $old_status != 'RETURNED') {
            $is_authorized = true;
        }
        // 2. User from origin region can update PENDING_ORIGIN_PICKUP to IN_TRANSIT or RETURNED
        if ($origin_region == $user_region && $old_status == 'PENDING_ORIGIN_PICKUP' &&
            ($new_status == 'IN_TRANSIT' || $new_status == 'RETURNED')) {
            $is_authorized = true;
        }
        // 3. User from destination region can update IN_TRANSIT to ARRIVED_PENDING_RECEIVE
        if ($destination_region == $user_region && $old_status == 'IN_TRANSIT' &&
            $new_status == 'ARRIVED_PENDING_RECEIVE') {
            $is_authorized = true;
        }
        // If the item is already ARRIVED_PENDING_RECEIVE, only the destination region can mark as DELIVERED/RETURNED
        if ($old_status == 'ARRIVED_PENDING_RECEIVE' && $current_location == $user_region &&
            ($new_status == 'DELIVERED' || $new_status == 'RETURNED')) {
            $is_authorized = true;
        }
    }

    // If not authorized, set error and redirect
    if (!$is_authorized) {
        $_SESSION['error_message'] = "You are not authorized to update the status of this item from its current state/location.";
        header("location: stock.php");
        exit;
    }

    // Validate status transition (this is a simplified logic, real-world apps might need a state machine)
    $valid_transition = false;
    switch ($old_status) {
        case 'PENDING_ORIGIN_PICKUP':
            if ($new_status == 'IN_TRANSIT' || $new_status == 'RETURNED') { $valid_transition = true; }
            // Admin specific transitions
            if ($user_region === 'ADMIN' && ($new_status == 'ARRIVED_PENDING_RECEIVE' || $new_status == 'DELIVERED')) { $valid_transition = true; }
            break;
        case 'IN_TRANSIT':
            if ($new_status == 'ARRIVED_PENDING_RECEIVE') { $valid_transition = true; }
            // Admin specific transitions
            if ($user_region === 'ADMIN' && ($new_status == 'DELIVERED' || $new_status == 'RETURNED')) { $valid_transition = true; }
            break;
        case 'ARRIVED_PENDING_RECEIVE':
            if ($new_status == 'DELIVERED' || $new_status == 'RETURNED') { $valid_transition = true; }
            break;
        // DELIVERED and RETURNED are final states, no further transitions are allowed
    }

    // If the selected new status is not a valid transition from the old status, set error and redirect
    if (!$valid_transition) {
        $_SESSION['error_message'] = "Invalid status transition: Cannot change from " . htmlspecialchars(str_replace('_', ' ', $old_status)) . " to " . htmlspecialchars(str_replace('_', ' ', $new_status)) . ".";
        header("location: stock.php");
        exit;
    }

    // Determine the new `current_location_region` based on the status update
    // This is a crucial part of tracking the physical location of the item.
    $final_location_for_update = $current_location; // Default: location doesn't change unless specified

    if ($new_status == 'IN_TRANSIT') {
        // When an item goes IN_TRANSIT, it is leaving the origin.
        // Its conceptual location is "between" origin and destination.
        // For current_location_region, it often remains at the origin until it physically arrives.
        // Or, if your system tracks hubs, it might go to a transit hub.
        // For this simple model, let's keep it at the origin region until it is marked as ARRIVED.
        $final_location_for_update = $origin_region; // Stays at origin until arrival
    } elseif ($new_status == 'ARRIVED_PENDING_RECEIVE') {
        // When it arrives, its location becomes the destination region.
        $final_location_for_update = $destination_region;
    } elseif ($new_status == 'DELIVERED' || $new_status == 'RETURNED') {
        // If delivered or returned, it implies the final action happened at the current_location.
        // So, the location remains where it was last processed (e.g., destination for delivery, current for return from transit/arrival).
        $final_location_for_update = $new_location_region; // Use the value from the modal's current location select
    }

    // Start a database transaction for the update operation
    mysqli_begin_transaction($conn);
    try {
        // Update the stock status and current_location_region in the database
        $sql_update = "UPDATE stock SET status = ?, current_location_region = ?, last_status_update_at = NOW() WHERE id = ?";
        if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
            mysqli_stmt_bind_param($stmt_update, "ssi", $new_status, $final_location_for_update, $stock_id); // Bind parameters
            if (mysqli_stmt_execute($stmt_update)) {
                $_SESSION['success_message'] = "Stock status updated successfully to " . htmlspecialchars(str_replace('_', ' ', $new_status)) . "!";
            } else {
                throw new Exception("Error updating stock status: " . mysqli_stmt_error($stmt_update));
            }
            mysqli_stmt_close($stmt_update); // Close the statement
        } else {
            throw new Exception("Database query preparation failed: " . mysqli_error($conn));
        }

        mysqli_commit($conn); // Commit the transaction if everything was successful

    } catch (Exception $e) {
        mysqli_rollback($conn); // Rollback the transaction on error
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Stock update transaction failed: " . $e->getMessage()); // Log detailed error
    } finally {
        mysqli_close($conn); // Close the database connection
    }

    header("location: stock.php"); // Redirect back to the stock view page
    exit; // Terminate script execution

} else {
    // If the request method is not POST, redirect to stock page
    header("location: stock.php");
    exit;
}
?>

<?php
session_start();
require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if the user is logged in and has permission
if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region'];

// Check if the form was submitted via POST
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stock_ids = $_POST['stock_ids'] ?? []; // Array of stock IDs
    $new_status = sanitize_input($_POST['bulk_new_status'] ?? ''); // New status for all selected items

    // Ensure at least one stock item is selected and a new status is provided
    if (empty($stock_ids) || empty($new_status)) {
        $_SESSION['error_message'] = "Please select at least one stock item and a new status.";
        header("Location: stock.php");
        exit();
    }

    $success_count = 0;
    $error_count = 0;

    // Begin a transaction for atomic updates
    mysqli_begin_transaction($conn);

    foreach ($stock_ids as $stock_id) {
        $stock_id = (int)$stock_id; // Ensure it's an integer for security

        // Fetch current stock details to apply status transition logic
        $sql_fetch_stock = "SELECT current_location_region, status, origin_region, destination_region FROM stock WHERE id = ?";
        if ($stmt_fetch = mysqli_prepare($conn, $sql_fetch_stock)) {
            mysqli_stmt_bind_param($stmt_fetch, "i", $stock_id);
            mysqli_stmt_execute($stmt_fetch);
            $result_fetch = mysqli_stmt_get_result($stmt_fetch);
            $stock_info = mysqli_fetch_assoc($result_fetch);
            mysqli_stmt_close($stmt_fetch);

            if (!$stock_info) {
                // Stock item not found, skip to next
                $error_count++;
                continue;
            }

            $current_status = $stock_info['status'];
            $current_location_region = $stock_info['current_location_region'];
            $origin_region = $stock_info['origin_region'];
            $destination_region = $stock_info['destination_region'];

            // Determine if the status transition is allowed for the current user's region
            $is_allowed_transition = false;
            $new_location_region = $current_location_region; // Default: location doesn't change unless specified

            if ($user_region === 'ADMIN') {
                // Admin can transition to any valid status
                // No specific region checks for admin beyond general status validity
                $is_allowed_transition = true;

                // Update location if relevant for status
                if ($new_status === 'ARRIVED_PENDING_RECEIVE') {
                    $new_location_region = $destination_region;
                }
            } else {
                // Regular user logic based on current status and user's region
                switch ($current_status) {
                    case 'PENDING_ORIGIN_PICKUP':
                        if ($origin_region === $user_region && ($new_status === 'IN_TRANSIT' || $new_status === 'RETURNED')) {
                            $is_allowed_transition = true;
                            // If IN_TRANSIT, current location remains origin until actually in transit to a different location
                            // (though typically this would be updated on dispatch)
                            // If RETURNED, location stays origin.
                        }
                        break;
                    case 'IN_TRANSIT':
                        if ($destination_region === $user_region && $new_status === 'ARRIVED_PENDING_RECEIVE') {
                            $is_allowed_transition = true;
                            $new_location_region = $destination_region; // Set current location to destination
                        }
                        break;
                    case 'ARRIVED_PENDING_RECEIVE':
                        if ($current_location_region === $user_region && ($new_status === 'DELIVERED' || $new_status === 'RETURNED')) {
                            $is_allowed_transition = true;
                            // Location remains at destination for DELIVERED/RETURNED
                        }
                        break;
                    // For 'DELIVERED' and 'RETURNED', no further transitions are generally allowed.
                }
            }

            if ($is_allowed_transition) {
                // Update the stock item's status and current location
                $sql_update = "UPDATE stock SET status = ?, current_location_region = ?, last_status_update_at = NOW() WHERE id = ?";
                if ($stmt_update = mysqli_prepare($conn, $sql_update)) {
                    mysqli_stmt_bind_param($stmt_update, "ssi", $new_status, $new_location_region, $stock_id);
                    if (mysqli_stmt_execute($stmt_update)) {
                        $success_count++;
                    } else {
                        $error_count++;
                        // Log error: "Failed to execute update for stock ID $stock_id: " . mysqli_error($conn);
                    }
                    mysqli_stmt_close($stmt_update);
                } else {
                    $error_count++;
                    // Log error: "Failed to prepare update statement for stock ID $stock_id: " . mysqli_error($conn);
                }
            } else {
                // Log or message that transition is not allowed for this item by this user
                $error_count++;
                // Optionally, store a more specific error message for this particular stock item
            }
        } else {
            $error_count++;
            // Log error: "Failed to prepare fetch statement for stock ID $stock_id: " . mysqli_error($conn);
        }
    }

    // Commit or rollback transaction
    if ($error_count === 0) {
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Successfully updated status for $success_count stock item(s).";
    } else {
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Updated $success_count stock item(s). Failed to update $error_count stock item(s) due to permission or invalid transition.";
    }

    mysqli_close($conn);
    header("Location: stock.php");
    exit();

} else {
    // If accessed directly without POST, redirect
    header("Location: stock.php");
    exit();
}
?>
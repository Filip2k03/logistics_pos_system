<?php
// mb_logistics/includes/functions.php

function generateVoucherNumber($conn, $region_code) {
    // Start a transaction to ensure atomic update of sequence
    // This prevents multiple users from generating the same voucher number concurrently
    mysqli_begin_transaction($conn);

    try {
        // Lock the row to prevent race conditions during sequence update
        // 'FOR UPDATE' locks the selected row until the transaction is committed or rolled back
        $sql = "SELECT voucher_prefix, current_sequence FROM regions WHERE region_code = ? FOR UPDATE";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $region_code); // Bind region_code to the parameter
            mysqli_stmt_execute($stmt); // Execute the prepared statement
            mysqli_stmt_bind_result($stmt, $prefix, $current_sequence); // Bind result variables
            mysqli_stmt_fetch($stmt); // Fetch the result
            mysqli_stmt_close($stmt); // Close the statement

            // If no prefix is found for the region, something is wrong with region data
            if (!$prefix) {
                // For admin, if they select a region that doesn't exist in `regions` table,
                // this might cause an issue. The `create_voucher.php` ensures only valid
                // regions are selectable, so this case should ideally not happen for valid inputs.
                throw new Exception("Voucher prefix not found for region code: " . $region_code);
            }

            $new_sequence = $current_sequence + 1; // Increment the sequence number
            // Format the voucher number (e.g., MD000001, TH000001)
            $voucher_number = $prefix . sprintf('%06d', $new_sequence);

            // Update the sequence in the database
            $update_sql = "UPDATE regions SET current_sequence = ? WHERE region_code = ?";
            if ($update_stmt = mysqli_prepare($conn, $update_sql)) {
                mysqli_stmt_bind_param($update_stmt, "is", $new_sequence, $region_code); // Bind new sequence and region code
                mysqli_stmt_execute($update_stmt); // Execute the update
                mysqli_stmt_close($update_stmt); // Close the update statement
            } else {
                throw new Exception("Error updating region sequence: " . mysqli_error($conn));
            }
        } else {
            throw new Exception("Error preparing region sequence query: " . mysqli_error($conn));
        }

        mysqli_commit($conn); // Commit the transaction if all operations were successful
        return $voucher_number; // Return the newly generated voucher number
    } catch (Exception $e) {
        mysqli_rollback($conn); // Rollback the transaction if any error occurs
        error_log("Voucher generation failed: " . $e->getMessage()); // Log the error
        return false; // Return false to indicate failure
    }
}

// Function to check if a user is logged in
function isLoggedIn() {
    return isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true;
}

// Function to redirect to the login page if not logged in
function redirectToLogin() {
    header("location: index.php"); // Redirect to the login page
    exit; // Terminate script execution after redirection
}

// Function to sanitize and validate input data to prevent XSS and other issues
function sanitize_input($data) {
    $data = trim($data); // Remove whitespace from the beginning and end of string
    $data = stripslashes($data); // Remove backslashes
    $data = htmlspecialchars($data); // Convert special characters to HTML entities
    return $data; // Return the sanitized data
}

/**
 * Fetches the count of vouchers created today.
 * For 'ADMIN' users, it fetches the count for all regions.
 * For other regions, it fetches the count only for vouchers created by users in their region.
 * @param mysqli $conn The database connection object.
 * @param string $user_region The region code of the logged-in user ('ADMIN' for all).
 * @return int The count of daily vouchers.
 */
function get_daily_voucher_count($conn, $user_region) {
    $count = 0;
    $sql = "SELECT COUNT(id) AS daily_count FROM vouchers WHERE DATE(created_at) = CURDATE()";
    $params = [];
    $types = "";

    // If the user is NOT an admin, filter by the region of the user who created the voucher
    if ($user_region !== 'ADMIN') {
        $sql .= " AND created_by_user_id IN (SELECT id FROM users WHERE region = ?)";
        $params[] = $user_region;
        $types .= "s";
    }

    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $count);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error fetching daily voucher count: " . mysqli_error($conn));
    }
    return $count;
}

/**
 * Calculates the total profit (total_amount) for vouchers created in the current month.
 * For 'ADMIN' users, it calculates profit for all regions.
 * For other regions, it calculates profit only for vouchers created by users in their region.
 * @param mysqli $conn The database connection object.
 * @param string $user_region The region code of the logged-in user ('ADMIN' for all).
 * @return float The monthly profit.
 */
function get_monthly_profit($conn, $user_region) {
    $profit = 0.00;
    // SUM(total_amount) assumes total_amount is profit. Adjust if profit calculation is different.
    $sql = "SELECT SUM(total_amount) AS monthly_profit FROM vouchers WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())";
    $params = [];
    $types = "";

    // If the user is NOT an admin, filter by the region of the user who created the voucher
    if ($user_region !== 'ADMIN') {
        $sql .= " AND created_by_user_id IN (SELECT id FROM users WHERE region = ?)";
        $params[] = $user_region;
        $types .= "s";
    }

    if ($stmt = mysqli_prepare($conn, $sql)) {
        if (!empty($params)) {
            mysqli_stmt_bind_param($stmt, $types, ...$params);
        }
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $profit);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);
    } else {
        error_log("Error fetching monthly profit: " . mysqli_error($conn));
    }
    // Return 0.00 if SUM returns null (no records)
    return $profit === null ? 0.00 : (float)$profit;
}

/**
 * Fetches all regions from the database.
 * @param mysqli $conn The database connection object.
 * @return array An array of regions, each containing 'region_code' and 'region_name'.
 */
// function get_regions($conn) {
//     $regions = [];
//     $sql = "SELECT region_code, region_name FROM regions";
//     $result = mysqli_query($conn, $sql);
//     if ($result) {
//         while ($row = mysqli_fetch_assoc($result)) {
//             $regions[] = $row;
//         }
//     }
//     return $regions;
// }

?>

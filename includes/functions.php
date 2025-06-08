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

?>

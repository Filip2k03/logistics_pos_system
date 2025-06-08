<?php
// mb_logistics/process_voucher.php

session_start(); // Start the session to access session variables

// Include necessary files
require_once 'config/config.php'; // For database connection and configuration
require_once 'includes/functions.php'; // For common functions like sanitization and voucher generation

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

// Process form data only if it's a POST request
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs received from the form
    // If admin, origin_region comes from the form, otherwise from session
    $user_region_session = $_SESSION['region'];
    $origin_region = ($user_region_session === 'ADMIN' && isset($_POST['origin_region']))
                     ? sanitize_input($_POST['origin_region'])
                     : $user_region_session;

    $destination_region = sanitize_input($_POST['destination_region']);
    $sender_name = sanitize_input($_POST['sender_name']);
    $sender_phone = sanitize_input($_POST['sender_phone']);
    $sender_address = sanitize_input($_POST['sender_address']);
    $receiver_name = sanitize_input($_POST['receiver_name']);
    $receiver_phone = sanitize_input($_POST['receiver_phone']);
    $receiver_address = sanitize_input($_POST['receiver_address']);
    $payment_method = sanitize_input($_POST['payment_method']);
    // Cast to float for numerical calculations, ensure proper handling of decimal values
    $weight_kg = (float)sanitize_input($_POST['weight_kg']);
    $price_per_kg_at_voucher = (float)sanitize_input($_POST['price_per_kg']);
    $total_amount = (float)sanitize_input($_POST['total_amount']);
    $currency = sanitize_input($_POST['currency']); // Get the new currency input
    $delivery_type = sanitize_input($_POST['delivery_type']); // Get delivery type
    $notes = sanitize_input($_POST['notes']); // Get notes
    $created_by_user_id = $_SESSION['id']; // Get the ID of the logged-in user

    // Basic server-side validation to ensure critical fields are not empty and values are positive
    if (empty($origin_region) || empty($destination_region) || empty($sender_name) || empty($receiver_name) || $weight_kg <= 0 || $price_per_kg_at_voucher <= 0 || empty($payment_method) || empty($currency) || empty($delivery_type)) {
        $_SESSION['error_message'] = "Please fill all required fields and ensure weight/price are positive. Also select a payment method, currency, and delivery type.";
        header("location: create_voucher.php"); // Redirect back to the form with an error
        exit;
    }

    // Ensure origin and destination regions are not the same
    if ($origin_region === $destination_region) {
        $_SESSION['error_message'] = "Origin and Destination regions cannot be the same. Please choose a different destination.";
        header("location: create_voucher.php"); // Redirect back to the form with an error
        exit;
    }

    // Start a transaction for atomicity:
    // All database operations within this block will either complete successfully or fail together.
    mysqli_begin_transaction($conn);

    try {
        // 1. Generate a unique Voucher Number for the origin region
        $voucher_number = generateVoucherNumber($conn, $origin_region);
        if (!$voucher_number) {
            // If voucher number generation fails, throw an exception to trigger rollback
            throw new Exception("Failed to generate voucher number for " . htmlspecialchars($origin_region) . ". Please ensure the region exists in the database and has a prefix.");
        }

        // 2. Insert the new voucher details into the 'vouchers' table
        // IMPORTANT: The 'currency', 'delivery_type', and 'notes' columns must exist in your 'vouchers' table.
        // If they don't, you'll need to add them with SQL ALTER TABLE statements.
        $sql_voucher = "INSERT INTO vouchers (voucher_number, origin_region, destination_region, sender_name, sender_phone, sender_address, receiver_name, receiver_phone, receiver_address, payment_method, weight_kg, price_per_kg_at_voucher, total_amount, currency, delivery_type, notes, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt_voucher = mysqli_prepare($conn, $sql_voucher)) {
            // Bind parameters to the prepared statement
            mysqli_stmt_bind_param($stmt_voucher, "ssssssssssdddsssi", // Added 's' for currency, 's' for delivery_type, 's' for notes
                $voucher_number, $origin_region, $destination_region, $sender_name, $sender_phone, $sender_address,
                $receiver_name, $receiver_phone, $receiver_address, $payment_method, $weight_kg,
                $price_per_kg_at_voucher, $total_amount, $currency, $delivery_type, $notes, $created_by_user_id // Added currency, delivery_type, notes
            );
            // Execute the statement and check for success
            if (!mysqli_stmt_execute($stmt_voucher)) {
                throw new Exception("Error inserting voucher details: " . mysqli_stmt_error($stmt_voucher));
            }
            $voucher_id = mysqli_insert_id($conn); // Get the ID of the newly inserted voucher
            mysqli_stmt_close($stmt_voucher); // Close the statement
        } else {
            throw new Exception("Error preparing voucher insertion statement: " . mysqli_error($conn));
        }

        // 3. Insert the new item into the 'stock' table with initial status
        // The initial status is 'PENDING_ORIGIN_PICKUP' and current location is the origin region
        $sql_stock = "INSERT INTO stock (voucher_id, current_location_region, status) VALUES (?, ?, ?)";
        if ($stmt_stock = mysqli_prepare($conn, $sql_stock)) {
            $initial_status = 'PENDING_ORIGIN_PICKUP';
            // Bind parameters to the prepared statement
            mysqli_stmt_bind_param($stmt_stock, "iss", $voucher_id, $origin_region, $initial_status);
            // Execute the statement and check for success
            if (!mysqli_stmt_execute($stmt_stock)) {
                throw new Exception("Error inserting stock item: " . mysqli_stmt_error($stmt_stock));
            }
            mysqli_stmt_close($stmt_stock); // Close the statement
        } else {
            throw new Exception("Error preparing stock insertion statement: " . mysqli_error($conn));
        }

        // If all database operations were successful, commit the transaction
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Voucher " . htmlspecialchars($voucher_number) . " created successfully and stock updated!";
        header("location: view_voucher.php?voucher_id=" . $voucher_id); // Redirect to the newly created voucher
        exit; // Terminate script execution

    } catch (Exception $e) {
        // If any error occurred during the transaction, rollback all changes
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Transaction failed: " . $e->getMessage();
        error_log("Voucher processing error: " . $e->getMessage()); // Log the detailed error for debugging
        header("location: create_voucher.php"); // Redirect back to the voucher creation page with an error
        exit; // Terminate script execution
    } finally {
        // Ensure the database connection is closed whether the transaction succeeded or failed
        mysqli_close($conn);
    }
} else {
    // If the request method is not POST, redirect to the voucher creation page
    header("location: create_voucher.php");
    exit; // Terminate script execution
}
?>

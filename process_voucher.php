<?php
// mb_logistics/process_voucher.php

session_start();

require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and validate inputs
    $origin_region = $_SESSION['region']; // Origin is always the user's region
    $destination_region = sanitize_input($_POST['destination_region']);
    $sender_name = sanitize_input($_POST['sender_name']);
    $sender_phone = sanitize_input($_POST['sender_phone']);
    $sender_address = sanitize_input($_POST['sender_address']);
    $receiver_name = sanitize_input($_POST['receiver_name']);
    $receiver_phone = sanitize_input($_POST['receiver_phone']);
    $receiver_address = sanitize_input($_POST['receiver_address']);
    $payment_method = sanitize_input($_POST['payment_method']);
    $weight_kg = (float)sanitize_input($_POST['weight_kg']);
    $price_per_kg_at_voucher = (float)sanitize_input($_POST['price_per_kg']);
    $total_amount = (float)sanitize_input($_POST['total_amount']);
    $created_by_user_id = $_SESSION['id'];

    // Basic validation
    if (empty($origin_region) || empty($destination_region) || empty($sender_name) || empty($receiver_name) || $weight_kg <= 0 || $price_per_kg_at_voucher <= 0) {
        $_SESSION['error_message'] = "Please fill all required fields and ensure weight/price are positive.";
        header("location: create_voucher.php");
        exit;
    }

    if ($origin_region === $destination_region) {
        $_SESSION['error_message'] = "Origin and Destination regions cannot be the same.";
        header("location: create_voucher.php");
        exit;
    }

    // Start a transaction for atomicity
    mysqli_begin_transaction($conn);

    try {
        // 1. Generate Voucher Number
        $voucher_number = generateVoucherNumber($conn, $origin_region);
        if (!$voucher_number) {
            throw new Exception("Failed to generate voucher number.");
        }

        // 2. Insert into Vouchers Table
        $sql_voucher = "INSERT INTO vouchers (voucher_number, origin_region, destination_region, sender_name, sender_phone, sender_address, receiver_name, receiver_phone, receiver_address, payment_method, weight_kg, price_per_kg_at_voucher, total_amount, created_by_user_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        if ($stmt_voucher = mysqli_prepare($conn, $sql_voucher)) {
            mysqli_stmt_bind_param($stmt_voucher, "ssssssssssdddi",
                $voucher_number, $origin_region, $destination_region, $sender_name, $sender_phone, $sender_address,
                $receiver_name, $receiver_phone, $receiver_address, $payment_method, $weight_kg,
                $price_per_kg_at_voucher, $total_amount, $created_by_user_id
            );
            if (!mysqli_stmt_execute($stmt_voucher)) {
                throw new Exception("Error inserting voucher: " . mysqli_stmt_error($stmt_voucher));
            }
            $voucher_id = mysqli_insert_id($conn);
            mysqli_stmt_close($stmt_voucher);
        } else {
            throw new Exception("Error preparing voucher statement: " . mysqli_error($conn));
        }

        // 3. Insert into Stock Table (initial status: PENDING_ORIGIN_PICKUP)
        $sql_stock = "INSERT INTO stock (voucher_id, current_location_region, status) VALUES (?, ?, ?)";
        if ($stmt_stock = mysqli_prepare($conn, $sql_stock)) {
            $initial_status = 'PENDING_ORIGIN_PICKUP';
            mysqli_stmt_bind_param($stmt_stock, "iss", $voucher_id, $origin_region, $initial_status);
            if (!mysqli_stmt_execute($stmt_stock)) {
                throw new Exception("Error inserting stock: " . mysqli_stmt_error($stmt_stock));
            }
            mysqli_stmt_close($stmt_stock);
        } else {
            throw new Exception("Error preparing stock statement: " . mysqli_error($conn));
        }

        // If all queries are successful, commit the transaction
        mysqli_commit($conn);
        $_SESSION['success_message'] = "Voucher " . htmlspecialchars($voucher_number) . " created successfully and stock updated!";
        header("location: dashboard.php");
        exit;

    } catch (Exception $e) {
        // If any error occurs, rollback the transaction
        mysqli_rollback($conn);
        $_SESSION['error_message'] = "Error: " . $e->getMessage();
        error_log("Voucher processing error: " . $e->getMessage()); // Log the error for debugging
        header("location: create_voucher.php");
        exit;
    } finally {
        // Close connection
        mysqli_close($conn);
    }
} else {
    // Not a POST request, redirect to create voucher page
    header("location: create_voucher.php");
    exit;
}
?>
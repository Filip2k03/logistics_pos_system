<?php
// mb_logistics/view_voucher.php

session_start();

require_once 'config/config.php';
require_once 'includes/functions.php';

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

$voucher_id = isset($_GET['voucher_id']) ? (int)$_GET['voucher_id'] : 0;
$voucher_data = null;
$error_message = '';

if ($voucher_id > 0) {
    // Fetch voucher details
    $sql = "SELECT
                v.voucher_number, v.origin_region, v.destination_region,
                v.sender_name, v.sender_phone, v.sender_address,
                v.receiver_name, v.receiver_phone, v.receiver_address,
                v.payment_method, v.weight_kg, v.price_per_kg_at_voucher, v.total_amount,
                v.currency, v.delivery_type, v.notes, /* Fetch currency, delivery_type, notes */
                v.created_at,
                s.status, s.current_location_region, s.last_status_update_at
            FROM
                vouchers v
            JOIN
                stock s ON v.id = s.voucher_id
            WHERE
                v.id = ?";

    if ($stmt = mysqli_prepare($conn, $sql)) {
        mysqli_stmt_bind_param($stmt, "i", $voucher_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $voucher_data = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!$voucher_data) {
            $error_message = "Voucher not found or you do not have access to this voucher.";
        } else {
            // Further authorization: A regular user can only view vouchers
            // if they are the origin or destination, or if the item is currently in their region.
            $user_region = $_SESSION['region'];
            if ($user_region !== 'ADMIN' &&
                $voucher_data['origin_region'] !== $user_region &&
                $voucher_data['destination_region'] !== $user_region &&
                $voucher_data['current_location_region'] !== $user_region) {
                $error_message = "You are not authorized to view this voucher.";
                $voucher_data = null; // Clear data if not authorized
            }
        }
    } else {
        $error_message = "Database query failed: " . mysqli_error($conn);
    }
} else {
    $error_message = "No voucher ID provided.";
}

mysqli_close($conn);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Voucher - <?php echo $voucher_data ? htmlspecialchars($voucher_data['voucher_number']) : 'Details'; ?></title>
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="[https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css](https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css)" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Google Fonts - Inter -->
    <link href="[https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap](https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap)" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar, .no-print {
            display: block; /* Ensure visible on screen */
        }
        /* Hide navbar and specific elements on print */
        @media print {
            body {
                margin: 0;
                padding: 0;
                /* Force A4 size for the print content */
                width: 210mm; /* A4 width */
                height: 297mm; /* A4 height */
            }
            .navbar, .no-print, footer, .alert {
                display: none !important;
            }
            .container {
                width: 100% !important; /* Full width for print */
                padding: 0 !important;
                margin: 0 !important;
                max-width: none !important;
            }
            .card {
                border: none !important; /* Remove card borders for print */
                box-shadow: none !important;
                padding: 0 !important;
                margin: 0 !important;
            }
            /* Specific styling for the voucher content for print */
            .voucher-print-container {
                width: 190mm; /* Slightly less than A4 to allow margins */
                min-height: 277mm; /* Adjust height as needed for content */
                margin: 10mm auto; /* Center on A4 paper with 10mm margins */
                padding: 15mm; /* Inner padding for content */
                border: 1px solid #ddd; /* Subtle border for the voucher itself */
                position: relative;
                overflow: hidden; /* Ensure background image doesn't bleed */
                background-color: #fff; /* Ensure white background for print */
            }

            .company-logo-bg {
                position: absolute;
                top: 20px; /* Adjust as needed */
                right: 20px; /* Adjust as needed */
                width: 100px; /* Adjust logo size */
                height: 100px; /* Adjust logo size */
                background-image: url('[https://placehold.co/100x100/000000/FFFFFF?text=Logo](https://placehold.co/100x100/000000/FFFFFF?text=Logo)'); /* **YOUR COMPANY LOGO URL HERE** */
                background-size: contain;
                background-repeat: no-repeat;
                background-position: center;
                opacity: 0.8; /* Slightly transparent */
                z-index: 1; /* Ensure logo is above text */
            }

            .voucher-header {
                text-align: center;
                margin-bottom: 20px;
                position: relative; /* For z-index if needed */
                z-index: 2; /* Ensure header is above logo */
            }

            .voucher-section {
                margin-bottom: 20px;
                padding-bottom: 10px;
                border-bottom: 1px dashed #eee; /* Light dashed separator */
                position: relative;
                z-index: 2;
            }
            .voucher-section:last-child {
                border-bottom: none;
            }

            .voucher-section h5 {
                color: #333;
                font-weight: 600;
                margin-bottom: 10px;
            }

            .voucher-info p {
                margin-bottom: 5px;
                line-height: 1.4;
                font-size: 0.95em;
            }
            .voucher-info strong {
                font-weight: 700;
            }
            .voucher-info address {
                white-space: pre-wrap; /* Preserve line breaks in address */
            }

            .voucher-footer {
                margin-top: 30px;
                text-align: center;
                font-size: 0.85em;
                color: #555;
                border-top: 1px dashed #eee;
                padding-top: 10px;
                position: relative;
                z-index: 2;
            }
        }
    </style>
</head>
<body>
    <!-- Include header for screen view -->
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <h1 class="mb-4 text-center no-print">Voucher Details</h1>

        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($voucher_data): ?>
            <div class="card p-4 mb-4">
                <div class="card-header bg-primary text-white text-center rounded-top-3 py-3 no-print">
                    <h4 class="mb-0">Voucher Number: <?php echo htmlspecialchars($voucher_data['voucher_number']); ?></h4>
                </div>
                <div class="card-body">
                    <!-- This div contains the printable voucher content -->
                    <div class="voucher-print-container">
                        <div class="company-logo-bg"></div> <!-- Company Logo Background -->
                        <div class="voucher-header">
                            <h2>MB Logistics</h2>
                            <h4>Shipment Voucher</h4>
                            <p><strong>Voucher Number: <?php echo htmlspecialchars($voucher_data['voucher_number']); ?></strong></p>
                        </div>

                        <div class="row voucher-section">
                            <div class="col-md-6 voucher-info">
                                <h5>Sender Information</h5>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($voucher_data['sender_name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($voucher_data['sender_phone']); ?></p>
                                <p><strong>Address:</strong> <address><?php echo nl2br(htmlspecialchars($voucher_data['sender_address'])); ?></address></p>
                            </div>
                            <div class="col-md-6 voucher-info">
                                <h5>Receiver Information</h5>
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($voucher_data['receiver_name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($voucher_data['receiver_phone']); ?></p>
                                <p><strong>Address:</strong> <address><?php echo nl2br(htmlspecialchars($voucher_data['receiver_address'])); ?></address></p>
                            </div>
                        </div>

                        <div class="row voucher-section">
                            <div class="col-md-6 voucher-info">
                                <h5>Shipment Details</h5>
                                <p><strong>Origin Region:</strong> <?php echo htmlspecialchars($voucher_data['origin_region']); ?></p>
                                <p><strong>Destination Region:</strong> <?php echo htmlspecialchars($voucher_data['destination_region']); ?></p>
                                <p><strong>Payment Method:</strong> <?php echo htmlspecialchars($voucher_data['payment_method']); ?></p>
                                <p><strong>Weight (KG):</strong> <?php echo htmlspecialchars($voucher_data['weight_kg']); ?> KG</p>
                                <p><strong>Price per KG:</strong> <?php echo $voucher_data['currency'] . ' ' . number_format($voucher_data['price_per_kg_at_voucher'], 2); ?></p>
                                <p><strong>Total Amount:</strong> <strong><?php echo $voucher_data['currency'] . ' ' . number_format($voucher_data['total_amount'], 2); ?></strong></p>
                            </div>
                            <div class="col-md-6 voucher-info">
                                <h5>Tracking Information</h5>
                                <p><strong>Current Status:</strong> <span class="badge bg-primary"><?php echo htmlspecialchars(str_replace('_', ' ', $voucher_data['status'])); ?></span></p>
                                <p><strong>Current Location:</strong> <?php echo htmlspecialchars($voucher_data['current_location_region']); ?></p>
                                <p><strong>Last Status Update:</strong> <?php echo date('Y-m-d H:i', strtotime($voucher_data['last_status_update_at'])); ?></p>
                                <p><strong>Voucher Created On:</strong> <?php echo date('Y-m-d H:i', strtotime($voucher_data['created_at'])); ?></p>
                                <p><strong>Delivery Type:</strong> <?php echo htmlspecialchars($voucher_data['delivery_type']); ?></p>
                                <?php if (!empty($voucher_data['notes'])): ?>
                                <p><strong>Notes:</strong> <?php echo nl2br(htmlspecialchars($voucher_data['notes'])); ?></p>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="voucher-footer">
                            <p>Thank you for choosing MB Logistics.</p>
                            <p>Printed On: <?php echo date('Y-m-d H:i:s'); ?></p>
                            <p>Company Contact: +123 456 7890 | info@mblogistics.com</p>
                        </div>
                    </div> <!-- End voucher-print-container -->

                    <div class="d-grid gap-2 mt-4 no-print">
                        <button class="btn btn-primary btn-lg" onclick="window.print()">Print Voucher</button>
                        <a href="javascript:history.back()" class="btn btn-secondary btn-lg">Go Back</a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Include footer for screen view -->
    <?php include 'includes/footer.php'; ?>

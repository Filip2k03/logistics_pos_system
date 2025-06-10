<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();

require_once 'config/config.php';
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    redirectToLogin();
}

$voucher_id = isset($_GET['voucher_id']) ? (int)$_GET['voucher_id'] : 0;
$voucher_data = null;
$error_message = '';

if ($voucher_id > 0) {
    $sql = "SELECT
                v.voucher_number, v.origin_region, v.destination_region,
                v.sender_name, v.sender_phone, v.sender_address,
                v.receiver_name, v.receiver_phone, v.receiver_address,
                v.weight_kg, v.price_per_kg_at_voucher, v.total_amount,
                v.currency,
                v.created_at
            FROM
                vouchers v
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
    <meta name="viewport" content="width=210mm, initial-scale=1.0">
    <title>Voucher - <?php echo $voucher_data ? htmlspecialchars($voucher_data['voucher_number']) : 'Details'; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .voucher-print-container {
            margin: 20px auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.07);
            padding: 32px 24px;
            position: relative;
            /*background-image: url('bg.jpg');*/
            /*background-size: contain;*/
            /*background-repeat: no-repeat;*/
            /*background-position: center;*/
        }
        .watermark-mb {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            font-size: 6rem;
            font-weight: 900;
            color: #2563eb;
            opacity: 0.07;
            white-space: nowrap;
            pointer-events: none;
            z-index: 0;
            text-shadow: 2px 2px 16px #000, 0 0 2px #2563eb;
            user-select: none;
        }
        .voucher-header,
        .voucher-section,
        .footer-section,
        .notes,
        .signature-section {
            position: relative;
            z-index: 1;
        }
        .voucher-header {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 24px;
            flex-wrap: wrap;
            text-align: left;
            margin-bottom: 2rem;
        }
        .voucher-header .logo-col {
            flex: 0 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .voucher-header .logo-col img {
            width: 90px;
            height: auto;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.07);
            background: #fff;
            padding: 4px;
        }
        .voucher-header .info-col {
            flex: 1 1 200px;
            min-width: 200px;
            text-align: left;
        }
        .voucher-header h2 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.25rem;
            margin-top: 0;
        }
        .voucher-header h4 {
            font-size: 1.2rem;
            font-weight: 500;
            margin-bottom: 0.5rem;
            margin-top: 0;
        }
        .voucher-header p {
            margin-bottom: 0;
            margin-top: 0.25rem;
        }
        .voucher-section-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: #2563eb;
            margin-bottom: 0.5rem;
        }
        .info-table {
            width: 100%;
            margin-bottom: 2rem;
            border-collapse: collapse;
        }
        .info-table th, .info-table td {
            border: 1px solid #e2e8f0;
            padding: 10px 14px;
            vertical-align: top;
        }
        .info-table th {
            background: #f1f5f9;
            width: 30%;
            font-weight: 600;
        }
        .footer-section {
            margin-top: 2.5rem;
            text-align: center;
            font-size: 1rem;
            color: #64748b;
            border-top: 1px dashed #e2e8f0;
            padding-top: 1rem;
        }
        .notes {
            margin-top: 2rem;
        }
        .signature-section {
            display: flex;
            justify-content: space-between;
            margin-top: 2rem;
            gap: 10px;
        }
        .signature-box {
            border-top: 1px solid #333;
            width: 32%;
            text-align: center;
            padding-top: 10px;
            font-size: 1rem;
            min-height: 40px;
        }
        @media print {
            body {
                background: #fff !important;
                
            }
            .voucher-print-container {
            margin: 20px auto;
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.07);
            padding: 32px 24px;
            position: relative;
                
            }
            .watermark-mb {
                font-size: 8rem;
                opacity: 0.09;
            }
            .no-print {
                display: none !important;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <h1 class="mb-4 text-center no-print">Shipment Voucher</h1>
        <?php if ($error_message): ?>
            <div class="alert alert-danger alert-dismissible fade show no-print" role="alert">
                <?php echo $error_message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php elseif ($voucher_data): ?>
            <div class="voucher-print-container">
                <div class="watermark-mb">MBlogistics</div>
                <div class="voucher-header">
                    <div class="logo-col">
                        <img src="bg.jpg" alt="MB Logistics Logo">
                    </div>
                    <div class="info-col">
                        <h2>MB LOGISTICS</h2>
                        <h4>Shipment Voucher</h4>
                        <p><strong>Voucher Number: <?php echo htmlspecialchars($voucher_data['voucher_number']); ?></strong></p>
                    </div>
                </div>

                <!-- Sender & Receiver Table -->
                <div class="voucher-section mb-4">
                    <div class="voucher-section-title">Sender & Receiver Information</div>
                    <table class="info-table">
                        <thead>
                            <tr>
                                <th>Sender</th>
                                <th>Receiver</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Name:</strong> <?php echo htmlspecialchars($voucher_data['sender_name']); ?><br>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($voucher_data['sender_phone']); ?><br>
                                    <strong>Address:</strong> <address><?php echo nl2br(htmlspecialchars($voucher_data['sender_address'])); ?></address>
                                </td>
                                <td>
                                    <strong>Name:</strong> <?php echo htmlspecialchars($voucher_data['receiver_name']); ?><br>
                                    <strong>Phone:</strong> <?php echo htmlspecialchars($voucher_data['receiver_phone']); ?><br>
                                    <strong>Address:</strong> <address><?php echo nl2br(htmlspecialchars($voucher_data['receiver_address'])); ?></address>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Voucher Details Table -->
                <div class="voucher-section mb-4">
                    <div class="voucher-section-title">Voucher Details</div>
                    <table class="info-table">
                        <tbody>
                            <tr>
                                <th>Origin Region</th>
                                <td><?php echo htmlspecialchars($voucher_data['origin_region']); ?></td>
                            </tr>
                            <tr>
                                <th>Destination Region</th>
                                <td><?php echo htmlspecialchars($voucher_data['destination_region']); ?></td>
                            </tr>
                            <tr>
                                <th>Weight (KG)</th>
                                <td><?php echo htmlspecialchars($voucher_data['weight_kg']); ?> KG</td>
                            </tr>
                            <tr>
                                <th>Price per KG</th>
                                <td><?php echo $voucher_data['currency'] . ' ' . number_format($voucher_data['price_per_kg_at_voucher'], 2); ?></td>
                            </tr>
                            <tr>
                                <th>Total Amount</th>
                                <td><strong><?php echo $voucher_data['currency'] . ' ' . number_format($voucher_data['total_amount'], 2); ?></strong></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
                <div class="footer-section">
                    <p>Thank you for choosing MB Logistics - Your trusted global shipping partner</p>
                    <p>Printed On: <?php echo date('Y-m-d H:i:s'); ?> | www.mblogistics.com</p>
                </div>

                <!-- Important Notes -->
                <div class="notes">
                    <p><strong>Important Notes:</strong></p>
                    <ol>
                        <li>Items over 5kg may be charged separately.</li>
                        <li>No illegal items (drugs, weapons, etc.) allowed.</li>
                        <li>Use appropriate packaging and label your items clearly.</li>
                    </ol>
                </div>

                <!-- Signature Section -->
                <div class="signature-section">
                    <div class="signature-box">Sender's Signature</div>
                    <div class="signature-box">Receiver's Signature</div>
                    <div class="signature-box">Staff Signature</div>
                </div>
            </div>

            <div class="d-grid gap-3 mt-4 no-print" style="max-width: 400px; margin: 0 auto;">
                <button class="btn btn-primary btn-lg" onclick="window.print()">
                    Print Voucher
                </button>
                <a href="javascript:history.back()" class="btn btn-outline-secondary btn-lg">
                    Back to Dashboard
                </a>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>
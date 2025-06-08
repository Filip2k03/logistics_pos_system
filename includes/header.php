<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MB Logistics POS</title>
    <!-- Bootstrap 5.3 CSS CDN -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Google Fonts - Inter -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f8f9fa;
        }
        .navbar {
            background-color: #343a40 !important; /* Dark background for navbar */
        }
        .navbar .nav-link, .navbar .navbar-brand {
            color: #ffffff !important;
        }
        .navbar .nav-link:hover {
            color: #adb5bd !important;
        }
        .container-fluid {
            padding-top: 20px;
        }
        .card {
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        .btn-primary {
            background-color: #007bff;
            border-color: #007bff;
            border-radius: 8px;
            transition: background-color 0.3s ease;
        }
        .btn-primary:hover {
            background-color: #0056b3;
            border-color: #0056b3;
        }
        .form-control, .form-select {
            border-radius: 8px;
        }
        .table-hover tbody tr:hover {
            background-color: #e2e6ea;
        }
        /* Styles for printing */
        @media print {
            .navbar, .btn, .alert, .card-header.bg-primary {
                display: none !important; /* Hide navigation, buttons, alerts, and blue card header during print */
            }
            .container-fluid {
                padding: 0 !important; /* Remove padding for print */
                margin: 0 !important; /* Remove margin for print */
            }
            body {
                background-color: #fff !important; /* White background for print */
                color: #000 !important; /* Black text for print */
            }
            .card {
                box-shadow: none !important;
                border: 1px solid #ccc !important;
                border-radius: 0 !important;
                margin: 0 !important;
                padding: 10px !important;
            }
            /* Specific styles for voucher print */
            .voucher-details p {
                margin-bottom: 5px;
            }
            .voucher-details h5 {
                margin-top: 15px;
                margin-bottom: 8px;
                border-bottom: 1px dashed #ccc;
                padding-bottom: 5px;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="dashboard.php">MB Logistics POS</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="create_voucher.php">Create Voucher</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="stock.php">Stock View</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="pending_receive.php">Delivery Pending Receive</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="voucher_list.php">Voucher List</a>
                        </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['region']); ?>)
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="index.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

<!-- Bootstrap 5.3 JS CDN -->
    <script src="[https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js](https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js)" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eOzr0E/o7N+o83V8zJ" crossorigin="anonymous"></script>
    <!-- Custom JS -->
    <script src="js/scripts.js"></script>
</body>
</html>

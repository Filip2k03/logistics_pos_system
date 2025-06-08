<?php
// mb_logistics/create_voucher.php

session_start(); // Start the session to access session variables

// Include necessary files
require_once 'config/config.php'; // For database connection and get_regions()
require_once 'includes/functions.php'; // For isLoggedIn() and redirectToLogin()

// Check if the user is logged in, otherwise redirect to login page
if (!isLoggedIn()) {
    redirectToLogin();
}

$user_region = $_SESSION['region']; // Get the logged-in user's region
$all_regions = get_regions($conn); // Fetch all regions from the database

?>

<?php include 'includes/header.php'; // Include the common header HTML ?>

<div class="container">
    <h1 class="mb-4 text-center">Create New Voucher
        <?php if ($user_region !== 'ADMIN'): ?>
            (From: <?php echo htmlspecialchars($user_region); ?>)
        <?php else: ?>
            (Admin Mode)
        <?php endif; ?>
    </h1>

    <div class="card p-4 mb-4">
        <form action="process_voucher.php" method="POST">
            <div class="row g-3">
                <div class="col-md-6">
                    <h4 class="mb-3 text-primary">Sender Details</h4>
                    <div class="mb-3">
                        <label for="sender_name" class="form-label">Sender Name</label>
                        <input type="text" class="form-control" id="sender_name" name="sender_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="sender_phone" class="form-label">Sender Phone</label>
                        <input type="tel" class="form-control" id="sender_phone" name="sender_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="sender_address" class="form-label">Sender Address</label>
                        <textarea class="form-control" id="sender_address" name="sender_address" rows="3" required></textarea>
                    </div>
                </div>

                <div class="col-md-6">
                    <h4 class="mb-3 text-primary">Receiver Details</h4>
                    <div class="mb-3">
                        <label for="receiver_name" class="form-label">Receiver Name</label>
                        <input type="text" class="form-control" id="receiver_name" name="receiver_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="receiver_phone" class="form-label">Receiver Phone</label>
                        <input type="tel" class="form-control" id="receiver_phone" name="receiver_phone" required>
                    </div>
                    <div class="mb-3">
                        <label for="receiver_address" class="form-label">Receiver Address</label>
                        <textarea class="form-control" id="receiver_address" name="receiver_address" rows="3" required></textarea>
                    </div>
                </div>
            </div>

            <hr class="my-4">

            <div class="row g-3">
                <div class="col-md-4">
                    <label for="origin_region" class="form-label">Origin Region</label>
                    <select class="form-select" id="origin_region" name="origin_region" required
                        <?php echo ($user_region !== 'ADMIN') ? 'readonly disabled' : ''; ?>>
                        <?php if ($user_region !== 'ADMIN'): ?>
                            <!-- For non-admin, origin is pre-selected and locked -->
                            <?php
                            foreach ($all_regions as $region) {
                                $selected = ($region['region_code'] == $user_region) ? 'selected' : '';
                                if ($selected) { // Only show the selected region for non-admin
                                    echo '<option value="' . htmlspecialchars($region['region_code']) . '" ' . $selected . ' data-priceperkg="' . htmlspecialchars($region['price_per_kg']) . '">' . htmlspecialchars($region['region_name']) . ' (' . htmlspecialchars($region['region_code']) . ')</option>';
                                }
                            }
                            ?>
                            <!-- Hidden input to actually pass the origin_region value for non-admin -->
                            <input type="hidden" name="origin_region" value="<?php echo htmlspecialchars($user_region); ?>">
                        <?php else: ?>
                            <!-- For admin, allow selecting any origin region -->
                            <option value="">Select Origin</option>
                            <?php
                            foreach ($all_regions as $region) {
                                // Exclude the 'ADMIN' region from the selectable origins if it somehow got into regions table
                                if ($region['region_code'] !== 'ADMIN') {
                                    echo '<option value="' . htmlspecialchars($region['region_code']) . '" data-priceperkg="' . htmlspecialchars($region['price_per_kg']) . '">' . htmlspecialchars($region['region_name']) . ' (' . htmlspecialchars($region['region_code']) . ')</option>';
                                }
                            }
                            ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="destination_region" class="form-label">Destination Region</label>
                    <select class="form-select" id="destination_region" name="destination_region" required>
                        <option value="">Select Destination</option>
                        <?php
                        // Allow choosing any region except the selected origin region (dynamically handled by JS if possible,
                        // but PHP still needs to populate all valid options).
                        // Exclude the 'ADMIN' region as a valid destination.
                        foreach ($all_regions as $region) {
                            if ($region['region_code'] !== 'ADMIN') {
                                echo '<option value="' . htmlspecialchars($region['region_code']) . '">' . htmlspecialchars($region['region_name']) . ' (' . htmlspecialchars($region['region_code']) . ')</option>';
                            }
                        }
                        ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="payment_method" class="form-label">Payment Method</label>
                    <select class="form-select" id="payment_method" name="payment_method" required>
                        <option value="">Select Method</option>
                        <option value="Cash">Cash</option>
                        <option value="Card">Card</option>
                        <option value="Online">Online Payment</option>
                    </select>
                </div>
            </div>

            <div class="row g-3 mt-3">
                <div class="col-md-4">
                    <label for="weight_kg" class="form-label">Weight (KG)</label>
                    <input type="number" step="0.01" class="form-control" id="weight_kg" name="weight_kg" min="0.01" required>
                </div>
                <div class="col-md-4">
                    <label for="price_per_kg" class="form-label">Price per KG</label>
                    <input type="number" step="0.01" class="form-control" id="price_per_kg" name="price_per_kg" min="0.00" required>
                </div>
                <div class="col-md-4">
                    <label for="total_amount" class="form-label">Total Amount</label>
                    <input type="text" class="form-control" id="total_amount" name="total_amount" readonly>
                </div>
            </div>

            <div class="d-grid gap-2 mt-4">
                <button type="submit" class="btn btn-primary btn-lg">Create Voucher</button>
            </div>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; // Include the common footer HTML ?>

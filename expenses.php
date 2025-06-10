<?php
// mb_logistics/add_expense.php

session_start();
require_once 'config/config.php';
require_once 'includes/functions.php'; // Make sure get_regions() is available here

// Restrict access: Only logged-in users with a region (not ADMIN only) can add expenses.
// An ADMIN can add expenses, but typically, expense entry is done by regional managers.
// If you want ONLY admins to add, keep $_SESSION['region'] !== 'ADMIN'.
// If any logged-in user who is *not* ADMIN can add, change this logic.
// For this example, I'll allow any non-ADMIN region user to add, and ADMINs too.
// This allows regional users to log their regional expenses.
if (!isLoggedIn()) {
    redirectToLogin();
}

// Get the user's region and ID
$user_region = $_SESSION['region'];
$user_id = $_SESSION['user_id']; // Assuming user_id is stored in the session upon login

$message = ''; // For success/error messages

// Fetch regions for the dropdown (admins can select any region for an expense)
$regions = get_regions($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and validate inputs
    $expense_date = sanitize_input($_POST['expense_date'] ?? '');
    $amount = floatval($_POST['amount'] ?? 0);
    $currency = sanitize_input($_POST['currency'] ?? '');
    $description = sanitize_input($_POST['description'] ?? '');
    $selected_region = sanitize_input($_POST['region'] ?? ''); // For ADMIN or if regional users select it

    $errors = [];

    // Basic validation
    if (empty($expense_date)) {
        $errors[] = "Expense date is required.";
    }
    if ($amount <= 0) {
        $errors[] = "Amount must be a positive number.";
    }
    if (empty($currency)) {
        $errors[] = "Currency is required.";
    }

    // Region validation based on user's role
    $expense_region_to_save = '';
    if ($user_region === 'ADMIN') {
        // Admins can select any region, so validate their selection
        if (empty($selected_region)) {
            $errors[] = "Please select a region for the expense.";
        } else {
            $expense_region_to_save = $selected_region;
        }
    } else {
        // Non-admin users automatically assign expenses to their own region
        $expense_region_to_save = $user_region;
        // Also, if a 'region' field was submitted by a non-admin, ensure it matches their region
        if (!empty($selected_region) && $selected_region !== $user_region) {
            // This is a security check to prevent a non-admin from submitting for another region
            $errors[] = "You can only add expenses for your own region.";
        }
    }


    if (empty($errors)) {
        // Insert into expenses table
        // We now include 'date', 'region', and 'created_by'
        $stmt = mysqli_prepare($conn, "INSERT INTO expenses (date, region, amount, currency, description, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");

        if ($stmt) {
            mysqli_stmt_bind_param($stmt, "ssdssi", $expense_date, $expense_region_to_save, $amount, $currency, $description, $user_id);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Expense added successfully.";
                // Redirect to expense_list.php after successful addition
                header("Location: expense_list.php");
                exit();
            } else {
                $message = '<div class="alert alert-danger">Failed to add expense: ' . mysqli_error($conn) . '</div>';
            }
            mysqli_stmt_close($stmt);
        } else {
            $message = '<div class="alert alert-danger">Database error: Could not prepare statement.</div>';
        }
    } else {
        // Display validation errors
        $message = '<div class="alert alert-danger">' . implode('<br>', $errors) . '</div>';
    }
}
?>

<?php include 'includes/header.php'; ?>
<div class="container py-4">
    <h1 class="mb-4 text-center">Add Expense</h1>
    <?php if ($message) echo $message; ?>

    <form method="post" class="mx-auto" style="max-width:400px;">
        <div class="mb-3">
            <label for="expense_date" class="form-label">Expense Date</label>
            <input type="date" class="form-control" id="expense_date" name="expense_date" value="<?php echo htmlspecialchars($_POST['expense_date'] ?? date('Y-m-d')); ?>" required>
            </div>

        <?php if ($user_region === 'ADMIN'): ?>
        <div class="mb-3">
            <label for="region" class="form-label">Region for Expense</label>
            <select name="region" id="region" class="form-select" required>
                <option value="">Select Region</option>
                <?php foreach ($regions as $region_data): ?>
                    <?php if ($region_data['region_code'] !== 'ADMIN'): // Exclude 'ADMIN' as a region for expenses ?>
                    <option value="<?php echo htmlspecialchars($region_data['region_code']); ?>"
                        <?php echo (isset($_POST['region']) && $_POST['region'] == $region_data['region_code']) ? 'selected' : ''; ?>>
                        <?php echo htmlspecialchars($region_data['region_name']); ?>
                    </option>
                    <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>
        <?php else: ?>
        <input type="hidden" name="region" value="<?php echo htmlspecialchars($user_region); ?>">
        <div class="mb-3">
            <label class="form-label">Region</label>
            <p class="form-control-plaintext">**<?php echo htmlspecialchars($user_region); ?>** (Your Region)</p>
        </div>
        <?php endif; ?>

        <div class="mb-3">
            <label for="amount" class="form-label">Amount</label>
            <input type="number" step="0.01" min="0" class="form-control" id="amount" name="amount" value="<?php echo htmlspecialchars($_POST['amount'] ?? ''); ?>" required>
        </div>
        <div class="mb-3">
            <label for="currency" class="form-label">Currency</label>
            <select class="form-select" id="currency" name="currency" required>
                <option value="">Select Currency</option>
                <option value="MMK" <?php echo (isset($_POST['currency']) && $_POST['currency'] == 'MMK') ? 'selected' : ''; ?>>MMK</option>
                <option value="RM" <?php echo (isset($_POST['currency']) && $_POST['currency'] == 'RM') ? 'selected' : ''; ?>>RM</option>
            </select>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description (optional)</label>
            <input type="text" class="form-control" id="description" name="description" maxlength="255" value="<?php echo htmlspecialchars($_POST['description'] ?? ''); ?>">
        </div>
        <button type="submit" class="btn btn-primary">Add Expense</button>
        <a href="expense_list.php" class="btn btn-secondary ms-2">Back to Expense List</a>
    </form>
</div>
<?php include 'includes/footer.php'; ?>
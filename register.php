<?php
// mb_logistics/register.php

session_start(); // Start the session for messages

// If a user is already logged in, redirect them away from registration
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}

require_once 'config/config.php'; // For database connection and get_regions()
require_once 'includes/functions.php'; // For sanitize_input()

$username = $password = $confirm_password = $region = "";
$username_err = $password_err = $confirm_password_err = $region_err = $register_err = "";
$all_regions = get_regions($conn); // Fetch all regions for the dropdown

// Process form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate username
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter a username.";
    } else {
        // Prepare a select statement to check if username already exists
        $sql = "SELECT id FROM users WHERE username = ?";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            mysqli_stmt_bind_param($stmt, "s", $param_username);
            $param_username = sanitize_input($_POST["username"]);
            if (mysqli_stmt_execute($stmt)) {
                mysqli_stmt_store_result($stmt);
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    $username_err = "This username is already taken.";
                } else {
                    $username = $param_username;
                }
            } else {
                $register_err = "Oops! Something went wrong. Please try again later.";
            }
            mysqli_stmt_close($stmt);
        }
    }

    // Validate password
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter a password.";
    } elseif (strlen(trim($_POST["password"])) < 6) {
        $password_err = "Password must have at least 6 characters.";
    } else {
        $password = trim($_POST["password"]);
    }

    // Validate confirm password
    if (empty(trim($_POST["confirm_password"]))) {
        $confirm_password_err = "Please confirm password.";
    } else {
        $confirm_password = trim($_POST["confirm_password"]);
        if (empty($password_err) && ($password != $confirm_password)) {
            $confirm_password_err = "Password did not match.";
        }
    }

    // Validate region
    if (empty(trim($_POST["region"]))) {
        $region_err = "Please select your region.";
    } else {
        $selected_region_code = sanitize_input($_POST["region"]);
        $region_exists = false;
        foreach ($all_regions as $r) {
            if ($r['region_code'] === $selected_region_code) {
                $region_exists = true;
                break;
            }
        }
        // Disallow 'ADMIN' region for regular user registration
        if (!$region_exists || $selected_region_code === 'ADMIN') {
            $region_err = "Invalid region selected.";
        } else {
            $region = $selected_region_code;
        }
    }


    // If no errors, insert user into database
    if (empty($username_err) && empty($password_err) && empty($confirm_password_err) && empty($region_err) && empty($register_err)) {
        $sql = "INSERT INTO users (username, password, region) VALUES (?, ?, ?)";
        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Hash password
            $hashed_password = password_hash($password, PASSWORD_BCRYPT); // Use bcrypt for strong hashing

            mysqli_stmt_bind_param($stmt, "sss", $username, $hashed_password, $region);
            if (mysqli_stmt_execute($stmt)) {
                $_SESSION['success_message'] = "Your account has been registered successfully! Please log in.";
                header("location: index.php"); // Redirect to login page
                exit;
            } else {
                $register_err = "Error: Could not register user. " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        } else {
            $register_err = "Error: Could not prepare statement. " . mysqli_error($conn);
        }
    }

    // Close connection
    mysqli_close($conn);
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card login-container">
                <div class="card-header text-center bg-primary text-white rounded-top-3 py-3">
                    <h2 class="mb-0">Register New Account</h2>
                </div>
                <div class="card-body p-4">
                    <?php
                    if (!empty($register_err)) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $register_err . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($username); ?>" required>
                            <div class="invalid-feedback"><?php echo $username_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" value="" required>
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <input type="password" name="confirm_password" id="confirm_password" class="form-control <?php echo (!empty($confirm_password_err)) ? 'is-invalid' : ''; ?>" value="" required>
                            <div class="invalid-feedback"><?php echo $confirm_password_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="region" class="form-label">Region</label>
                            <select name="region" id="region" class="form-select <?php echo (!empty($region_err)) ? 'is-invalid' : ''; ?>" required>
                                <option value="">Select your region</option>
                                <?php
                                foreach ($all_regions as $r) {
                                    // Exclude 'ADMIN' as a selectable region for regular user registration
                                    if ($r['region_code'] !== 'ADMIN') {
                                        $selected = ($r['region_code'] == $region) ? 'selected' : '';
                                        echo '<option value="' . htmlspecialchars($r['region_code']) . '" ' . $selected . '>' . htmlspecialchars($r['region_name']) . ' (' . htmlspecialchars($r['region_code']) . ')</option>';
                                    }
                                }
                                ?>
                            </select>
                            <div class="invalid-feedback"><?php echo $region_err; ?></div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Register</button>
                        </div>
                        <p class="mt-3 mb-0 text-center">Already have an account? <a href="index.php">Login here</a>.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
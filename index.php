<?php
// mb_logistics/index.php

session_start();

// Check if user is already logged in, redirect to dashboard
if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true) {
    header("location: dashboard.php");
    exit;
}

// Include the configuration file which contains database connection setup
require_once 'config/config.php';
// Include functions for common utilities like input sanitization and redirection
require_once 'includes/functions.php';

$username = $password = "";
$username_err = $password_err = $login_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Check if username field is empty
    if (empty(trim($_POST["username"]))) {
        $username_err = "Please enter username.";
    } else {
        $username = sanitize_input($_POST["username"]); // Sanitize the username input
    }

    // Check if password field is empty
    if (empty(trim($_POST["password"]))) {
        $password_err = "Please enter your password.";
    } else {
        $password = trim($_POST["password"]); // Get password, not sanitized yet for hashing
    }

    // Validate credentials if there are no input errors
    if (empty($username_err) && empty($password_err)) {
        // Prepare a select statement to fetch user data
        $sql = "SELECT id, username, password, region FROM users WHERE username = ?";

        if ($stmt = mysqli_prepare($conn, $sql)) {
            // Bind variables to the prepared statement as parameters
            mysqli_stmt_bind_param($stmt, "s", $param_username);

            // Set parameters
            $param_username = $username;

            // Attempt to execute the prepared statement
            if (mysqli_stmt_execute($stmt)) {
                // Store result (allows us to check number of rows and fetch data)
                mysqli_stmt_store_result($stmt);

                // Check if username exists (should be exactly 1 row)
                if (mysqli_stmt_num_rows($stmt) == 1) {
                    // Bind result variables to retrieve data from the fetched row
                    mysqli_stmt_bind_result($stmt, $id, $username, $hashed_password, $region);
                    if (mysqli_stmt_fetch($stmt)) {
                        // Verify the submitted password against the hashed password in the database
                        if (password_verify($password, $hashed_password)) {
                            // Password is correct, start a new session
                            session_regenerate_id(); // Regenerate session ID for security reasons
                            $_SESSION["loggedin"] = true; // Set logged in flag
                            $_SESSION["id"] = $id;         // Store user ID
                            $_SESSION["username"] = $username; // Store username
                            $_SESSION["region"] = $region;     // Store user's region

                            // Redirect user to dashboard page
                            header("location: dashboard.php");
                            exit; // Terminate script execution
                        } else {
                            // Password is not valid, set a generic error message
                            $login_err = "Invalid username or password.";
                        }
                    }
                } else {
                    // Username doesn't exist, set a generic error message
                    $login_err = "Invalid username or password.";
                }
            } else {
                // Error during statement execution
                echo "<div class='alert alert-danger'>Oops! Something went wrong. Please try again later.</div>";
            }

            // Close the prepared statement
            mysqli_stmt_close($stmt);
        }
    }

    // Close the database connection
    mysqli_close($conn);
}
?>

<?php include 'includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-6">
            <div class="card login-container">
                <div class="card-header text-center bg-primary text-white rounded-top-3 py-3">
                    <h2 class="mb-0">MB Logistics POS Login</h2>
                </div>
                <div class="card-body p-4">
                    <?php
                    // Display login error message if any
                    if (!empty($login_err)) {
                        echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">' . $login_err . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                    }
                    // Display success message from registration if redirected here
                    if (isset($_SESSION['success_message'])) {
                        echo '<div class="alert alert-success alert-dismissible fade show" role="alert">' . $_SESSION['success_message'] . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>';
                        unset($_SESSION['success_message']); // Clear the message
                    }
                    ?>
                    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <input type="text" name="username" id="username" class="form-control <?php echo (!empty($username_err)) ? 'is-invalid' : ''; ?>" value="<?php echo $username; ?>" required>
                            <div class="invalid-feedback"><?php echo $username_err; ?></div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <input type="password" name="password" id="password" class="form-control <?php echo (!empty($password_err)) ? 'is-invalid' : ''; ?>" required>
                            <div class="invalid-feedback"><?php echo $password_err; ?></div>
                        </div>
                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary btn-lg">Login</button>
                        </div>
                        <p class="mt-3 mb-0 text-center">Don't have an account? <a href="register.php">Register here</a>.</p>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
<?php
// mb_logistics/maintenance.php

// !!! IMPORTANT SECURITY WARNING !!!
// This file provides direct database access.
// DO NOT DEPLOY THIS FILE TO A PRODUCTION SERVER WITHOUT ROBUST AUTHENTICATION AND AUTHORIZATION.
// It is intended for development and manual debugging only.

// Basic Password Protection for this script (replace with a strong password)
$maintenance_password = 'your_strong_maintenance_password'; // <--- CHANGE THIS PASSWORD!

session_start();

// Check if the user is authenticated for this maintenance script
if (!isset($_SESSION['maintenance_logged_in']) || $_SESSION['maintenance_logged_in'] !== true) {
    if (isset($_POST['maintenance_pass']) && $_POST['maintenance_pass'] === $maintenance_password) {
        $_SESSION['maintenance_logged_in'] = true;
    } else {
        // Display a simple login form for the maintenance script
        echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DB Maintenance Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: "Inter", sans-serif; background-color: #f8f9fa; display: flex; justify-content: center; align-items: center; min-height: 100vh; }
        .login-card { padding: 30px; border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); background-color: #fff; max-width: 350px; width: 100%; }
        .btn-primary { background-color: #007bff; border-color: #007bff; border-radius: 8px; }
        .btn-primary:hover { background-color: #0056b3; border-color: #0056b3; }
    </style>
</head>
<body>
    <div class="card login-card">
        <h4 class="card-title text-center mb-4">DB Maintenance Access</h4>
        <form method="POST">
            <div class="mb-3">
                <label for="maintenance_pass" class="form-label">Password</label>
                <input type="password" name="maintenance_pass" id="maintenance_pass" class="form-control" required>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary">Login</button>
            </div>
        </form>';
        if (isset($_POST['maintenance_pass'])) {
             echo '<div class="alert alert-danger mt-3">Incorrect password.</div>';
        }
        echo '</div>
</body>
</html>';
        exit; // Stop execution if not authenticated
    }
}

// Include database configuration
require_once 'config/config.php';

$message = '';
$message_type = ''; // 'success' or 'danger'
$query_result = null;
$executed_query = '';
$selected_table = isset($_GET['table']) ? htmlspecialchars($_GET['table']) : '';
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 20; // Number of rows per page for table viewing

// --- Handle SQL Query Execution ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sql_query'])) {
    $executed_query = trim($_POST['sql_query']);
    if (!empty($executed_query)) {
        // Prevent certain destructive queries for safety (basic check)
        $lower_query = strtolower($executed_query);
        if (preg_match('/^(drop|alter|truncate)\s+table/', $lower_query) && !str_contains($lower_query, ';')) {
            $message = "Direct 'DROP TABLE', 'ALTER TABLE', or 'TRUNCATE TABLE' commands are highly destructive and not allowed via this interface without a semicolon. Use with extreme caution.";
            $message_type = 'danger';
        } else {
            // Execute the query
            if ($result = mysqli_query($conn, $executed_query)) {
                if (mysqli_num_rows($result) > 0) {
                    // Query returned rows (SELECT statement)
                    $query_result = mysqli_fetch_all($result, MYSQLI_ASSOC);
                    $message = "Query executed successfully. " . mysqli_num_rows($result) . " rows returned.";
                    $message_type = 'success';
                } else {
                    // Query did not return rows (INSERT, UPDATE, DELETE, CREATE, etc.)
                    $message = "Query executed successfully. " . mysqli_affected_rows($conn) . " rows affected (if applicable).";
                    $message_type = 'success';
                }
                mysqli_free_result($result);
            } else {
                $message = "Query execution failed: " . mysqli_error($conn);
                $message_type = 'danger';
            }
        }
    } else {
        $message = "Please enter an SQL query.";
        $message_type = 'danger';
    }
}

// --- Fetch Tables in the Database ---
$tables = [];
$result_tables = mysqli_query($conn, "SHOW TABLES");
if ($result_tables) {
    while ($row = mysqli_fetch_row($result_tables)) {
        $tables[] = $row[0];
    }
    mysqli_free_result($result_tables);
} else {
    $message = "Could not fetch tables: " . mysqli_error($conn);
    $message_type = 'danger';
}

// --- Fetch Data for Selected Table ---
$table_data = [];
$total_rows = 0;
if (!empty($selected_table) && in_array($selected_table, $tables)) {
    // Get total rows for pagination
    $count_sql = "SELECT COUNT(*) FROM `" . mysqli_real_escape_string($conn, $selected_table) . "`";
    if ($count_result = mysqli_query($conn, $count_sql)) {
        $total_rows = mysqli_fetch_row($count_result)[0];
        mysqli_free_result($count_result);
    }

    $offset = ($page - 1) * $limit;
    $data_sql = "SELECT * FROM `" . mysqli_real_escape_string($conn, $selected_table) . "` LIMIT " . $limit . " OFFSET " . $offset;
    if ($result_data = mysqli_query($conn, $data_sql)) {
        while ($row = mysqli_fetch_assoc($result_data)) {
            $table_data[] = $row;
        }
        mysqli_free_result($result_data);
    } else {
        $message = "Could not fetch data from table '" . htmlspecialchars($selected_table) . "': " . mysqli_error($conn);
        $message_type = 'danger';
    }
}

// Close connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MB Logistics DB Maintenance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" xintegrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8f9fa; }
        .container-fluid { padding: 20px; }
        .card { border-radius: 15px; box-shadow: 0 4px 8px rgba(0,0,0,0.1); margin-bottom: 20px; }
        .card-header { background-color: #007bff; color: white; border-radius: 15px 15px 0 0 !important; }
        .form-control, .form-select, .btn { border-radius: 8px; }
        textarea.form-control { min-height: 100px; }
        .table-responsive { max-height: 500px; overflow-y: auto; border-radius: 8px; }
        table { margin-bottom: 0; }
        th, td { white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 200px; }
        .pagination-controls { display: flex; justify-content: center; align-items: center; margin-top: 15px; }
        .pagination-controls .page-link { border-radius: 8px; margin: 0 5px; }
        .alert-fixed { position: fixed; top: 20px; right: 20px; z-index: 1050; min-width: 250px; }
    </style>
</head>
<body>
    <div class="container-fluid">
        <h1 class="mb-4 text-center">MB Logistics Database Maintenance</h1>

        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show alert-fixed" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- SQL Query Executor Card -->
        <div class="card">
            <div class="card-header">
                <h5>SQL Query Executor</h5>
            </div>
            <div class="card-body">
                <form method="POST">
                    <div class="mb-3">
                        <label for="sql_query" class="form-label">Enter SQL Query:</label>
                        <textarea class="form-control" id="sql_query" name="sql_query" rows="5" placeholder="e.g., SELECT * FROM users;"><?php echo htmlspecialchars($executed_query); ?></textarea>
                        <div class="form-text text-danger">Use with extreme caution! Direct modification or deletion of data is possible.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">Execute Query</button>
                    <a href="?action=logout" class="btn btn-danger float-end">Logout Maintenance</a>
                </form>
                <?php if ($query_result !== null): ?>
                    <h6 class="mt-4">Query Result:</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-sm">
                            <thead>
                                <tr>
                                    <?php foreach (array_keys($query_result[0]) as $col_name): ?>
                                        <th><?php echo htmlspecialchars($col_name); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($query_result as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Table Viewer Card -->
        <div class="card">
            <div class="card-header">
                <h5>Table Viewer</h5>
            </div>
            <div class="card-body">
                <form method="GET" class="mb-3">
                    <div class="row align-items-end">
                        <div class="col-md-6 mb-3 mb-md-0">
                            <label for="select_table" class="form-label">Select Table:</label>
                            <select class="form-select" id="select_table" name="table" onchange="this.form.submit()">
                                <option value="">-- Select a table --</option>
                                <?php foreach ($tables as $table): ?>
                                    <option value="<?php echo htmlspecialchars($table); ?>" <?php echo ($selected_table === $table) ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($table); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php if ($selected_table): ?>
                            <div class="col-md-6 text-md-end">
                                <p class="mb-0 text-muted">Total Rows: <?php echo $total_rows; ?></p>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>

                <?php if (!empty($table_data)): ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped table-hover table-sm">
                            <thead class="table-dark">
                                <tr>
                                    <?php foreach (array_keys($table_data[0]) as $col_name): ?>
                                        <th><?php echo htmlspecialchars($col_name); ?></th>
                                    <?php endforeach; ?>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($table_data as $row): ?>
                                    <tr>
                                        <?php foreach ($row as $value): ?>
                                            <td><?php echo htmlspecialchars($value); ?></td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php
                    // Pagination controls
                    $total_pages = ceil($total_rows / $limit);
                    if ($total_pages > 1):
                    ?>
                        <div class="pagination-controls">
                            <nav>
                                <ul class="pagination">
                                    <li class="page-item <?php echo ($page <= 1) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?table=<?php echo htmlspecialchars($selected_table); ?>&page=<?php echo $page - 1; ?>">Previous</a>
                                    </li>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo ($page === $i) ? 'active' : ''; ?>">
                                            <a class="page-link" href="?table=<?php echo htmlspecialchars($selected_table); ?>&page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <li class="page-item <?php echo ($page >= $total_pages) ? 'disabled' : ''; ?>">
                                        <a class="page-link" href="?table=<?php echo htmlspecialchars($selected_table); ?>&page=<?php echo $page + 1; ?>">Next</a>
                                    </li>
                                </ul>
                            </nav>
                        </div>
                    <?php endif; ?>
                <?php elseif ($selected_table): ?>
                    <p class="text-center text-muted">No data found for table '<?php echo htmlspecialchars($selected_table); ?>'.</p>
                <?php else: ?>
                    <p class="text-center text-muted">Select a table to view its data.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" xintegrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eOzr0E/o7N+o83V8zJ" crossorigin="anonymous"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            const alerts = document.querySelectorAll('.alert-fixed');
            alerts.forEach(alert => {
                setTimeout(() => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000); // 5000 milliseconds = 5 seconds
            });
        });
    </script>
</body>
</html>
<?php
// Logout logic for the maintenance script
if (isset($_GET['action']) && $_GET['action'] === 'logout') {
    unset($_SESSION['maintenance_logged_in']);
    session_destroy();
    header("location: maintenance.php");
    exit;
}
?>

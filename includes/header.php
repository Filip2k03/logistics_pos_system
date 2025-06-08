<?php
if (session_status() === PHP_SESSION_NONE) session_start();

// Set or get theme from session
if (isset($_GET['toggle_theme'])) {
  $_SESSION['theme'] = ($_SESSION['theme'] ?? 'light') === 'dark' ? 'light' : 'dark';
  // Redirect to remove toggle_theme from URL
  $redirect = strtok($_SERVER["REQUEST_URI"], '?');
  header("Location: $redirect");
  exit;
}
$theme = $_SESSION['theme'] ?? 'light';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>MB Logistics POS</title>
  <!-- Bootstrap 5.3 CSS CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" crossorigin="anonymous">
  <!-- Custom CSS -->
  <link rel="stylesheet" href="css/style.css">
  <!-- Google Fonts - Inter -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css">
</head>
<body class="<?php echo $theme === 'dark' ? 'dark-mode' : ''; ?>">
  <nav class="navbar navbar-expand-lg navbar-dark">
      <div class="container-fluid">
          <a class="navbar-brand d-flex align-items-center" href="dashboard.php">
              <!-- Logistics Icon -->
              <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-truck me-2" viewBox="0 0 16 16">
                  <path d="M0 3.5A1.5 1.5 0 0 1 1.5 2h9A1.5 1.5 0 0 1 12 3.5V5h1.02a1.5 1.5 0 0 1 1.17.563l1.481 1.85a1.5 1.5 0 0 1 .329.938V10.5a1.5 1.5 0 0 1-1.5 1.5H14a2 2 0 1 1-4 0H5a2 2 0 1 1-3.998-.085A1.5 1.5 0 0 1 0 10.5v-7zm1.294 7.456A1.999 1.999 0 0 1 4.732 11h5.536a2.01 2.01 0 0 1 .732-.732V3.5a.5.5 0 0 0-.5-.5h-9a.5.5 0 0 0-.5.5v7a.5.5 0 0 0 .294.456zM12 10a2 2 0 0 1 1.732 1h.768a.5.5 0 0 0 .5-.5V8.35a.5.5 0 0 0-.11-.312l-1.48-1.85A.5.5 0 0 0 13.02 6H12v4zm-9 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zm9 0a1 1 0 1 0 0 2 1 1 0 0 0 0-2z"/>
              </svg>
              MB Logistics POS
          </a>
          <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
              <span class="navbar-toggler-icon"></span>
          </button>
          <div class="collapse navbar-collapse" id="navbarNav">
              <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                  <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                      <li class="nav-item">
                          <a class="nav-link" href="create_voucher.php">
                              <i class="bi bi-file-earmark-plus me-1"></i> Create Voucher
                          </a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="stock.php">
                              <i class="bi bi-boxes me-1"></i> Stock View
                          </a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="pending_receive.php">
                              <i class="bi bi-clock-history me-1"></i> Pending Receive
                          </a>
                      </li>
                      <li class="nav-item">
                          <a class="nav-link" href="voucher_list.php">
                              <i class="bi bi-list-check me-1"></i> Voucher List
                          </a>
                      </li>
                  <?php endif; ?>
              </ul>
              <ul class="navbar-nav">
                  <li class="nav-item">
                      <a href="?toggle_theme=1" id="theme-toggle" class="btn btn-outline-light ms-2" title="Toggle dark/light mode">
                          <span id="theme-icon" class="bi <?php echo $theme === 'dark' ? 'bi-sun' : 'bi-moon'; ?>"></span>
                      </a>
                  </li>
              </ul>
              <ul class="navbar-nav">
                  <?php if (isset($_SESSION['loggedin']) && $_SESSION['loggedin'] === true): ?>
                      <li class="nav-item dropdown">
                          <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                              <i class="bi bi-person-circle me-2"></i>
                              <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['region']); ?>)
                          </a>
                          <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                              <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                          </ul>
                      </li>
                  <?php else: ?>
                      <li class="nav-item">
                          <a class="nav-link" href="index.php"><i class="bi bi-box-arrow-in-right me-1"></i>Login</a>
                      </li>
                  <?php endif; ?>
              </ul>
          </div>
      </div>
  </nav>

  <!-- Bootstrap 5.3 JS CDN -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" crossorigin="anonymous"></script>
  <!-- Custom JS -->
  <script src="js/scripts.js"></script>
</body>
</html>
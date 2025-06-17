<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sushi Website</title>
    <!-- Bootstrap CSS (Local) -->
    <link rel="stylesheet" href="assets/lib/bootstrap/css/bootstrap-main.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Global Frontend Styles -->
    <link rel="stylesheet" href="assets/frontend/css/global.css">

    <!-- Page-specific styles will be loaded by individual pages -->
    <?php
    // Auto-load page-specific CSS based on current page
    $current_page = basename($_SERVER['PHP_SELF'], '.php');
    $page_css = "assets/frontend/css/{$current_page}.css";
    if (file_exists($page_css)) {
        echo "<link rel=\"stylesheet\" href=\"{$page_css}\">\n";
    }
    ?>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="index.php">Sushi Master</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#menu">Menu</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#reservation">Reservation</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#events">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="promotions.php">
                            <i class="fas fa-tags me-1"></i>Promotions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="custom_offers.php">
                            <i class="fas fa-gift me-1"></i>Custom Offers
                        </a>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if (isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="admin/dashboard.php">Dashboard</a>
                            </li>
                        <?php endif; ?>
                        <li class="nav-item">
                            <span class="nav-link">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error_message'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?></div>
        <?php endif; ?>
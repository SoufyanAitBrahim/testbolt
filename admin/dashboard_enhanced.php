<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

// Get admin role and info
$admin_role = getAdminRole();
$is_super_admin = isSuperAdmin();

// Get counts for dashboard
$meals_count = $pdo->query("SELECT COUNT(*) FROM MEALS")->fetchColumn();
$orders_count = $pdo->query("SELECT COUNT(*) FROM `ORDER`")->fetchColumn();
$reservations_count = $pdo->query("SELECT COUNT(*) FROM BOOK_TABLE")->fetchColumn();
$events_count = $pdo->query("SELECT COUNT(*) FROM EVENT_BOOKINGS")->fetchColumn();

// Get promotion orders count (using existing table structure)
try {
    $promotion_orders_count = $pdo->query("
        SELECT COUNT(DISTINCT ID_CLIENTS)
        FROM ADMIN_CUSTOMIZED_OFFERS
        WHERE ID_CLIENTS IS NOT NULL AND ID_CLIENTS > 0
    ")->fetchColumn();
} catch (PDOException $e) {
    $promotion_orders_count = 0;
}

// Get custom client offers count (from CLIENTS_OFFERS table)
try {
    $custom_offers_count = $pdo->query("SELECT COUNT(*) FROM CLIENTS_OFFERS")->fetchColumn();
} catch (PDOException $e) {
    $custom_offers_count = 0;
}
$categories_count = $pdo->query("SELECT COUNT(*) FROM CATEGORIES")->fetchColumn();
$clients_count = $pdo->query("SELECT COUNT(*) FROM CLIENTS")->fetchColumn();

// Recent menu orders (regular orders from ORDER table)
$recent_menu_orders = $pdo->query("
    SELECT o.DATE_ORDER, c.FULLNAME, m.NAME as meal_name, o.ORDER_SITUATION
    FROM `ORDER` o
    JOIN CLIENTS c ON o.ID_CLIENTS = c.ID_CLIENTS
    JOIN MEALS m ON o.ID_MEALS = m.ID_MEALS
    ORDER BY o.DATE_ORDER DESC LIMIT 5
")->fetchAll();

// Recent reservations
$recent_reservations = $pdo->query("
    SELECT b.TABLE_BOOK_DATE, b.FULLNAME, b.NUMBER_OF_GUESTS, b.EVENT_DATE, b.EVENT_TIME, r.NAME as restaurant_name
    FROM BOOK_TABLE b
    JOIN RESTAURANTS r ON b.ID_RESTAURANTS = r.ID_RESTAURANTS
    ORDER BY b.TABLE_BOOK_DATE DESC LIMIT 5
")->fetchAll();

// Additional stats for super admin
if ($is_super_admin) {
    $admins_count = $pdo->query("SELECT COUNT(*) FROM ADMINS")->fetchColumn();
    $super_admins_count = $pdo->query("SELECT COUNT(*) FROM ADMINS WHERE ROLE = 1")->fetchColumn();
    $secondary_admins_count = $pdo->query("SELECT COUNT(*) FROM ADMINS WHERE ROLE = 2")->fetchColumn();
    $restaurants_count = $pdo->query("SELECT COUNT(*) FROM RESTAURANTS")->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - Sushi Admin</title>
    <!-- Bootstrap CSS (Local) -->
    <link rel="stylesheet" href="../assets/lib/bootstrap/css/bootstrap-main.css">

    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">

    <!-- Backend Dashboard Styles -->
    <link rel="stylesheet" href="../assets/backend/css/dashboard.css">
    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
        }
        .role-indicator {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .secondary-role {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard_enhanced.php">
                <i class="fas fa-utensils me-2"></i>Sushi Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link active" href="dashboard_enhanced.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                
                <?php if ($is_super_admin): ?>
                <a class="nav-link" href="meals_enhanced.php">
                    <i class="fas fa-fish me-1"></i>Meals
                </a>
                <a class="nav-link" href="restaurants_management.php">
                    <i class="fas fa-building me-1"></i>Restaurants
                </a>
                <a class="nav-link" href="admin_management.php">
                    <i class="fas fa-users-cog me-1"></i>Admins
                </a>
                <a class="nav-link" href="offers.php">
                    <i class="fas fa-tags me-1"></i>Admin's offers
                </a>
                <a class="nav-link" href="custom_offers_management.php">
                    <i class="fas fa-tags me-1"></i>Client's offers
                </a>
                <?php endif; ?>
                
                <a class="nav-link" href="orders_management.php">
                    <i class="fas fa-shopping-cart me-1"></i>Orders
                </a>
                <a class="nav-link" href="reservations.php">
                    <i class="fas fa-calendar me-1"></i>Reservations
                </a>
                <a class="nav-link" href="events.php">
                    <i class="fas fa-calendar-check me-1"></i>Events
                </a>
                
                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><span class="dropdown-item-text">
                            <small class="text-muted">
                                <?php echo $is_super_admin ? 'Super Admin' : 'Secondary Admin'; ?>
                            </small>
                        </span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="../logout.php">
                            <i class="fas fa-sign-out-alt me-1"></i>Logout
                        </a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-tachometer-alt me-2"></i>Admin Dashboard</h2>
                        <p class="text-muted mb-0">Welcome back, <?php echo htmlspecialchars($_SESSION['user_name']); ?>!</p>
                    </div>
                    <div>
                        <span class="badge <?php echo $is_super_admin ? 'role-indicator' : 'secondary-role'; ?> text-white fs-6">
                            <i class="fas <?php echo $is_super_admin ? 'fa-crown' : 'fa-user-tie'; ?> me-1"></i>
                            <?php echo $is_super_admin ? 'Super Admin' : 'Secondary Admin'; ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-fish fa-2x mb-2"></i>
                        <h3><?php echo $meals_count; ?></h3>
                        <p class="mb-0">Total Meals</p>
                        <?php if ($is_super_admin): ?>
                            <small><a href="meals_enhanced.php" class="text-white">Manage →</a></small>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-shopping-cart fa-2x mb-2"></i>
                        <h3><?php echo $orders_count; ?></h3>
                        <p class="mb-0">Orders</p>
                        <small><a href="orders_management.php" class="text-white">View →</a></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar fa-2x mb-2"></i>
                        <h3><?php echo $reservations_count; ?></h3>
                        <p class="mb-0">Reservations</p>
                        <small><a href="reservations.php" class="text-white">View →</a></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <h3><?php echo $events_count; ?></h3>
                        <p class="mb-0">Events</p>
                        <small><a href="events.php" class="text-dark">View →</a></small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Custom Offers & Promotion Orders Section -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-info text-white stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-gift fa-2x mb-2"></i>
                        <h3><?php echo $custom_offers_count; ?></h3>
                        <p class="mb-0">Custom Offers</p>
                        <small><a href="custom_offers_management.php" class="text-white">View →</a></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-tags fa-2x mb-2"></i>
                        <h3><?php echo $promotion_orders_count; ?></h3>
                        <p class="mb-0">Admin Offers</p>
                        <small><a href="offers.php" class="text-white">View →</a></small>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-gift me-2"></i>Recent Custom Offers</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get recent custom client offers using CLIENT_CUSTOMIZED_OFFERS table
                        try {
                            $recent_custom_offers = $pdo->query("
                                SELECT
                                    co.*,
                                    COUNT(cco.ID_MEALS) as meal_count,
                                    GROUP_CONCAT(m.NAME SEPARATOR ', ') as meal_names
                                FROM CLIENTS_OFFERS co
                                LEFT JOIN CLIENT_CUSTOMIZED_OFFERS cco ON co.ID_CLIENTS_OFFERS = cco.ID_CLIENTS_OFFERS
                                LEFT JOIN MEALS m ON cco.ID_MEALS = m.ID_MEALS
                                GROUP BY co.ID_CLIENTS_OFFERS
                                ORDER BY co.created_date DESC
                                LIMIT 5
                            ")->fetchAll();
                        } catch (PDOException $e) {
                            $recent_custom_offers = [];
                            error_log("Custom offers query error: " . $e->getMessage());
                        }
                        ?>

                        <?php if (empty($recent_custom_offers)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-gift fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No custom offers yet</p>
                                <small class="text-muted">Client-created offers will appear here</small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover table-sm">
                                    <thead>
                                        <tr>
                                            <th>Client</th>
                                            <th>Meals</th>
                                            <th>Total & Savings</th>
                                            <th>Date & Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_custom_offers as $offer): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($offer['client_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($offer['client_phone']); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-primary"><?php echo $offer['meal_count']; ?> meals</span>
                                                    <?php if ($offer['meal_names']): ?>
                                                        <br><small class="text-muted" title="<?php echo htmlspecialchars($offer['meal_names']); ?>">
                                                            <?php echo htmlspecialchars(strlen($offer['meal_names']) > 30 ? substr($offer['meal_names'], 0, 30) . '...' : $offer['meal_names']); ?>
                                                        </small>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <strong>$<?php echo number_format($offer['total_price'], 2); ?></strong><br>
                                                    <small class="text-success">
                                                        <?php
                                                        // Calculate discount using function
                                                        include_once '../includes/discount_functions.php';
                                                        $discount_info = calculateDiscount($offer['total_price'], $pdo);
                                                        if ($discount_info['discount_percentage'] > 0) {
                                                            echo "Discount: {$discount_info['discount_percentage']}% (Save: $" . number_format($discount_info['discount_amount'], 2) . ")";
                                                        } else {
                                                            echo "No discount applied";
                                                        }
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, H:i', strtotime($offer['created_date'])); ?><br>
                                                    <span class="badge bg-<?php echo $offer['status'] == 'completed' ? 'success' : ($offer['status'] == 'pending' ? 'warning' : 'secondary'); ?>">
                                                        <?php echo ucfirst($offer['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Recent Admin Offers Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Get recent admin offers orders using existing table structure
                        try {
                            $recent_admin_offers = $pdo->query("
                                SELECT
                                    c.FULLNAME as customer_name,
                                    c.PHONE_NUMBER as customer_phone,
                                    c.EMAIL as customer_email,
                                    ao.ADMINS_OFFERS_NAME as offer_name,
                                    ao.ADMINS_OFFERS_DESCRIPTION as offer_description,
                                    ao.OFFERS_PRICE as order_total,
                                    SUM(m.PRICE) as original_price,
                                    (SUM(m.PRICE) - ao.OFFERS_PRICE) as savings_amount,
                                    aco.OFFERS_ADMIN_DATE as order_date,
                                    aco.ID_CLIENTS,
                                    aco.ID_ADMINS_OFFERS,
                                    'pending' as order_status,
                                    GROUP_CONCAT(m.NAME SEPARATOR ', ') as selected_meals
                                FROM ADMIN_CUSTOMIZED_OFFERS aco
                                JOIN CLIENTS c ON aco.ID_CLIENTS = c.ID_CLIENTS
                                JOIN ADMINS_OFFERS ao ON aco.ID_ADMINS_OFFERS = ao.ID_ADMINS_OFFERS
                                JOIN MEALS m ON aco.ID_MEALS = m.ID_MEALS
                                WHERE aco.ID_CLIENTS > 0 AND aco.OFFERS_ADMIN_DATE IS NOT NULL
                                GROUP BY aco.ID_CLIENTS, aco.ID_ADMINS_OFFERS
                                ORDER BY aco.OFFERS_ADMIN_DATE DESC
                                LIMIT 5
                            ")->fetchAll();
                        } catch (PDOException $e) {
                            $recent_admin_offers = [];
                            error_log("Admin offers dashboard query error: " . $e->getMessage());
                        }
                        ?>

                        <?php if (empty($recent_admin_offers)): ?>
                            <div class="text-center py-3">
                                <i class="fas fa-tags fa-2x text-muted mb-2"></i>
                                <p class="text-muted">No admin offers orders yet</p>
                                <small class="text-muted">Orders from admin offers will appear here</small>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Customer</th>
                                            <th>Offer</th>
                                            <th>Total</th>
                                            <th>Status</th>
                                            <th>Date</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_admin_offers as $order): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($order['customer_name']); ?></strong><br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($order['customer_phone']); ?></small>
                                                </td>
                                                <td><?php echo htmlspecialchars($order['offer_name']); ?></td>
                                                <td>
                                                    <strong>$<?php echo number_format($order['order_total'], 2); ?></strong><br>
                                                    <small class="text-success">Saved: $<?php echo number_format($order['savings_amount'], 2); ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php
                                                        echo $order['order_status'] == 'pending' ? 'warning' :
                                                            ($order['order_status'] == 'preparing' ? 'info' :
                                                            ($order['order_status'] == 'ready' ? 'primary' :
                                                            ($order['order_status'] == 'completed' ? 'success' :
                                                            ($order['order_status'] == 'cancelled' ? 'danger' : 'secondary'))));
                                                    ?>">
                                                        <?php echo ucfirst($order['order_status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('M d, H:i', strtotime($order['order_date'])); ?></td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#orderModal<?php echo $order['ID_CLIENTS'] . '_' . $order['ID_ADMINS_OFFERS']; ?>">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                </td>
                                            </tr>

                                            <!-- Order Details Modal -->
                                            <div class="modal fade" id="orderModal<?php echo $order['ID_CLIENTS'] . '_' . $order['ID_ADMINS_OFFERS']; ?>" tabindex="-1">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Promotion Order Details</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <div class="row">
                                                                <div class="col-md-6">
                                                                    <h6>Customer Information:</h6>
                                                                    <p><strong>Name:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                                                                    <p><strong>Phone:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                                                                    <?php if ($order['customer_email']): ?>
                                                                        <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                                                                    <?php endif; ?>
                                                                </div>
                                                                <div class="col-md-6">
                                                                    <h6>Order Information:</h6>
                                                                    <p><strong>Offer:</strong> <?php echo htmlspecialchars($order['offer_name']); ?></p>
                                                                    <p><strong>Original Price:</strong> $<?php echo number_format($order['original_price'], 2); ?></p>
                                                                    <p><strong>Order Total:</strong> $<?php echo number_format($order['order_total'], 2); ?></p>
                                                                    <p><strong>Savings:</strong> <span class="text-success">$<?php echo number_format($order['savings_amount'], 2); ?></span></p>
                                                                </div>
                                                            </div>
                                                            <?php if (!empty($order['offer_description'])): ?>
                                                                <div class="mt-3">
                                                                    <h6>Offer Description:</h6>
                                                                    <p class="bg-light p-2 rounded"><?php echo htmlspecialchars($order['offer_description']); ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                            <?php if (!empty($order['selected_meals'])): ?>
                                                                <div class="mt-3">
                                                                    <h6>Selected Meals:</h6>
                                                                    <p class="bg-light p-2 rounded"><?php echo htmlspecialchars($order['selected_meals']); ?></p>
                                                                </div>
                                                            <?php endif; ?>
                                                            <div class="mt-3">
                                                                <h6>Order Details:</h6>
                                                                <div class="alert alert-info">
                                                                    <i class="fas fa-info-circle me-2"></i>
                                                                    <strong>Order Reference:</strong> Client ID #<?php echo $order['ID_CLIENTS']; ?> - Offer #<?php echo $order['ID_ADMINS_OFFERS']; ?><br>
                                                                    <strong>Order Date:</strong> <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?><br>
                                                                    <strong>Status:</strong> Order placed successfully
                                                                </div>
                                                                <div class="text-center">
                                                                    <a href="tel:<?php echo $order['customer_phone']; ?>" class="btn btn-success me-2">
                                                                        <i class="fas fa-phone me-1"></i>Call Customer
                                                                    </a>
                                                                    <?php if ($order['customer_email'] && !strpos($order['customer_email'], '@sushi-customer.com')): ?>
                                                                        <a href="mailto:<?php echo $order['customer_email']; ?>" class="btn btn-primary">
                                                                            <i class="fas fa-envelope me-1"></i>Email Customer
                                                                        </a>
                                                                    <?php endif; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Super Admin Additional Stats -->
        <?php if ($is_super_admin): ?>
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-secondary text-white stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-tags fa-2x mb-2"></i>
                        <h3><?php echo $categories_count; ?></h3>
                        <p class="mb-0">Categories</p>
                        <small><a href="meals_enhanced.php#categories" class="text-white">Manage →</a></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-dark text-white stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h3><?php echo $clients_count; ?></h3>
                        <p class="mb-0">Clients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white stats-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <div class="card-body text-center">
                        <i class="fas fa-users-cog fa-2x mb-2"></i>
                        <h3><?php echo $admins_count; ?></h3>
                        <p class="mb-0">Total Admins</p>
                        <small><a href="admin_management.php" class="text-white">Manage →</a></small>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-gradient text-white stats-card" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <div class="card-body text-center">
                        <i class="fas fa-crown fa-2x mb-2"></i>
                        <h3><?php echo $super_admins_count; ?> / <?php echo $secondary_admins_count; ?></h3>
                        <p class="mb-0">Super / Secondary</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Super Admin Stats Row -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card text-white stats-card" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                    <div class="card-body text-center">
                        <i class="fas fa-building fa-2x mb-2"></i>
                        <h3><?php echo $restaurants_count; ?></h3>
                        <p class="mb-0">Restaurants</p>
                        <small><a href="restaurants_management.php" class="text-white">Manage →</a></small>
                    </div>
                </div>
            </div>
            <!-- <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-chart-line me-2"></i>System Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-2">
                                <div class="border-end">
                                    <h4 class="text-primary"><?php echo $restaurants_count; ?></h4>
                                    <small class="text-muted">Restaurants</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border-end">
                                    <h4 class="text-success"><?php echo $categories_count; ?></h4>
                                    <small class="text-muted">Categories</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border-end">
                                    <h4 class="text-info"><?php echo $meals_count; ?></h4>
                                    <small class="text-muted">Meals</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border-end">
                                    <h4 class="text-warning"><?php echo $clients_count; ?></h4>
                                    <small class="text-muted">Clients</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="border-end">
                                    <h4 class="text-secondary"><?php echo $admins_count; ?></h4>
                                    <small class="text-muted">Admins</small>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <h4 class="text-danger"><?php echo $orders_count + $reservations_count; ?></h4>
                                <small class="text-muted">Total Orders</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
        <?php endif; ?>

        <!-- Recent Activity -->
        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Recent Orders</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_menu_orders)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Client</th>
                                        <th>Meal</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_menu_orders as $order): ?>
                                    <tr>
                                        <td><?php echo date('M d', strtotime($order['DATE_ORDER'])); ?></td>
                                        <td><?php echo htmlspecialchars($order['FULLNAME']); ?></td>
                                        <td><?php echo htmlspecialchars($order['meal_name']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php
                                                echo $order['ORDER_SITUATION'] == 'Pending' ? 'warning' :
                                                    ($order['ORDER_SITUATION'] == 'Ready' ? 'success' : 'info');
                                            ?>">
                                                <?php echo htmlspecialchars($order['ORDER_SITUATION']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center">No recent menu orders</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-calendar me-2"></i>Recent Reservations</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($recent_reservations)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Guests</th>
                                        <th>Date</th>
                                        <th>Restaurant</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_reservations as $reservation): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($reservation['FULLNAME']); ?></td>
                                        <td><?php echo $reservation['NUMBER_OF_GUESTS']; ?></td>
                                        <td><?php echo date('M d', strtotime($reservation['EVENT_DATE'])); ?></td>
                                        <td><?php echo htmlspecialchars($reservation['restaurant_name']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-muted text-center">No recent reservations</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php if ($is_super_admin): ?>
                            <div class="col-md-3 mb-2">
                                <a href="meals_enhanced.php#add-meal" class="btn btn-primary w-100">
                                    <i class="fas fa-plus me-1"></i>Add New Meal
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="admin_management.php#add-admin" class="btn btn-success w-100">
                                    <i class="fas fa-user-plus me-1"></i>Add Admin
                                </a>
                            </div>
                            <?php endif; ?>
                            <div class="col-md-3 mb-2">
                                <a href="orders_management.php" class="btn btn-info w-100">
                                    <i class="fas fa-eye me-1"></i>View Orders
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="reservations.php" class="btn btn-warning w-100">
                                    <i class="fas fa-calendar-check me-1"></i>View Reservations
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="events.php" class="btn btn-secondary w-100">
                                    <i class="fas fa-calendar-plus me-1"></i>View Events
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="custom_offers_management.php" class="btn btn-success w-100">
                                    <i class="fas fa-gift me-1"></i>Custom Offers
                                </a>
                            </div>
                            <?php if ($is_super_admin): ?>
                            <div class="col-md-3 mb-2">
                                <a href="offers.php" class="btn btn-warning w-100">
                                    <i class="fas fa-tags me-1"></i>Admin Offers
                                </a>
                            </div>
                            <div class="col-md-3 mb-2">
                                <a href="restaurants_management.php" class="btn btn-primary w-100">
                                    <i class="fas fa-building me-1"></i>Manage Restaurants
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JavaScript (Local) -->
    <script src="../assets/lib/bootstrap/js/bootstrap.js"></script>

    <!-- Backend Dashboard JavaScript -->
    <script src="../assets/backend/js/dashboard.js"></script>
</body>
</html>

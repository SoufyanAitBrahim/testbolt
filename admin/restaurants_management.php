<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

// Check if user is super admin using the auth functions
if (!isSuperAdmin()) {
    header("Location: dashboard_enhanced.php?error=access_denied");
    exit();
}

$is_super_admin = true; // If we reach here, user is super admin

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_restaurant'])) {
        // Add new restaurant
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        if (!empty($name) && !empty($address) && !empty($phone) && !empty($email)) {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM RESTAURANTS WHERE EMAIL = ?");
            $stmt->execute([$email]);
            if ($stmt->fetchColumn() > 0) {
                $error = "A restaurant with this email already exists!";
            } else {
                $stmt = $pdo->prepare("INSERT INTO RESTAURANTS (NAME, ADDRESS, PHONE, EMAIL) VALUES (?, ?, ?, ?)");
                $stmt->execute([$name, $address, $phone, $email]);
                $success = "Restaurant added successfully!";
            }
        } else {
            $error = "Please fill in all fields!";
        }
    } elseif (isset($_POST['update_restaurant'])) {
        // Update restaurant
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $address = trim($_POST['address']);
        $phone = trim($_POST['phone']);
        $email = trim($_POST['email']);

        if (!empty($name) && !empty($address) && !empty($phone) && !empty($email)) {
            // Check if email already exists for other restaurants
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM RESTAURANTS WHERE EMAIL = ? AND ID_RESTAURANTS != ?");
            $stmt->execute([$email, $id]);
            if ($stmt->fetchColumn() > 0) {
                $error = "Another restaurant with this email already exists!";
            } else {
                $stmt = $pdo->prepare("UPDATE RESTAURANTS SET NAME = ?, ADDRESS = ?, PHONE = ?, EMAIL = ? WHERE ID_RESTAURANTS = ?");
                $stmt->execute([$name, $address, $phone, $email, $id]);
                $success = "Restaurant updated successfully!";
            }
        } else {
            $error = "Please fill in all fields!";
        }
    } elseif (isset($_GET['delete'])) {
        // Delete restaurant
        $id = intval($_GET['delete']);
        
        // Check if restaurant has any bookings
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM BOOK_TABLE WHERE ID_RESTAURANTS = ?");
        $stmt->execute([$id]);
        $booking_count = $stmt->fetchColumn();
        
        if ($booking_count > 0) {
            $error = "Cannot delete restaurant! It has $booking_count table bookings. Please handle the bookings first.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM RESTAURANTS WHERE ID_RESTAURANTS = ?");
            $stmt->execute([$id]);
            $success = "Restaurant deleted successfully!";
        }
    }
}

// Get all restaurants with statistics
$restaurants = $pdo->query("
    SELECT 
        r.*,
        COUNT(DISTINCT bt.ID_BOOK_TABLE) as total_bookings,
        COUNT(DISTINCT CASE WHEN bt.EVENT_DATE >= CURDATE() THEN bt.ID_BOOK_TABLE END) as upcoming_bookings,
        COUNT(DISTINCT CASE WHEN bt.EVENT_DATE < CURDATE() THEN bt.ID_BOOK_TABLE END) as past_bookings
    FROM RESTAURANTS r
    LEFT JOIN BOOK_TABLE bt ON r.ID_RESTAURANTS = bt.ID_RESTAURANTS
    GROUP BY r.ID_RESTAURANTS
    ORDER BY r.NAME
")->fetchAll();

// Get overall statistics
$total_restaurants = count($restaurants);
$total_bookings = $pdo->query("SELECT COUNT(*) FROM BOOK_TABLE")->fetchColumn();
$upcoming_bookings = $pdo->query("SELECT COUNT(*) FROM BOOK_TABLE WHERE EVENT_DATE >= CURDATE()")->fetchColumn();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restaurant Management - Sushi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .stats-card {
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-2px);
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
                <a class="nav-link" href="dashboard_enhanced.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link" href="meals_enhanced.php">
                    <i class="fas fa-fish me-1"></i>Meals
                </a>
                <a class="nav-link active" href="restaurants_management.php">
                    <i class="fas fa-building me-1"></i>Restaurants
                </a>
                <a class="nav-link" href="admin_management.php">
                    <i class="fas fa-users-cog me-1"></i>Admins
                </a>
                <a class="nav-link" href="offers.php">
                    <i class="fas fa-tags me-1"></i>Admin Offers
                </a>
                <a class="nav-link" href="orders_management.php">
                    <i class="fas fa-shopping-cart me-1"></i>Orders
                </a>
                <a class="nav-link" href="reservations.php">
                    <i class="fas fa-calendar me-1"></i>Reservations
                </a>

                <div class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Admin'); ?>
                    </a>
                    <ul class="dropdown-menu">
                        <li><span class="dropdown-item-text">
                            <small class="text-muted">Super Admin</small>
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
    <div class="row">
        <!-- Main Content -->
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-building me-2"></i>Restaurant Management</h2>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addRestaurantModal">
                    <i class="fas fa-plus me-2"></i>Add New Restaurant
                </button>
            </div>

            <!-- Success/Error Messages -->
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card bg-primary text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-building fa-2x mb-2"></i>
                            <h3><?php echo $total_restaurants; ?></h3>
                            <p class="mb-0">Total Restaurants</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-success text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i>
                            <h3><?php echo $total_bookings; ?></h3>
                            <p class="mb-0">Total Bookings</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card bg-info text-white">
                        <div class="card-body text-center">
                            <i class="fas fa-clock fa-2x mb-2"></i>
                            <h3><?php echo $upcoming_bookings; ?></h3>
                            <p class="mb-0">Upcoming Bookings</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Restaurants Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Restaurants (<?php echo $total_restaurants; ?>)</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($restaurants)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-building fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No restaurants found</h5>
                            <p class="text-muted">Click "Add New Restaurant" to get started.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>ID</th>
                                        <th>Restaurant Name</th>
                                        <th>Address</th>
                                        <th>Contact</th>
                                        <th>Bookings</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($restaurants as $restaurant): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-secondary">#<?php echo $restaurant['ID_RESTAURANTS']; ?></span>
                                            </td>
                                            <td>
                                                <strong><?php echo htmlspecialchars($restaurant['NAME']); ?></strong>
                                            </td>
                                            <td>
                                                <i class="fas fa-map-marker-alt me-1 text-muted"></i>
                                                <?php echo htmlspecialchars($restaurant['ADDRESS']); ?>
                                            </td>
                                            <td>
                                                <div>
                                                    <i class="fas fa-phone me-1 text-muted"></i>
                                                    <a href="tel:<?php echo $restaurant['PHONE']; ?>"><?php echo htmlspecialchars($restaurant['PHONE']); ?></a>
                                                </div>
                                                <div>
                                                    <i class="fas fa-envelope me-1 text-muted"></i>
                                                    <a href="mailto:<?php echo $restaurant['EMAIL']; ?>"><?php echo htmlspecialchars($restaurant['EMAIL']); ?></a>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex flex-column">
                                                    <span class="badge bg-primary mb-1">Total: <?php echo $restaurant['total_bookings']; ?></span>
                                                    <span class="badge bg-success mb-1">Upcoming: <?php echo $restaurant['upcoming_bookings']; ?></span>
                                                    <span class="badge bg-secondary">Past: <?php echo $restaurant['past_bookings']; ?></span>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-outline-primary" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#editRestaurantModal<?php echo $restaurant['ID_RESTAURANTS']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <a href="?delete=<?php echo $restaurant['ID_RESTAURANTS']; ?>" 
                                                       class="btn btn-sm btn-outline-danger"
                                                       onclick="return confirm('Are you sure you want to delete this restaurant? This action cannot be undone.')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                    <a href="reservations.php?restaurant=<?php echo $restaurant['ID_RESTAURANTS']; ?>" 
                                                       class="btn btn-sm btn-outline-info">
                                                        <i class="fas fa-calendar"></i>
                                                    </a>
                                                </div>
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
    </div>
</div>

<!-- Add Restaurant Modal -->
<div class="modal fade" id="addRestaurantModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus me-2"></i>Add New Restaurant
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Restaurant Name</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" name="add_restaurant" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Add Restaurant
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Restaurant Modals -->
<?php foreach ($restaurants as $restaurant): ?>
<div class="modal fade" id="editRestaurantModal<?php echo $restaurant['ID_RESTAURANTS']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Restaurant: <?php echo htmlspecialchars($restaurant['NAME']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $restaurant['ID_RESTAURANTS']; ?>">

                    <div class="mb-3">
                        <label class="form-label">Restaurant Name</label>
                        <input type="text" name="name" class="form-control"
                               value="<?php echo htmlspecialchars($restaurant['NAME']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Address</label>
                        <textarea name="address" class="form-control" rows="3" required><?php echo htmlspecialchars($restaurant['ADDRESS']); ?></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number</label>
                        <input type="tel" name="phone" class="form-control"
                               value="<?php echo htmlspecialchars($restaurant['PHONE']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control"
                               value="<?php echo htmlspecialchars($restaurant['EMAIL']); ?>" required>
                    </div>

                    <!-- Restaurant Statistics -->
                    <div class="alert alert-info">
                        <h6><i class="fas fa-chart-bar me-2"></i>Restaurant Statistics:</h6>
                        <div class="row text-center">
                            <div class="col-4">
                                <strong><?php echo $restaurant['total_bookings']; ?></strong><br>
                                <small>Total Bookings</small>
                            </div>
                            <div class="col-4">
                                <strong><?php echo $restaurant['upcoming_bookings']; ?></strong><br>
                                <small>Upcoming</small>
                            </div>
                            <div class="col-4">
                                <strong><?php echo $restaurant['past_bookings']; ?></strong><br>
                                <small>Past</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" name="update_restaurant" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Auto-hide alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(function(alert) {
        setTimeout(function() {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>

</body>
</html>

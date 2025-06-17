<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

// Check if user has permission to manage meals (only super admins)
if (!hasPermission('manage_meals') && !isSuperAdmin()) {
    header("Location: dashboard.php?error=access_denied");
    exit();
}

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_category'])) {
        // Add new category
        $name = trim($_POST['category_name']);
        $description = trim($_POST['category_description']);
        
        if (!empty($name)) {
            try {
                $stmt = $pdo->prepare("INSERT INTO CATEGORIES (NAME, DESCRIPTION) VALUES (?, ?)");
                $stmt->execute([$name, $description]);
                $success = "Category added successfully!";
            } catch (PDOException $e) {
                $error = "Error adding category: " . $e->getMessage();
            }
        } else {
            $error = "Category name is required!";
        }
    }
    
    elseif (isset($_POST['add_meal'])) {
        // Add new meal
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = intval($_POST['category']);
        $admin_id = $_SESSION['user_id'];
        
        if (!empty($name) && !empty($description) && $price > 0 && $category > 0) {
            // Set default image URL
            $image_url = 'assets/images/meals/default-meal.jpg';

            try {
                $stmt = $pdo->prepare("INSERT INTO MEALS (ID_ADMINS, ID_CATEGORIES, NAME, DESCRIPTION, PRICE, IMAGE_URL) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$admin_id, $category, $name, $description, $price, $image_url]);
                $success = "Meal added successfully!";
            } catch (PDOException $e) {
                $error = "Error adding meal: " . $e->getMessage();
            }
        } else {
            $error = "All fields are required and price must be greater than 0!";
        }
    }
    
    elseif (isset($_POST['update_meal'])) {
        // Update meal
        $id = intval($_POST['id']);
        $name = trim($_POST['name']);
        $description = trim($_POST['description']);
        $price = floatval($_POST['price']);
        $category = intval($_POST['category']);
        
        if (!empty($name) && !empty($description) && $price > 0 && $category > 0) {
            try {
                $stmt = $pdo->prepare("UPDATE MEALS SET NAME = ?, DESCRIPTION = ?, PRICE = ?, ID_CATEGORIES = ? WHERE ID_MEALS = ?");
                $stmt->execute([$name, $description, $price, $category, $id]);
                $success = "Meal updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating meal: " . $e->getMessage();
            }
        } else {
            $error = "All fields are required and price must be greater than 0!";
        }
    }
    
    elseif (isset($_POST['delete_meal'])) {
        // Delete meal
        $id = intval($_POST['meal_id']);
        
        try {
            // Get image path to delete file
            $stmt = $pdo->prepare("SELECT IMAGE_URL FROM MEALS WHERE ID_MEALS = ?");
            $stmt->execute([$id]);
            $meal = $stmt->fetch();
            
            // Delete meal from database
            $stmt = $pdo->prepare("DELETE FROM MEALS WHERE ID_MEALS = ?");
            $stmt->execute([$id]);
            
            // Delete image file if it exists and is not default
            if ($meal && $meal['IMAGE_URL'] && $meal['IMAGE_URL'] != 'assets/images/meals/default-meal.jpg') {
                $image_path = '../' . $meal['IMAGE_URL'];
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            $success = "Meal deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting meal: " . $e->getMessage();
        }
    }
    
    elseif (isset($_POST['delete_category'])) {
        // Delete category (only if no meals are using it)
        $id = intval($_POST['category_id']);
        
        try {
            // Check if category has meals
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM MEALS WHERE ID_CATEGORIES = ?");
            $stmt->execute([$id]);
            $meal_count = $stmt->fetchColumn();
            
            if ($meal_count > 0) {
                $error = "Cannot delete category. It has $meal_count meals assigned to it.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM CATEGORIES WHERE ID_CATEGORIES = ?");
                $stmt->execute([$id]);
                $success = "Category deleted successfully!";
            }
        } catch (PDOException $e) {
            $error = "Error deleting category: " . $e->getMessage();
        }
    }
}

// Get all meals with category information
$meals = $pdo->query("
    SELECT m.*, c.NAME as category_name, a.FULLNAME as admin_name
    FROM MEALS m
    JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
    JOIN ADMINS a ON m.ID_ADMINS = a.ID_ADMINS
    ORDER BY c.NAME, m.NAME
")->fetchAll();

// Get all categories
$categories = $pdo->query("SELECT * FROM CATEGORIES ORDER BY NAME")->fetchAll();

// Get meals count by category
$category_stats = $pdo->query("
    SELECT c.ID_CATEGORIES, c.NAME, COUNT(m.ID_MEALS) as meal_count
    FROM CATEGORIES c
    LEFT JOIN MEALS m ON c.ID_CATEGORIES = m.ID_CATEGORIES
    GROUP BY c.ID_CATEGORIES, c.NAME
    ORDER BY c.NAME
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meal Management - Sushi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .meal-image {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: 8px;
        }
        .category-card {
            transition: transform 0.2s;
        }
        .category-card:hover {
            transform: translateY(-2px);
        }
        .stats-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard.php">
                <i class="fas fa-utensils me-2"></i>Sushi Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>
                <a class="nav-link active" href="meals_enhanced.php">
                    <i class="fas fa-fish me-1"></i>Meals
                </a>
                <?php if (isSuperAdmin()): ?>
                <a class="nav-link" href="admin_management.php">
                    <i class="fas fa-users-cog me-1"></i>Admins
                </a>
                <?php endif; ?>
                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-fish me-2"></i>Meal & Category Management</h2>
                    <div>
                        <span class="badge bg-primary fs-6">Super Admin Access</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-fish fa-2x mb-2"></i>
                        <h3><?php echo count($meals); ?></h3>
                        <p class="mb-0">Total Meals</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-tags fa-2x mb-2"></i>
                        <h3><?php echo count($categories); ?></h3>
                        <p class="mb-0">Categories</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h3>$<?php 
                            $avg_price = 0;
                            if (count($meals) > 0) {
                                $total = array_sum(array_column($meals, 'PRICE'));
                                $avg_price = $total / count($meals);
                            }
                            echo number_format($avg_price, 2); 
                        ?></h3>
                        <p class="mb-0">Avg Price</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line fa-2x mb-2"></i>
                        <h3><?php 
                            $max_price = count($meals) > 0 ? max(array_column($meals, 'PRICE')) : 0;
                            echo '$' . number_format($max_price, 2); 
                        ?></h3>
                        <p class="mb-0">Highest Price</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs" id="managementTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="meals-tab" data-bs-toggle="tab" data-bs-target="#meals" type="button" role="tab">
                            <i class="fas fa-fish me-1"></i>Manage Meals
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="categories-tab" data-bs-toggle="tab" data-bs-target="#categories" type="button" role="tab">
                            <i class="fas fa-tags me-1"></i>Manage Categories
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-meal-tab" data-bs-toggle="tab" data-bs-target="#add-meal" type="button" role="tab">
                            <i class="fas fa-plus me-1"></i>Add New Meal
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="managementTabsContent">
                    <!-- Meals Management Tab -->
                    <div class="tab-pane fade show active" id="meals" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-fish me-2"></i>All Meals</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Name</th>
                                                <th>Category</th>
                                                <th>Price</th>
                                                <th>Added By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($meals as $meal): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($meal['NAME']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($meal['DESCRIPTION'], 0, 50)) . '...'; ?></small>
                                                </td>
                                                <td>
                                                    <span class="badge bg-secondary"><?php echo htmlspecialchars($meal['category_name']); ?></span>
                                                </td>
                                                <td>
                                                    <strong class="text-success">$<?php echo number_format($meal['PRICE'], 2); ?></strong>
                                                </td>
                                                <td>
                                                    <small><?php echo htmlspecialchars($meal['admin_name']); ?></small>
                                                </td>
                                                <td>
                                                    <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editMealModal<?php echo $meal['ID_MEALS']; ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteMealModal<?php echo $meal['ID_MEALS']; ?>">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Categories Management Tab -->
                    <div class="tab-pane fade" id="categories" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Category</h5>
                                    </div>
                                    <div class="card-body">
                                        <form method="POST">
                                            <div class="mb-3">
                                                <label class="form-label">Category Name</label>
                                                <input type="text" name="category_name" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="category_description" class="form-control" rows="3"></textarea>
                                            </div>
                                            <button type="submit" name="add_category" class="btn btn-success">
                                                <i class="fas fa-plus me-1"></i>Add Category
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header">
                                        <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Existing Categories</h5>
                                    </div>
                                    <div class="card-body">
                                        <?php foreach ($category_stats as $cat): ?>
                                        <div class="category-card card mb-2">
                                            <div class="card-body py-2">
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <strong><?php echo htmlspecialchars($cat['NAME']); ?></strong>
                                                        <br><small class="text-muted"><?php echo $cat['meal_count']; ?> meals</small>
                                                    </div>
                                                    <div>
                                                        <?php if ($cat['meal_count'] == 0): ?>
                                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteCategoryModal<?php echo $cat['ID_CATEGORIES']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                        <?php else: ?>
                                                        <span class="badge bg-warning">Has meals</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Meal Tab -->
                    <div class="tab-pane fade" id="add-meal" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Add New Meal</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" enctype="multipart/form-data">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Meal Name</label>
                                                <input type="text" name="name" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Description</label>
                                                <textarea name="description" class="form-control" rows="4" required></textarea>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Price ($)</label>
                                                <input type="number" step="0.01" name="price" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Category</label>
                                                <select name="category" class="form-control" required>
                                                    <option value="">Select Category</option>
                                                    <?php foreach ($categories as $category): ?>
                                                        <option value="<?php echo $category['ID_CATEGORIES']; ?>">
                                                            <?php echo htmlspecialchars($category['NAME']); ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </select>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Meal Image</label>
                                                <input type="file" name="image" class="form-control" accept="image/*">
                                                <small class="text-muted">Max size: 5MB. Formats: JPG, PNG, GIF</small>
                                            </div>
                                            <div class="mb-3">
                                                <button type="submit" name="add_meal" class="btn btn-primary btn-lg w-100">
                                                    <i class="fas fa-plus me-1"></i>Add Meal
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    <?php include 'meal_modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

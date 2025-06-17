<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    try {
        if (isset($_POST['add_offer'])) {
            // Add new offer with meals
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $selected_meals = isset($_POST['selected_meals']) ? $_POST['selected_meals'] : [];

            if (empty($name) || empty($description) || $price <= 0) {
                $error = "Please fill all fields with valid data!";
            } elseif (empty($selected_meals)) {
                $error = "Please select at least one meal for this offer!";
            } else {
                // Start transaction
                $pdo->beginTransaction();

                // Insert the offer
                $stmt = $pdo->prepare("INSERT INTO ADMINS_OFFERS (ADMINS_OFFERS_NAME, ADMINS_OFFERS_DESCRIPTION, OFFERS_PRICE, created_date, status) VALUES (?, ?, ?, NOW(), 'active')");
                $stmt->execute([$name, $description, $price]);
                $offer_id = $pdo->lastInsertId();

                // Insert meal assignments with ID_CLIENTS = 0 (no client has ordered yet)
                $admin_id = $_SESSION['user_id'];
                $stmt = $pdo->prepare("INSERT INTO ADMIN_CUSTOMIZED_OFFERS (ID_ADMINS, ID_MEALS, ID_ADMINS_OFFERS, ID_CLIENTS, OFFERS_ADMIN_DATE) VALUES (?, ?, ?, 0, NOW())");

                foreach ($selected_meals as $meal_id) {
                    $stmt->execute([$admin_id, intval($meal_id), $offer_id]);
                }

                $pdo->commit();
                $success = "Offer created successfully with " . count($selected_meals) . " meals!";
            }
        } elseif (isset($_POST['update_offer'])) {
            // Update offer with validation
            $id = intval($_POST['id']);
            $name = trim($_POST['name']);
            $description = trim($_POST['description']);
            $price = floatval($_POST['price']);
            $status = $_POST['status'];

            if (empty($name) || empty($description) || $price <= 0) {
                $error = "Please fill all fields with valid data!";
            } else {
                $stmt = $pdo->prepare("UPDATE ADMINS_OFFERS SET ADMINS_OFFERS_NAME = ?, ADMINS_OFFERS_DESCRIPTION = ?, OFFERS_PRICE = ?, status = ? WHERE ID_ADMINS_OFFERS = ?");
                $stmt->execute([$name, $description, $price, $status, $id]);
                $success = "Offer updated successfully!";
            }
        } elseif (isset($_POST['remove_meal_from_offer'])) {
            // Remove specific meal from offer
            $meal_id = intval($_POST['meal_id']);
            $offer_id = intval($_POST['offer_id']);

            if ($meal_id > 0 && $offer_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM ADMIN_CUSTOMIZED_OFFERS WHERE ID_MEALS = ? AND ID_ADMINS_OFFERS = ?");
                $stmt->execute([$meal_id, $offer_id]);
                $success = "Meal removed from offer successfully!";
            } else {
                $error = "Invalid meal or offer ID!";
            }
        } elseif (isset($_POST['remove_assignment'])) {
            // Remove offer assignment
            $meal_id = intval($_POST['remove_meal_id']);
            $offer_id = intval($_POST['remove_offer_id']);

            if ($meal_id > 0 && $offer_id > 0) {
                $stmt = $pdo->prepare("DELETE FROM ADMIN_CUSTOMIZED_OFFERS WHERE ID_MEALS = ? AND ID_ADMINS_OFFERS = ? AND ID_ADMINS > 0");
                $stmt->execute([$meal_id, $offer_id]);
                $success = "Offer assignment removed successfully!";
            } else {
                $error = "Invalid assignment data!";
            }
        } elseif (isset($_POST['update_admin_offer_status'])) {
            // Update admin offer order status
            $client_id = intval($_POST['client_id']);
            $offer_id = intval($_POST['offer_id']);
            $status = trim($_POST['status']);

            if ($client_id > 0 && $offer_id > 0 && !empty($status)) {
                // Update status in database
                $stmt = $pdo->prepare("UPDATE ADMIN_CUSTOMIZED_OFFERS SET status = ? WHERE ID_CLIENTS = ? AND ID_ADMINS_OFFERS = ?");
                $stmt->execute([$status, $client_id, $offer_id]);
                $success = "Order status updated to '" . ucfirst($status) . "' successfully!";
            } else {
                $error = "Invalid order data!";
            }
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
    }
}

// Handle delete action
if (isset($_GET['delete'])) {
    try {
        $id = intval($_GET['delete']);
        if ($id > 0) {
            $stmt = $pdo->prepare("DELETE FROM ADMINS_OFFERS WHERE ID_ADMINS_OFFERS = ?");
            $stmt->execute([$id]);
            $success = "Offer deleted successfully!";
        } else {
            $error = "Invalid offer ID!";
        }
    } catch (PDOException $e) {
        $error = "Error deleting offer: " . $e->getMessage();
    }
}

// Get all offers
$offers = $pdo->query("SELECT * FROM ADMINS_OFFERS ORDER BY ID_ADMINS_OFFERS DESC")->fetchAll();

// Get all categories for the form
$categories = $pdo->query("SELECT * FROM CATEGORIES ORDER BY NAME")->fetchAll();

// Get all meals with category information
$meals = $pdo->query("
    SELECT m.*, c.NAME as category_name
    FROM MEALS m
    LEFT JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
    ORDER BY c.NAME, m.NAME
")->fetchAll();

// Get assigned meals for each offer
$offer_meals = [];
foreach ($offers as $offer) {
    $offer_meals[$offer['ID_ADMINS_OFFERS']] = $pdo->query("
        SELECT DISTINCT m.*, c.NAME as category_name
        FROM ADMIN_CUSTOMIZED_OFFERS aco
        JOIN MEALS m ON aco.ID_MEALS = m.ID_MEALS
        LEFT JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
        WHERE aco.ID_ADMINS_OFFERS = " . $offer['ID_ADMINS_OFFERS'] . "
        ORDER BY c.NAME, m.NAME
    ")->fetchAll();
}

// Get all admin offers orders (where clients have ordered)
try {
    $admin_offers_orders = $pdo->query("
        SELECT
            aco.ID_CLIENTS,
            aco.ID_ADMINS_OFFERS,
            aco.OFFERS_ADMIN_DATE as order_date,
            c.FULLNAME as client_name,
            c.PHONE_NUMBER as client_phone,
            c.EMAIL as client_email,
            ao.ADMINS_OFFERS_NAME as offer_name,
            ao.ADMINS_OFFERS_DESCRIPTION as offer_description,
            ao.OFFERS_PRICE as offer_price,
            SUM(m.PRICE) as total_meals_price,
            COALESCE(aco.status, 'pending') as status,
            GROUP_CONCAT(m.NAME SEPARATOR ', ') as meals_list
        FROM ADMIN_CUSTOMIZED_OFFERS aco
        JOIN CLIENTS c ON aco.ID_CLIENTS = c.ID_CLIENTS
        JOIN ADMINS_OFFERS ao ON aco.ID_ADMINS_OFFERS = ao.ID_ADMINS_OFFERS
        JOIN MEALS m ON aco.ID_MEALS = m.ID_MEALS
        WHERE aco.ID_CLIENTS > 0
        GROUP BY aco.ID_CLIENTS, aco.ID_ADMINS_OFFERS
        ORDER BY aco.OFFERS_ADMIN_DATE DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $admin_offers_orders = [];
}

include '../includes/header.php';
?>

<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2><i class="fas fa-tags me-2"></i>Admin Offers Management</h2>
            <p class="text-muted mb-0">Create and manage promotional offers for your restaurant</p>
        </div>
        <div>
            <a href="../promotions.php" class="btn btn-outline-primary" target="_blank">
                <i class="fas fa-eye me-2"></i>View Client Promotions Page
            </a>
        </div>
    </div>

    <!-- Alerts -->
    <?php if (isset($success)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?php echo $success; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    <?php if (isset($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-circle me-2"></i><?php echo $error; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>
    
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-plus me-2"></i>Create New Offer</h5>
                </div>
                <div class="card-body">
                    <form method="POST" id="offerForm">
                        <!-- Basic Offer Information -->
                        <div class="mb-3">
                            <label class="form-label">Offer Name *</label>
                            <input type="text" name="name" class="form-control" placeholder="e.g., Monday Special, Weekend Combo" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Description *</label>
                            <textarea name="description" class="form-control" rows="3" placeholder="Describe what makes this offer special..." required></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Offer Price ($) *</label>
                            <input type="number" step="0.01" min="0.01" name="price" class="form-control" placeholder="0.00" required>
                        </div>

                        <!-- Meal Selection Section -->
                        <hr>
                        <h6 class="mb-3"><i class="fas fa-utensils me-2"></i>Select Meals for This Offer</h6>

                        <div class="mb-3">
                            <label class="form-label">Choose Category</label>
                            <select id="categorySelect" class="form-select">
                                <option value="">Select a category to see meals...</option>
                                <?php foreach ($categories as $category): ?>
                                    <option value="<?php echo $category['ID_CATEGORIES']; ?>">
                                        <?php echo htmlspecialchars($category['NAME']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Available Meals</label>
                            <div id="mealsContainer" class="border rounded p-3" style="min-height: 100px; max-height: 300px; overflow-y: auto;">
                                <p class="text-muted text-center mb-0">Select a category above to see available meals</p>
                            </div>
                        </div>

                        <!-- Selected Meals Display -->
                        <div class="mb-3">
                            <label class="form-label">Selected Meals</label>
                            <div id="selectedMeals" class="border rounded p-3 bg-light" style="min-height: 80px;">
                                <p class="text-muted text-center mb-0">No meals selected yet</p>
                            </div>
                        </div>

                        <button type="submit" name="add_offer" class="btn btn-primary btn-lg w-100">
                            <i class="fas fa-plus me-2"></i>Create Offer with Selected Meals
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    All Offers
                </div>
                <div class="card-body">
                    <?php if (empty($offers)): ?>
                        <div class="text-center py-4">
                            <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No offers created yet</h5>
                            <p class="text-muted">Create your first admin offer using the form on the left.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Name</th>
                                        <th>Description</th>
                                        <th>Price</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($offers as $offer): ?>
                                    <tr>
                                        <td>
                                            <strong><?php echo htmlspecialchars($offer['ADMINS_OFFERS_NAME']); ?></strong>
                                        </td>
                                        <td>
                                            <small><?php echo htmlspecialchars(substr($offer['ADMINS_OFFERS_DESCRIPTION'], 0, 50)); ?>...</small>
                                        </td>
                                        <td>
                                            <span class="badge bg-success">$<?php echo number_format($offer['OFFERS_PRICE'], 2); ?></span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php echo (isset($offer['status']) && $offer['status'] == 'active') ? 'success' : 'secondary'; ?>">
                                                <?php echo isset($offer['status']) ? ucfirst($offer['status']) : 'Active'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editOfferModal<?php echo $offer['ID_ADMINS_OFFERS']; ?>">
                                                <i class="fas fa-edit"></i> Edit
                                            </button>
                                            <a href="?delete=<?php echo $offer['ID_ADMINS_OFFERS']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure you want to delete this offer?')">
                                                <i class="fas fa-trash"></i> Delete
                                            </a>
                                            <br><small class="text-muted mt-1">
                                                <?php echo count($offer_meals[$offer['ID_ADMINS_OFFERS']]); ?> meals assigned
                                            </small>
                                        </td>
                                    </tr>
                            
                            <!-- Enhanced Edit Modal -->
                            <div class="modal fade" id="editOfferModal<?php echo $offer['ID_ADMINS_OFFERS']; ?>" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-edit me-2"></i>Edit Offer: <?php echo htmlspecialchars($offer['ADMINS_OFFERS_NAME']); ?>
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="id" value="<?php echo $offer['ID_ADMINS_OFFERS']; ?>">

                                                <!-- Basic Offer Information -->
                                                <div class="row">
                                                    <div class="col-md-6">
                                                        <div class="mb-3">
                                                            <label class="form-label">Offer Name</label>
                                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($offer['ADMINS_OFFERS_NAME']); ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <label class="form-label">Price ($)</label>
                                                            <input type="number" step="0.01" min="0.01" name="price" class="form-control" value="<?php echo $offer['OFFERS_PRICE']; ?>" required>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-3">
                                                        <div class="mb-3">
                                                            <label class="form-label">Status</label>
                                                            <select name="status" class="form-select" required>
                                                                <option value="active" <?php echo (isset($offer['status']) && $offer['status'] == 'active') ? 'selected' : ''; ?>>Active</option>
                                                                <option value="inactive" <?php echo (isset($offer['status']) && $offer['status'] == 'inactive') ? 'selected' : ''; ?>>Inactive</option>
                                                            </select>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Description</label>
                                                    <textarea name="description" class="form-control" rows="3" required><?php echo htmlspecialchars($offer['ADMINS_OFFERS_DESCRIPTION']); ?></textarea>
                                                </div>

                                                <!-- Assigned Meals Section -->
                                                <hr>
                                                <h6 class="mb-3"><i class="fas fa-utensils me-2"></i>Assigned Meals (<?php echo count($offer_meals[$offer['ID_ADMINS_OFFERS']]); ?>)</h6>

                                                <?php if (empty($offer_meals[$offer['ID_ADMINS_OFFERS']])): ?>
                                                    <div class="alert alert-info">
                                                        <i class="fas fa-info-circle me-2"></i>No meals assigned to this offer yet.
                                                    </div>
                                                <?php else: ?>
                                                    <div class="row">
                                                        <?php foreach ($offer_meals[$offer['ID_ADMINS_OFFERS']] as $meal): ?>
                                                            <div class="col-md-6 mb-2">
                                                                <div class="card">
                                                                    <div class="card-body p-2">
                                                                        <div class="d-flex justify-content-between align-items-center">
                                                                            <div>
                                                                                <strong><?php echo htmlspecialchars($meal['NAME']); ?></strong><br>
                                                                                <small class="text-muted"><?php echo htmlspecialchars($meal['category_name']); ?></small><br>
                                                                                <span class="badge bg-success">$<?php echo number_format($meal['PRICE'], 2); ?></span>
                                                                            </div>
                                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                                    onclick="removeMealFromOffer(<?php echo $meal['ID_MEALS']; ?>, <?php echo $offer['ID_ADMINS_OFFERS']; ?>)">
                                                                                <i class="fas fa-times"></i>
                                                                            </button>
                                                                        </div>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                                                    <i class="fas fa-times me-2"></i>Close
                                                </button>
                                                <button type="submit" name="update_offer" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Save Changes
                                                </button>
                                            </div>
                                        </form>
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

    <!-- All Offers Orders Section -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-list-alt me-2"></i>All Offers Orders</h5>
                    <small class="text-muted">Manage orders from admin-created offers</small>
                </div>
                <div class="card-body">
                    <?php if (empty($admin_offers_orders)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-shopping-cart fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No orders yet</h5>
                            <p class="text-muted">Orders from admin offers will appear here when clients place them.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($admin_offers_orders as $order): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-left-primary">
                                        <div class="card-body">
                                            <!-- Customer Info -->
                                            <div class="d-flex justify-content-between align-items-start mb-3">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <i class="fas fa-user me-2"></i><?php echo htmlspecialchars($order['client_name']); ?>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-phone me-1"></i><?php echo htmlspecialchars($order['client_phone']); ?>
                                                    </small>
                                                </div>
                                                <span class="badge bg-<?php
                                                    echo $order['status'] == 'pending' ? 'warning' :
                                                        ($order['status'] == 'completed' ? 'success' :
                                                        ($order['status'] == 'cancelled' ? 'danger' : 'info'));
                                                ?>">
                                                    <?php echo ucfirst($order['status']); ?>
                                                </span>
                                            </div>

                                            <!-- Offer Details -->
                                            <div class="mb-3">
                                                <h6 class="text-primary mb-1"><?php echo htmlspecialchars($order['offer_name']); ?></h6>
                                                <p class="text-muted small mb-2"><?php echo htmlspecialchars($order['offer_description']); ?></p>
                                            </div>

                                            <!-- Selected Meals -->
                                            <div class="mb-3">
                                                <strong class="small">Selected Meals:</strong>
                                                <p class="text-muted small mb-0"><?php echo htmlspecialchars($order['meals_list']); ?></p>
                                            </div>

                                            <!-- Pricing -->
                                            <div class="row text-center mb-3">
                                                <div class="col-4">
                                                    <small class="text-muted">Meals Total</small>
                                                    <div class="fw-bold text-decoration-line-through">$<?php echo number_format($order['total_meals_price'], 2); ?></div>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Discount</small>
                                                    <div class="fw-bold text-success">
                                                        <?php
                                                        $discount_amount = $order['total_meals_price'] - $order['offer_price'];
                                                        $discount_percentage = $order['total_meals_price'] > 0 ? round(($discount_amount / $order['total_meals_price']) * 100) : 0;
                                                        echo $discount_percentage;
                                                        ?>%
                                                    </div>
                                                </div>
                                                <div class="col-4">
                                                    <small class="text-muted">Offer Price</small>
                                                    <div class="fw-bold text-primary">$<?php echo number_format($order['offer_price'], 2); ?></div>
                                                </div>
                                            </div>

                                            <!-- Order Date -->
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?php echo date('M d, Y H:i', strtotime($order['order_date'])); ?>
                                                </small>
                                            </div>

                                            <!-- Update Status Button -->
                                            <button class="btn btn-outline-primary btn-sm w-100"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#updateStatusModal<?php echo $order['ID_CLIENTS'] . '_' . $order['ID_ADMINS_OFFERS']; ?>">
                                                <i class="fas fa-edit me-2"></i>Update Status
                                            </button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Update Status Modal -->
                                <div class="modal fade" id="updateStatusModal<?php echo $order['ID_CLIENTS'] . '_' . $order['ID_ADMINS_OFFERS']; ?>" tabindex="-1">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title">Update Order Status</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                            </div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="update_admin_offer_status" value="1">
                                                    <input type="hidden" name="client_id" value="<?php echo $order['ID_CLIENTS']; ?>">
                                                    <input type="hidden" name="offer_id" value="<?php echo $order['ID_ADMINS_OFFERS']; ?>">

                                                    <div class="mb-3">
                                                        <label class="form-label">Customer: <?php echo htmlspecialchars($order['client_name']); ?></label>
                                                        <p class="text-muted small">Offer: <?php echo htmlspecialchars($order['offer_name']); ?></p>
                                                    </div>

                                                    <div class="mb-3">
                                                        <label class="form-label">Order Status</label>
                                                        <select name="status" class="form-select" required>
                                                            <option value="pending" <?php echo $order['status'] == 'pending' ? 'selected' : ''; ?>>Pending</option>
                                                            <option value="preparing" <?php echo $order['status'] == 'preparing' ? 'selected' : ''; ?>>Preparing</option>
                                                            <option value="ready" <?php echo $order['status'] == 'ready' ? 'selected' : ''; ?>>Ready</option>
                                                            <option value="completed" <?php echo $order['status'] == 'completed' ? 'selected' : ''; ?>>Completed</option>
                                                            <option value="cancelled" <?php echo $order['status'] == 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" class="btn btn-primary">Update Status</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Meals data from PHP
const mealsData = <?php echo json_encode($meals); ?>;
let selectedMeals = [];

// Handle category selection
document.getElementById('categorySelect').addEventListener('change', function() {
    const categoryId = this.value;
    const mealsContainer = document.getElementById('mealsContainer');

    if (!categoryId) {
        mealsContainer.innerHTML = '<p class="text-muted text-center mb-0">Select a category above to see available meals</p>';
        return;
    }

    // Filter meals by category
    const categoryMeals = mealsData.filter(meal => meal.ID_CATEGORIES == categoryId);

    if (categoryMeals.length === 0) {
        mealsContainer.innerHTML = '<p class="text-muted text-center mb-0">No meals found in this category</p>';
        return;
    }

    // Display meals as checkboxes
    let html = '<div class="row">';
    categoryMeals.forEach(meal => {
        const isSelected = selectedMeals.some(selected => selected.ID_MEALS == meal.ID_MEALS);
        html += `
            <div class="col-12 mb-2">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" value="${meal.ID_MEALS}"
                           id="meal_${meal.ID_MEALS}" ${isSelected ? 'checked' : ''}
                           onchange="toggleMeal(${meal.ID_MEALS}, '${meal.NAME.replace(/'/g, "\\'")}', ${meal.PRICE})">
                    <label class="form-check-label d-flex justify-content-between" for="meal_${meal.ID_MEALS}">
                        <span>${meal.NAME}</span>
                        <span class="badge bg-success">$${parseFloat(meal.PRICE).toFixed(2)}</span>
                    </label>
                </div>
            </div>
        `;
    });
    html += '</div>';

    mealsContainer.innerHTML = html;
});

// Toggle meal selection
function toggleMeal(mealId, mealName, mealPrice) {
    const index = selectedMeals.findIndex(meal => meal.ID_MEALS == mealId);

    if (index > -1) {
        // Remove meal
        selectedMeals.splice(index, 1);
    } else {
        // Add meal
        selectedMeals.push({
            ID_MEALS: mealId,
            NAME: mealName,
            PRICE: mealPrice
        });
    }

    updateSelectedMealsDisplay();
}

// Update selected meals display
function updateSelectedMealsDisplay() {
    const container = document.getElementById('selectedMeals');

    if (selectedMeals.length === 0) {
        container.innerHTML = '<p class="text-muted text-center mb-0">No meals selected yet</p>';
        return;
    }

    let html = '<div class="row">';
    selectedMeals.forEach(meal => {
        html += `
            <div class="col-md-6 mb-2">
                <div class="d-flex justify-content-between align-items-center bg-white p-2 rounded border">
                    <div>
                        <strong>${meal.NAME}</strong><br>
                        <small class="text-success">$${parseFloat(meal.PRICE).toFixed(2)}</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeMeal(${meal.ID_MEALS})">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <input type="hidden" name="selected_meals[]" value="${meal.ID_MEALS}">
            </div>
        `;
    });
    html += '</div>';

    container.innerHTML = html;
}

// Remove meal from selection
function removeMeal(mealId) {
    const index = selectedMeals.findIndex(meal => meal.ID_MEALS == mealId);
    if (index > -1) {
        selectedMeals.splice(index, 1);
        updateSelectedMealsDisplay();

        // Uncheck the checkbox if visible
        const checkbox = document.getElementById(`meal_${mealId}`);
        if (checkbox) {
            checkbox.checked = false;
        }
    }
}

function removeMealFromOffer(mealId, offerId) {
    if (confirm('Are you sure you want to remove this meal from the offer?')) {
        // Create a form to submit the removal request
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '';

        const mealInput = document.createElement('input');
        mealInput.type = 'hidden';
        mealInput.name = 'meal_id';
        mealInput.value = mealId;

        const offerInput = document.createElement('input');
        offerInput.type = 'hidden';
        offerInput.name = 'offer_id';
        offerInput.value = offerId;

        const actionInput = document.createElement('input');
        actionInput.type = 'hidden';
        actionInput.name = 'remove_meal_from_offer';
        actionInput.value = '1';

        form.appendChild(mealInput);
        form.appendChild(offerInput);
        form.appendChild(actionInput);

        document.body.appendChild(form);
        form.submit();
    }
}

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

<?php include '../includes/footer.php'; ?>
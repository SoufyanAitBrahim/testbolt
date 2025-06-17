<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        // Handle status updates
        $offer_id = intval($_POST['offer_id']);
        $new_status = $_POST['status'];

        if (in_array($new_status, ['pending', 'approved', 'rejected', 'completed'])) {
            try {
                $stmt = $pdo->prepare("UPDATE CLIENTS_OFFERS SET status = ? WHERE ID_CLIENTS_OFFERS = ?");
                $stmt->execute([$new_status, $offer_id]);
                $success = "Offer status updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating status: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['add_discount_rule'])) {
        // Handle adding discount rules to DISCOUNT_RULES table
        $min_price = floatval($_POST['min_price']);
        $discount_percentage = intval($_POST['discount_percentage']);
        $rule_name = trim($_POST['rule_name']);
        $description = trim($_POST['description']);

        if ($min_price > 0 && $discount_percentage > 0 && $discount_percentage <= 100) {
            try {
                // Check if rule already exists
                $stmt = $pdo->prepare("SELECT * FROM DISCOUNT_RULES WHERE min_price = ?");
                $stmt->execute([$min_price]);

                if ($stmt->rowCount() == 0) {
                    $stmt = $pdo->prepare("INSERT INTO DISCOUNT_RULES (min_price, discount_percentage, rule_name, description, status) VALUES (?, ?, ?, ?, 'active')");
                    $stmt->execute([$min_price, $discount_percentage, $rule_name, $description]);
                    $success = "Discount rule added successfully!";
                } else {
                    $error = "A discount rule for this price already exists!";
                }
            } catch (PDOException $e) {
                $error = "Error adding discount rule: " . $e->getMessage();
            }
        } else {
            $error = "Please enter valid price and discount percentage (1-100)!";
        }
    } elseif (isset($_POST['update_discount_rule'])) {
        // Handle updating discount rules
        $rule_id = intval($_POST['rule_id']);
        $min_price = floatval($_POST['min_price']);
        $discount_percentage = intval($_POST['discount_percentage']);
        $rule_name = trim($_POST['rule_name']);
        $description = trim($_POST['description']);
        $status = $_POST['status'];

        if ($min_price > 0 && $discount_percentage > 0 && $discount_percentage <= 100) {
            try {
                $stmt = $pdo->prepare("UPDATE DISCOUNT_RULES SET min_price = ?, discount_percentage = ?, rule_name = ?, description = ?, status = ? WHERE id = ?");
                $stmt->execute([$min_price, $discount_percentage, $rule_name, $description, $status, $rule_id]);
                $success = "Discount rule updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating discount rule: " . $e->getMessage();
            }
        } else {
            $error = "Please enter valid price and discount percentage (1-100)!";
        }
    } elseif (isset($_POST['delete_discount_rule'])) {
        // Handle deleting discount rules
        $rule_id = intval($_POST['rule_id']);

        try {
            $stmt = $pdo->prepare("DELETE FROM DISCOUNT_RULES WHERE id = ?");
            $stmt->execute([$rule_id]);
            $success = "Discount rule deleted successfully!";
        } catch (PDOException $e) {
            $error = "Error deleting discount rule: " . $e->getMessage();
        }
    }
}

// Create DISCOUNT_RULES table if it doesn't exist
try {
    $pdo->exec("CREATE TABLE IF NOT EXISTS DISCOUNT_RULES (
        id INT AUTO_INCREMENT PRIMARY KEY,
        min_price DECIMAL(10,2) NOT NULL,
        discount_percentage INT NOT NULL,
        rule_name VARCHAR(255) DEFAULT NULL,
        description TEXT DEFAULT NULL,
        created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
        status ENUM('active', 'inactive') DEFAULT 'active',
        UNIQUE KEY unique_price (min_price)
    ) ENGINE=InnoDB");
} catch (PDOException $e) {
    // Table creation failed, but continue
}

// Get all custom offers with meal details
try {
    $offers = $pdo->query("
        SELECT
            co.*,
            COUNT(cco.ID_MEALS) as meal_count,
            GROUP_CONCAT(m.NAME SEPARATOR ', ') as meal_names
        FROM CLIENTS_OFFERS co
        LEFT JOIN CLIENT_CUSTOMIZED_OFFERS cco ON co.ID_CLIENTS_OFFERS = cco.ID_CLIENTS_OFFERS
        LEFT JOIN MEALS m ON cco.ID_MEALS = m.ID_MEALS
        GROUP BY co.ID_CLIENTS_OFFERS
        ORDER BY co.created_date DESC
    ")->fetchAll();

    // Get statistics
    $total_offers = count($offers);
    $pending_offers = count(array_filter($offers, function($o) { return $o['status'] == 'pending'; }));
    $approved_offers = count(array_filter($offers, function($o) { return $o['status'] == 'approved'; }));
    $total_revenue = array_sum(array_map(function($o) use ($pdo) {
        if ($o['status'] == 'completed') {
            include_once '../includes/discount_functions.php';
            $discount_info = calculateDiscount($o['total_price'], $pdo);
            return $discount_info['final_price'];
        }
        return 0;
    }, $offers));

} catch (PDOException $e) {
    $offers = [];
    $total_offers = $pending_offers = $approved_offers = $total_revenue = 0;
}

// Get discount rules from DISCOUNT_RULES table
try {
    $discount_rules = $pdo->query("SELECT * FROM DISCOUNT_RULES ORDER BY min_price ASC")->fetchAll();
} catch (PDOException $e) {
    $discount_rules = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Custom Offers Management - Sushi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .navbar { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .card { border: none; border-radius: 15px; box-shadow: 0 0 20px rgba(0,0,0,0.1); }
        .card-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border-radius: 15px 15px 0 0 !important; }
        .stats-card { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; }
        .offer-card { border-left: 4px solid #007bff; }
        .offer-card.pending { border-left-color: #ffc107; }
        .offer-card.approved { border-left-color: #28a745; }
        .offer-card.rejected { border-left-color: #dc3545; }
        .offer-card.completed { border-left-color: #6f42c1; }
        .status-badge.pending { background: #ffc107; }
        .status-badge.approved { background: #28a745; }
        .status-badge.rejected { background: #dc3545; }
        .status-badge.completed { background: #6f42c1; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard_enhanced.php">
                <i class="fas fa-fish me-2"></i>Sushi Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard_enhanced.php">
                    <i class="fas fa-arrow-left me-1"></i>Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Page Header -->
        <div class="row mb-4">
            <div class="col-12">
                <h2><i class="fas fa-gift me-2"></i>Custom Offers Management</h2>
                <p class="text-muted">Manage client custom offers and track performance</p>
            </div>
        </div>

        <!-- Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-gift fa-2x mb-2"></i>
                        <h4><?php echo $total_offers; ?></h4>
                        <p class="mb-0">Total Offers</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h4><?php echo $pending_offers; ?></h4>
                        <p class="mb-0">Pending Review</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h4><?php echo $approved_offers; ?></h4>
                        <p class="mb-0">Approved</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card stats-card">
                    <div class="card-body text-center">
                        <i class="fas fa-dollar-sign fa-2x mb-2"></i>
                        <h4>$<?php echo number_format($total_revenue, 2); ?></h4>
                        <p class="mb-0">Revenue</p>
                    </div>
                </div>
            </div>
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

        <!-- Discount Rules Section -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-percentage me-2"></i>Discount Rules Management</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <!-- Add New Rule Form -->
                    <div class="col-md-4">
                        <h6><i class="fas fa-plus me-2"></i>Add New Discount Rule</h6>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label">Rule Name</label>
                                <input type="text" name="rule_name" class="form-control" placeholder="e.g., Premium Discount" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Minimum Price ($)</label>
                                <input type="number" step="0.01" min="0.01" name="min_price" class="form-control" placeholder="e.g., 50.00" required>
                                <small class="text-muted">If total price is ≥ this amount</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Discount Percentage (%)</label>
                                <input type="number" min="1" max="100" name="discount_percentage" class="form-control" placeholder="e.g., 25" required>
                                <small class="text-muted">Discount to apply (1-100%)</small>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Optional description..."></textarea>
                            </div>
                            <button type="submit" name="add_discount_rule" class="btn btn-primary w-100">
                                <i class="fas fa-plus me-2"></i>Add Rule
                            </button>
                        </form>
                    </div>

                    <!-- Current Rules -->
                    <div class="col-md-8">
                        <h6><i class="fas fa-list me-2"></i>Current Discount Rules</h6>
                        <?php if (empty($discount_rules)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>No discount rules set yet. Add your first rule to get started!
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-dark">
                                        <tr>
                                            <th>Rule Name</th>
                                            <th>Min Price</th>
                                            <th>Discount</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($discount_rules as $rule): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($rule['rule_name'] ?: 'Unnamed Rule'); ?></strong>
                                                <?php if ($rule['description']): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($rule['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <strong>$<?php echo number_format($rule['min_price'], 2); ?></strong>
                                            </td>
                                            <td>
                                                <span class="badge bg-success"><?php echo $rule['discount_percentage']; ?>%</span>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php echo $rule['status'] == 'active' ? 'success' : 'secondary'; ?>">
                                                    <?php echo ucfirst($rule['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editRuleModal<?php echo $rule['id']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <form method="POST" style="display: inline;">
                                                    <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                                    <button type="submit" name="delete_discount_rule" class="btn btn-sm btn-outline-danger" onclick="return confirm('Are you sure?')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                            </td>
                                        </tr>

                                        <!-- Edit Rule Modal -->
                                        <div class="modal fade" id="editRuleModal<?php echo $rule['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">
                                                            <i class="fas fa-edit me-2"></i>Edit Discount Rule
                                                        </h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <form method="POST">
                                                        <div class="modal-body">
                                                            <input type="hidden" name="rule_id" value="<?php echo $rule['id']; ?>">
                                                            <div class="mb-3">
                                                                <label class="form-label">Rule Name</label>
                                                                <input type="text" name="rule_name" class="form-control" value="<?php echo htmlspecialchars($rule['rule_name']); ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Minimum Price ($)</label>
                                                                <input type="number" step="0.01" min="0.01" name="min_price" class="form-control" value="<?php echo $rule['min_price']; ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Discount Percentage (%)</label>
                                                                <input type="number" min="1" max="100" name="discount_percentage" class="form-control" value="<?php echo $rule['discount_percentage']; ?>" required>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Description</label>
                                                                <textarea name="description" class="form-control" rows="2"><?php echo htmlspecialchars($rule['description']); ?></textarea>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Status</label>
                                                                <select name="status" class="form-select" required>
                                                                    <option value="active" <?php echo $rule['status'] == 'active' ? 'selected' : ''; ?>>Active</option>
                                                                    <option value="inactive" <?php echo $rule['status'] == 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                                                </select>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" name="update_discount_rule" class="btn btn-primary">
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

                        <!-- Rules Preview -->
                        <?php if (!empty($discount_rules)): ?>
                            <div class="mt-3">
                                <h6><i class="fas fa-eye me-2"></i>Active Discount Rules:</h6>
                                <div class="alert alert-light">
                                    <small>
                                        <?php
                                        $active_rules = array_filter($discount_rules, function($r) { return $r['status'] == 'active'; });
                                        if (empty($active_rules)): ?>
                                            <em>No active discount rules. Activate rules to apply discounts to client offers.</em>
                                        <?php else: ?>
                                            <?php foreach ($active_rules as $rule): ?>
                                                • <strong><?php echo htmlspecialchars($rule['rule_name']); ?>:</strong>
                                                If total ≥ <strong>$<?php echo number_format($rule['min_price'], 2); ?></strong> → <strong><?php echo $rule['discount_percentage']; ?>% discount</strong><br>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Offers List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="fas fa-list me-2"></i>All Custom Offers</h5>
            </div>
            <div class="card-body">
                <?php if (empty($offers)): ?>
                    <div class="text-center py-5">
                        <i class="fas fa-gift fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">No Custom Offers Yet</h5>
                        <p class="text-muted">Custom offers will appear here when clients create them.</p>
                        <a href="../custom_offers.php" class="btn btn-primary" target="_blank">
                            <i class="fas fa-external-link-alt me-2"></i>View Client Interface
                        </a>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($offers as $offer): ?>
                            <div class="col-lg-6 mb-4">
                                <div class="card offer-card <?php echo $offer['status']; ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div>
                                                <h6 class="mb-1">
                                                    <i class="fas fa-user me-2"></i>
                                                    <?php echo htmlspecialchars($offer['client_name']); ?>
                                                </h6>
                                                <small class="text-muted">
                                                    <i class="fas fa-phone me-1"></i>
                                                    <?php echo htmlspecialchars($offer['client_phone']); ?>
                                                </small>
                                            </div>
                                            <span class="badge status-badge <?php echo $offer['status']; ?> text-white">
                                                <?php echo ucfirst($offer['status']); ?>
                                            </span>
                                        </div>

                                        <div class="mb-3">
                                            <strong>Selected Meals (<?php echo $offer['meal_count']; ?>):</strong>
                                            <p class="small text-muted mb-0">
                                                <?php echo htmlspecialchars($offer['meal_names'] ?: 'No meals found'); ?>
                                            </p>
                                        </div>

                                        <div class="row text-center mb-3">
                                            <div class="col-4">
                                                <small class="text-muted">Original Price</small>
                                                <div class="fw-bold">$<?php echo number_format($offer['total_price'], 2); ?></div>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted">Discount</small>
                                                <div class="fw-bold text-success">
                                                    <?php
                                                    // Calculate discount using function
                                                    include_once '../includes/discount_functions.php';
                                                    $discount_info = calculateDiscount($offer['total_price'], $pdo);
                                                    echo $discount_info['discount_percentage'];
                                                    ?>%
                                                </div>
                                            </div>
                                            <div class="col-4">
                                                <small class="text-muted">Final Price</small>
                                                <div class="fw-bold text-primary">$<?php echo number_format($discount_info['final_price'], 2); ?></div>
                                            </div>
                                        </div>

                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?php echo date('M d, Y H:i', strtotime($offer['created_date'])); ?>
                                            </small>
                                            <div>
                                                <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $offer['ID_CLIENTS_OFFERS']; ?>">
                                                    <i class="fas fa-edit me-1"></i>Update Status
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Status Update Modal -->
                            <div class="modal fade" id="statusModal<?php echo $offer['ID_CLIENTS_OFFERS']; ?>" tabindex="-1">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">
                                                <i class="fas fa-edit me-2"></i>Update Offer Status
                                            </h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <form method="POST">
                                            <div class="modal-body">
                                                <input type="hidden" name="offer_id" value="<?php echo $offer['ID_CLIENTS_OFFERS']; ?>">
                                                
                                                <div class="mb-3">
                                                    <label class="form-label">Customer:</label>
                                                    <p class="fw-bold"><?php echo htmlspecialchars($offer['client_name']); ?></p>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">Current Status:</label>
                                                    <span class="badge status-badge <?php echo $offer['status']; ?> text-white">
                                                        <?php echo ucfirst($offer['status']); ?>
                                                    </span>
                                                </div>

                                                <div class="mb-3">
                                                    <label class="form-label">New Status:</label>
                                                    <select class="form-select" name="status" required>
                                                        <option value="pending" <?php echo $offer['status'] == 'pending' ? 'selected' : ''; ?>>
                                                            Pending Review
                                                        </option>
                                                        <option value="approved" <?php echo $offer['status'] == 'approved' ? 'selected' : ''; ?>>
                                                            Approved
                                                        </option>
                                                        <option value="rejected" <?php echo $offer['status'] == 'rejected' ? 'selected' : ''; ?>>
                                                            Rejected
                                                        </option>
                                                        <option value="completed" <?php echo $offer['status'] == 'completed' ? 'selected' : ''; ?>>
                                                            Completed
                                                        </option>
                                                    </select>
                                                </div>

                                                <div class="alert alert-info">
                                                    <i class="fas fa-info-circle me-2"></i>
                                                    <strong>Status Guide:</strong><br>
                                                    <small>
                                                        • <strong>Pending:</strong> Awaiting review<br>
                                                        • <strong>Approved:</strong> Offer accepted, ready for preparation<br>
                                                        • <strong>Rejected:</strong> Offer declined<br>
                                                        • <strong>Completed:</strong> Order fulfilled and paid
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                <button type="submit" name="update_status" class="btn btn-primary">
                                                    <i class="fas fa-save me-2"></i>Update Status
                                                </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
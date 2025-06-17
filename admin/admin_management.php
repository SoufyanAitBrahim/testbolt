<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotSuperAdmin(); // Only super admins can access this

$success = '';
$error = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['add_admin'])) {
        // Add new admin
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $password = trim($_POST['password']);
        $role = intval($_POST['role']);
        
        if (!empty($fullname) && !empty($email) && !empty($password) && in_array($role, [1, 2])) {
            // Check if email already exists
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM ADMINS WHERE EMAIL = ?");
            $stmt->execute([$email]);
            
            if ($stmt->fetchColumn() > 0) {
                $error = "Email already exists!";
            } else {
                try {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO ADMINS (FULLNAME, EMAIL, PASSWORD, ROLE, ADDED_DATE) VALUES (?, ?, ?, ?, ?)");
                    $stmt->execute([$fullname, $email, $hashed_password, $role, date('Y-m-d')]);
                    $success = "Admin added successfully!";
                } catch (PDOException $e) {
                    $error = "Error adding admin: " . $e->getMessage();
                }
            }
        } else {
            $error = "All fields are required and role must be valid!";
        }
    }
    
    elseif (isset($_POST['update_admin'])) {
        // Update admin
        $id = intval($_POST['id']);
        $fullname = trim($_POST['fullname']);
        $email = trim($_POST['email']);
        $role = intval($_POST['role']);
        
        // Don't allow changing own role
        if ($id == $_SESSION['user_id']) {
            $error = "You cannot change your own role!";
        } elseif (!empty($fullname) && !empty($email) && in_array($role, [1, 2])) {
            try {
                $stmt = $pdo->prepare("UPDATE ADMINS SET FULLNAME = ?, EMAIL = ?, ROLE = ? WHERE ID_ADMINS = ?");
                $stmt->execute([$fullname, $email, $role, $id]);
                $success = "Admin updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating admin: " . $e->getMessage();
            }
        } else {
            $error = "All fields are required and role must be valid!";
        }
    }
    
    elseif (isset($_POST['delete_admin'])) {
        // Delete admin
        $id = intval($_POST['admin_id']);
        
        // Don't allow deleting own account
        if ($id == $_SESSION['user_id']) {
            $error = "You cannot delete your own account!";
        } else {
            try {
                $stmt = $pdo->prepare("DELETE FROM ADMINS WHERE ID_ADMINS = ?");
                $stmt->execute([$id]);
                $success = "Admin deleted successfully!";
            } catch (PDOException $e) {
                $error = "Error deleting admin: " . $e->getMessage();
            }
        }
    }
    
    elseif (isset($_POST['reset_password'])) {
        // Reset admin password
        $id = intval($_POST['admin_id']);
        $new_password = trim($_POST['new_password']);
        
        if (!empty($new_password) && strlen($new_password) >= 6) {
            try {
                $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE ADMINS SET PASSWORD = ? WHERE ID_ADMINS = ?");
                $stmt->execute([$hashed_password, $id]);
                $success = "Password reset successfully!";
            } catch (PDOException $e) {
                $error = "Error resetting password: " . $e->getMessage();
            }
        } else {
            $error = "Password must be at least 6 characters long!";
        }
    }
}

// Get all admins
$admins = $pdo->query("
    SELECT *, 
           CASE 
               WHEN ROLE = 1 THEN 'Super Admin'
               WHEN ROLE = 2 THEN 'Secondary Admin'
               ELSE 'Unknown'
           END as role_name
    FROM ADMINS 
    ORDER BY ROLE, FULLNAME
")->fetchAll();

// Get admin statistics
$total_admins = count($admins);
$super_admins = count(array_filter($admins, function($admin) { return $admin['ROLE'] == 1; }));
$secondary_admins = count(array_filter($admins, function($admin) { return $admin['ROLE'] == 2; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Management - Sushi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .admin-card {
            transition: transform 0.2s;
        }
        .admin-card:hover {
            transform: translateY(-2px);
        }
        .role-badge-super {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .role-badge-secondary {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
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
                <a class="nav-link" href="meals_enhanced.php">
                    <i class="fas fa-fish me-1"></i>Meals
                </a>
                <a class="nav-link active" href="admin_management.php">
                    <i class="fas fa-users-cog me-1"></i>Admins
                </a>
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
                    <h2><i class="fas fa-users-cog me-2"></i>Admin Management</h2>
                    <div>
                        <span class="badge bg-danger fs-6">Super Admin Only</span>
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
            <div class="col-md-4">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-users fa-2x mb-2"></i>
                        <h3><?php echo $total_admins; ?></h3>
                        <p class="mb-0">Total Admins</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-crown fa-2x mb-2"></i>
                        <h3><?php echo $super_admins; ?></h3>
                        <p class="mb-0">Super Admins</p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-info text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tie fa-2x mb-2"></i>
                        <h3><?php echo $secondary_admins; ?></h3>
                        <p class="mb-0">Secondary Admins</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Tabs -->
        <div class="row">
            <div class="col-12">
                <ul class="nav nav-tabs" id="adminTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="admins-tab" data-bs-toggle="tab" data-bs-target="#admins" type="button" role="tab">
                            <i class="fas fa-users me-1"></i>All Admins
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="add-admin-tab" data-bs-toggle="tab" data-bs-target="#add-admin" type="button" role="tab">
                            <i class="fas fa-user-plus me-1"></i>Add New Admin
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="permissions-tab" data-bs-toggle="tab" data-bs-target="#permissions" type="button" role="tab">
                            <i class="fas fa-shield-alt me-1"></i>Role Permissions
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="adminTabsContent">
                    <!-- All Admins Tab -->
                    <div class="tab-pane fade show active" id="admins" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-users me-2"></i>All Administrators</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead class="table-dark">
                                            <tr>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Added Date</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($admins as $admin): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($admin['FULLNAME']); ?></strong>
                                                    <?php if ($admin['ID_ADMINS'] == $_SESSION['user_id']): ?>
                                                        <span class="badge bg-warning ms-2">You</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($admin['EMAIL']); ?></td>
                                                <td>
                                                    <?php if ($admin['ROLE'] == 1): ?>
                                                        <span class="badge role-badge-super text-white">
                                                            <i class="fas fa-crown me-1"></i>Super Admin
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge role-badge-secondary text-white">
                                                            <i class="fas fa-user-tie me-1"></i>Secondary Admin
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo date('M d, Y', strtotime($admin['ADDED_DATE'])); ?></td>
                                                <td>
                                                    <?php if ($admin['ID_ADMINS'] != $_SESSION['user_id']): ?>
                                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editAdminModal<?php echo $admin['ID_ADMINS']; ?>">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#resetPasswordModal<?php echo $admin['ID_ADMINS']; ?>">
                                                            <i class="fas fa-key"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteAdminModal<?php echo $admin['ID_ADMINS']; ?>">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    <?php else: ?>
                                                        <span class="text-muted">Current User</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Add Admin Tab -->
                    <div class="tab-pane fade" id="add-admin" role="tabpanel">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user-plus me-2"></i>Add New Administrator</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Full Name</label>
                                                <input type="text" name="fullname" class="form-control" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Email Address</label>
                                                <input type="email" name="email" class="form-control" required>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <div class="mb-3">
                                                <label class="form-label">Password</label>
                                                <input type="password" name="password" class="form-control" required minlength="6">
                                                <small class="text-muted">Minimum 6 characters</small>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label">Admin Role</label>
                                                <select name="role" class="form-control" required>
                                                    <option value="">Select Role</option>
                                                    <option value="1">Super Admin (Full Access)</option>
                                                    <option value="2">Secondary Admin (Limited Access)</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="row">
                                        <div class="col-12">
                                            <div class="alert alert-info">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>Role Information:</strong>
                                                <ul class="mb-0 mt-2">
                                                    <li><strong>Super Admin:</strong> Can manage meals, categories, other admins, and view all data</li>
                                                    <li><strong>Secondary Admin:</strong> Can only view orders, reservations, and statistics</li>
                                                </ul>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="submit" name="add_admin" class="btn btn-success btn-lg">
                                        <i class="fas fa-user-plus me-1"></i>Add Administrator
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Permissions Tab -->
                    <div class="tab-pane fade" id="permissions" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-success text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-crown me-2"></i>Super Admin (Role 1)
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="text-success">Full System Access</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-check text-success me-2"></i>Manage Meals & Categories</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Add/Edit/Delete Other Admins</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Manage Special Offers</li>
                                            <li><i class="fas fa-check text-success me-2"></i>View All Orders & Reservations</li>
                                            <li><i class="fas fa-check text-success me-2"></i>Access All Statistics</li>
                                            <li><i class="fas fa-check text-success me-2"></i>System Configuration</li>
                                            <li><i class="fas fa-check text-success me-2"></i>User Management</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card">
                                    <div class="card-header bg-info text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-user-tie me-2"></i>Secondary Admin (Role 2)
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <h6 class="text-info">Limited Access</h6>
                                        <ul class="list-unstyled">
                                            <li><i class="fas fa-times text-danger me-2"></i>Cannot Manage Meals</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>Cannot Manage Admins</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>Cannot Manage Offers</li>
                                            <li><i class="fas fa-check text-success me-2"></i>View Orders & Update Status</li>
                                            <li><i class="fas fa-check text-success me-2"></i>View Reservations & Bookings</li>
                                            <li><i class="fas fa-check text-success me-2"></i>View Basic Statistics</li>
                                            <li><i class="fas fa-times text-danger me-2"></i>No System Configuration</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Important Notes:</strong>
                                    <ul class="mb-0 mt-2">
                                        <li>Only Super Admins can access this admin management page</li>
                                        <li>You cannot change your own role or delete your own account</li>
                                        <li>Role changes take effect immediately upon saving</li>
                                        <li>Secondary admins will be redirected if they try to access restricted areas</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    <?php include 'admin_modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

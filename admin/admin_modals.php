<?php
// This file contains all the modals for admin management
// Include this file in admin_management.php
?>

<!-- Edit Admin Modals -->
<?php foreach ($admins as $admin): ?>
<?php if ($admin['ID_ADMINS'] != $_SESSION['user_id']): ?>
<div class="modal fade" id="editAdminModal<?php echo $admin['ID_ADMINS']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Admin: <?php echo htmlspecialchars($admin['FULLNAME']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $admin['ID_ADMINS']; ?>">
                    <div class="mb-3">
                        <label class="form-label">Full Name</label>
                        <input type="text" name="fullname" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['FULLNAME']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email Address</label>
                        <input type="email" name="email" class="form-control" 
                               value="<?php echo htmlspecialchars($admin['EMAIL']); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Admin Role</label>
                        <select name="role" class="form-control" required>
                            <option value="1" <?php echo $admin['ROLE'] == 1 ? 'selected' : ''; ?>>
                                Super Admin (Full Access)
                            </option>
                            <option value="2" <?php echo $admin['ROLE'] == 2 ? 'selected' : ''; ?>>
                                Secondary Admin (Limited Access)
                            </option>
                        </select>
                    </div>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <small>Password will remain unchanged. Use "Reset Password" to change it.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_admin" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<!-- Reset Password Modals -->
<?php foreach ($admins as $admin): ?>
<?php if ($admin['ID_ADMINS'] != $_SESSION['user_id']): ?>
<div class="modal fade" id="resetPasswordModal<?php echo $admin['ID_ADMINS']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-warning">
                    <i class="fas fa-key me-2"></i>Reset Password
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="admin_id" value="<?php echo $admin['ID_ADMINS']; ?>">
                    <div class="text-center mb-3">
                        <i class="fas fa-user-circle fa-3x text-muted"></i>
                        <h6 class="mt-2"><?php echo htmlspecialchars($admin['FULLNAME']); ?></h6>
                        <p class="text-muted"><?php echo htmlspecialchars($admin['EMAIL']); ?></p>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">New Password</label>
                        <input type="password" name="new_password" class="form-control" required minlength="6">
                        <small class="text-muted">Minimum 6 characters</small>
                    </div>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> The admin will need to use this new password to login.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="reset_password" class="btn btn-warning">
                        <i class="fas fa-key me-1"></i>Reset Password
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<!-- Delete Admin Modals -->
<?php foreach ($admins as $admin): ?>
<?php if ($admin['ID_ADMINS'] != $_SESSION['user_id']): ?>
<div class="modal fade" id="deleteAdminModal<?php echo $admin['ID_ADMINS']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Administrator
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                    <h6><?php echo htmlspecialchars($admin['FULLNAME']); ?></h6>
                    <p class="text-muted"><?php echo htmlspecialchars($admin['EMAIL']); ?></p>
                    <span class="badge <?php echo $admin['ROLE'] == 1 ? 'role-badge-super' : 'role-badge-secondary'; ?> text-white">
                        <?php echo $admin['role_name']; ?>
                    </span>
                </div>
                <div class="alert alert-danger mt-3">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. The administrator will be permanently deleted and will lose access to the system.
                </div>
                <p class="text-center">Are you sure you want to delete this administrator?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="admin_id" value="<?php echo $admin['ID_ADMINS']; ?>">
                    <button type="submit" name="delete_admin" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Administrator
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

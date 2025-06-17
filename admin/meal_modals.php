<?php
// This file contains all the modals for meal management
// Include this file in meals_enhanced.php
?>

<!-- Edit Meal Modals -->
<?php foreach ($meals as $meal): ?>
<div class="modal fade" id="editMealModal<?php echo $meal['ID_MEALS']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Edit Meal: <?php echo htmlspecialchars($meal['NAME']); ?>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="id" value="<?php echo $meal['ID_MEALS']; ?>">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Meal Name</label>
                                <input type="text" name="name" class="form-control" 
                                       value="<?php echo htmlspecialchars($meal['NAME']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Description</label>
                                <textarea name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($meal['DESCRIPTION']); ?></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label">Price ($)</label>
                                <input type="number" step="0.01" name="price" class="form-control" 
                                       value="<?php echo $meal['PRICE']; ?>" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Category</label>
                                <select name="category" class="form-control" required>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['ID_CATEGORIES']; ?>" 
                                                <?php echo $category['ID_CATEGORIES'] == $meal['ID_CATEGORIES'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['NAME']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label class="form-label">Current Image</label>
                                <div>
                                    <img src="../<?php echo htmlspecialchars($meal['IMAGE_URL']); ?>" 
                                         alt="Current meal image" 
                                         style="max-width: 200px; max-height: 150px; object-fit: cover;"
                                         onerror="this.src='../assets/images/meals/default-meal.jpg'">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_meal" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Save Changes
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Delete Meal Modals -->
<?php foreach ($meals as $meal): ?>
<div class="modal fade" id="deleteMealModal<?php echo $meal['ID_MEALS']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Meal
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <img src="../<?php echo htmlspecialchars($meal['IMAGE_URL']); ?>" 
                         alt="<?php echo htmlspecialchars($meal['NAME']); ?>" 
                         style="max-width: 150px; max-height: 100px; object-fit: cover; border-radius: 8px;"
                         onerror="this.src='../assets/images/meals/default-meal.jpg'">
                    <h6 class="mt-3"><?php echo htmlspecialchars($meal['NAME']); ?></h6>
                    <p class="text-muted"><?php echo htmlspecialchars($meal['category_name']); ?> - $<?php echo number_format($meal['PRICE'], 2); ?></p>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. The meal will be permanently deleted from the system.
                </div>
                <p class="text-center">Are you sure you want to delete this meal?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="meal_id" value="<?php echo $meal['ID_MEALS']; ?>">
                    <button type="submit" name="delete_meal" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Meal
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Delete Category Modals -->
<?php foreach ($category_stats as $cat): ?>
<?php if ($cat['meal_count'] == 0): ?>
<div class="modal fade" id="deleteCategoryModal<?php echo $cat['ID_CATEGORIES']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>Delete Category
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="fas fa-tags fa-3x text-muted mb-3"></i>
                    <h6><?php echo htmlspecialchars($cat['NAME']); ?></h6>
                    <p class="text-muted">No meals in this category</p>
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    <strong>Warning:</strong> This action cannot be undone. The category will be permanently deleted.
                </div>
                <p class="text-center">Are you sure you want to delete this category?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="category_id" value="<?php echo $cat['ID_CATEGORIES']; ?>">
                    <button type="submit" name="delete_category" class="btn btn-danger">
                        <i class="fas fa-trash me-1"></i>Delete Category
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>
<?php endforeach; ?>

<?php
include 'includes/config.php';
include 'includes/discount_functions.php';

// Load discount rules from database
try {
    $discount_rules_query = $pdo->query("
        SELECT min_price as min_amount, discount_percentage as discount, rule_name as description
        FROM DISCOUNT_RULES
        WHERE status = 'active'
        ORDER BY min_price DESC
    ");
    $discount_rules = $discount_rules_query->fetchAll();

    // Add a "no discount" rule for display
    $discount_rules[] = ['min_amount' => 0, 'discount' => 0, 'description' => 'No Discount'];
} catch (PDOException $e) {
    // Fallback rules if database error
    $discount_rules = [
        ['min_amount' => 0, 'discount' => 0, 'description' => 'No Discount']
    ];
}

// Get all meals with categories
try {
    $meals = $pdo->query("
        SELECT m.*, c.NAME as category_name 
        FROM MEALS m 
        LEFT JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES 
        ORDER BY c.NAME, m.NAME
    ")->fetchAll();
    
    // Group meals by category
    $meals_by_category = [];
    foreach ($meals as $meal) {
        $category = $meal['category_name'] ?: 'Other';
        $meals_by_category[$category][] = $meal;
    }
} catch (PDOException $e) {
    $meals_by_category = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Custom Offer - Sushi Restaurant</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; }
        .container { padding-top: 50px; padding-bottom: 50px; }
        .card { border: none; border-radius: 20px; box-shadow: 0 10px 30px rgba(0,0,0,0.2); }
        .card-header { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); color: white; border-radius: 20px 20px 0 0 !important; }
        .meal-card { border: 2px solid #e9ecef; border-radius: 15px; transition: all 0.3s; cursor: pointer; }
        .meal-card:hover { border-color: #007bff; transform: translateY(-2px); }
        .meal-card.selected { border-color: #28a745; background: #f8fff9; }
        .cart-item { background: #f8f9fa; border-radius: 10px; padding: 15px; margin-bottom: 10px; }
        .discount-info { background: linear-gradient(135deg, #ff6b6b 0%, #feca57 100%); color: white; border-radius: 15px; }
        .btn-primary { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none; }
        .btn-success { background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%); border: none; }
        .price-display { font-size: 1.5rem; font-weight: bold; }
        .category-header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Header -->
        <div class="row mb-4">
            <div class="col-12 text-center">
                <h1 class="text-white mb-3">
                    <i class="fas fa-gift me-2"></i>Create Your Custom Offer
                </h1>
                <p class="text-white-50">Select 4 or more meals and get automatic discounts!</p>
                <a href="index.php" class="btn btn-outline-light">
                    <i class="fas fa-arrow-left me-2"></i>Back to Menu
                </a>
            </div>
        </div>

        <div class="row">
            <!-- Meals Selection -->
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-utensils me-2"></i>Select Your Meals</h5>
                    </div>
                    <div class="card-body">
                        <?php if (empty($meals_by_category)): ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                No meals available at the moment.
                            </div>
                        <?php else: ?>
                            <?php foreach ($meals_by_category as $category => $category_meals): ?>
                                <div class="mb-4">
                                    <h6 class="category-header p-2 rounded">
                                        <i class="fas fa-tag me-2"></i><?php echo htmlspecialchars($category); ?>
                                    </h6>
                                    <div class="row">
                                        <?php foreach ($category_meals as $meal): ?>
                                            <div class="col-md-6 mb-3">
                                                <div class="meal-card p-3" onclick="toggleMeal(<?php echo $meal['ID_MEALS']; ?>, '<?php echo htmlspecialchars($meal['NAME']); ?>', <?php echo $meal['PRICE']; ?>)">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <h6 class="mb-1"><?php echo htmlspecialchars($meal['NAME']); ?></h6>
                                                            <p class="text-muted small mb-2"><?php echo htmlspecialchars($meal['DESCRIPTION']); ?></p>
                                                            <span class="badge bg-primary">$<?php echo number_format($meal['PRICE'], 2); ?></span>
                                                        </div>
                                                        <div class="text-end">
                                                            <i class="fas fa-plus-circle fa-2x text-muted" id="icon-<?php echo $meal['ID_MEALS']; ?>"></i>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Cart & Summary -->
            <div class="col-lg-4">
                <div class="card sticky-top">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-shopping-cart me-2"></i>Your Custom Offer</h5>
                    </div>
                    <div class="card-body">
                        <!-- Selected Meals -->
                        <div id="selected-meals">
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-utensils fa-3x mb-3"></i>
                                <p>Select at least 4 meals to create your custom offer</p>
                            </div>
                        </div>

                        <!-- Price Summary -->
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Subtotal:</span>
                                <span class="price-display" id="subtotal">$0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2" id="discount-row" style="display: none !important;">
                                <span>Discount (<span id="discount-percent">0</span>%):</span>
                                <span class="text-success" id="discount-amount">-$0.00</span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="price-display text-success" id="total">$0.00</strong>
                            </div>

                            <!-- Discount Info -->
                            <div class="discount-info p-3 mb-3">
                                <h6><i class="fas fa-percentage me-2"></i>Available Discounts:</h6>
                                <?php foreach ($discount_rules as $rule): ?>
                                    <?php if ($rule['discount'] > 0): ?>
                                        <small class="d-block">
                                            Spend $<?php echo number_format($rule['min_amount'], 0); ?>+ → <?php echo $rule['discount']; ?>% off
                                        </small>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>

                            <!-- Submit Button -->
                            <button type="button" class="btn btn-success w-100" id="submit-offer" disabled onclick="submitOffer()">
                                <i class="fas fa-paper-plane me-2"></i>Submit Custom Offer
                            </button>
                            <small class="text-muted d-block mt-2 text-center">
                                Minimum 4 meals required
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Customer Info Modal -->
    <div class="modal fade" id="customerModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="fas fa-user me-2"></i>Your Information</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="customer-form">
                        <div class="mb-3">
                            <label class="form-label">Full Name *</label>
                            <input type="text" class="form-control" id="customer-name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Phone Number *</label>
                            <input type="tel" class="form-control" id="customer-phone" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Email Address (Optional)</label>
                            <input type="email" class="form-control" id="customer-email" placeholder="For order updates and special offers">
                            <small class="text-muted">We'll use this to send you updates about your custom offer</small>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>What happens next?</strong><br>
                            • Your custom offer will be reviewed by our team<br>
                            • We'll contact you within 24 hours with confirmation<br>
                            • If approved, we'll prepare your special meal combination<br>
                            • You can track your offer status through your phone number
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" onclick="finalSubmit()">
                        <i class="fas fa-check me-2"></i>Submit Offer
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let selectedMeals = [];
        const discountRules = <?php echo json_encode($discount_rules); ?>;

        function toggleMeal(id, name, price) {
            const existingIndex = selectedMeals.findIndex(meal => meal.id === id);
            const mealCard = document.querySelector(`[onclick*="${id}"]`);
            const icon = document.getElementById(`icon-${id}`);

            if (existingIndex > -1) {
                // Remove meal
                selectedMeals.splice(existingIndex, 1);
                mealCard.classList.remove('selected');
                icon.className = 'fas fa-plus-circle fa-2x text-muted';
            } else {
                // Add meal
                selectedMeals.push({ id, name, price });
                mealCard.classList.add('selected');
                icon.className = 'fas fa-check-circle fa-2x text-success';
            }

            updateCart();
        }

        function updateCart() {
            const container = document.getElementById('selected-meals');
            const submitBtn = document.getElementById('submit-offer');

            if (selectedMeals.length === 0) {
                container.innerHTML = `
                    <div class="text-center text-muted py-4">
                        <i class="fas fa-utensils fa-3x mb-3"></i>
                        <p>Select at least 4 meals to create your custom offer</p>
                    </div>
                `;
                submitBtn.disabled = true;
            } else {
                let html = '';
                selectedMeals.forEach((meal, index) => {
                    html += `
                        <div class="cart-item">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-0">${meal.name}</h6>
                                    <small class="text-muted">$${meal.price.toFixed(2)}</small>
                                </div>
                                <button class="btn btn-sm btn-outline-danger" onclick="removeMeal(${index})">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>
                        </div>
                    `;
                });
                container.innerHTML = html;
                submitBtn.disabled = selectedMeals.length < 4;
            }

            updatePricing();
        }

        function removeMeal(index) {
            const meal = selectedMeals[index];
            const mealCard = document.querySelector(`[onclick*="${meal.id}"]`);
            const icon = document.getElementById(`icon-${meal.id}`);
            
            mealCard.classList.remove('selected');
            icon.className = 'fas fa-plus-circle fa-2x text-muted';
            
            selectedMeals.splice(index, 1);
            updateCart();
        }

        function updatePricing() {
            const subtotal = selectedMeals.reduce((sum, meal) => sum + meal.price, 0);
            const discountPercent = calculateDiscount(subtotal);
            const discountAmount = subtotal * (discountPercent / 100);
            const total = subtotal - discountAmount;

            document.getElementById('subtotal').textContent = `$${subtotal.toFixed(2)}`;
            document.getElementById('total').textContent = `$${total.toFixed(2)}`;
            
            const discountRow = document.getElementById('discount-row');
            if (discountPercent > 0) {
                document.getElementById('discount-percent').textContent = discountPercent;
                document.getElementById('discount-amount').textContent = `-$${discountAmount.toFixed(2)}`;
                discountRow.style.display = 'flex';
            } else {
                discountRow.style.display = 'none';
            }
        }

        function calculateDiscount(totalPrice) {
            for (let rule of discountRules) {
                if (totalPrice >= rule.min_amount) {
                    return rule.discount;
                }
            }
            return 0;
        }

        function submitOffer() {
            if (selectedMeals.length < 4) {
                alert('Please select at least 4 meals to create a custom offer.');
                return;
            }
            
            const modal = new bootstrap.Modal(document.getElementById('customerModal'));
            modal.show();
        }

        function finalSubmit() {
            const name = document.getElementById('customer-name').value.trim();
            const phone = document.getElementById('customer-phone').value.trim();
            const email = document.getElementById('customer-email').value.trim();

            if (!name || !phone) {
                alert('Please fill in all required fields.');
                return;
            }

            const subtotal = selectedMeals.reduce((sum, meal) => sum + meal.price, 0);
            const discountPercent = calculateDiscount(subtotal);
            const discountAmount = subtotal * (discountPercent / 100);
            const total = subtotal - discountAmount;

            // Submit to server
            const formData = new FormData();
            formData.append('action', 'create_offer');
            formData.append('customer_name', name);
            formData.append('customer_phone', phone);
            formData.append('customer_email', email);
            formData.append('meals', JSON.stringify(selectedMeals));
            formData.append('total_price', subtotal.toFixed(2));
            formData.append('discount_percentage', discountPercent);
            formData.append('final_price', total.toFixed(2));

            fetch('process_custom_offer.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Your custom offer has been submitted successfully! We will contact you within 24 hours.');
                    window.location.href = 'index.php';
                } else {
                    alert('Error submitting offer: ' + data.message);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Error submitting offer. Please try again.');
            });
        }
    </script>
</body>
</html>

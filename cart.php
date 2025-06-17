<?php
include 'includes/config.php';
include 'includes/functions.php';

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'add':
                $meal_id = intval($_POST['meal_id']);
                $meal_name = $_POST['meal_name'];
                $meal_price = floatval($_POST['meal_price']);
                $quantity = intval($_POST['quantity']) ?: 1;
                
                // Check if item already exists in cart
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if ($item['id'] == $meal_id) {
                        $item['quantity'] += $quantity;
                        $found = true;
                        break;
                    }
                }
                
                // If not found, add new item
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'id' => $meal_id,
                        'name' => $meal_name,
                        'price' => $meal_price,
                        'quantity' => $quantity
                    ];
                }
                
                echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
                exit();
                
            case 'remove':
                $meal_id = intval($_POST['meal_id']);
                $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($meal_id) {
                    return $item['id'] != $meal_id;
                });
                $_SESSION['cart'] = array_values($_SESSION['cart']); // Reindex array
                
                echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
                exit();
                
            case 'update':
                $meal_id = intval($_POST['meal_id']);
                $quantity = intval($_POST['quantity']);
                
                if ($quantity <= 0) {
                    // Remove item if quantity is 0 or negative
                    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($meal_id) {
                        return $item['id'] != $meal_id;
                    });
                    $_SESSION['cart'] = array_values($_SESSION['cart']);
                } else {
                    // Update quantity
                    foreach ($_SESSION['cart'] as &$item) {
                        if ($item['id'] == $meal_id) {
                            $item['quantity'] = $quantity;
                            break;
                        }
                    }
                }
                
                echo json_encode(['success' => true, 'cart_count' => count($_SESSION['cart'])]);
                exit();
                
            case 'clear':
                $_SESSION['cart'] = [];
                echo json_encode(['success' => true, 'cart_count' => 0]);
                exit();
        }
    }
}

// Calculate cart totals
$cart_total = 0;
$cart_items = 0;
foreach ($_SESSION['cart'] as $item) {
    $cart_total += $item['price'] * $item['quantity'];
    $cart_items += $item['quantity'];
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="container-fluid bg-primary text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">
                    <i class="fas fa-shopping-cart me-2"></i>Your Cart
                </h1>
                <p class="mb-0">Review your items and proceed to checkout</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="menu.php" class="btn btn-light">
                    <i class="fas fa-plus me-1"></i>Add More Items
                </a>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <?php if (empty($_SESSION['cart'])): ?>
        <!-- Empty Cart -->
        <div class="text-center py-5">
            <i class="fas fa-shopping-cart fa-4x text-muted mb-4"></i>
            <h3 class="text-muted mb-3">Your cart is empty</h3>
            <p class="text-muted mb-4">Add some delicious meals to get started!</p>
            <a href="menu.php" class="btn btn-primary btn-lg">
                <i class="fas fa-utensils me-2"></i>Browse Menu
            </a>
        </div>
    <?php else: ?>
        
        <div class="row">
            <!-- Cart Items -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Cart Items (<?php echo $cart_items; ?>)
                            </h5>
                            <button class="btn btn-outline-danger btn-sm" onclick="clearCart()">
                                <i class="fas fa-trash me-1"></i>Clear All
                            </button>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <?php foreach ($_SESSION['cart'] as $index => $item): ?>
                            <div class="cart-item border-bottom p-3" data-item-id="<?php echo $item['id']; ?>">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">$<?php echo number_format($item['price'], 2); ?> each</small>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="input-group input-group-sm">
                                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] - 1; ?>)">-</button>
                                            <input type="number" class="form-control text-center" value="<?php echo $item['quantity']; ?>" min="1" onchange="updateQuantity(<?php echo $item['id']; ?>, this.value)">
                                            <button class="btn btn-outline-secondary" type="button" onclick="updateQuantity(<?php echo $item['id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                                        </div>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                    </div>
                                    <div class="col-md-1 text-end">
                                        <button class="btn btn-outline-danger btn-sm" onclick="removeFromCart(<?php echo $item['id']; ?>)">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>

            <!-- Order Summary & Checkout -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>Order Summary
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-2">
                            <span>Items (<?php echo $cart_items; ?>):</span>
                            <span>$<?php echo number_format($cart_total, 2); ?></span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span>Delivery Fee:</span>
                            <span class="text-success">Free</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between mb-3">
                            <strong>Total:</strong>
                            <strong class="text-success">$<?php echo number_format($cart_total, 2); ?></strong>
                        </div>
                        
                        <button class="btn btn-success btn-lg w-100 mb-3" data-bs-toggle="modal" data-bs-target="#checkoutModal">
                            <i class="fas fa-credit-card me-2"></i>Proceed to Checkout
                        </button>
                        
                        <a href="menu.php" class="btn btn-outline-primary w-100">
                            <i class="fas fa-plus me-2"></i>Add More Items
                        </a>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<!-- Checkout Modal -->
<div class="modal fade" id="checkoutModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card me-2"></i>Checkout
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form id="checkoutForm">
                <div class="modal-body">
                    <div class="row">
                        <!-- Customer Information -->
                        <div class="col-md-6">
                            <h6 class="mb-3">
                                <i class="fas fa-user me-2"></i>Customer Information
                            </h6>

                            <div class="mb-3">
                                <label class="form-label">Full Name *</label>
                                <input type="text" name="fullname" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Phone Number *</label>
                                <input type="tel" name="phone" class="form-control" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Email Address</label>
                                <input type="email" name="email" class="form-control">
                                <small class="text-muted">Optional - for order updates</small>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Delivery Address *</label>
                                <textarea name="address" class="form-control" rows="3" required placeholder="Street address, city, postal code"></textarea>
                            </div>
                        </div>

                        <!-- Order Summary -->
                        <div class="col-md-6">
                            <h6 class="mb-3">
                                <i class="fas fa-receipt me-2"></i>Order Summary
                            </h6>

                            <div class="bg-light p-3 rounded mb-3">
                                <?php foreach ($_SESSION['cart'] as $item): ?>
                                    <div class="d-flex justify-content-between mb-2">
                                        <div>
                                            <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                                            <small class="text-muted">Qty: <?php echo $item['quantity']; ?> × $<?php echo number_format($item['price'], 2); ?></small>
                                        </div>
                                        <div class="text-end">
                                            <strong>$<?php echo number_format($item['price'] * $item['quantity'], 2); ?></strong>
                                        </div>
                                    </div>
                                <?php endforeach; ?>

                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Total:</strong>
                                    <strong class="text-success">$<?php echo number_format($cart_total, 2); ?></strong>
                                </div>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                <strong>Payment:</strong> Cash on delivery<br>
                                <strong>Delivery:</strong> 30-45 minutes<br>
                                <strong>Fee:</strong> Free delivery
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-check me-2"></i>Place Order ($<?php echo number_format($cart_total, 2); ?>)
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Cart management functions
function addToCart(mealId, mealName, mealPrice, quantity = 1) {
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('meal_id', mealId);
    formData.append('meal_name', mealName);
    formData.append('meal_price', mealPrice);
    formData.append('quantity', quantity);

    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            showToast('Item added to cart!', 'success');
            location.reload(); // Refresh to show updated cart
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding item to cart', 'error');
    });
}

function removeFromCart(mealId) {
    if (!confirm('Remove this item from cart?')) return;

    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('meal_id', mealId);

    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            showToast('Item removed from cart', 'success');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error removing item', 'error');
    });
}

function updateQuantity(mealId, quantity) {
    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('meal_id', mealId);
    formData.append('quantity', quantity);

    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(data.cart_count);
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error updating quantity', 'error');
    });
}

function clearCart() {
    if (!confirm('Clear all items from cart?')) return;

    const formData = new FormData();
    formData.append('action', 'clear');

    fetch('cart.php', {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            updateCartCount(0);
            showToast('Cart cleared', 'success');
            location.reload();
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error clearing cart', 'error');
    });
}

function updateCartCount(count) {
    const cartCountElements = document.querySelectorAll('#cart-count, .cart-count');
    cartCountElements.forEach(element => {
        element.textContent = count;
    });
}

function showToast(message, type = 'success') {
    // Create toast element
    const toast = document.createElement('div');
    toast.className = `alert alert-${type === 'success' ? 'success' : 'danger'} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

    document.body.appendChild(toast);

    // Auto remove after 3 seconds
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, 3000);
}

// Checkout form submission
document.getElementById('checkoutForm').addEventListener('submit', function(e) {
    e.preventDefault();

    console.log('Checkout form submitted');

    const formData = new FormData(this);
    formData.append('action', 'checkout');

    // Add cart items to form data
    const cartItems = <?php echo json_encode($_SESSION['cart']); ?>;
    formData.append('cart_items', JSON.stringify(cartItems));
    formData.append('total_amount', <?php echo $cart_total; ?>);

    console.log('Cart items:', cartItems);
    console.log('Total amount:', <?php echo $cart_total; ?>);

    // Show loading state
    const submitButton = this.querySelector('button[type="submit"]');
    const originalText = submitButton.innerHTML;
    submitButton.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
    submitButton.disabled = true;

    fetch('process_order.php', {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        return response.text();
    })
    .then(text => {
        console.log('Response text:', text);
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showToast('Order placed successfully!', 'success');
                // Redirect to success page (cart will be cleared by server)
                setTimeout(() => {
                    window.location.href = 'order_success.php?order_id=' + data.order_id;
                }, 1000);
            } else {
                showToast(data.message || 'Error placing order', 'error');
                // Restore button
                submitButton.innerHTML = originalText;
                submitButton.disabled = false;
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            showToast('Server error - check console for details', 'error');
            // Restore button
            submitButton.innerHTML = originalText;
            submitButton.disabled = false;
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showToast('Network error: ' + error.message, 'error');
        // Restore button
        submitButton.innerHTML = originalText;
        submitButton.disabled = false;
    });
});
</script>

<style>
.cart-item {
    transition: background-color 0.3s ease;
}

.cart-item:hover {
    background-color: #f8f9fa;
}

.meal-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.meal-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.sticky-top {
    position: sticky;
    top: 20px;
}

@media (max-width: 768px) {
    .sticky-top {
        position: relative;
        top: auto;
    }
}
</style>

<?php include 'includes/footer.php'; ?>

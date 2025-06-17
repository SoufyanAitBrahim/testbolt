<?php
include 'includes/config.php';
include 'includes/functions.php';

// Get meal ID from URL
$meal_id = isset($_GET['meal']) ? intval($_GET['meal']) : 0;

if ($meal_id <= 0) {
    header("Location: menu.php");
    exit();
}

// Initialize cart if not exists
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

try {
    // Get the selected meal details
    $stmt = $pdo->prepare("
        SELECT m.*, c.NAME as category_name, c.ID_CATEGORIES
        FROM MEALS m
        JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
        WHERE m.ID_MEALS = ?
    ");
    $stmt->execute([$meal_id]);
    $selected_meal = $stmt->fetch();
    
    if (!$selected_meal) {
        header("Location: menu.php");
        exit();
    }
    
    // Get related meals from the same category (excluding the selected meal)
    $stmt = $pdo->prepare("
        SELECT m.*, c.NAME as category_name
        FROM MEALS m
        JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
        WHERE m.ID_CATEGORIES = ? AND m.ID_MEALS != ?
        ORDER BY m.NAME
        LIMIT 8
    ");
    $stmt->execute([$selected_meal['ID_CATEGORIES'], $meal_id]);
    $related_meals = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="container-fluid bg-primary text-white py-4">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="h3 mb-2">
                    <i class="fas fa-shopping-cart me-2"></i>Order Details
                </h1>
                <p class="mb-0">Review your selection and add more items</p>
            </div>
            <div class="col-md-4 text-end">
                <a href="menu.php" class="btn btn-light">
                    <i class="fas fa-arrow-left me-1"></i>Back to Menu
                </a>
                <button class="btn btn-warning ms-2" onclick="viewCart()">
                    <i class="fas fa-shopping-cart me-1"></i>Cart (<span id="cart-count"><?php echo count($_SESSION['cart']); ?></span>)
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container my-5">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <h4>Error</h4>
            <p>Unable to load meal details. Please try again.</p>
            <small><?php echo htmlspecialchars($error_message); ?></small>
        </div>
    <?php else: ?>
        
        <!-- Selected Meal Section -->
        <div class="row mb-5">
            <div class="col-12">
                <div class="card border-0 shadow-lg">
                    <div class="card-header bg-success text-white">
                        <h4 class="mb-0">
                            <i class="fas fa-star me-2"></i>Your Selection
                        </h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <img src="<?php echo htmlspecialchars($selected_meal['IMAGE_URL']); ?>" 
                                     class="img-fluid rounded shadow" 
                                     alt="<?php echo htmlspecialchars($selected_meal['NAME']); ?>"
                                     onerror="this.src='assets/images/placeholder.jpg'"
                                     style="height: 250px; width: 100%; object-fit: cover;">
                            </div>
                            <div class="col-md-8">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h3 class="mb-2"><?php echo htmlspecialchars($selected_meal['NAME']); ?></h3>
                                        <span class="badge bg-primary fs-6 mb-2"><?php echo htmlspecialchars($selected_meal['category_name']); ?></span>
                                    </div>
                                    <div class="text-end">
                                        <h3 class="text-success mb-0">$<?php echo number_format($selected_meal['PRICE'], 2); ?></h3>
                                    </div>
                                </div>
                                
                                <p class="text-muted mb-4"><?php echo htmlspecialchars($selected_meal['DESCRIPTION']); ?></p>
                                
                                <div class="d-flex gap-3">
                                    <button class="btn btn-success btn-lg" onclick="addToCart(<?php echo $selected_meal['ID_MEALS']; ?>, '<?php echo htmlspecialchars($selected_meal['NAME']); ?>', <?php echo $selected_meal['PRICE']; ?>)">
                                        <i class="fas fa-cart-plus me-2"></i>Add to Cart
                                    </button>
                                    <a href="menu.php" class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-utensils me-2"></i>Continue Shopping
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Related Meals Section -->
        <?php if (!empty($related_meals)): ?>
        <div class="row mb-5">
            <div class="col-12">
                <h3 class="mb-4">
                    <i class="fas fa-utensils me-2 text-primary"></i>
                    More from <?php echo htmlspecialchars($selected_meal['category_name']); ?>
                </h3>
                
                <div class="row">
                    <?php foreach ($related_meals as $meal): ?>
                        <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                            <div class="card meal-card h-100 border-0 shadow-sm">
                                <div class="position-relative">
                                    <img src="<?php echo htmlspecialchars($meal['IMAGE_URL']); ?>" 
                                         class="card-img-top" 
                                         alt="<?php echo htmlspecialchars($meal['NAME']); ?>"
                                         onerror="this.src='assets/images/placeholder.jpg'"
                                         style="height: 180px; object-fit: cover;">
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-success fs-6">
                                            $<?php echo number_format($meal['PRICE'], 2); ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body d-flex flex-column">
                                    <h6 class="card-title mb-2"><?php echo htmlspecialchars($meal['NAME']); ?></h6>
                                    <p class="card-text text-muted small flex-grow-1">
                                        <?php echo htmlspecialchars(strlen($meal['DESCRIPTION']) > 60 ? substr($meal['DESCRIPTION'], 0, 60) . '...' : $meal['DESCRIPTION']); ?>
                                    </p>
                                    <div class="mt-auto">
                                        <div class="d-flex gap-2">
                                            <button class="btn btn-primary btn-sm flex-grow-1" onclick="addToCart(<?php echo $meal['ID_MEALS']; ?>, '<?php echo htmlspecialchars($meal['NAME']); ?>', <?php echo $meal['PRICE']; ?>)">
                                                <i class="fas fa-cart-plus me-1"></i>Add
                                            </button>
                                            <a href="order.php?meal=<?php echo $meal['ID_MEALS']; ?>" class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Quick Actions -->
        <div class="row">
            <div class="col-12">
                <div class="card bg-light border-0">
                    <div class="card-body text-center py-4">
                        <h5 class="mb-3">Ready to complete your order?</h5>
                        <div class="d-flex justify-content-center gap-3 flex-wrap">
                            <button class="btn btn-success btn-lg" onclick="viewCart()">
                                <i class="fas fa-shopping-cart me-2"></i>View Cart & Checkout
                            </button>
                            <a href="menu.php" class="btn btn-outline-primary btn-lg">
                                <i class="fas fa-plus me-2"></i>Add More Items
                            </a>
                            <a href="index.php" class="btn btn-outline-secondary btn-lg">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
// Cart management functions
function addToCart(mealId, mealName, mealPrice, quantity = 1) {
    console.log('Adding to cart:', {mealId, mealName, mealPrice, quantity});

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('meal_id', mealId);
    formData.append('meal_name', mealName);
    formData.append('meal_price', mealPrice);
    formData.append('quantity', quantity);

    showToast('Adding item to cart...', 'info');

    fetch('cart.php', {
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
                updateCartCount(data.cart_count);
                showToast('Item added to cart!', 'success');
            } else {
                showToast(data.message || 'Failed to add item', 'error');
            }
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response was:', text);
            showToast('Server error - check console', 'error');
        }
    })
    .catch(error => {
        console.error('Fetch error:', error);
        showToast('Network error: ' + error.message, 'error');
    });
}

function viewCart() {
    window.location.href = 'cart.php';
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
    let alertClass = 'alert-success';
    let iconClass = 'check-circle';

    if (type === 'error') {
        alertClass = 'alert-danger';
        iconClass = 'exclamation-circle';
    } else if (type === 'info') {
        alertClass = 'alert-info';
        iconClass = 'info-circle';
    }

    toast.className = `alert ${alertClass} position-fixed`;
    toast.style.cssText = 'top: 20px; right: 20px; z-index: 9999; min-width: 300px;';
    toast.innerHTML = `
        <i class="fas fa-${iconClass} me-2"></i>
        ${message}
        <button type="button" class="btn-close" onclick="this.parentElement.remove()"></button>
    `;

    document.body.appendChild(toast);

    // Auto remove after 3 seconds (except for info messages which stay longer)
    const timeout = type === 'info' ? 1500 : 3000;
    setTimeout(() => {
        if (toast.parentElement) {
            toast.remove();
        }
    }, timeout);
}
</script>

<style>
.meal-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.meal-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}
</style>

<?php include 'includes/footer.php'; ?>

<?php
include 'includes/config.php';
include 'includes/functions.php';

// Get pagination parameters
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : null;
$meals_per_category = 12; // Show 12 meals per category per page

// Initialize variables
$categories = [];
$meals_by_category = [];
$total_pages = 1;

try {
    // Get all categories
    $categories = getCategories($pdo);
    
    // If specific category is requested, filter to that category only
    if ($category_filter) {
        $categories = array_filter($categories, function($cat) use ($category_filter) {
            return $cat['ID_CATEGORIES'] == $category_filter;
        });
    }
    
    // Get meals for each category with pagination
    foreach ($categories as $category) {
        $category_id = $category['ID_CATEGORIES'];
        
        // Calculate offset for this page
        $offset = ($page - 1) * $meals_per_category;
        
        // Get meals for this category
        $stmt = $pdo->prepare("
            SELECT m.*, c.NAME as category_name
            FROM MEALS m
            JOIN CATEGORIES c ON m.ID_CATEGORIES = c.ID_CATEGORIES
            WHERE m.ID_CATEGORIES = ?
            ORDER BY m.NAME
            LIMIT $meals_per_category OFFSET $offset
        ");
        $stmt->execute([$category_id]);
        $meals = $stmt->fetchAll();
        
        // Get total count for pagination
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM MEALS WHERE ID_CATEGORIES = ?");
        $count_stmt->execute([$category_id]);
        $total_meals = $count_stmt->fetchColumn();
        
        $meals_by_category[$category_id] = [
            'category' => $category,
            'meals' => $meals,
            'total_meals' => $total_meals,
            'has_more' => $total_meals > ($page * $meals_per_category)
        ];
    }
    
    // Calculate total pages (based on category with most meals)
    if (!empty($meals_by_category)) {
        $max_meals = max(array_column($meals_by_category, 'total_meals'));
        $total_pages = ceil($max_meals / $meals_per_category);
    }
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="container-fluid bg-primary text-white py-5">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-book-open me-3"></i>Our Complete Menu
                </h1>
                <p class="lead mb-0">
                    <?php if ($category_filter && !empty($categories)): ?>
                        Explore our <?php echo htmlspecialchars(reset($categories)['NAME']); ?> selection
                    <?php else: ?>
                        Discover authentic Japanese cuisine crafted by our master chefs
                    <?php endif; ?>
                </p>
            </div>
            <div class="col-md-4 text-end">
                <?php if ($category_filter): ?>
                    <a href="menu.php" class="btn btn-light btn-lg">
                        <i class="fas fa-arrow-left me-2"></i>All Categories
                    </a>
                <?php else: ?>
                    <a href="index.php" class="btn btn-light btn-lg">
                        <i class="fas fa-home me-2"></i>Back to Home
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Category Filter Navigation -->
<?php if (!$category_filter): ?>
<div class="container-fluid bg-light py-3">
    <div class="container">
        <div class="row">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="menu.php" class="btn btn-outline-primary <?php echo !$category_filter ? 'active' : ''; ?>">
                        <i class="fas fa-th-large me-1"></i>All Categories
                    </a>
                    <?php foreach ($categories as $category): ?>
                        <a href="menu.php?category=<?php echo $category['ID_CATEGORIES']; ?>" 
                           class="btn btn-outline-primary">
                            <?php echo htmlspecialchars($category['NAME']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Menu Content -->
<div class="container my-5">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <h4>Database Error</h4>
            <p>Unable to load menu items. Please try again later.</p>
            <small><?php echo htmlspecialchars($error_message); ?></small>
        </div>
    <?php elseif (empty($meals_by_category)): ?>
        <div class="text-center py-5">
            <i class="fas fa-utensils fa-4x text-muted mb-4"></i>
            <h3 class="text-muted">No menu items available</h3>
            <p class="text-muted">Please check back later or contact us for more information.</p>
            <a href="index.php" class="btn btn-primary">Return to Home</a>
        </div>
    <?php else: ?>
        
        <!-- Page Info -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h5 class="text-muted mb-0">
                            Page <?php echo $page; ?> of <?php echo $total_pages; ?>
                            <?php if ($category_filter): ?>
                                - <?php echo htmlspecialchars(reset($categories)['NAME']); ?>
                            <?php endif; ?>
                        </h5>
                    </div>
                    <div>
                        <span class="badge bg-primary fs-6">
                            Showing <?php echo $meals_per_category; ?> items per category
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Menu Items by Category -->
        <?php foreach ($meals_by_category as $category_data): ?>
            <?php 
            $category = $category_data['category'];
            $meals = $category_data['meals'];
            $has_more = $category_data['has_more'];
            ?>
            
            <div class="category-section mb-5">
                <!-- Category Header -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card bg-light border-0">
                            <div class="card-body py-3">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h3 class="mb-1">
                                            <i class="fas fa-utensils me-2 text-primary"></i>
                                            <?php echo htmlspecialchars($category['NAME']); ?>
                                        </h3>
                                        <p class="text-muted mb-0"><?php echo htmlspecialchars($category['DESCRIPTION']); ?></p>
                                    </div>
                                    <div class="text-end">
                                        <span class="badge bg-primary fs-6">
                                            <?php echo count($meals); ?> items
                                            <?php if ($has_more): ?>
                                                (more available)
                                            <?php endif; ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Meals Grid -->
                <?php if (empty($meals)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-utensils fa-2x text-muted mb-3"></i>
                        <p class="text-muted">No items available in this category for page <?php echo $page; ?></p>
                    </div>
                <?php else: ?>
                    <div class="row">
                        <?php foreach ($meals as $meal): ?>
                            <div class="col-lg-3 col-md-4 col-sm-6 mb-4">
                                <div class="card menu-item h-100 border-0 shadow-sm">
                                    <div class="position-relative">
                                        <img src="<?php echo htmlspecialchars($meal['IMAGE_URL']); ?>" 
                                             class="card-img-top menu-item-img" 
                                             alt="<?php echo htmlspecialchars($meal['NAME']); ?>" 
                                             onerror="this.src='assets/images/placeholder.jpg'"
                                             style="height: 200px; object-fit: cover;">
                                        <div class="position-absolute top-0 end-0 m-2">
                                            <span class="badge bg-success fs-6">
                                                $<?php echo number_format($meal['PRICE'], 2); ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="card-body d-flex flex-column">
                                        <h6 class="card-title mb-2"><?php echo htmlspecialchars($meal['NAME']); ?></h6>
                                        <p class="card-text text-muted small flex-grow-1">
                                            <?php echo htmlspecialchars(strlen($meal['DESCRIPTION']) > 80 ? substr($meal['DESCRIPTION'], 0, 80) . '...' : $meal['DESCRIPTION']); ?>
                                        </p>
                                        <div class="mt-auto">
                                            <div class="d-flex gap-2">
                                                <a href="order.php?meal=<?php echo $meal['ID_MEALS']; ?>" class="btn btn-primary btn-sm flex-grow-1">
                                                    <i class="fas fa-shopping-cart me-1"></i>Order Now
                                                </a>
                                                <button class="btn btn-outline-primary btn-sm" onclick="addToCart(<?php echo $meal['ID_MEALS']; ?>, '<?php echo htmlspecialchars($meal['NAME']); ?>', <?php echo $meal['PRICE']; ?>)">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
            <div class="row mt-5">
                <div class="col-12">
                    <nav aria-label="Menu pagination">
                        <ul class="pagination justify-content-center">
                            <!-- Previous Page -->
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>">
                                        <i class="fas fa-chevron-left me-1"></i>Previous
                                    </a>
                                </li>
                            <?php endif; ?>

                            <!-- Page Numbers -->
                            <?php
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $page + 2);
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>">
                                        <?php echo $i; ?>
                                    </a>
                                </li>
                            <?php endfor; ?>

                            <!-- Next Page -->
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>">
                                        Next<i class="fas fa-chevron-right ms-1"></i>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            </div>
        <?php endif; ?>

        <!-- See More Button (Alternative to pagination) -->
        <?php if ($page < $total_pages): ?>
            <div class="text-center mt-4">
                <a href="?page=<?php echo ($page + 1); ?><?php echo $category_filter ? '&category=' . $category_filter : ''; ?>" 
                   class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-plus me-2"></i>See More Items
                </a>
            </div>
        <?php endif; ?>

    <?php endif; ?>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-cart me-2"></i>Add to Order
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-utensils fa-3x text-primary mb-3"></i>
                    <h5 id="meal-name">Meal Name</h5>
                    <p class="text-muted">Price: <span id="meal-price" class="h5 text-success">$0.00</span></p>
                </div>

                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Coming Soon!</strong><br>
                    Online ordering functionality will be available soon. For now, please call us or visit our restaurant to place your order.
                </div>

                <div class="text-center">
                    <a href="index.php#reservation" class="btn btn-primary me-2">
                        <i class="fas fa-calendar me-1"></i>Book a Table
                    </a>
                    <a href="index.php#contact" class="btn btn-outline-primary">
                        <i class="fas fa-phone me-1"></i>Contact Us
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
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
            showToast('Item added to cart!', 'success');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showToast('Error adding item to cart', 'error');
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

// Add smooth scrolling for category navigation
document.addEventListener('DOMContentLoaded', function() {
    // Animate menu items on load
    const menuItems = document.querySelectorAll('.menu-item');
    menuItems.forEach((item, index) => {
        item.style.opacity = '0';
        item.style.transform = 'translateY(20px)';

        setTimeout(() => {
            item.style.transition = 'all 0.5s ease';
            item.style.opacity = '1';
            item.style.transform = 'translateY(0)';
        }, index * 50);
    });

    // Smooth scroll to category sections
    const categoryLinks = document.querySelectorAll('a[href^="#category-"]');
    categoryLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const targetId = this.getAttribute('href').substring(1);
            const targetElement = document.getElementById(targetId);
            if (targetElement) {
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
</script>

<style>
.menu-item {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.menu-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.menu-item-img {
    transition: transform 0.3s ease;
}

.menu-item:hover .menu-item-img {
    transform: scale(1.05);
}

.category-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.category-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.1) !important;
}

.category-icon {
    transition: transform 0.3s ease;
}

.category-card:hover .category-icon {
    transform: scale(1.1);
}

.pagination .page-link {
    border-radius: 50px;
    margin: 0 2px;
    border: none;
    background: #f8f9fa;
    color: #007bff;
}

.pagination .page-item.active .page-link {
    background: #007bff;
    color: white;
}

.pagination .page-link:hover {
    background: #007bff;
    color: white;
    transform: translateY(-2px);
}

.category-section {
    scroll-margin-top: 100px;
}

@media (max-width: 768px) {
    .menu-item {
        margin-bottom: 1rem;
    }

    .pagination {
        font-size: 0.9rem;
    }
}
</style>

<?php include 'includes/footer.php'; ?>

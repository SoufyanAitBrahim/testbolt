<?php
include 'includes/config.php';
include 'includes/functions.php';

// Initialize variables with empty arrays to prevent errors
$categories = [];
$featured_meals = [];
$offers = [];
$restaurants = [];

try {
    // Get categories for menu section
    $categories = getCategories($pdo);

    // Get featured meals (first 6 meals)
    $featured_meals = $pdo->query("SELECT * FROM MEALS LIMIT 6")->fetchAll();

    // Get offers
    $offers = getOffers($pdo);

    // Get restaurants for reservation
    $restaurants = getRestaurants($pdo);

} catch (PDOException $e) {
    // If database queries fail, show a setup message
    $database_error = true;
    $error_message = $e->getMessage();
}

include 'includes/header.php';

// If there's a database error, show setup instructions
if (isset($database_error)) {
    echo '<div class="container mt-5">';
    echo '<div class="alert alert-warning">';
    echo '<h4>Database Setup Required</h4>';
    echo '<p>It looks like the database tables haven\'t been created yet. Please run the setup first:</p>';
    echo '<p><a href="simple_setup.php" class="btn btn-primary">Run Database Setup</a></p>';
    echo '<p><small>Error: ' . htmlspecialchars($error_message) . '</small></p>';
    echo '</div>';
    echo '</div>';
    include 'includes/footer.php';
    exit;
}
?>

<!-- Hero Section -->
<section class="hero">
    <div class="hero-overlay">
        <div class="hero-text">
            <h1 class="display-4">Authentic Japanese Sushi</h1>
            <p class="lead">Experience the taste of tradition with our master chefs</p>
            <a href="#categories" class="btn btn-primary btn-lg">Explore Menu</a>
        </div>
    </div>
</section>

<!-- Categories Section -->
<section id="categories" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">
            <i class="fas fa-utensils me-3"></i>Explore Our Menu Categories
        </h2>
        <p class="text-center text-muted mb-5">Discover our authentic Japanese cuisine organized by category</p>

        <div class="row">
            <?php if (!empty($categories)): ?>
                <?php foreach ($categories as $category): ?>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="card category-card h-100 border-0 shadow-sm">
                        <div class="card-body text-center p-4">
                            <div class="category-icon mb-3">
                                <?php
                                // Assign icons based on category name
                                $category_name = strtolower($category['NAME']);
                                $icon = 'fas fa-utensils'; // default icon

                                if (strpos($category_name, 'sushi') !== false || strpos($category_name, 'roll') !== false) {
                                    $icon = 'fas fa-fish';
                                } elseif (strpos($category_name, 'appetizer') !== false || strpos($category_name, 'starter') !== false) {
                                    $icon = 'fas fa-seedling';
                                } elseif (strpos($category_name, 'soup') !== false) {
                                    $icon = 'fas fa-bowl-hot';
                                } elseif (strpos($category_name, 'dessert') !== false || strpos($category_name, 'sweet') !== false) {
                                    $icon = 'fas fa-ice-cream';
                                } elseif (strpos($category_name, 'drink') !== false || strpos($category_name, 'beverage') !== false) {
                                    $icon = 'fas fa-glass-water';
                                } elseif (strpos($category_name, 'main') !== false || strpos($category_name, 'entree') !== false) {
                                    $icon = 'fas fa-drumstick-bite';
                                } elseif (strpos($category_name, 'noodle') !== false || strpos($category_name, 'ramen') !== false) {
                                    $icon = 'fas fa-wheat-awn';
                                }
                                ?>
                                <i class="<?php echo $icon; ?> fa-3x text-primary"></i>
                            </div>
                            <h4 class="card-title mb-3"><?php echo htmlspecialchars($category['NAME']); ?></h4>
                            <p class="card-text text-muted mb-4"><?php echo htmlspecialchars($category['DESCRIPTION']); ?></p>
                            <a href="menu.php?category=<?php echo $category['ID_CATEGORIES']; ?>" class="btn btn-primary btn-lg">
                                <i class="fas fa-arrow-right me-2"></i>View Menu
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body py-5">
                            <i class="fas fa-utensils fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No categories available yet</h5>
                            <p class="text-muted">Please run the database setup to add sample categories.</p>
                            <a href="simple_setup.php" class="btn btn-primary">Setup Database</a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <div class="text-center mt-5">
            <a href="menu.php" class="btn btn-outline-primary btn-lg">
                <i class="fas fa-list me-2"></i>View Complete Menu
            </a>
        </div>
    </div>
</section>

<!-- Featured Menu Section -->
<section id="menu" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Our Featured Menu</h2>
        
        <div class="row">
            <?php if (!empty($featured_meals)): ?>
                <?php foreach ($featured_meals as $meal): ?>
                <div class="col-md-4 mb-4">
                    <div class="card menu-item h-100">
                        <img src="<?php echo htmlspecialchars($meal['IMAGE_URL']); ?>" class="card-img-top menu-item-img" alt="<?php echo htmlspecialchars($meal['NAME']); ?>" onerror="this.src='assets/images/placeholder.jpg'">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo htmlspecialchars($meal['NAME']); ?></h5>
                            <p class="card-text"><?php echo htmlspecialchars($meal['DESCRIPTION']); ?></p>
                            <p class="text-muted">$<?php echo number_format($meal['PRICE'], 2); ?></p>
                            <a href="#" class="btn btn-primary">Add to Order</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center">
                    <p class="text-muted">No meals available yet. Please run the database setup to add sample data.</p>
                    <a href="simple_setup.php" class="btn btn-primary">Setup Database</a>
                </div>
            <?php endif; ?>
        </div>
        
        <div class="text-center mt-4">
            <a href="menu.php" class="btn btn-outline-primary">View Full Menu</a>
        </div>
    </div>
</section>

<!-- Special Offers Section -->
<?php if (!empty($offers)): ?>
<section class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Special Offers</h2>
        
        <div class="row">
            <?php foreach ($offers as $offer): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <h5 class="card-title text-danger"><?php echo $offer['NAME']; ?></h5>
                        <p class="card-text"><?php echo $offer['DESCRIPTION']; ?></p>
                        <p class="text-success">Save <?php echo ($offer['discount_price'] * 100); ?>%</p>
                        <p class="text-muted"><del>$<?php echo number_format($offer['PRICE'], 2); ?></del> 
                        <span class="text-danger">$<?php echo number_format($offer['PRICE'] * (1 - $offer['discount_price']), 2); ?></span></p>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- Reservation Section -->
<section id="reservation" class="py-5">
    <div class="container">
        <h2 class="text-center mb-5">Book a Table</h2>
        
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body">
                        <form id="reservationForm" method="POST" action="process_reservation.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Full Name</label>
                                        <input type="text" name="fullname" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Number of Guests</label>
                                        <select name="guests" class="form-control" required>
                                            <option value="1">1 Person</option>
                                            <option value="2">2 People</option>
                                            <option value="3">3 People</option>
                                            <option value="4">4 People</option>
                                            <option value="5">5 People</option>
                                            <option value="6">6 People</option>
                                            <option value="7">7 People</option>
                                            <option value="8">8+ People</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Restaurant Location</label>
                                        <select name="restaurant" class="form-control" required>
                                            <?php if (!empty($restaurants)): ?>
                                                <?php foreach ($restaurants as $restaurant): ?>
                                                    <option value="<?php echo $restaurant['ID_RESTAURANTS']; ?>"><?php echo htmlspecialchars($restaurant['NAME']); ?></option>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <option value="">No restaurants available - Please setup database</option>
                                            <?php endif; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Date</label>
                                        <input type="date" name="date" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label>Time</label>
                                        <input type="time" name="time" class="form-control" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-3">
                                <label>Special Requests (Optional)</label>
                                <textarea name="requests" class="form-control" rows="3"></textarea>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Book Table</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Event Booking Section -->
<section id="events" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Book an Event</h2>
        <p class="text-center text-muted mb-5">Planning a special occasion? Let us host your event with our authentic Japanese cuisine!</p>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0"><i class="fas fa-calendar-plus me-2"></i>Event Booking Form</h5>
                    </div>
                    <div class="card-body">
                        <form id="eventForm" method="POST" action="process_event.php">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label"><i class="fas fa-user me-1"></i>Full Name</label>
                                        <input type="text" name="fullname" class="form-control" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label"><i class="fas fa-phone me-1"></i>Phone Number</label>
                                        <input type="tel" name="phone" class="form-control" required>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label"><i class="fas fa-calendar me-1"></i>Event Date</label>
                                        <input type="date" name="event_date" class="form-control" required min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group mb-3">
                                        <label class="form-label"><i class="fas fa-star me-1"></i>Event Type</label>
                                        <select name="event_type" class="form-control" required>
                                            <option value="">Select Event Type</option>
                                            <option value="Family Parties">🎉 Family Parties</option>
                                            <option value="Corporate Events">🏢 Corporate Events</option>
                                            <option value="Educational Events">🎓 Educational Events</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label"><i class="fas fa-info-circle me-1"></i>Additional Information (Optional)</label>
                                <textarea name="additional_info" class="form-control" rows="3" placeholder="Tell us more about your event (number of guests, special requirements, etc.)"></textarea>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-lightbulb me-2"></i>
                                <strong>Event Types:</strong>
                                <ul class="mb-0 mt-2">
                                    <li><strong>Family Parties:</strong> Birthdays, anniversaries, celebrations</li>
                                    <li><strong>Corporate Events:</strong> Business meetings, team building, company parties</li>
                                    <li><strong>Educational Events:</strong> Workshops, cultural events, cooking classes</li>
                                </ul>
                            </div>

                            <div class="text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-calendar-check me-2"></i>Book Event
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Contact Section -->
<section id="contact" class="py-5 bg-light">
    <div class="container">
        <h2 class="text-center mb-5">Contact Us</h2>
        
        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-body">
                        <h5 class="card-title">Our Locations</h5>
                        <ul class="list-unstyled">
                            <?php if (!empty($restaurants)): ?>
                                <?php foreach ($restaurants as $restaurant): ?>
                                <li class="mb-2">
                                    <strong><?php echo htmlspecialchars($restaurant['NAME']); ?></strong><br>
                                    <?php echo htmlspecialchars($restaurant['ADDRESS']); ?><br>
                                    Phone: <?php echo htmlspecialchars($restaurant['PHONE']); ?>
                                </li>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <li>No restaurant locations available yet.</li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- <div class="col-md-6">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Send Us a Message</h5>
                        <form id="contactForm">
                            <div class="form-group mb-3">
                                <label>Name</label>
                                <input type="text" name="name" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
                            </div>
                            <div class="form-group mb-3">
                                <label>Message</label>
                                <textarea name="message" class="form-control" rows="4" required></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary">Send Message</button>
                        </form>
                    </div>
                </div>
            </div> -->
        </div>
    </div>
</section>

<style>
.category-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    cursor: pointer;
}

.category-card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(0,0,0,0.15) !important;
}

.category-icon {
    transition: transform 0.3s ease;
}

.category-card:hover .category-icon {
    transform: scale(1.1) rotate(5deg);
}

.menu-item {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.menu-item:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1) !important;
}

.menu-item-img {
    height: 200px;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.menu-item:hover .menu-item-img {
    transform: scale(1.05);
}

/* Hero section improvements */
.hero {
    background: linear-gradient(rgba(0,0,0,0.5), rgba(0,0,0,0.5)), url('assets/images/hero-bg.jpg');
    background-size: cover;
    background-position: center;
    min-height: 70vh;
    display: flex;
    align-items: center;
}

.hero-overlay {
    width: 100%;
}

.hero-text {
    text-align: center;
    color: white;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .category-card {
        margin-bottom: 1.5rem;
    }

    .hero-text h1 {
        font-size: 2.5rem;
    }

    .hero-text .lead {
        font-size: 1.1rem;
    }
}

/* Animation for category cards */
.category-card {
    opacity: 0;
    transform: translateY(30px);
    animation: fadeInUp 0.6s ease forwards;
}

.category-card:nth-child(1) { animation-delay: 0.1s; }
.category-card:nth-child(2) { animation-delay: 0.2s; }
.category-card:nth-child(3) { animation-delay: 0.3s; }
.category-card:nth-child(4) { animation-delay: 0.4s; }
.category-card:nth-child(5) { animation-delay: 0.5s; }
.category-card:nth-child(6) { animation-delay: 0.6s; }

@keyframes fadeInUp {
    to {
        opacity: 1;
        transform: translateY(0);
    }
}
</style>

<?php include 'includes/footer.php'; ?>
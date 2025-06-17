<?php
include 'includes/config.php';

// Get all active offers with meal details
try {
    $offers = $pdo->query("
        SELECT 
            ao.*,
            COUNT(aco.ID_MEALS) as meal_count,
            GROUP_CONCAT(m.NAME SEPARATOR ', ') as meal_names,
            GROUP_CONCAT(CONCAT(m.NAME, '|', m.DESCRIPTION, '|', m.PRICE) SEPARATOR '###') as meal_details,
            SUM(m.PRICE) as calculated_original_price
        FROM ADMINS_OFFERS ao
        LEFT JOIN ADMIN_CUSTOMIZED_OFFERS aco ON ao.ID_ADMINS_OFFERS = aco.ID_ADMINS_OFFERS
        LEFT JOIN MEALS m ON aco.ID_MEALS = m.ID_MEALS
        WHERE (ao.status = 'active' OR ao.status IS NULL)
        AND (ao.valid_until >= CURDATE() OR ao.valid_until IS NULL)
        GROUP BY ao.ID_ADMINS_OFFERS
        HAVING meal_count > 0
        ORDER BY ao.created_date DESC
    ")->fetchAll();
} catch (PDOException $e) {
    $offers = [];
}

include 'includes/header.php';
?>

<div class="container-fluid" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 200px; margin-top: -24px;">
    <div class="container">
        <div class="row align-items-center" style="min-height: 200px;">
            <div class="col-12 text-center text-white">
                <h1 class="display-4 mb-3">
                    <i class="fas fa-tags me-3"></i>Special Promotions
                </h1>
                <p class="lead">Discover our amazing daily offers and save on your favorite meals!</p>
            </div>
        </div>
    </div>
</div>

<div class="container mt-5">
    <?php if (empty($offers)): ?>
        <!-- No Offers Available -->
        <div class="row justify-content-center">
            <div class="col-md-8 text-center">
                <div class="card border-0 shadow-lg">
                    <div class="card-body py-5">
                        <i class="fas fa-tags fa-4x text-muted mb-4"></i>
                        <h3 class="text-muted mb-3">No Active Promotions</h3>
                        <p class="text-muted mb-4">We're currently preparing some amazing offers for you. Check back soon!</p>
                        <a href="index.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-arrow-left me-2"></i>Back to Menu
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php else: ?>
        <!-- Offers Grid -->
        <div class="row">
            <?php foreach ($offers as $offer): ?>
                <?php
                // Parse meal details
                $meal_details = [];
                if (!empty($offer['meal_details'])) {
                    $meals_data = explode('###', $offer['meal_details']);
                    foreach ($meals_data as $meal_data) {
                        $parts = explode('|', $meal_data);
                        if (count($parts) >= 3) {
                            $meal_details[] = [
                                'name' => $parts[0],
                                'description' => $parts[1],
                                'price' => floatval($parts[2])
                            ];
                        }
                    }
                }
                
                $original_price = $offer['calculated_original_price'] ?? 0;
                $offer_price = $offer['OFFERS_PRICE'];
                $savings = $original_price - $offer_price;
                $savings_percent = $original_price > 0 ? round(($savings / $original_price) * 100) : 0;
                ?>
                
                <div class="col-lg-6 col-xl-4 mb-4">
                    <div class="card h-100 border-0 shadow-lg offer-card">
                        <div class="card-header bg-gradient text-white position-relative" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <h5 class="mb-0"><?php echo htmlspecialchars($offer['ADMINS_OFFERS_NAME']); ?></h5>
                            <?php if ($savings > 0): ?>
                                <span class="position-absolute top-0 end-0 badge bg-danger" style="transform: translate(25%, -25%);">
                                    Save <?php echo $savings_percent; ?>%
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <p class="text-muted mb-3"><?php echo htmlspecialchars($offer['ADMINS_OFFERS_DESCRIPTION']); ?></p>
                            
                            <!-- Meal Details -->
                            <div class="mb-3">
                                <h6 class="text-primary mb-2">
                                    <i class="fas fa-utensils me-2"></i>Includes (<?php echo $offer['meal_count']; ?> items):
                                </h6>
                                <div class="meal-list">
                                    <?php foreach ($meal_details as $meal): ?>
                                        <div class="d-flex justify-content-between align-items-center mb-2 p-2 bg-light rounded">
                                            <div>
                                                <strong class="small"><?php echo htmlspecialchars($meal['name']); ?></strong>
                                                <div class="text-muted small"><?php echo htmlspecialchars($meal['description']); ?></div>
                                            </div>
                                            <span class="badge bg-secondary">$<?php echo number_format($meal['price'], 2); ?></span>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <!-- Pricing -->
                            <div class="pricing-section bg-light p-3 rounded mb-3">
                                <div class="row text-center">
                                    <div class="col-6">
                                        <div class="text-muted small">Regular Price</div>
                                        <div class="h5 text-decoration-line-through text-muted">
                                            $<?php echo number_format($original_price, 2); ?>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="text-muted small">Offer Price</div>
                                        <div class="h4 text-success fw-bold">
                                            $<?php echo number_format($offer_price, 2); ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <?php if ($savings > 0): ?>
                                    <div class="text-center mt-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-piggy-bank me-1"></i>
                                            You Save $<?php echo number_format($savings, 2); ?>!
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>
                            
                            <!-- Validity -->
                            <?php if (isset($offer['valid_from']) && isset($offer['valid_until'])): ?>
                                <div class="text-center mb-3">
                                    <small class="text-muted">
                                        <i class="fas fa-calendar me-1"></i>
                                        Valid from <?php echo date('M d', strtotime($offer['valid_from'])); ?> 
                                        to <?php echo date('M d, Y', strtotime($offer['valid_until'])); ?>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-footer bg-transparent border-0 text-center">
                            <button class="btn btn-success btn-lg w-100" onclick="orderOffer(<?php echo $offer['ID_ADMINS_OFFERS']; ?>, '<?php echo htmlspecialchars($offer['ADMINS_OFFERS_NAME']); ?>', <?php echo $offer_price; ?>)">
                                <i class="fas fa-shopping-cart me-2"></i>Order This Offer
                            </button>
                            <small class="text-muted d-block mt-2">
                                <i class="fas fa-clock me-1"></i>Limited time offer
                            </small>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        
        <!-- Call to Action -->
        <div class="row mt-5">
            <div class="col-12">
                <div class="card bg-primary text-white border-0 shadow-lg">
                    <div class="card-body text-center py-4">
                        <h4 class="mb-3">
                            <i class="fas fa-gift me-2"></i>Want a Custom Offer?
                        </h4>
                        <p class="mb-3">Create your own personalized meal combination and get automatic discounts!</p>
                        <a href="custom_offers.php" class="btn btn-light btn-lg">
                            <i class="fas fa-magic me-2"></i>Create Custom Offer
                        </a>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

<!-- Order Modal -->
<div class="modal fade" id="orderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-shopping-cart me-2"></i>Order Confirmation
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                    <h5 id="offer-name">Offer Name</h5>
                    <p class="text-muted">Total: <span id="offer-price" class="h5 text-success">$0.00</span></p>
                </div>
                
                <form id="order-form">
                    <input type="hidden" id="offer-id" name="offer_id">
                    <div class="mb-3">
                        <label class="form-label">Your Name *</label>
                        <input type="text" class="form-control" name="customer_name" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Phone Number *</label>
                        <input type="tel" class="form-control" name="customer_phone" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email (Optional)</label>
                        <input type="email" class="form-control" name="customer_email">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Special Instructions (Optional)</label>
                        <textarea class="form-control" name="special_instructions" rows="3" placeholder="Any special requests or dietary requirements..."></textarea>
                    </div>
                </form>
                
                <div class="alert alert-info">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>What happens next?</strong><br>
                    • We'll prepare your order according to the offer specifications<br>
                    • You'll receive a confirmation call within 30 minutes<br>
                    • Pickup or delivery options will be discussed
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitOrder()">
                    <i class="fas fa-check me-2"></i>Confirm Order
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
    function orderOffer(offerId, offerName, offerPrice) {
        document.getElementById('offer-id').value = offerId;
        document.getElementById('offer-name').textContent = offerName;
        document.getElementById('offer-price').textContent = '$' + offerPrice.toFixed(2);
        
        const modal = new bootstrap.Modal(document.getElementById('orderModal'));
        modal.show();
    }

    function submitOrder() {
        const form = document.getElementById('order-form');
        const formData = new FormData(form);
        formData.append('action', 'order_promotion');

        // Basic validation
        const name = formData.get('customer_name').trim();
        const phone = formData.get('customer_phone').trim();

        if (!name || !phone) {
            alert('Please fill in your name and phone number.');
            return;
        }

        // Show loading state
        const submitBtn = document.querySelector('#orderModal .btn-success');
        const originalText = submitBtn.innerHTML;
        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Processing...';
        submitBtn.disabled = true;

        // Submit to server
        fetch('process_promotion_order.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert(`Thank you ${name}! Your order for "${data.offer_name}" has been received. Order ID: #${data.order_id}. We will contact you shortly to confirm the details.`);

                // Close modal and reset form
                bootstrap.Modal.getInstance(document.getElementById('orderModal')).hide();
                form.reset();
            } else {
                alert('Error placing order: ' + data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error placing order. Please try again.');
        })
        .finally(() => {
            // Restore button state
            submitBtn.innerHTML = originalText;
            submitBtn.disabled = false;
        });
    }

    // Add some animation to offer cards
    document.addEventListener('DOMContentLoaded', function() {
        const cards = document.querySelectorAll('.offer-card');
        cards.forEach((card, index) => {
            card.style.opacity = '0';
            card.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                card.style.transition = 'all 0.5s ease';
                card.style.opacity = '1';
                card.style.transform = 'translateY(0)';
            }, index * 100);
        });
    });
</script>

<style>
.offer-card {
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.offer-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.15) !important;
}

.meal-list {
    max-height: 200px;
    overflow-y: auto;
}

.pricing-section {
    border: 2px dashed #28a745;
}

.bg-gradient {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%) !important;
}
</style>

<?php include 'includes/footer.php'; ?>

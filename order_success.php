<?php
include 'includes/config.php';
include 'includes/functions.php';

$order_id = isset($_GET['order_id']) ? $_GET['order_id'] : '';

if (empty($order_id)) {
    header("Location: index.php");
    exit();
}

// Parse order_id (format: client_id_timestamp)
$order_parts = explode('_', $order_id);
if (count($order_parts) != 2) {
    header("Location: index.php");
    exit();
}

$client_id = intval($order_parts[0]);
$order_timestamp = intval($order_parts[1]);

try {
    // Get order details from ORDER table using client_id and timestamp
    $order_date = date('Y-m-d H:i:s', $order_timestamp);
    $order_date_range_start = date('Y-m-d H:i:s', $order_timestamp - 300); // 5 minutes before
    $order_date_range_end = date('Y-m-d H:i:s', $order_timestamp + 300);   // 5 minutes after

    $stmt = $pdo->prepare("
        SELECT
            o.*,
            c.FULLNAME,
            c.PHONE_NUMBER,
            c.EMAIL
        FROM `ORDER` o
        JOIN CLIENTS c ON o.ID_CLIENTS = c.ID_CLIENTS
        WHERE o.ID_CLIENTS = ?
        AND o.DATE_ORDER BETWEEN ? AND ?
        LIMIT 1
    ");
    $stmt->execute([$client_id, $order_date_range_start, $order_date_range_end]);
    $order = $stmt->fetch();

    if (!$order) {
        header("Location: index.php");
        exit();
    }

    // Get all order items for this order (same client, same delivery address, same time period)
    $stmt = $pdo->prepare("
        SELECT
            o.*,
            m.NAME as meal_name,
            m.DESCRIPTION as meal_description,
            m.PRICE as original_price
        FROM `ORDER` o
        JOIN MEALS m ON o.ID_MEALS = m.ID_MEALS
        WHERE o.ID_CLIENTS = ?
        AND o.delivery_address = ?
        AND o.DATE_ORDER BETWEEN ? AND ?
        ORDER BY m.NAME
    ");
    $stmt->execute([$client_id, $order['delivery_address'], $order_date_range_start, $order_date_range_end]);
    $order_items = $stmt->fetchAll();

    // Calculate actual total from items
    $calculated_total = 0;
    foreach ($order_items as $item) {
        $calculated_total += $item['original_price'] * $item['quantity'];
    }
    
} catch (PDOException $e) {
    $error_message = $e->getMessage();
}

include 'includes/header.php';
?>

<!-- Hero Section -->
<div class="container-fluid bg-success text-white py-5">
    <div class="container">
        <div class="text-center">
            <i class="fas fa-check-circle fa-4x mb-3"></i>
            <h1 class="display-4 mb-3">Order Confirmed!</h1>
            <p class="lead mb-0">Thank you for your order. We're preparing your delicious meal!</p>
        </div>
    </div>
</div>

<div class="container my-5">
    <?php if (isset($error_message)): ?>
        <div class="alert alert-danger">
            <h4>Error</h4>
            <p>Unable to load order details. Please contact us if you need assistance.</p>
            <small><?php echo htmlspecialchars($error_message); ?></small>
        </div>
    <?php else: ?>
        
        <div class="row">
            <!-- Order Details -->
            <div class="col-lg-8">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">
                            <i class="fas fa-receipt me-2"></i>Order Details
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Order Information</h6>
                                <p class="mb-1"><strong>Order ID:</strong> #<?php echo strtoupper(substr(md5($order_id), 0, 8)); ?></p>
                                <p class="mb-1"><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['DATE_ORDER'])); ?></p>
                                <p class="mb-1"><strong>Status:</strong>
                                    <span class="badge bg-warning text-dark"><?php echo ucfirst($order['ORDER_SITUATION']); ?></span>
                                </p>
                                <p class="mb-0"><strong>Total Amount:</strong>
                                    <span class="text-success h5">$<?php echo number_format($calculated_total, 2); ?></span>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Customer Information</h6>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($order['FULLNAME']); ?></p>
                                <p class="mb-1"><strong>Phone:</strong> <?php echo htmlspecialchars($order['PHONE_NUMBER']); ?></p>
                                <?php if (!empty($order['EMAIL'])): ?>
                                    <p class="mb-1"><strong>Email:</strong> <?php echo htmlspecialchars($order['EMAIL']); ?></p>
                                <?php endif; ?>
                                <p class="mb-0"><strong>Delivery Address:</strong><br>
                                    <?php echo nl2br(htmlspecialchars($order['delivery_address'])); ?>
                                </p>
                            </div>
                        </div>
                        
                        <h6 class="text-muted mb-3">Ordered Items</h6>
                        <div class="table-responsive">
                            <table class="table table-borderless">
                                <thead class="table-light">
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-center">Quantity</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($order_items as $item): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($item['meal_name']); ?></strong>
                                                <?php if (!empty($item['meal_description'])): ?>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars(substr($item['meal_description'], 0, 100)); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td class="text-center"><?php echo $item['quantity']; ?></td>
                                            <td class="text-end">$<?php echo number_format($item['original_price'], 2); ?></td>
                                            <td class="text-end"><strong>$<?php echo number_format($item['original_price'] * $item['quantity'], 2); ?></strong></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                                <tfoot class="table-light">
                                    <tr>
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="text-end text-success">$<?php echo number_format($calculated_total, 2); ?></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Order Status & Actions -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-clock me-2"></i>Order Status
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="timeline">
                            <div class="timeline-item active">
                                <div class="timeline-marker bg-success">
                                    <i class="fas fa-check"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Order Received</h6>
                                    <small class="text-muted"><?php echo date('g:i A', strtotime($order['DATE_ORDER'])); ?></small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="fas fa-utensils"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Preparing</h6>
                                    <small class="text-muted">15-20 minutes</small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="fas fa-truck"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Out for Delivery</h6>
                                    <small class="text-muted">10-15 minutes</small>
                                </div>
                            </div>
                            <div class="timeline-item">
                                <div class="timeline-marker">
                                    <i class="fas fa-home"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6>Delivered</h6>
                                    <small class="text-muted">30-45 minutes total</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="alert alert-info mt-3">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Estimated Delivery:</strong><br>
                            <?php echo date('g:i A', strtotime($order['DATE_ORDER'] . ' +45 minutes')); ?>
                        </div>
                    </div>
                </div>
                
                <div class="card border-0 shadow-sm">
                    <div class="card-body text-center">
                        <h6 class="mb-3">Need Help?</h6>
                        <div class="d-grid gap-2">
                            <a href="index.php#contact" class="btn btn-outline-primary">
                                <i class="fas fa-phone me-2"></i>Contact Us
                            </a>
                            <a href="menu.php" class="btn btn-outline-success">
                                <i class="fas fa-utensils me-2"></i>Order Again
                            </a>
                            <a href="index.php" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Back to Home
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    <?php endif; ?>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 20px;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    top: 0;
    width: 30px;
    height: 30px;
    border-radius: 50%;
    background: #dee2e6;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
}

.timeline-item.active .timeline-marker {
    background: #28a745;
}

.timeline-content h6 {
    margin-bottom: 5px;
    font-weight: 600;
}

.timeline-item.active .timeline-content h6 {
    color: #28a745;
}
</style>

<?php include 'includes/footer.php'; ?>

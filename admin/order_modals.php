<?php
// This file contains all the modals for order management
// Include this file in orders_management.php
?>

<!-- Order Details Modals -->
<?php foreach ($orders as $order): ?>
<div class="modal fade" id="orderModal<?php echo $order['ID_CLIENTS'].'_'.$order['ID_MEALS']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-receipt me-2"></i>Order Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Customer Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($order['client_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($order['client_email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td><?php echo htmlspecialchars($order['client_phone']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Location:</strong></td>
                                <td><?php echo htmlspecialchars($order['client_location']); ?></td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">Order Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Order Date:</strong></td>
                                <td><?php echo date('M d, Y H:i', strtotime($order['DATE_ORDER'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Order Type:</strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo $order['ORDER_TYPE'] == 1 ? 'Delivery' : 'Pickup'; ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        echo $order['ORDER_SITUATION'] == 'Pending' ? 'warning' : 
                                            ($order['ORDER_SITUATION'] == 'Ready' ? 'success' : 
                                            ($order['ORDER_SITUATION'] == 'Delivered' ? 'secondary' : 
                                            ($order['ORDER_SITUATION'] == 'Cancelled' ? 'danger' : 'info'))); 
                                    ?>">
                                        <?php echo htmlspecialchars($order['ORDER_SITUATION']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Payment:</strong></td>
                                <td>
                                    <span class="badge bg-<?php echo $order['PAYMENT_SITUATION'] == 1 ? 'success' : ($order['PAYMENT_SITUATION'] == 2 ? 'warning' : 'secondary'); ?>">
                                        <?php echo $order['payment_status_text']; ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-warning">Meal Details</h6>
                        <div class="card">
                            <div class="card-body">
                                <div class="d-flex align-items-center">
                                    <div class="me-3" style="width: 80px; height: 80px; background: #f8f9fa; border-radius: 8px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-utensils fa-2x text-muted"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h5 class="mb-1"><?php echo htmlspecialchars($order['meal_name']); ?></h5>
                                        <p class="text-muted mb-1"><?php echo htmlspecialchars($order['meal_description']); ?></p>
                                        <p class="mb-1">
                                            <span class="badge bg-secondary"><?php echo htmlspecialchars($order['category_name']); ?></span>
                                        </p>
                                        <h4 class="text-success mb-0">$<?php echo number_format($order['meal_price'], 2); ?></h4>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Print Order
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Status Update Modals -->
<?php foreach ($orders as $order): ?>
<div class="modal fade" id="statusModal<?php echo $order['ID_CLIENTS'].'_'.$order['ID_MEALS']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Update Order Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="<?php echo $order['ID_CLIENTS']; ?>">
                    <input type="hidden" name="meal_id" value="<?php echo $order['ID_MEALS']; ?>">
                    
                    <div class="text-center mb-3">
                        <h6><?php echo htmlspecialchars($order['client_name']); ?></h6>
                        <p class="text-muted"><?php echo htmlspecialchars($order['meal_name']); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Order Status</label>
                        <select name="new_status" class="form-control" required>
                            <option value="Pending" <?php echo $order['ORDER_SITUATION'] == 'Pending' ? 'selected' : ''; ?>>
                                🕐 Pending
                            </option>
                            <option value="Confirmed" <?php echo $order['ORDER_SITUATION'] == 'Confirmed' ? 'selected' : ''; ?>>
                                ✅ Confirmed
                            </option>
                            <option value="Preparing" <?php echo $order['ORDER_SITUATION'] == 'Preparing' ? 'selected' : ''; ?>>
                                👨‍🍳 Preparing
                            </option>
                            <option value="Ready" <?php echo $order['ORDER_SITUATION'] == 'Ready' ? 'selected' : ''; ?>>
                                🍱 Ready for Pickup/Delivery
                            </option>
                            <option value="Delivered" <?php echo $order['ORDER_SITUATION'] == 'Delivered' ? 'selected' : ''; ?>>
                                🚚 Delivered/Completed
                            </option>
                            <option value="Cancelled" <?php echo $order['ORDER_SITUATION'] == 'Cancelled' ? 'selected' : ''; ?>>
                                ❌ Cancelled
                            </option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Status Workflow:</strong>
                        <br>Pending → Confirmed → Preparing → Ready → Delivered
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_status" class="btn btn-success">
                        <i class="fas fa-save me-1"></i>Update Status
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Payment Status Modal -->
<div class="modal fade" id="paymentModal<?php echo $order['ID_CLIENTS'].'_'.$order['ID_MEALS']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-credit-card me-2"></i>Update Payment Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="client_id" value="<?php echo $order['ID_CLIENTS']; ?>">
                    <input type="hidden" name="meal_id" value="<?php echo $order['ID_MEALS']; ?>">
                    
                    <div class="text-center mb-3">
                        <h6><?php echo htmlspecialchars($order['client_name']); ?></h6>
                        <p class="text-muted">$<?php echo number_format($order['meal_price'], 2); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Status</label>
                        <select name="payment_status" class="form-control" required>
                            <option value="0" <?php echo $order['PAYMENT_SITUATION'] == 0 ? 'selected' : ''; ?>>
                                💳 Pending Payment
                            </option>
                            <option value="1" <?php echo $order['PAYMENT_SITUATION'] == 1 ? 'selected' : ''; ?>>
                                ✅ Paid
                            </option>
                            <option value="2" <?php echo $order['PAYMENT_SITUATION'] == 2 ? 'selected' : ''; ?>>
                                🔄 Refunded
                            </option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_payment" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i>Update Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endforeach; ?>

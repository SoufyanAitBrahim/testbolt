<?php
// This file contains all the modals for event management
// Include this file in events.php
?>

<!-- Event Details Modals -->
<?php foreach ($events as $event): ?>
<div class="modal fade" id="eventModal<?php echo $event['ID_EVENT_BOOKINGS']; ?>" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-calendar-check me-2"></i>Event Details
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">Client Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo htmlspecialchars($event['client_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Phone:</strong></td>
                                <td><?php echo htmlspecialchars($event['client_phone']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><?php echo htmlspecialchars($event['client_email']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Client Type:</strong></td>
                                <td>
                                    <span class="badge bg-info">
                                        <?php echo htmlspecialchars($event['client_type']); ?>
                                    </span>
                                </td>
                            </tr>
                        </table>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success">Event Information</h6>
                        <table class="table table-sm">
                            <tr>
                                <td><strong>Event Type:</strong></td>
                                <td>
                                    <span class="badge bg-warning text-dark">
                                        <?php echo htmlspecialchars($event['EVENT_TYPE']); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Event Date:</strong></td>
                                <td><?php echo date('M d, Y', strtotime($event['EVENT_DATE'])); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Status:</strong></td>
                                <td>
                                    <span class="badge bg-<?php 
                                        $status = $event['event_status'] ?? 'Pending';
                                        echo $status == 'Pending' ? 'warning' : 
                                            ($status == 'Confirmed' ? 'success' : 
                                            ($status == 'Completed' ? 'secondary' : 
                                            ($status == 'Cancelled' ? 'danger' : 'info'))); 
                                    ?>">
                                        <?php echo htmlspecialchars($status); ?>
                                    </span>
                                </td>
                            </tr>
                            <tr>
                                <td><strong>Booking ID:</strong></td>
                                <td>#<?php echo $event['ID_EVENT_BOOKINGS']; ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-info">Event Type Information</h6>
                        <div class="alert alert-info">
                            <?php 
                            $event_type = $event['EVENT_TYPE'];
                            if ($event_type == 'Family Parties') {
                                echo '<i class="fas fa-birthday-cake me-2"></i><strong>Family Parties:</strong> Perfect for birthdays, anniversaries, and family celebrations. We provide a warm, intimate atmosphere for your special moments.';
                            } elseif ($event_type == 'Corporate Events') {
                                echo '<i class="fas fa-briefcase me-2"></i><strong>Corporate Events:</strong> Ideal for business meetings, team building, and company parties. Professional setting with excellent service.';
                            } elseif ($event_type == 'Educational Events') {
                                echo '<i class="fas fa-graduation-cap me-2"></i><strong>Educational Events:</strong> Great for workshops, cultural events, and cooking classes. Learn about Japanese cuisine and culture.';
                            } else {
                                echo '<i class="fas fa-star me-2"></i><strong>Special Event:</strong> Custom event tailored to your specific needs.';
                            }
                            ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12">
                        <h6 class="text-warning">Event Timeline</h6>
                        <div class="timeline">
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-calendar-plus text-primary me-2"></i>
                                <span>Event requested for: <strong><?php echo date('M d, Y', strtotime($event['EVENT_DATE'])); ?></strong></span>
                            </div>
                            <div class="d-flex align-items-center mb-2">
                                <i class="fas fa-clock text-info me-2"></i>
                                <span>Days until event: <strong><?php echo max(0, ceil((strtotime($event['EVENT_DATE']) - time()) / 86400)); ?> days</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="window.print()">
                    <i class="fas fa-print me-1"></i>Print Details
                </button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Status Update Modals -->
<?php foreach ($events as $event): ?>
<div class="modal fade" id="statusModal<?php echo $event['ID_EVENT_BOOKINGS']; ?>" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-edit me-2"></i>Update Event Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body">
                    <input type="hidden" name="event_id" value="<?php echo $event['ID_EVENT_BOOKINGS']; ?>">
                    
                    <div class="text-center mb-3">
                        <h6><?php echo htmlspecialchars($event['client_name']); ?></h6>
                        <p class="text-muted"><?php echo htmlspecialchars($event['EVENT_TYPE']); ?> on <?php echo date('M d, Y', strtotime($event['EVENT_DATE'])); ?></p>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Event Status</label>
                        <select name="new_status" class="form-control" required>
                            <option value="Pending" <?php echo ($event['event_status'] ?? 'Pending') == 'Pending' ? 'selected' : ''; ?>>
                                🕐 Pending Review
                            </option>
                            <option value="Confirmed" <?php echo ($event['event_status'] ?? 'Pending') == 'Confirmed' ? 'selected' : ''; ?>>
                                ✅ Confirmed
                            </option>
                            <option value="In Progress" <?php echo ($event['event_status'] ?? 'Pending') == 'In Progress' ? 'selected' : ''; ?>>
                                🔄 In Progress
                            </option>
                            <option value="Completed" <?php echo ($event['event_status'] ?? 'Pending') == 'Completed' ? 'selected' : ''; ?>>
                                🎉 Completed
                            </option>
                            <option value="Cancelled" <?php echo ($event['event_status'] ?? 'Pending') == 'Cancelled' ? 'selected' : ''; ?>>
                                ❌ Cancelled
                            </option>
                        </select>
                    </div>
                    
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Status Workflow:</strong>
                        <br>Pending → Confirmed → In Progress → Completed
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
<?php endforeach; ?>

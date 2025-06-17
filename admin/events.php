<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

$success = '';
$error = '';

// Handle status updates
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_status'])) {
        $event_id = intval($_POST['event_id']);
        $new_status = trim($_POST['new_status']);

        $valid_statuses = ['Pending', 'Confirmed', 'In Progress', 'Completed', 'Cancelled'];

        if (in_array($new_status, $valid_statuses)) {
            try {
                // Add status column if it doesn't exist
                $pdo->exec("ALTER TABLE event_bookings ADD COLUMN IF NOT EXISTS status VARCHAR(50) DEFAULT 'Pending'");

                $stmt = $pdo->prepare("UPDATE event_bookings SET status = ? WHERE ID_EVENT_BOOKINGS = ?");
                $stmt->execute([$new_status, $event_id]);
                $success = "Event status updated successfully!";
            } catch (PDOException $e) {
                $error = "Error updating event status: " . $e->getMessage();
            }
        }
    }
}

// Get all event bookings (simplified query without BOOK_EVENT table)
$events = $pdo->query("
    SELECT
        e.*,
        e.FULLNAME as client_name,
        e.PHONE_NUMBER as client_phone,
        'Not registered' as client_email,
        'Guest' as client_type,
        COALESCE(e.status, 'Pending') as event_status
    FROM event_bookings e
    ORDER BY e.EVENT_DATE DESC
")->fetchAll();

// Get statistics
$total_events = count($events);
$pending_events = count(array_filter($events, function($e) { return ($e['event_status'] ?? 'Pending') == 'Pending'; }));
$confirmed_events = count(array_filter($events, function($e) { return ($e['event_status'] ?? 'Pending') == 'Confirmed'; }));
$completed_events = count(array_filter($events, function($e) { return ($e['event_status'] ?? 'Pending') == 'Completed'; }));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Bookings - Sushi Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .event-card {
            transition: transform 0.2s;
            border-left: 4px solid #dee2e6;
        }
        .event-card:hover {
            transform: translateY(-2px);
        }
        .event-card.status-pending { border-left-color: #ffc107; }
        .event-card.status-confirmed { border-left-color: #28a745; }
        .event-card.status-in-progress { border-left-color: #17a2b8; }
        .event-card.status-completed { border-left-color: #6c757d; }
        .event-card.status-cancelled { border-left-color: #dc3545; }
    </style>
</head>
<body class="bg-light">
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="dashboard_enhanced.php">
                <i class="fas fa-utensils me-2"></i>Sushi Admin
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="dashboard_enhanced.php">
                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                </a>

                <?php if (isSuperAdmin()): ?>
                <a class="nav-link" href="meals_enhanced.php">
                    <i class="fas fa-fish me-1"></i>Meals
                </a>
                <a class="nav-link" href="admin_management.php">
                    <i class="fas fa-users-cog me-1"></i>Admins
                </a>
                <?php endif; ?>

                <a class="nav-link" href="orders_management.php">
                    <i class="fas fa-shopping-cart me-1"></i>Orders
                </a>
                <a class="nav-link" href="reservations.php">
                    <i class="fas fa-calendar me-1"></i>Reservations
                </a>
                <a class="nav-link active" href="events.php">
                    <i class="fas fa-calendar-check me-1"></i>Events
                </a>

                <a class="nav-link" href="../logout.php">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <!-- Header -->
        <div class="row">
            <div class="col-12">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2><i class="fas fa-calendar-check me-2"></i>Event Bookings Management</h2>
                        <p class="text-muted mb-0">Manage and track all event bookings</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card bg-primary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-calendar-check fa-2x mb-2"></i>
                        <h3><?php echo $total_events; ?></h3>
                        <p class="mb-0">Total Events</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-warning text-dark">
                    <div class="card-body text-center">
                        <i class="fas fa-clock fa-2x mb-2"></i>
                        <h3><?php echo $pending_events; ?></h3>
                        <p class="mb-0">Pending Events</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-success text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                        <h3><?php echo $confirmed_events; ?></h3>
                        <p class="mb-0">Confirmed Events</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card bg-secondary text-white">
                    <div class="card-body text-center">
                        <i class="fas fa-flag-checkered fa-2x mb-2"></i>
                        <h3><?php echo $completed_events; ?></h3>
                        <p class="mb-0">Completed Events</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Display -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>Event Bookings
                            <span class="badge bg-primary"><?php echo count($events); ?> events</span>
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($events)): ?>
                        <div class="row">
                            <?php foreach ($events as $event): ?>
                            <div class="col-md-6 col-lg-4 mb-3">
                                <div class="card event-card status-<?php echo strtolower(str_replace(' ', '-', $event['event_status'] ?? 'pending')); ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-2">
                                            <div>
                                                <h6 class="card-title mb-1"><?php echo htmlspecialchars($event['client_name']); ?></h6>
                                                <small class="text-muted"><?php echo htmlspecialchars($event['client_phone']); ?></small>
                                                <br><small class="badge bg-info"><?php echo htmlspecialchars($event['client_type']); ?></small>
                                            </div>
                                            <span class="badge bg-<?php
                                                $status = $event['event_status'] ?? 'Pending';
                                                echo $status == 'Pending' ? 'warning' :
                                                    ($status == 'Confirmed' ? 'success' :
                                                    ($status == 'Completed' ? 'secondary' :
                                                    ($status == 'Cancelled' ? 'danger' : 'info')));
                                            ?>">
                                                <?php echo htmlspecialchars($status); ?>
                                            </span>
                                        </div>

                                        <div class="mb-2">
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fas fa-star text-warning me-2"></i>
                                                <strong><?php echo htmlspecialchars($event['EVENT_TYPE']); ?></strong>
                                            </div>
                                            <div class="d-flex align-items-center mb-1">
                                                <i class="fas fa-calendar text-primary me-2"></i>
                                                <span><?php echo date('M d, Y', strtotime($event['EVENT_DATE'])); ?></span>
                                            </div>
                                            <?php if ($event['client_email'] != 'Not registered'): ?>
                                            <div class="d-flex align-items-center">
                                                <i class="fas fa-envelope text-info me-2"></i>
                                                <small><?php echo htmlspecialchars($event['client_email']); ?></small>
                                            </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="d-flex gap-1">
                                            <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#eventModal<?php echo $event['ID_EVENT_BOOKINGS']; ?>">
                                                <i class="fas fa-eye"></i> View
                                            </button>
                                            <button class="btn btn-sm btn-outline-success" data-bs-toggle="modal" data-bs-target="#statusModal<?php echo $event['ID_EVENT_BOOKINGS']; ?>">
                                                <i class="fas fa-edit"></i> Update
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <?php else: ?>
                        <div class="text-center py-5">
                            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No event bookings found</h5>
                            <p class="text-muted">No events have been booked yet.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Include Modals -->
    <?php include 'event_modals.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

<div class="container">
    <h2>Event Bookings</h2>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Event Type</th>
                        <th>Event Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($events as $event): ?>
                    <tr>
                        <td><?php echo $event['client_name']; ?></td>
                        <td><?php echo $event['client_phone']; ?></td>
                        <td><?php echo $event['EVENT_TYPE']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($event['EVENT_DATE'])); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
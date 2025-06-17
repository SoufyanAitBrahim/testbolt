<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

// Get all reservations
$reservations = $pdo->query("
    SELECT b.*, r.NAME as restaurant_name 
    FROM BOOK_TABLE b
    JOIN RESTAURANTS r ON b.ID_RESTAURANTS = r.ID_RESTAURANTS
    ORDER BY b.TABLE_BOOK_DATE DESC
")->fetchAll();

include '../includes/header.php';
?>

<div class="container">
    <h2>Table Reservations</h2>
    
    <div class="card">
        <div class="card-body">
            <table class="table">
                <thead>
                    <tr>
                        <th>Booked On</th>
                        <th>Name</th>
                        <th>Phone</th>
                        <th>Guests</th>
                        <th>Date/Time</th>
                        <th>Restaurant</th>
                        <th>Location</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservations as $reservation): ?>
                    <tr>
                        <td><?php echo date('M d, Y', strtotime($reservation['TABLE_BOOK_DATE'])); ?></td>
                        <td><?php echo $reservation['FULLNAME']; ?></td>
                        <td><?php echo $reservation['PHONE_NUMBER']; ?></td>
                        <td><?php echo $reservation['NUMBER_OF_GUESTS']; ?></td>
                        <td><?php echo date('M d', strtotime($reservation['EVENT_DATE'])) . ' at ' . $reservation['EVENT_TIME']; ?></td>
                        <td><?php echo $reservation['restaurant_name']; ?></td>
                        <td><?php echo $reservation['RESTAURANT_LOCATION']; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
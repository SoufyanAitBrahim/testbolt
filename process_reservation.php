<?php
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $guests = intval($_POST['guests']);
    $restaurant_id = intval($_POST['restaurant']);
    $date = trim($_POST['date']);
    $time = trim($_POST['time']);
    $requests = trim($_POST['requests'] ?? '');
    
    // Get client ID if logged in
    $client_id = 0;
    if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'client') {
        $client_id = $_SESSION['user_id'];
    }
    
    // Get restaurant location
    $stmt = $pdo->prepare("SELECT ADDRESS FROM RESTAURANTS WHERE ID_RESTAURANTS = ?");
    $stmt->execute([$restaurant_id]);
    $restaurant = $stmt->fetch();
    $location = $restaurant['ADDRESS'] ?? 'Unknown';
    
    try {
        $stmt = $pdo->prepare("INSERT INTO BOOK_TABLE (ID_CLIENTS, ID_RESTAURANTS, TABLE_BOOK_DATE, FULLNAME, PHONE_NUMBER, NUMBER_OF_GUESTS, RESTAURANT_LOCATION, EVENT_DATE, EVENT_TIME, SPECIAL_REQUESTS) VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$client_id, $restaurant_id, $fullname, $phone, $guests, $location, $date, $time, $requests]);
        
        $_SESSION['success_message'] = "Reservation booked successfully! We'll confirm your booking shortly.";
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error booking reservation: " . $e->getMessage();
    }
    
    header("Location: index.php");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>
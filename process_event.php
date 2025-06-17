<?php
session_start();
include 'includes/config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $event_date = trim($_POST['event_date']);
    $event_type = trim($_POST['event_type']);
    $additional_info = trim($_POST['additional_info'] ?? '');
    
    // Validate inputs
    if (empty($fullname) || empty($phone) || empty($event_date) || empty($event_type)) {
        $_SESSION['error_message'] = "Please fill in all required fields.";
        header("Location: index.php#events");
        exit();
    }
    
    // Validate event date (must be in the future)
    if (strtotime($event_date) <= time()) {
        $_SESSION['error_message'] = "Event date must be in the future.";
        header("Location: index.php#events");
        exit();
    }
    
    // Validate event type
    $valid_event_types = ['Family Parties', 'Corporate Events', 'Educational Events'];
    if (!in_array($event_type, $valid_event_types)) {
        $_SESSION['error_message'] = "Please select a valid event type.";
        header("Location: index.php#events");
        exit();
    }
    
    try {
        // Insert into event_bookings table
        $stmt = $pdo->prepare("INSERT INTO event_bookings (FULLNAME, PHONE_NUMBER, EVENT_TYPE, EVENT_DATE) VALUES (?, ?, ?, ?)");
        $stmt->execute([$fullname, $phone, $event_type, $event_date]);
        
        $event_id = $pdo->lastInsertId();
        
        // If user is logged in as client, also create BOOK_EVENT record
        if (isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'client') {
            $client_id = $_SESSION['user_id'];
            $stmt = $pdo->prepare("INSERT INTO BOOK_EVENT (ID_EVENT_BOOKINGS, ID_CLIENTS, EVENT_BOOK_DATE) VALUES (?, ?, NOW())");
            $stmt->execute([$event_id, $client_id]);
        }
        
        $_SESSION['success_message'] = "Event booking submitted successfully! We'll contact you within 24 hours to confirm your event details.";
        
        // Log the event booking for admin notification
        error_log("New event booking: $fullname ($phone) - $event_type on $event_date");
        
    } catch (PDOException $e) {
        $_SESSION['error_message'] = "Error booking event: " . $e->getMessage();
        error_log("Event booking error: " . $e->getMessage());
    }
    
    header("Location: index.php#events");
    exit();
} else {
    header("Location: index.php");
    exit();
}
?>

<?php
include 'includes/config.php';

// Enable error logging for debugging
error_reporting(E_ALL);
ini_set('log_errors', 1);

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] != 'POST') {
    echo json_encode(['success' => false, 'message' => 'Only POST requests allowed']);
    exit();
}

if (!isset($_POST['action']) || $_POST['action'] != 'checkout') {
    echo json_encode(['success' => false, 'message' => 'Invalid action. Expected: checkout, Got: ' . ($_POST['action'] ?? 'none')]);
    exit();
}

try {
    // Get form data
    $fullname = trim($_POST['fullname']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $cart_items = json_decode($_POST['cart_items'], true);
    $total_amount = floatval($_POST['total_amount']);
    
    // Validate required fields
    if (empty($fullname) || empty($phone) || empty($address)) {
        echo json_encode(['success' => false, 'message' => 'Please fill in all required fields']);
        exit();
    }
    
    if (empty($cart_items)) {
        echo json_encode(['success' => false, 'message' => 'Cart is empty']);
        exit();
    }
    
    // Validate phone number (basic validation)
    if (!preg_match('/^[\d\s\-\+\(\)]+$/', $phone)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid phone number']);
        exit();
    }
    
    // Validate email if provided
    if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['success' => false, 'message' => 'Please enter a valid email address']);
        exit();
    }
    
    $pdo->beginTransaction();
    
    // Check if client exists or create new one
    $client_id = null;
    
    // First, try to find existing client by phone number
    $stmt = $pdo->prepare("SELECT ID_CLIENTS FROM CLIENTS WHERE PHONE_NUMBER = ?");
    $stmt->execute([$phone]);
    $existing_client = $stmt->fetch();
    
    if ($existing_client) {
        // Client exists, update their information
        $client_id = $existing_client['ID_CLIENTS'];
        $stmt = $pdo->prepare("
            UPDATE CLIENTS 
            SET FULLNAME = ?, EMAIL = ? 
            WHERE ID_CLIENTS = ?
        ");
        $stmt->execute([$fullname, $email, $client_id]);
    } else {
        // Create new client
        $stmt = $pdo->prepare("
            INSERT INTO CLIENTS (FULLNAME, PHONE_NUMBER, EMAIL) 
            VALUES (?, ?, ?)
        ");
        $stmt->execute([$fullname, $phone, $email]);
        $client_id = $pdo->lastInsertId();
    }
    
    // Insert order items into ORDER table
    $stmt = $pdo->prepare("
        INSERT INTO `ORDER`
        (ID_CLIENTS, ID_MEALS, ORDER_TYPE, ORDER_SITUATION, PAYMENT_SITUATION, DATE_ORDER, total_amount, delivery_address, quantity)
        VALUES (?, ?, 1, 'pending', 0, NOW(), ?, ?, ?)
        ON DUPLICATE KEY UPDATE
        quantity = quantity + VALUES(quantity),
        total_amount = VALUES(total_amount),
        delivery_address = VALUES(delivery_address),
        DATE_ORDER = NOW()
    ");

    $order_timestamp = date('Y-m-d H:i:s');
    $first_meal_id = null;

    foreach ($cart_items as $item) {
        if ($first_meal_id === null) {
            $first_meal_id = $item['id'];
        }

        $stmt->execute([
            $client_id,
            $item['id'],
            $total_amount, // Store total amount with each item
            $address,
            $item['quantity']
        ]);
    }

    // Create a unique order identifier using client_id and timestamp
    $order_id = $client_id . '_' . strtotime($order_timestamp);
    
    $pdo->commit();
    
    // Clear the cart
    $_SESSION['cart'] = [];
    
    echo json_encode([
        'success' => true, 
        'order_id' => $order_id,
        'message' => 'Order placed successfully!'
    ]);
    
} catch (PDOException $e) {
    $pdo->rollBack();
    error_log("Order processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Database error. Please try again.']);
} catch (Exception $e) {
    if (isset($pdo)) {
        $pdo->rollBack();
    }
    error_log("Order processing error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred. Please try again.']);
}
?>

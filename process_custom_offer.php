<?php
// Prevent any output before JSON response
ob_start();
error_reporting(E_ERROR | E_PARSE); // Only show critical errors

header('Content-Type: application/json');
include 'includes/config.php';
include 'includes/discount_functions.php';

// Clear any unwanted output
ob_clean();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['action']) || $_POST['action'] !== 'create_offer') {
        throw new Exception('Invalid action');
    }

    // Validate input
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $meals_json = $_POST['meals'] ?? '';
    $total_price = floatval($_POST['total_price'] ?? 0);
    $discount_percentage = intval($_POST['discount_percentage'] ?? 0);
    $final_price = floatval($_POST['final_price'] ?? 0);

    if (empty($customer_name) || empty($customer_phone)) {
        throw new Exception('Customer name and phone are required');
    }

    if (empty($meals_json)) {
        throw new Exception('No meals selected');
    }

    $selected_meals = json_decode($meals_json, true);
    if (!is_array($selected_meals) || count($selected_meals) < 4) {
        throw new Exception('Minimum 4 meals required');
    }

    // Validate meals exist and calculate actual total
    $actual_total = 0;
    $valid_meal_ids = [];
    
    foreach ($selected_meals as $meal) {
        if (!isset($meal['id']) || !isset($meal['price'])) {
            throw new Exception('Invalid meal data');
        }
        
        // Verify meal exists in database
        $stmt = $pdo->prepare("SELECT ID_MEALS, PRICE FROM MEALS WHERE ID_MEALS = ?");
        $stmt->execute([$meal['id']]);
        $db_meal = $stmt->fetch();
        
        if (!$db_meal) {
            throw new Exception('Invalid meal selected: ' . $meal['id']);
        }
        
        // Use database price for security
        $actual_total += $db_meal['PRICE'];
        $valid_meal_ids[] = $meal['id'];
    }

    // Recalculate discount based on actual total using database rules
    $discount_info = calculateDiscount($actual_total, $pdo);
    $actual_discount_percentage = $discount_info['discount_percentage'];
    $actual_discount_amount = $discount_info['discount_amount'];
    $actual_final_price = $discount_info['final_price'];

    // Start transaction
    $pdo->beginTransaction();

    // Insert into CLIENTS_OFFERS (simple structure)
    $stmt = $pdo->prepare("
        INSERT INTO CLIENTS_OFFERS
        (total_price, status, created_date, client_name, client_phone)
        VALUES (?, 'pending', NOW(), ?, ?)
    ");

    $stmt->execute([
        $actual_total,
        $customer_name,
        $customer_phone
    ]);

    $offer_id = $pdo->lastInsertId();

    // Auto-register the client (they don't know they're being registered)
    $client_id = null;

    // Check if client already exists by phone number
    $stmt = $pdo->prepare("SELECT ID_CLIENTS FROM CLIENTS WHERE PHONE_NUMBER = ?");
    $stmt->execute([$customer_phone]);
    $existing_client = $stmt->fetch();

    if ($existing_client) {
        // Client already exists, use their ID
        $client_id = $existing_client['ID_CLIENTS'];
    } else {
        // Auto-create new client account (seamless registration)
        $final_email = !empty($customer_email) ? $customer_email : 'auto_' . time() . '_' . substr(md5($customer_phone), 0, 8) . '@sushi-customer.com';
        $auto_password = password_hash('temp_' . time(), PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO CLIENTS (FULLNAME, PHONE_NUMBER, EMAIL, PASSWORD, LOCATION)
            VALUES (?, ?, ?, ?, 'Auto-registered via Custom Offer')
        ");
        $stmt->execute([$customer_name, $customer_phone, $final_email, $auto_password]);
        $client_id = $pdo->lastInsertId();
    }

    // Insert selected meals into CLIENT_CUSTOMIZED_OFFERS
    $stmt = $pdo->prepare("
        INSERT INTO CLIENT_CUSTOMIZED_OFFERS
        (ID_CLIENTS_OFFERS, ID_MEALS, ID_CLIENTS, OFFERS_CLIENT_DATE, quantity)
        VALUES (?, ?, ?, NOW(), 1)
    ");

    foreach ($valid_meal_ids as $meal_id) {
        $stmt->execute([$offer_id, $meal_id, $client_id]);
    }

    // Commit transaction
    $pdo->commit();

    // Calculate discount using the new function (already included at top)
    $discount_info = calculateDiscount($actual_total, $pdo);

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Custom offer created successfully',
        'offer_id' => $offer_id,
        'total_price' => $actual_total,
        'discount_percentage' => $discount_info['discount_percentage'],
        'final_price' => $discount_info['final_price'],
        'discount_amount' => $discount_info['discount_amount']
    ]);

} catch (Exception $e) {
    // Rollback transaction if it was started
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    // Rollback transaction if it was started
    if ($pdo->inTransaction()) {
        $pdo->rollback();
    }
    
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

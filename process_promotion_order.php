<?php
header('Content-Type: application/json');
include 'includes/config.php';

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    if (!isset($_POST['action']) || $_POST['action'] !== 'order_promotion') {
        throw new Exception('Invalid action');
    }

    // Validate input
    $offer_id = intval($_POST['offer_id'] ?? 0);
    $customer_name = trim($_POST['customer_name'] ?? '');
    $customer_phone = trim($_POST['customer_phone'] ?? '');
    $customer_email = trim($_POST['customer_email'] ?? '');
    $special_instructions = trim($_POST['special_instructions'] ?? '');

    if ($offer_id <= 0) {
        throw new Exception('Invalid offer selected');
    }

    if (empty($customer_name) || empty($customer_phone)) {
        throw new Exception('Customer name and phone are required');
    }

    // Get offer details
    $stmt = $pdo->prepare("
        SELECT 
            ao.*,
            COALESCE(ao.original_price, ao.OFFERS_PRICE * 1.3) as calculated_original_price
        FROM ADMINS_OFFERS ao 
        WHERE ao.ID_ADMINS_OFFERS = ? AND (ao.status = 'active' OR ao.status IS NULL)
    ");
    $stmt->execute([$offer_id]);
    $offer = $stmt->fetch();

    if (!$offer) {
        throw new Exception('Offer not found or no longer available');
    }

    // Calculate pricing
    $order_total = $offer['OFFERS_PRICE'];
    $original_price = $offer['calculated_original_price'];
    $savings_amount = $original_price - $order_total;

    // Start transaction
    $pdo->beginTransaction();

    // Auto-register client (same approach as custom offers)
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
        $final_email = !empty($customer_email) ? $customer_email : 'promo_' . time() . '_' . substr(md5($customer_phone), 0, 8) . '@sushi-customer.com';
        $auto_password = password_hash('temp_' . time(), PASSWORD_DEFAULT);

        $stmt = $pdo->prepare("
            INSERT INTO CLIENTS (FULLNAME, PHONE_NUMBER, EMAIL, PASSWORD, LOCATION)
            VALUES (?, ?, ?, ?, 'Auto-registered via Promotion Order')
        ");
        $stmt->execute([$customer_name, $customer_phone, $final_email, $auto_password]);
        $client_id = $pdo->lastInsertId();
    }

    // Get all meals included in this offer
    $stmt = $pdo->prepare("
        SELECT ID_MEALS
        FROM ADMIN_CUSTOMIZED_OFFERS
        WHERE ID_ADMINS_OFFERS = ?
    ");
    $stmt->execute([$offer_id]);
    $offer_meals = $stmt->fetchAll();

    if (empty($offer_meals)) {
        throw new Exception('No meals found for this offer');
    }

    // Get the admin who created this offer
    $admin_stmt = $pdo->prepare("
        SELECT DISTINCT ID_ADMINS
        FROM ADMIN_CUSTOMIZED_OFFERS
        WHERE ID_ADMINS_OFFERS = ? AND ID_CLIENTS = 0
        LIMIT 1
    ");
    $admin_stmt->execute([$offer_id]);
    $admin_result = $admin_stmt->fetch();
    $offer_admin_id = $admin_result ? $admin_result['ID_ADMINS'] : 1;

    // Update existing records to set client ID and order date
    // This changes ID_CLIENTS from 0 to actual client ID and updates OFFERS_ADMIN_DATE
    $stmt = $pdo->prepare("
        UPDATE ADMIN_CUSTOMIZED_OFFERS
        SET ID_CLIENTS = ?, OFFERS_ADMIN_DATE = NOW()
        WHERE ID_ADMINS_OFFERS = ? AND ID_ADMINS = ? AND ID_CLIENTS = 0
    ");
    $stmt->execute([$client_id, $offer_id, $offer_admin_id]);

    // Commit transaction
    $pdo->commit();
    $order_id = $client_id; // Use client ID as order reference

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Order placed successfully!',
        'order_id' => $order_id,
        'offer_name' => $offer['ADMINS_OFFERS_NAME'],
        'order_total' => $order_total,
        'savings' => $savings_amount
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} catch (PDOException $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>

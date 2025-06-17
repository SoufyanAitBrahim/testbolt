<?php
include '../includes/config.php';
include '../includes/auth.php';
redirectIfNotAdmin();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $order_id = intval($_POST['order_id']);
    $status = $_POST['status'];
    
    $valid_statuses = ['pending', 'confirmed', 'preparing', 'ready', 'completed', 'cancelled'];
    
    if (in_array($status, $valid_statuses)) {
        try {
            $stmt = $pdo->prepare("UPDATE PROMOTION_ORDERS SET order_status = ? WHERE ID_PROMOTION_ORDER = ?");
            $stmt->execute([$status, $order_id]);
            
            $_SESSION['success_message'] = "Order status updated successfully!";
        } catch (PDOException $e) {
            $_SESSION['error_message'] = "Error updating order status: " . $e->getMessage();
        }
    } else {
        $_SESSION['error_message'] = "Invalid status selected!";
    }
}

header("Location: dashboard_enhanced.php");
exit();
?>

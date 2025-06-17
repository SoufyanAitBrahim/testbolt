<?php
/**
 * Discount Functions for Client Offers
 * Uses DISCOUNT_RULES table to calculate discounts
 */

/**
 * Simple discount calculation function
 * Checks total_price against DISCOUNT_RULES table and applies highest applicable discount
 * @param float $total_price The total price from CLIENTS_OFFERS
 * @param PDO $pdo Database connection
 * @return array ['discount_percentage' => int, 'discount_amount' => float, 'final_price' => float]
 */
function calculateDiscount($total_price, $pdo) {
    try {
        // Get the highest applicable discount rule
        $rule = $pdo->query("
            SELECT * FROM DISCOUNT_RULES
            WHERE status = 'active' AND min_price <= $total_price
            ORDER BY min_price DESC
            LIMIT 1
        ")->fetch();

        if ($rule) {
            // Apply discount
            $discount_percentage = $rule['discount_percentage'];
            $discount_amount = $total_price * ($discount_percentage / 100);
            $final_price = $total_price - $discount_amount;

            return [
                'discount_percentage' => $discount_percentage,
                'discount_amount' => round($discount_amount, 2),
                'final_price' => round($final_price, 2)
            ];
        } else {
            // No discount
            return [
                'discount_percentage' => 0,
                'discount_amount' => 0.00,
                'final_price' => $total_price
            ];
        }
    } catch (PDOException $e) {
        // Error - no discount
        return [
            'discount_percentage' => 0,
            'discount_amount' => 0.00,
            'final_price' => $total_price
        ];
    }
}

/**
 * Get all active discount rules for display
 * @param PDO $pdo Database connection
 * @return array Array of active discount rules
 */
function getActiveDiscountRules($pdo) {
    try {
        return $pdo->query("
            SELECT * FROM DISCOUNT_RULES 
            WHERE status = 'active' 
            ORDER BY min_price ASC
        ")->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

/**
 * Get discount preview for a given price
 * @param float $total_price The price to check
 * @param PDO $pdo Database connection
 * @return string HTML preview of applicable discount
 */
function getDiscountPreview($total_price, $pdo) {
    $discount_info = calculateDiscount($total_price, $pdo);
    
    if ($discount_info['discount_percentage'] > 0) {
        return "<span class='text-success'>
            <strong>{$discount_info['rule_name']}</strong>: {$discount_info['discount_percentage']}% discount
            <br>Save: $" . number_format($discount_info['discount_amount'], 2) . "
            <br>Final Price: $" . number_format($discount_info['final_price'], 2) . "
        </span>";
    } else {
        return "<span class='text-muted'>No discount applicable</span>";
    }
}

/**
 * Create or update DISCOUNT_RULES table
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function createDiscountRulesTable($pdo) {
    try {
        $pdo->exec("CREATE TABLE IF NOT EXISTS DISCOUNT_RULES (
            id INT AUTO_INCREMENT PRIMARY KEY,
            min_price DECIMAL(10,2) NOT NULL,
            discount_percentage INT NOT NULL,
            rule_name VARCHAR(255) DEFAULT NULL,
            description TEXT DEFAULT NULL,
            created_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            status ENUM('active', 'inactive') DEFAULT 'active',
            UNIQUE KEY unique_price (min_price)
        ) ENGINE=InnoDB");
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

/**
 * Add default discount rules if table is empty
 * @param PDO $pdo Database connection
 * @return bool Success status
 */
function addDefaultDiscountRules($pdo) {
    try {
        // Check if rules already exist
        $count = $pdo->query("SELECT COUNT(*) FROM DISCOUNT_RULES")->fetchColumn();
        
        if ($count == 0) {
            $default_rules = [
                [50.00, 25, 'Premium Discount', 'High value orders get premium discount'],
                [30.00, 15, 'Standard Plus Discount', 'Medium-high value orders'],
                [20.00, 10, 'Standard Discount', 'Standard discount for qualifying orders']
            ];
            
            $stmt = $pdo->prepare("INSERT INTO DISCOUNT_RULES (min_price, discount_percentage, rule_name, description) VALUES (?, ?, ?, ?)");
            
            foreach ($default_rules as $rule) {
                $stmt->execute($rule);
            }
            
            return true;
        }
        
        return true;
    } catch (PDOException $e) {
        return false;
    }
}
?>

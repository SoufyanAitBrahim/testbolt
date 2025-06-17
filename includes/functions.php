<?php
function getCategories($pdo) {
    try {
        return $pdo->query("SELECT * FROM CATEGORIES")->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getMeals($pdo, $category_id = null) {
    try {
        if ($category_id) {
            $stmt = $pdo->prepare("SELECT * FROM MEALS WHERE ID_CATEGORIES = ?");
            $stmt->execute([$category_id]);
            return $stmt->fetchAll();
        } else {
            return $pdo->query("SELECT * FROM MEALS")->fetchAll();
        }
    } catch (PDOException $e) {
        return [];
    }
}

function getOffers($pdo) {
    try {
        return $pdo->query("
            SELECT m.*, o.OFFERS_PRICE as discount_price
            FROM MEALS m
            JOIN ADMIN_CUSTOMIZED_OFFERS a ON m.ID_MEALS = a.ID_MEALS
            JOIN ADMINS_OFFERS o ON a.ID_ADMINS_OFFERS = o.ID_ADMINS_OFFERS
        ")->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}

function getRestaurants($pdo) {
    try {
        return $pdo->query("SELECT * FROM RESTAURANTS")->fetchAll();
    } catch (PDOException $e) {
        return [];
    }
}
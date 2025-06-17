<?php
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin';
}

function isSuperAdmin() {
    return isAdmin() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 1;
}

function isSecondaryAdmin() {
    return isAdmin() && isset($_SESSION['admin_role']) && $_SESSION['admin_role'] == 2;
}

function getAdminRole() {
    return isset($_SESSION['admin_role']) ? $_SESSION['admin_role'] : null;
}

function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        header("Location: ../login.php");
        exit();
    }
}

function redirectIfNotAdmin() {
    redirectIfNotLoggedIn();
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function redirectIfNotSuperAdmin() {
    redirectIfNotAdmin();
    if (!isSuperAdmin()) {
        header("Location: dashboard.php?error=access_denied");
        exit();
    }
}

function hasPermission($permission) {
    if (!isAdmin()) return false;

    $role = getAdminRole();

    // Super Admin (role 1) has all permissions
    if ($role == 1) return true;

    // Secondary Admin (role 2) permissions
    if ($role == 2) {
        $allowedPermissions = [
            'view_orders',
            'view_reservations',
            'view_statistics',
            'view_dashboard'
        ];
        return in_array($permission, $allowedPermissions);
    }

    return false;
}
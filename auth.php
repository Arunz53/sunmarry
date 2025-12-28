<?php
require_once 'db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure required user columns exist to avoid runtime errors in permission checks
try {
    $pdo = getDB();
    $colStmt = $pdo->query("SHOW COLUMNS FROM users");
    $cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'Field');

    if (!in_array('role', $colNames)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN role ENUM('super_admin', 'manager', 'support') NOT NULL DEFAULT 'support'");
    }
    if (!in_array('credits', $colNames)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN credits INT DEFAULT 10");
    }
    if (!in_array('profiles_viewed', $colNames)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN profiles_viewed INT DEFAULT 0");
    }
    if (!in_array('last_login', $colNames)) {
        $pdo->exec("ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL");
    }
} catch (PDOException $e) {
    // If we can't modify schema (no privileges or table missing), continue gracefully.
}

// Authentication Functions
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function getUserRole() {
    return $_SESSION['role'] ?? null;
}

function checkPermission($required_role) {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }

    $role_hierarchy = [
        'super_admin' => 3,
        'manager' => 2,
        'support' => 1
    ];

    $user_role = getUserRole();
    
    if (!isset($role_hierarchy[$user_role]) || 
        $role_hierarchy[$user_role] < $role_hierarchy[$required_role]) {
        header('Location: access_denied.php');
        exit();
    }
    
    // Check credits for support role
    if ($user_role === 'support') {
        $pdo = getDB();
        $stmt = $pdo->prepare("SELECT credits, profiles_viewed FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user['profiles_viewed'] >= 10) {
            session_destroy();
            header('Location: login.php?error=credits_expired');
            exit();
        }
    }
    
    return true;
}

function incrementProfileViews() {
    if (getUserRole() === 'support') {
        $pdo = getDB();
        $pdo->prepare("UPDATE users SET profiles_viewed = profiles_viewed + 1 WHERE id = ?")->execute([$_SESSION['user_id']]);
    }
}
?>
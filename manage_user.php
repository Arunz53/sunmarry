<?php
require_once 'db.php';
require_once 'auth.php';

// Ensure only super admin can access
checkPermission('super_admin');

function generateRandomPassword() {
    return substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*'), 0, 12);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'add') {
        $username = trim($_POST['username']);
        $password = trim($_POST['password']);
        $role = $_POST['role'];

        // Validate role
        if (!in_array($role, ['manager', 'support'])) {
            die('Invalid role specified');
        }

        // Check if username exists
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetchColumn() > 0) {
            die('Username already exists');
        }

        // Add new user
        $stmt = $pdo->prepare("INSERT INTO users (username, password, role) VALUES (?, ?, ?)");
        $stmt->execute([$username, md5($password), $role]);

        header('Location: admin_dashboard.php?success=created');
        exit();
    }
}

if (isset($_GET['action'])) {
    $userId = $_GET['id'] ?? 0;

    // Verify user exists and is not super_admin
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role != 'super_admin'");
    $stmt->execute([$userId]);
    $user = $stmt->fetch();

    if (!$user) {
        die('Invalid user');
    }

    switch ($_GET['action']) {
        case 'reset':
            $newPassword = generateRandomPassword();
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([md5($newPassword), $userId]);
            
            // Show the new password to the admin
            echo "New password for {$user['username']}: {$newPassword}";
            echo "<br><a href='admin_dashboard.php'>Back to Dashboard</a>";
            exit();

        case 'delete':
            // Don't allow deleting if it's the last manager
            if ($user['role'] === 'manager') {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'manager'");
                $stmt->execute();
                if ($stmt->fetchColumn() <= 1) {
                    die('Cannot delete the last manager account');
                }
            }

            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            
            header('Location: admin_dashboard.php?success=deleted');
            exit();
    }
}

// If we get here, something went wrong
header('Location: admin_dashboard.php?error=invalid_action');
exit();
?>
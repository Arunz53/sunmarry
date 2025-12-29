<?php
require_once 'db.php';

try {
    $pdo = getDB();
    
    // Check if phone column exists in users table
    $colStmt = $pdo->query("SHOW COLUMNS FROM users");
    $cols = $colStmt->fetchAll(PDO::FETCH_ASSOC);
    $colNames = array_column($cols, 'Field');
    
    if (!in_array('phone', $colNames)) {
        // Add phone column if it doesn't exist
        $pdo->exec("ALTER TABLE users ADD COLUMN phone VARCHAR(20) DEFAULT NULL");
        echo "✓ Phone column added to users table successfully<br>";
    } else {
        echo "✓ Phone column already exists in users table<br>";
    }
    
    if (!in_array('created_at', $colNames)) {
        // Add created_at column if it doesn't exist
        $pdo->exec("ALTER TABLE users ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP");
        echo "✓ Created_at column added to users table successfully<br>";
    } else {
        echo "✓ Created_at column already exists in users table<br>";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>


<?php
// Diagnostic: show current database and columns of profiles table
require_once 'db.php'; // provides $pdo

try {
    $dbRow = $pdo->query("SELECT DATABASE() AS db")->fetch();
    $dbName = $dbRow['db'] ?? '(unknown)';
    echo "Current database: " . htmlspecialchars($dbName) . "\n\n";

    $q = $pdo->query("SHOW COLUMNS FROM profiles");
    $cols = $q->fetchAll(PDO::FETCH_ASSOC);
    if (!$cols) {
        echo "Table `profiles` not found in database $dbName\n";
        exit(1);
    }

    echo "Columns in profiles table:\n";
    foreach ($cols as $c) {
        echo "- " . $c['Field'] . " (" . $c['Type'] . ")" . (strtoupper($c['Null']) === 'NO' ? ' NOT NULL' : '') . "\n";
    }

    echo "\nSHOW CREATE TABLE profiles:\n";
    $r = $pdo->query("SHOW CREATE TABLE profiles")->fetch(PDO::FETCH_ASSOC);
    echo $r['Create Table'] . "\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

?>
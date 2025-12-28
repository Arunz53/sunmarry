<?php
/**
 * Migration helper: checks `profiles` table columns and adds any missing
 * columns required by the current schema (based on database.sql).
 *
 * Usage: run once from command line or via browser (recommended CLI):
 * php migrate_profiles_add_columns.php
 */
require_once 'db.php'; // provides $pdo

$desired = [
    'name' => 'VARCHAR(255) NOT NULL',
    'age' => 'INT NOT NULL',
    'marriage_type' => 'VARCHAR(50) NOT NULL',
    'gender' => 'VARCHAR(10) NOT NULL',
    'district' => 'VARCHAR(100) NOT NULL',
    'city' => 'VARCHAR(100) NOT NULL',
    'caste' => 'VARCHAR(100)',
    'subcaste' => 'VARCHAR(255)',
    'nakshatram' => 'VARCHAR(100)',
    'rasi' => 'VARCHAR(100)',
    'religion' => 'VARCHAR(50)',
    'kulam' => 'VARCHAR(150)',
    'education_type' => 'VARCHAR(100)',
    'brothers_total' => 'INT DEFAULT 0',
    'brothers_married' => 'INT DEFAULT 0',
    'sisters_total' => 'INT DEFAULT 0',
    'sisters_married' => 'INT DEFAULT 0',
    'father_name' => 'VARCHAR(255)',
    'mother_name' => 'VARCHAR(255)',
    'birth_date' => 'DATE',
    'birth_time' => 'VARCHAR(10)',
    'profession' => 'VARCHAR(150)',
    'phone_primary' => 'VARCHAR(20)',
    'phone_secondary' => 'VARCHAR(20)',
    'phone_tertiary' => 'VARCHAR(20)',
    'profile_photo' => 'VARCHAR(255)',
    'file_upload' => 'VARCHAR(255)',
    'deleted_at' => 'DATETIME NULL',
    'created_at' => 'DATETIME DEFAULT CURRENT_TIMESTAMP'
];

try {
    // Get current database name used by PDO connection
    $stmt = $pdo->query("SELECT DATABASE() AS db");
    $row = $stmt->fetch();
    $databaseName = $row['db'] ?? null;
    if (!$databaseName) {
        throw new Exception('Unable to determine current database name.');
    }

    // Get existing columns for profiles
    $colStmt = $pdo->prepare("SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA = :db AND TABLE_NAME = 'profiles'");
    $colStmt->execute([':db' => $databaseName]);
    $existing = $colStmt->fetchAll(PDO::FETCH_COLUMN);
    $existing = array_map('strtolower', $existing);

    $toAdd = [];
    foreach ($desired as $col => $def) {
        if (!in_array(strtolower($col), $existing)) {
            $toAdd[$col] = $def;
        }
    }

    if (empty($toAdd)) {
        echo "No missing columns detected. profiles table is up to date." . PHP_EOL;
        exit(0);
    }

    foreach ($toAdd as $col => $def) {
        $sql = "ALTER TABLE profiles ADD COLUMN `$col` $def";
        echo "Adding column $col... ";
        try {
            $pdo->exec($sql);
            echo "OK" . PHP_EOL;
        } catch (PDOException $e) {
            echo "FAILED: " . $e->getMessage() . PHP_EOL;
        }
    }

    echo "Migration completed." . PHP_EOL;
} catch (Exception $e) {
    echo "Migration error: " . $e->getMessage() . PHP_EOL;
    exit(1);
}

?>
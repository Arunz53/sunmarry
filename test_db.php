<?php
require 'db.php';
if (isset($pdo)) {
    $res = $pdo->query("SHOW TABLES");
    echo "Connected (PDO). Tables:<br>";
    foreach ($res as $row) echo htmlspecialchars(current($row)) . "<br>";
} else {
    echo "No DB object found — check require path.";
}

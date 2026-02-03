<?php
include 'backend/db.php';
$stmt = $pdo->query("DESCRIBE drivers");
while ($row = $stmt->fetch()) {
    echo $row['Field'] . " - " . $row['Type'] . "\n";
}
?>
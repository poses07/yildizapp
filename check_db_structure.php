<?php
require_once 'backend/db.php';
try {
    $stmt = $pdo->query("DESCRIBE drivers");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
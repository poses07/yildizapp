<?php
require_once 'backend/db.php';
try {
    echo "USERS Table:\n";
    $stmt = $pdo->query("DESCRIBE users");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
    
    echo "\nDRIVERS Table:\n";
    $stmt = $pdo->query("DESCRIBE drivers");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
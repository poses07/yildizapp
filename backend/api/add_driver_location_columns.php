<?php
require_once __DIR__ . '/../db.php';

try {
    // Check if latitude column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'latitude'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE drivers ADD COLUMN latitude DECIMAL(10, 8) DEFAULT NULL");
        echo "Added latitude column.\n";
    } else {
        echo "latitude column already exists.\n";
    }

    // Check if longitude column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'longitude'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE drivers ADD COLUMN longitude DECIMAL(11, 8) DEFAULT NULL");
        echo "Added longitude column.\n";
    } else {
        echo "longitude column already exists.\n";
    }
    
    echo "Driver location columns check complete.";
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

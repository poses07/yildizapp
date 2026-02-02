<?php
require_once __DIR__ . '/../db.php';

try {
    $sql = "SHOW COLUMNS FROM drivers LIKE 'profile_photo'";
    $stmt = $pdo->query($sql);
    $exists = $stmt->fetch();

    if (!$exists) {
        $sql = "ALTER TABLE drivers ADD COLUMN profile_photo VARCHAR(255) DEFAULT NULL";
        $pdo->exec($sql);
        echo "profile_photo column added successfully to drivers table.";
    } else {
        echo "profile_photo column already exists in drivers table.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

<?php
require_once 'backend/db.php';

try {
    // Check if status column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM drivers LIKE 'status'");
    $column = $stmt->fetch();

    if (!$column) {
        $pdo->exec("ALTER TABLE drivers ADD COLUMN status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'");
        echo "Added status column to drivers table.\n";
    } else {
        echo "Status column already exists.\n";
    }

    // Also check if users table has phone column (it should based on previous code)
    // But let's make sure we have everything needed.
    
    echo "Database update completed successfully.";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

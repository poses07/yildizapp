<?php
require_once __DIR__ . '/../db.php';

try {
    // Check if column exists
    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'verification_code'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN verification_code VARCHAR(10) DEFAULT NULL");
        echo "Added verification_code column.\n";
    } else {
        echo "verification_code column already exists.\n";
    }

    $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'is_verified'");
    $exists = $stmt->fetch();

    if (!$exists) {
        $pdo->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
        echo "Added is_verified column.\n";
    } else {
        echo "is_verified column already exists.\n";
    }
    
    // Make sure name column is long enough (it should be, but just in case)
    // users table usually has name, phone, status, created_at
    
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
<?php
require_once 'backend/db.php';
try {
    $pdo->exec("UPDATE drivers SET status = 'approved'");
    echo "All existing drivers set to approved.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>

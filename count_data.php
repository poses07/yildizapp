<?php
include 'backend/db.php';
$count = $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
echo "Driver count: " . $count . "\n";

$users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
echo "User count: " . $users . "\n";
?>
<?php
include 'backend/db.php';

echo "=== DRIVER LOCATIONS ===\n";
$stmt = $pdo->query("SELECT id, full_name, latitude, longitude, last_update FROM drivers");
while ($row = $stmt->fetch()) {
    echo "Driver {$row['id']} ({$row['full_name']}): {$row['latitude']}, {$row['longitude']} (Last Update: {$row['last_update']})\n";
}

echo "\n=== RECENT BOOKINGS ===\n";
$stmt = $pdo->query("SELECT id, pickup_address, pickup_lat, pickup_lng, status FROM bookings ORDER BY id DESC LIMIT 5");
while ($row = $stmt->fetch()) {
    echo "Booking {$row['id']} ({$row['status']}): {$row['pickup_lat']}, {$row['pickup_lng']} - {$row['pickup_address']}\n";
}
?>
<?php
require_once __DIR__ . '/../db.php';

try {
    // Create booking_messages table
    $sqlMessages = "CREATE TABLE IF NOT EXISTS booking_messages (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        sender_id INT NULL,
        receiver_id INT NULL,
        sender_type ENUM('user', 'driver', 'admin') NOT NULL,
        message TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlMessages);
    echo "Table 'booking_messages' created successfully.<br>";

    // Create booking_ratings table
    $sqlRatings = "CREATE TABLE IF NOT EXISTS booking_ratings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        booking_id INT NOT NULL,
        user_id INT NOT NULL,
        driver_id INT NOT NULL,
        rating INT NOT NULL,
        comment TEXT,
        tags TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE CASCADE
    )";
    $pdo->exec($sqlRatings);
    echo "Table 'booking_ratings' created successfully.<br>";

} catch (PDOException $e) {
    die("Error creating tables: " . $e->getMessage());
}
?>

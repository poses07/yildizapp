<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        driver_id INT NULL,
        pickup_address VARCHAR(255) NOT NULL,
        dropoff_address VARCHAR(255) NOT NULL,
        pickup_lat DECIMAL(10, 8) NOT NULL,
        pickup_lng DECIMAL(11, 8) NOT NULL,
        dropoff_lat DECIMAL(10, 8) NOT NULL,
        dropoff_lng DECIMAL(11, 8) NOT NULL,
        status ENUM('pending', 'accepted', 'on_way', 'completed', 'cancelled') DEFAULT 'pending',
        price DECIMAL(10, 2) DEFAULT 0.00,
        distance_km DECIMAL(10, 2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (driver_id) REFERENCES drivers(id) ON DELETE SET NULL
    )";

    $pdo->exec($sql);
    echo json_encode(["success" => true, "message" => "Bookings table created successfully."]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
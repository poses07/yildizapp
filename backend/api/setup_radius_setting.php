<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';

try {
    // Create app_settings table if not exists
    $sql = "CREATE TABLE IF NOT EXISTS app_settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        setting_key VARCHAR(50) NOT NULL UNIQUE,
        setting_value TEXT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);

    // Insert driver_search_radius default value if not exists
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM app_settings WHERE setting_key = 'driver_search_radius'");
    $stmt->execute();
    $exists = $stmt->fetchColumn();

    if (!$exists) {
        $sql = "INSERT INTO app_settings (setting_key, setting_value) VALUES ('driver_search_radius', '5')"; // Default 5 km
        $pdo->exec($sql);
        echo json_encode(["success" => true, "message" => "Settings table checked and driver_search_radius initialized to 5km."]);
    } else {
        echo json_encode(["success" => true, "message" => "Settings table exists and driver_search_radius is already set."]);
    }

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>

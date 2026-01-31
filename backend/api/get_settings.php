<?php
header('Content-Type: application/json');
require_once '../db.php';

try {
    $stmt = $pdo->query("SELECT setting_key, setting_value FROM app_settings");
    $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
    
    // Cast numeric values
    foreach ($settings as $key => $value) {
        if (is_numeric($value)) {
            $settings[$key] = (float)$value;
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $settings
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>

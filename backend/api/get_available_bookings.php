<?php
ob_start();
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db.php';

// Log request for debugging
$logFile = __DIR__ . '/../api_debug.log';
$logMessage = date('Y-m-d H:i:s') . " - Request: " . print_r($_GET, true) . "\n";
file_put_contents($logFile, $logMessage, FILE_APPEND);

try {
    // NUCLEAR OPTION: Return ALL pending bookings regardless of location/radius
    // We are stripping out the distance calculation temporarily to ensure data delivery.
    
    $sql = "SELECT b.*, u.full_name as user_name, u.phone as user_phone,
            0 as distance_from_driver 
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id 
            WHERE b.status = 'pending' 
            ORDER BY b.created_at DESC";
            
    $stmt = $pdo->prepare($sql);
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        "success" => true, 
        "data" => $bookings,
        "search_radius_km" => 99999, // Infinite
        "debug_message" => "Returning all pending bookings (Nuclear Mode)"
    ]);

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
}
?>
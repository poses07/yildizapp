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
    // Get driver location from request
    $lat = isset($_GET['lat']) ? $_GET['lat'] : (isset($_POST['lat']) ? $_POST['lat'] : null);
    $lng = isset($_GET['lng']) ? $_GET['lng'] : (isset($_POST['lng']) ? $_POST['lng'] : null);

    // Get Search Radius from settings (default 50km)
    $radius = 50; 
    try {
        $stmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'driver_search_radius'");
        if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $radius = intval($row['setting_value']);
        }
    } catch (PDOException $e) {
        // Table might not exist yet, use default
    }
    
    // Fallback if radius is 0 or invalid
    if ($radius <= 0) $radius = 50;

    $sql = "SELECT b.*, u.name as user_name, u.phone as user_phone,
            (6371 * acos(least(1.0, greatest(-1.0, 
                cos(radians(:lat1)) * cos(radians(b.pickup_lat)) * 
                cos(radians(b.pickup_lng) - radians(:lng)) + 
                sin(radians(:lat2)) * sin(radians(b.pickup_lat))
            )))) AS distance_from_driver
            FROM bookings b 
            LEFT JOIN users u ON b.user_id = u.id 
            WHERE b.status = 'pending' 
            HAVING distance_from_driver <= :radius
            ORDER BY distance_from_driver ASC";

    if ($lat === null || $lng === null) {
        // If no location provided, show all (fallback)
        $sql = "SELECT b.*, u.name as user_name, u.phone as user_phone,
                0 as distance_from_driver 
                FROM bookings b 
                LEFT JOIN users u ON b.user_id = u.id 
                WHERE b.status = 'pending' 
                ORDER BY b.created_at DESC";
        $stmt = $pdo->prepare($sql);
    } else {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':lat1', $lat);
        $stmt->bindParam(':lat2', $lat);
        $stmt->bindParam(':lng', $lng);
        $stmt->bindParam(':radius', $radius);
    }
            
    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    ob_clean();
    echo json_encode([
        "success" => true, 
        "data" => $bookings,
        "search_radius_km" => $radius,
        "debug_message" => "Distance calculation active"
    ]);

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
}
?>
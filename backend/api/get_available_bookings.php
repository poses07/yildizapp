<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';

// Get driver's current location from request parameters
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

try {
    // 1. Get Radius Setting from Database
    $radiusStmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'driver_search_radius'");
    $radius = $radiusStmt->fetchColumn();
    
    // Default to 5km if not set or invalid
    if ($radius === false || !is_numeric($radius)) {
        $radius = 5;
    } else {
        $radius = floatval($radius);
    }

    // 2. Fetch Bookings
    if ($lat && $lng) {
        // Use Haversine formula to calculate distance and filter by radius
        // 6371 is Earth's radius in kilometers
        $sql = "SELECT b.*, u.full_name as user_name, u.phone as user_phone,
                (6371 * acos(cos(radians(:lat)) * cos(radians(b.pickup_lat)) * cos(radians(b.pickup_lng) - radians(:lng)) + sin(radians(:lat)) * sin(radians(b.pickup_lat)))) AS distance_from_driver
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.status = 'pending' 
                HAVING distance_from_driver <= :radius
                ORDER BY distance_from_driver ASC";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':lat', $lat);
        $stmt->bindParam(':lng', $lng);
        $stmt->bindParam(':radius', $radius);
        $stmt->execute();
    } else {
        // If no location provided, show all (backward compatibility)
        // Or we could return empty to force location usage.
        // For now, let's return all but maybe the client should always send location.
        $sql = "SELECT b.*, u.full_name as user_name, u.phone as user_phone 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.status = 'pending' 
                ORDER BY b.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        "success" => true, 
        "data" => $bookings,
        "search_radius_km" => $radius
    ]);

} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
}
?>

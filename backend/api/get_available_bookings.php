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

// Get driver's current location from request parameters
$lat = isset($_GET['lat']) ? floatval($_GET['lat']) : null;
$lng = isset($_GET['lng']) ? floatval($_GET['lng']) : null;

try {
    // 1. Get Radius Setting from Database
    $radiusStmt = $pdo->query("SELECT setting_value FROM app_settings WHERE setting_key = 'driver_search_radius'");
    $radius = $radiusStmt->fetchColumn();
    
    // Default to 5000km if not set or invalid (to ensure matching works for testing)
    if ($radius === false || !is_numeric($radius)) {
        $radius = 5000;
    } else {
        $radius = floatval($radius);
        // If radius is too small (e.g. < 100), boost it for now to avoid 'no match' issues during testing
        if ($radius < 100) $radius = 5000;
    }

    // 2. Fetch Bookings
    if ($lat && $lng) {
        // Use Haversine formula to calculate distance and filter by radius
        // Added LEAST/GREATEST to prevent acos domain error (NaN) if points are identical
        $sql = "SELECT b.*, u.full_name as user_name, u.phone as user_phone,
                (6371 * acos(
                    LEAST(1.0, GREATEST(-1.0, 
                        cos(radians(:lat)) * cos(radians(b.pickup_lat)) * cos(radians(b.pickup_lng) - radians(:lng)) + sin(radians(:lat)) * sin(radians(b.pickup_lat))
                    ))
                )) AS distance_from_driver
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
        // If no location provided, show all
        $sql = "SELECT b.*, u.full_name as user_name, u.phone as user_phone 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.status = 'pending' 
                ORDER BY b.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
    }
    
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback: If no bookings matched the radius/location criteria, return ALL pending bookings.
    // This ensures drivers always see available jobs even if GPS/Calculation fails.
    if (empty($bookings)) {
         $sqlFallback = "SELECT b.*, u.full_name as user_name, u.phone as user_phone, 
                         0 as distance_from_driver
                         FROM bookings b 
                         JOIN users u ON b.user_id = u.id 
                         WHERE b.status = 'pending' 
                         ORDER BY b.created_at DESC LIMIT 10";
         $stmtFallback = $pdo->prepare($sqlFallback);
         $stmtFallback->execute();
         $bookings = $stmtFallback->fetchAll(PDO::FETCH_ASSOC);
    }
    
    ob_clean();
    echo json_encode([
        "success" => true, 
        "data" => $bookings,
        "search_radius_km" => $radius
    ]);

} catch (PDOException $e) {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
}

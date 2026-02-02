<?php
ob_start(); // Start output buffering
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../db.php';

$booking_id = isset($_GET['booking_id']) ? $_GET['booking_id'] : null;

if ($booking_id) {
    try {
        $sql = "SELECT b.id, b.status, b.driver_id, 
                       d.full_name as driver_name, d.username as driver_phone, 
                       d.car_model, d.plate_number, 
                       d.latitude as driver_lat, d.longitude as driver_lng
                FROM bookings b
                LEFT JOIN drivers d ON b.driver_id = d.id
                WHERE b.id = :booking_id";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':booking_id', $booking_id);
        $stmt->execute();
        
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);
        
        ob_clean(); // Clean buffer before output
        if ($booking) {
            echo json_encode(["success" => true, "data" => $booking]);
        } else {
            echo json_encode(["success" => false, "message" => "Booking not found."]);
        }
    } catch (PDOException $e) {
        ob_clean();
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
} else {
    ob_clean();
    echo json_encode(["success" => false, "message" => "Booking ID required."]);
}

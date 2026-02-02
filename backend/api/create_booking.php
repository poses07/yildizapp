<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../db.php';

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->user_id) &&
    !empty($data->pickup_address) &&
    !empty($data->dropoff_address) &&
    isset($data->pickup_lat) &&
    isset($data->pickup_lng) &&
    isset($data->dropoff_lat) &&
    isset($data->dropoff_lng)
) {
    try {
        $sql = "INSERT INTO bookings (user_id, pickup_address, dropoff_address, pickup_lat, pickup_lng, dropoff_lat, dropoff_lng, price, distance_km, status, created_at) 
                VALUES (:user_id, :pickup_address, :dropoff_address, :pickup_lat, :pickup_lng, :dropoff_lat, :dropoff_lng, :price, :distance_km, 'pending', NOW())";
        
        $stmt = $pdo->prepare($sql);
        
        $stmt->bindParam(':user_id', $data->user_id);
        $stmt->bindParam(':pickup_address', $data->pickup_address);
        $stmt->bindParam(':dropoff_address', $data->dropoff_address);
        $stmt->bindParam(':pickup_lat', $data->pickup_lat);
        $stmt->bindParam(':pickup_lng', $data->pickup_lng);
        $stmt->bindParam(':dropoff_lat', $data->dropoff_lat);
        $stmt->bindParam(':dropoff_lng', $data->dropoff_lng);
        $stmt->bindParam(':price', $data->price);
        $stmt->bindParam(':distance_km', $data->distance_km);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Rezervasyon oluşturuldu.", "booking_id" => $pdo->lastInsertId()]);
        } else {
            echo json_encode(["success" => false, "message" => "Rezervasyon oluşturulamadı."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
}
?>
<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->booking_id) && !empty($data->status)) {
    try {
        $allowed_statuses = ['on_way', 'completed', 'cancelled'];
        if (!in_array($data->status, $allowed_statuses)) {
             echo json_encode(["success" => false, "message" => "Geçersiz durum."]);
             exit;
        }

        $sql = "UPDATE bookings SET status = :status WHERE id = :booking_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':status', $data->status);
        $stmt->bindParam(':booking_id', $data->booking_id);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "Durum güncellendi."]);
        } else {
            echo json_encode(["success" => false, "message" => "Güncelleme başarısız."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
}
?>
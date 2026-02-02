<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../db.php';

$data = json_decode(file_get_contents("php://input"));

if (!empty($data->booking_id) && !empty($data->driver_id)) {
    try {
        // Önce rezervasyonun hala 'pending' olup olmadığını kontrol et
        $checkSql = "SELECT status FROM bookings WHERE id = :booking_id";
        $checkStmt = $pdo->prepare($checkSql);
        $checkStmt->bindParam(':booking_id', $data->booking_id);
        $checkStmt->execute();
        $booking = $checkStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$booking) {
            echo json_encode(["success" => false, "message" => "Rezervasyon bulunamadı."]);
            exit;
        }
        
        if ($booking['status'] !== 'pending') {
            echo json_encode(["success" => false, "message" => "Bu rezervasyon artık müsait değil."]);
            exit;
        }
        
        // Rezervasyonu güncelle
        $sql = "UPDATE bookings SET driver_id = :driver_id, status = 'accepted' WHERE id = :booking_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':driver_id', $data->driver_id);
        $stmt->bindParam(':booking_id', $data->booking_id);
        
        if ($stmt->execute()) {
            echo json_encode(["success" => true, "message" => "İş kabul edildi."]);
        } else {
            echo json_encode(["success" => false, "message" => "İş kabul edilemedi."]);
        }
    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
}
?>
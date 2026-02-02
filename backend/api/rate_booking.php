<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../db.php';

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->booking_id) &&
    !empty($data->rating)
) {
    $bookingId = $data->booking_id;
    $rating = $data->rating;
    $comment = isset($data->comment) ? $data->comment : '';
    $tags = isset($data->tags) ? $data->tags : ''; // Comma separated tags

    try {
        // 1. Get User ID and Driver ID from booking
        $stmt = $pdo->prepare("SELECT user_id, driver_id FROM bookings WHERE id = ?");
        $stmt->execute([$bookingId]);
        $booking = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$booking) {
            echo json_encode(["success" => false, "message" => "Yolculuk bulunamadı."]);
            exit;
        }

        $userId = $booking['user_id'];
        $driverId = $booking['driver_id'];

        // 2. Check if already rated
        $checkStmt = $pdo->prepare("SELECT id FROM booking_ratings WHERE booking_id = ?");
        $checkStmt->execute([$bookingId]);
        if ($checkStmt->fetch()) {
            echo json_encode(["success" => false, "message" => "Bu yolculuk zaten değerlendirilmiş."]);
            exit;
        }

        // 3. Insert Rating
        $insertStmt = $pdo->prepare("INSERT INTO booking_ratings (booking_id, user_id, driver_id, rating, comment, tags) VALUES (?, ?, ?, ?, ?, ?)");
        $insertStmt->execute([$bookingId, $userId, $driverId, $rating, $comment, $tags]);

        echo json_encode(["success" => true, "message" => "Değerlendirme kaydedildi."]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
}
?>

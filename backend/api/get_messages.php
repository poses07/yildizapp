<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once '../db.php';

if (isset($_GET['booking_id'])) {
    $bookingId = $_GET['booking_id'];

    try {
        $stmt = $pdo->prepare("SELECT * FROM booking_messages WHERE booking_id = ? ORDER BY created_at ASC");
        $stmt->execute([$bookingId]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "data" => $messages]);

    } catch (PDOException $e) {
        echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Booking ID gerekli."]);
}
?>

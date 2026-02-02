<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../db.php';

$user_id = isset($_GET['user_id']) ? $_GET['user_id'] : null;
$driver_id = isset($_GET['driver_id']) ? $_GET['driver_id'] : null;

try {
    if ($user_id) {
        $sql = "SELECT b.*, d.full_name as driver_name, d.username as driver_phone 
                FROM bookings b 
                LEFT JOIN drivers d ON b.driver_id = d.id 
                WHERE b.user_id = :user_id 
                ORDER BY b.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $user_id);
    } elseif ($driver_id) {
        $sql = "SELECT b.*, u.name as user_name, u.phone as user_phone 
                FROM bookings b 
                JOIN users u ON b.user_id = u.id 
                WHERE b.driver_id = :driver_id 
                ORDER BY b.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':driver_id', $driver_id);
    } else {
        echo json_encode(["success" => false, "message" => "User ID veya Driver ID gerekli."]);
        exit;
    }

    $stmt->execute();
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(["success" => true, "data" => $bookings]);
} catch (PDOException $e) {
    echo json_encode(["success" => false, "message" => "Veritabanı hatası: " . $e->getMessage()]);
}
?>
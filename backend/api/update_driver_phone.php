<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: POST");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once __DIR__ . '/../db.php';

$data = json_decode(file_get_contents("php://input"));

if (
    !empty($data->driver_id) &&
    !empty($data->new_phone)
) {
    $driverId = $data->driver_id;
    $newPhone = $data->new_phone;

    try {
        $pdo->beginTransaction();

        // Check if new phone already exists in users (excluding the current user)
        // First get the user_id of this driver
        $stmt = $pdo->prepare("SELECT user_id FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$driver) {
            throw new Exception("Sürücü bulunamadı.");
        }

        $userId = $driver['user_id'];

        // Check availability
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
        $checkStmt->execute([$newPhone, $userId]);
        if ($checkStmt->fetch()) {
            throw new Exception("Bu telefon numarası başka bir kullanıcı tarafından kullanılıyor.");
        }

        // Update users table
        $updateUser = $pdo->prepare("UPDATE users SET phone = ? WHERE id = ?");
        $updateUser->execute([$newPhone, $userId]);

        // Update drivers table (username field stores phone)
        $updateDriver = $pdo->prepare("UPDATE drivers SET username = ? WHERE id = ?");
        $updateDriver->execute([$newPhone, $driverId]);

        $pdo->commit();

        echo json_encode(["success" => true, "message" => "Telefon numarası güncellendi."]);

    } catch (Exception $e) {
        $pdo->rollBack();
        echo json_encode(["success" => false, "message" => $e->getMessage()]);
    }
} else {
    echo json_encode(["success" => false, "message" => "Eksik veri."]);
}
?>

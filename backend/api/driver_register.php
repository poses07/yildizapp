<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['full_name']) && isset($input['phone']) && 
        isset($input['car_model']) && isset($input['plate_number'])) {
        
        $fullName = $input['full_name'];
        $phone = $input['phone'];
        // Şifre artık kullanılmıyor, varsayılan bir değer atıyoruz
        $password = md5(uniqid()); 
        $carModel = $input['car_model'];
        $plateNumber = $input['plate_number'];
        
        try {
            $pdo->beginTransaction();

            // 1. Check/Create User
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                $userId = $user['id'];
                // Update name if needed
                $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$fullName, $userId]);
            } else {
                $stmt = $pdo->prepare("INSERT INTO users (phone, name, status) VALUES (?, ?, 'active')");
                $stmt->execute([$phone, $fullName]);
                $userId = $pdo->lastInsertId();
            }

            // 2. Check if Driver exists
            $stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
            $stmt->execute([$userId]);
            if ($stmt->fetch()) {
                $pdo->rollBack();
                $response['success'] = false;
                $response['message'] = 'Bu telefon numarası ile zaten bir sürücü kaydı mevcut.';
                echo json_encode($response);
                exit;
            }

            // 3. Create Driver
            // username is phone
            // subscription_end_date defaults to now (expired) or some trial? 
            // Let's set it to current time, admin will extend it.
            $subscriptionEnd = date('Y-m-d H:i:s');
            
            $stmt = $pdo->prepare("INSERT INTO drivers (full_name, username, password, car_model, plate_number, subscription_end_date, user_id, status) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')");
            $stmt->execute([$fullName, $phone, $password, $carModel, $plateNumber, $subscriptionEnd, $userId]);
            
            $pdo->commit();

            $response['success'] = true;
            $response['message'] = 'Başvurunuz alındı. Yönetici onayı bekleniyor.';
            // Return data so we can "login" them into the waiting screen
            $response['data'] = array(
                'full_name' => $fullName,
                'status' => 'pending'
            );

        } catch (PDOException $e) {
            $pdo->rollBack();
            $response['success'] = false;
            $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Eksik parametreler.';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Geçersiz istek metodu.';
}

echo json_encode($response);
?>
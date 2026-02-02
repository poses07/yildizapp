<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['phone']) && isset($input['code'])) {
        $phone = $input['phone'];
        $code = $input['code'];
        
        try {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ? AND verification_code = ?");
            $stmt->execute([$phone, $code]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($user) {
                // Device ID check
                $inputDeviceId = isset($input['device_id']) ? $input['device_id'] : null;
                
                if ($inputDeviceId) {
                    if ($user['device_id'] !== null && $user['device_id'] !== '' && $user['device_id'] !== $inputDeviceId) {
                        $response['success'] = false;
                        $response['message'] = 'Bu hesaba sadece kayıtlı cihazdan giriş yapılabilir. Cihaz değişikliği için yönetici ile iletişime geçin.';
                        echo json_encode($response);
                        exit;
                    }
                }

                // Verify
                $updateSql = "UPDATE users SET is_verified = 1, status = 'active', verification_code = NULL";
                $params = [];
                
                // If this is the first time (device_id is null) or re-login from same device, update/ensure device_id
                if ($inputDeviceId && ($user['device_id'] === null || $user['device_id'] === '')) {
                    $updateSql .= ", device_id = ?";
                    $params[] = $inputDeviceId;
                }
                
                $updateSql .= " WHERE id = ?";
                $params[] = $user['id'];

                $stmt = $pdo->prepare($updateSql);
                $stmt->execute($params);
                
                $response['success'] = true;
                $response['message'] = 'Doğrulama başarılı.';
                
                $user['is_verified'] = 1;
                $user['status'] = 'active';
                $response['data'] = $user;
                $response['role'] = 'customer';
            } else {
                $response['success'] = false;
                $response['message'] = 'Geçersiz doğrulama kodu.';
            }
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Eksik bilgi.';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Geçersiz istek.';
}

echo json_encode($response);
?>
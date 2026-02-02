<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['username']) && isset($input['password'])) {
        $username = $input['username'];
        $password = md5($input['password']);

        try {
            $stmt = $pdo->prepare("SELECT * FROM drivers WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($driver) {
                // Device ID check
                $inputDeviceId = isset($input['device_id']) ? $input['device_id'] : null;

                if ($inputDeviceId) {
                    if ($driver['device_id'] !== null && $driver['device_id'] !== '' && $driver['device_id'] !== $inputDeviceId) {
                        $response['success'] = false;
                        $response['message'] = 'Bu sürücü hesabına sadece kayıtlı cihazdan giriş yapılabilir. Cihaz değişikliği için yönetici ile iletişime geçin.';
                        echo json_encode($response);
                        exit;
                    }
                    
                    // If device_id is empty, save it
                    if ($driver['device_id'] === null || $driver['device_id'] === '') {
                        $upd = $pdo->prepare("UPDATE drivers SET device_id = ? WHERE id = ?");
                        $upd->execute([$inputDeviceId, $driver['id']]);
                    }
                }

                // Check status first
                $status = isset($driver['status']) ? $driver['status'] : 'approved'; // Default to approved if column missing/null (safety)

                if ($status === 'rejected') {
                    $response['success'] = false;
                    $response['message'] = 'Başvurunuz reddedilmiştir.';
                } elseif ($status === 'pending') {
                    // Allow login but with pending status
                    $response['success'] = true;
                    $response['message'] = 'Başvurunuz onay bekliyor.';
                    $response['data'] = array(
                        'id' => $driver['id'],
                        'full_name' => $driver['full_name'],
                        'status' => 'pending'
                    );
                } else {
                    // Approved, check subscription
                    $now = time();
                    $subEnd = strtotime($driver['subscription_end_date']);

                    if ($subEnd > $now) {
                        $response['success'] = true;
                        $response['message'] = 'Giriş başarılı';
                        $response['data'] = array(
                            'id' => $driver['id'],
                            'full_name' => $driver['full_name'],
                            'car_model' => $driver['car_model'],
                            'plate_number' => $driver['plate_number'],
                            'subscription_end_date' => $driver['subscription_end_date'],
                            'status' => 'approved'
                        );
                    } else {
                        $response['success'] = false;
                        $response['message'] = 'Abonelik süreniz dolmuştur. Lütfen yönetici ile iletişime geçin.';
                        $response['error_code'] = 'SUBSCRIPTION_EXPIRED';
                    }
                }
            } else {
                $response['success'] = false;
                $response['message'] = 'Kullanıcı adı veya şifre hatalı.';
            }
        } catch (PDOException $e) {
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

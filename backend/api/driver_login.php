<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['username']) && isset($input['password'])) {
        $username = $input['username'];
        $password = md5($input['password']);

        try {
            $stmt = $pdo->prepare("SELECT * FROM drivers WHERE username = ? AND password = ?");
            $stmt->execute([$username, $password]);
            $driver = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($driver) {
                // Check subscription status
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
                        'subscription_end_date' => $driver['subscription_end_date']
                    );
                } else {
                    $response['success'] = false;
                    $response['message'] = 'Abonelik süreniz dolmuştur. Lütfen yönetici ile iletişime geçin.';
                    $response['error_code'] = 'SUBSCRIPTION_EXPIRED';
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

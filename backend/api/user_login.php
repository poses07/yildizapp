<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['phone'])) {
        $phone = $input['phone'];
        // İsim alanı artık opsiyonel ve sadece yeni kayıtta kullanılabilir ama login ekranından kaldırıldı
        // Ancak yine de API tarafında tutabiliriz.

        try {
            // 1. Kullanıcıyı users tablosunda ara
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                // Kullanıcı var
                
                // Durum kontrolü
                if ($user['status'] === 'blocked') {
                    $response['success'] = false;
                    $response['message'] = 'Hesabınız askıya alınmıştır. Lütfen destek ile iletişime geçin.';
                    $response['error_code'] = 'ACCOUNT_BLOCKED';
                    echo json_encode($response);
                    exit;
                } elseif ($user['status'] === 'pending') {
                    $response['success'] = false;
                    $response['message'] = 'Hesabınız onay bekliyor.';
                    $response['error_code'] = 'ACCOUNT_PENDING';
                    echo json_encode($response);
                    exit;
                }

                // Sürücü kontrolü
                $stmt = $pdo->prepare("SELECT * FROM drivers WHERE user_id = ?");
                $stmt->execute([$user['id']]);
                $driver = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($driver) {
                    // Kullanıcı aynı zamanda bir SÜRÜCÜ
                    // Abonelik süresini kontrol et
                    $subscriptionEnd = strtotime($driver['subscription_end_date']);
                    $now = time();

                    if ($now > $subscriptionEnd) {
                        $response['success'] = false;
                        $response['message'] = 'Sürücü abonelik süreniz dolmuştur. Lütfen yöneticinizle iletişime geçin.';
                        $response['role'] = 'driver_expired';
                    } else {
                        $response['success'] = true;
                        $response['message'] = 'Sürücü girişi başarılı';
                        $response['role'] = 'driver';
                        
                        // Sürücü ismini user objesine de yansıt ki arayüzde doğru görünsün
                        $user['name'] = $driver['full_name'];
                        
                        $response['data'] = array_merge($user, ['driver_details' => $driver]);
                    }
                } else {
                    // Kullanıcı sadece MÜŞTERİ
                    $response['success'] = true;
                    $response['message'] = 'Müşteri girişi başarılı';
                    $response['role'] = 'customer';
                    $response['data'] = $user;
                }

            } else {
                // Kullanıcı bulunamadı - Otomatik kayıt YAPMA
                $response['success'] = false;
                $response['message'] = 'Telefon numaranız sistemde kayıtlı değil. Lütfen kayıt için bizimle iletişime geçiniz.';
                $response['error_code'] = 'USER_NOT_FOUND';
            }
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Telefon numarası gerekli.';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Geçersiz istek metodu.';
}

echo json_encode($response);
?>
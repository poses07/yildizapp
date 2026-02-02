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

            // Eğer users tablosunda yoksa, drivers tablosuna bak (username = phone)
            if (!$user) {
                $stmt = $pdo->prepare("SELECT * FROM drivers WHERE username = ?");
                $stmt->execute([$phone]);
                $driver = $stmt->fetch(PDO::FETCH_ASSOC);

                if ($driver) {
                    // Sürücü bulundu ama users kaydı eksik. Oluşturalım.
                    $stmt = $pdo->prepare("INSERT INTO users (phone, name, status) VALUES (?, ?, 'active')");
                    $stmt->execute([$phone, $driver['full_name']]);
                    $newUserId = $pdo->lastInsertId();

                    // Sürücüyü bu user_id ile güncelle
                    $pdo->prepare("UPDATE drivers SET user_id = ? WHERE id = ?")->execute([$newUserId, $driver['id']]);

                    // User nesnesini getir
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$newUserId]);
                    $user = $stmt->fetch(PDO::FETCH_ASSOC);
                }
            }

            if ($user) {
                // Kullanıcı var
                
                // Device ID check
                $inputDeviceId = isset($input['device_id']) ? $input['device_id'] : null;

                if ($inputDeviceId) {
                    if ($user['device_id'] !== null && $user['device_id'] !== '' && $user['device_id'] !== $inputDeviceId) {
                        $response['success'] = false;
                        $response['message'] = 'Bu hesaba sadece kayıtlı cihazdan giriş yapılabilir. Cihaz değişikliği için yönetici ile iletişime geçin.';
                        echo json_encode($response);
                        exit;
                    }

                    // If device_id is empty, save it
                    if ($user['device_id'] === null || $user['device_id'] === '') {
                        $upd = $pdo->prepare("UPDATE users SET device_id = ? WHERE id = ?");
                        $upd->execute([$inputDeviceId, $user['id']]);
                    }
                }
                
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
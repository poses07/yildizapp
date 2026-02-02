<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['full_name']) && isset($input['phone'])) {
        $fullName = $input['full_name'];
        $phone = $input['phone'];
        
        // Clean phone
        $phoneClean = preg_replace('/[^0-9]/', '', $phone);
        
        try {
            // Check if user exists
            $stmt = $pdo->prepare("SELECT * FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // Generate OTP
            $otp = rand(1000, 9999);
            
            if ($user) {
                // If user exists, update OTP
                $stmt = $pdo->prepare("UPDATE users SET verification_code = ? WHERE id = ?");
                $stmt->execute([$otp, $user['id']]);
                $userId = $user['id'];
            } else {
                // Create new user
                $stmt = $pdo->prepare("INSERT INTO users (name, phone, status, verification_code, is_verified) VALUES (?, ?, 'pending', ?, 0)");
                $stmt->execute([$fullName, $phone, $otp]);
                $userId = $pdo->lastInsertId();
            }
            
            // Send WhatsApp Message
            $code = $otp;
            
            try {
                $logFile = __DIR__ . '/../admin/debug_whatsapp.log';
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] WhatsApp süreci başladı. Kod: $code\n", FILE_APPEND);
                
                require_once __DIR__ . '/../admin/wpvoucher/evolution_api_client.php';
                
                // Use the user's phone number
                $wpPhones = [$phoneClean];
                
                $wpMessage = "Merhaba $fullName,\n\n" 
                           . "Yıldız Taksi doğrulama kodunuz: *$code*\n\n" 
                           . "Bu kodu uygulamaya girerek kaydınızı tamamlayabilirsiniz.";
                
                $config = require __DIR__ . '/../admin/wpvoucher/config.php';
                $instanceName = $config['evolution']['instance'] ?? 'OrionVIP';
                $instanceNameEnc = rawurlencode($instanceName);
                
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Instance: $instanceName\n", FILE_APPEND);
                file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Mesaj İçeriği:\n$wpMessage\n", FILE_APPEND);

                foreach ($wpPhones as $ph) {
                    $phClean = preg_replace('/[^0-9]/', '', $ph);
                    if ($phClean) {
                        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Gönderiliyor: $phClean\n", FILE_APPEND);
                        $res = evoRequest('POST', 'message/sendText/' . $instanceNameEnc, [], [
                            'number' => $phClean,
                            'text' => $wpMessage
                        ], ['auth' => 'apikey']);
                        file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] Sonuç ($phClean): " . json_encode($res) . "\n", FILE_APPEND);
                    }
                }
                
                $response['success'] = true;
                $response['message'] = 'Doğrulama kodu WhatsApp ile gönderildi.';
                
            } catch (Throwable $wpErr) {
                 $logFile = __DIR__ . '/../admin/debug_whatsapp.log';
                 file_put_contents($logFile, "[" . date('Y-m-d H:i:s') . "] HATA: " . $wpErr->getMessage() . "\n" . $wpErr->getTraceAsString() . "\n", FILE_APPEND);
                 
                 $response['success'] = false;
                 $response['message'] = 'WhatsApp gönderim hatası: ' . $wpErr->getMessage();
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
<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

$response = array();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (isset($input['type']) && isset($input['id'])) {
        $type = $input['type']; // 'user' or 'driver'
        $id = $input['id'];
        
        try {
            if ($type === 'user') {
                $stmt = $pdo->prepare("UPDATE users SET device_id = NULL WHERE id = ?");
                $stmt->execute([$id]);
                $response['success'] = true;
                $response['message'] = 'Kullanıcı cihaz kilidi sıfırlandı.';
            } elseif ($type === 'driver') {
                $stmt = $pdo->prepare("UPDATE drivers SET device_id = NULL WHERE id = ?");
                $stmt->execute([$id]);
                $response['success'] = true;
                $response['message'] = 'Sürücü cihaz kilidi sıfırlandı.';
            } else {
                $response['success'] = false;
                $response['message'] = 'Geçersiz tip. (user veya driver olmalı)';
            }
        } catch (PDOException $e) {
            $response['success'] = false;
            $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Eksik parametreler (type, id).';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Geçersiz istek metodu.';
}

echo json_encode($response);
?>

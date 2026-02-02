<?php
header("Content-Type: application/json; charset=UTF-8");
require_once '../db.php';

$response = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_FILES['photo']) && isset($_POST['user_id'])) {
        $userId = $_POST['user_id'];
        $file = $_FILES['photo'];
        
        // Klasör kontrolü
        $targetDir = "../uploads/profiles/";
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0777, true);
        }
        
        $fileName = "user_" . $userId . "_" . time() . "_" . basename($file["name"]);
        $targetFilePath = $targetDir . $fileName;
        $fileType = pathinfo($targetFilePath, PATHINFO_EXTENSION);
        
        // İzin verilen dosya türleri
        $allowTypes = array('jpg', 'png', 'jpeg', 'gif');
        if (in_array(strtolower($fileType), $allowTypes)) {
            if (move_uploaded_file($file["tmp_name"], $targetFilePath)) {
                // Veritabanını güncelle
                // URL path relative to domain root
                $dbFilePath = "backend/uploads/profiles/" . $fileName;
                
                try {
                    $stmt = $pdo->prepare("UPDATE users SET profile_photo = ? WHERE id = ?");
                    $stmt->execute([$dbFilePath, $userId]);
                    
                    $response['success'] = true;
                    $response['message'] = 'Profil fotoğrafı güncellendi.';
                    $response['photo_url'] = $dbFilePath;
                } catch (PDOException $e) {
                    $response['success'] = false;
                    $response['message'] = 'Veritabanı hatası: ' . $e->getMessage();
                }
            } else {
                $response['success'] = false;
                $response['message'] = 'Dosya yüklenirken hata oluştu.';
            }
        } else {
            $response['success'] = false;
            $response['message'] = 'Sadece JPG, JPEG, PNG & GIF dosyaları yüklenebilir.';
        }
    } else {
        $response['success'] = false;
        $response['message'] = 'Dosya veya kullanıcı ID eksik.';
    }
} else {
    $response['success'] = false;
    $response['message'] = 'Geçersiz istek.';
}

echo json_encode($response);
?>
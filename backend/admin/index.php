<?php
session_start();
require_once '../db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = md5($_POST['password']); 

    $stmt = $pdo->prepare("SELECT * FROM admin_users WHERE username = ? AND password = ?");
    $stmt->execute([$username, $password]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['admin_id'] = $user['id'];
        header("Location: dashboard.php");
        exit;
    } else {
        $error = "Hatalı kullanıcı adı veya şifre!";
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Giriş Yap - Yıldız Taksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; display: flex; align-items: center; justify-content: center; min-height: 100vh; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .login-card { width: 100%; max-width: 400px; padding: 40px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); background: white; border: 1px solid #f0f0f0; }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo i { font-size: 48px; color: #ffc107; margin-bottom: 10px; display: block; }
        .logo h4 { font-weight: 700; color: #333; margin: 0; }
        .form-control { border-radius: 10px; padding: 12px 15px; border: 1px solid #e0e0e0; background-color: #f9f9f9; }
        .form-control:focus { box-shadow: none; border-color: #ffc107; background-color: #fff; }
        .btn-primary { background-color: #ffc107; border: none; color: #000; font-weight: 600; padding: 12px; border-radius: 10px; width: 100%; transition: all 0.3s; }
        .btn-primary:hover { background-color: #ffca2c; transform: translateY(-2px); box-shadow: 0 4px 10px rgba(255, 193, 7, 0.3); }
        .input-group-text { border-radius: 10px 0 0 10px; background-color: #fff; border: 1px solid #e0e0e0; border-right: none; color: #aaa; }
        .alert { border-radius: 10px; font-size: 0.9rem; }
    </style>
</head>
<body>
    <div class="container px-4">
        <div class="login-card mx-auto">
            <div class="logo">
                <i class="fas fa-taxi"></i>
                <h4>Yıldız Taksi</h4>
                <small class="text-muted">Yönetim Paneli</small>
            </div>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-danger text-center shadow-sm border-0 bg-danger bg-opacity-10 text-danger">
                    <i class="fas fa-exclamation-circle me-1"></i> <?= $error ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="mb-3">
                    <label class="form-label text-muted small fw-bold">Kullanıcı Adı</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                        <input type="text" name="username" class="form-control" placeholder="Kullanıcı adınız" required>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label text-muted small fw-bold">Şifre</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-lock"></i></span>
                        <input type="password" name="password" class="form-control" placeholder="••••••••" required>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-lg shadow-sm">
                    Giriş Yap <i class="fas fa-arrow-right ms-2"></i>
                </button>
            </form>
            
            <div class="text-center mt-4">
                <small class="text-muted">&copy; <?= date('Y') ?> Tüm Hakları Saklıdır</small>
            </div>
        </div>
    </div>
</body>
</html>

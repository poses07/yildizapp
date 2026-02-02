<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
require_once '../db.php';

// Ayar Güncelleme İşlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_settings'])) {
    foreach ($_POST['settings'] as $key => $value) {
        $stmt = $pdo->prepare("UPDATE app_settings SET setting_value = ? WHERE setting_key = ?");
        $stmt->execute([$value, $key]);
    }
    $success = "Ayarlar başarıyla güncellendi!";
}

// Ayarları Çek
$settings = $pdo->query("SELECT * FROM app_settings")->fetchAll();

// İstatistikleri Çek
$pendingDrivers = $pdo->query("SELECT COUNT(*) FROM drivers WHERE status = 'pending'")->fetchColumn();
$totalDrivers = $pdo->query("SELECT COUNT(*) FROM drivers")->fetchColumn();
$totalUsers = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Yıldız Taksi Yönetim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 20px; border-radius: 15px 15px 0 0 !important; }
        .card-header h5 { margin: 0; color: #333; font-weight: 600; }
        .form-control { border-radius: 10px; padding: 12px; border: 1px solid #e0e0e0; background-color: #f9f9f9; }
        .form-control:focus { box-shadow: none; border-color: #ffc107; background-color: #fff; }
        .btn-primary { background-color: #ffc107; border: none; color: #000; font-weight: 600; padding: 12px 24px; border-radius: 10px; width: 100%; }
        .btn-primary:hover { background-color: #ffca2c; color: #000; }
        .input-group-text { border-radius: 10px 0 0 10px; background-color: #fff; border: 1px solid #e0e0e0; border-right: none; }
        .form-label { font-weight: 500; color: #555; margin-bottom: 8px; font-size: 0.95rem; }
        .setting-key { font-size: 0.8rem; color: #999; display: block; margin-top: 4px; }
        .alert { border-radius: 10px; }
        
        /* Layout & Sidebar Adjustment */
        .main-content {
            margin-left: 280px;
            padding: 20px;
        }
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding-top: 60px;
            }
        }
    </style>
</head>
<body>
    
    <!-- Sidebar Include -->
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    
                    <!-- Stats Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card bg-warning text-dark h-100 shadow-sm border-0">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-1 fw-bold">Bekleyen Sürücüler</h6>
                                        <h2 class="mb-0 fw-bold"><?= $pendingDrivers ?></h2>
                                    </div>
                                    <i class="fas fa-user-clock fa-3x opacity-50"></i>
                                </div>
                                <?php if($pendingDrivers > 0): ?>
                                <a href="drivers.php" class="card-footer bg-transparent border-top-0 text-dark text-decoration-none small fw-bold">
                                    İncele <i class="fas fa-arrow-right ms-1"></i>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3 mb-md-0">
                            <div class="card bg-white h-100 shadow-sm border-0">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-1 text-muted fw-bold">Toplam Sürücü</h6>
                                        <h2 class="mb-0 fw-bold text-dark"><?= $totalDrivers ?></h2>
                                    </div>
                                    <i class="fas fa-taxi fa-3x text-warning opacity-50"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-white h-100 shadow-sm border-0">
                                <div class="card-body d-flex align-items-center justify-content-between">
                                    <div>
                                        <h6 class="card-title mb-1 text-muted fw-bold">Toplam Müşteri</h6>
                                        <h2 class="mb-0 fw-bold text-dark"><?= $totalUsers ?></h2>
                                    </div>
                                    <i class="fas fa-users fa-3x text-info opacity-50"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if (isset($success)): ?>
                        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                            <i class="fas fa-check-circle me-2"></i> <?= $success ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    <?php endif; ?>

                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <div class="bg-warning bg-opacity-10 p-2 rounded-circle me-3">
                                <i class="fas fa-sliders-h text-warning fs-5"></i>
                            </div>
                            <h5 class="mb-0">Fiyatlandırma & Ayarlar</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <?php foreach ($settings as $setting): ?>
                                    <div class="mb-4">
                                        <label class="form-label d-flex justify-content-between align-items-center">
                                            <?= htmlspecialchars($setting['description']) ?>
                                        </label>
                                        
                                        <div class="input-group">
                                            <input type="number" step="0.01" name="settings[<?= $setting['setting_key'] ?>]" 
                                                   class="form-control form-control-lg" 
                                                   value="<?= htmlspecialchars($setting['setting_value']) ?>"
                                                   placeholder="0.00">
                                            <span class="input-group-text text-muted fw-bold">
                                                <?= $setting['setting_key'] === 'driver_search_radius' ? 'KM' : 'TL' ?>
                                            </span>
                                        </div>
                                        <span class="setting-key"><i class="fas fa-code me-1"></i><?= $setting['setting_key'] ?></span>
                                    </div>
                                <?php endforeach; ?>
                                
                                <div class="d-grid gap-2 mt-5">
                                    <button type="submit" name="update_settings" class="btn btn-primary btn-lg shadow-sm">
                                        <i class="fas fa-save me-2"></i> Değişiklikleri Kaydet
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="text-center text-muted mt-4">
                        <small>&copy; <?= date('Y') ?> Yıldız Taksi Yönetim Paneli</small>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

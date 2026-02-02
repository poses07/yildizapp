<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}

require_once '../db.php';

$success = null;
$error = null;

// Handle Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action'])) {
        $userId = $_POST['user_id'];
        
        if ($_POST['action'] === 'update_status') {
            $newStatus = $_POST['status'];
            try {
                $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ?");
                $stmt->execute([$newStatus, $userId]);
                $success = "Kullanıcı durumu güncellendi.";
            } catch (PDOException $e) {
                $error = "Hata: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'delete_user') {
            try {
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $success = "Kullanıcı başarıyla silindi.";
            } catch (PDOException $e) {
                $error = "Hata: " . $e->getMessage();
            }
        } elseif ($_POST['action'] === 'add_user') {
            $name = $_POST['name'];
            $phone = $_POST['phone'];
            
            // Telefon kontrolü
            $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
            $stmt->execute([$phone]);
            if ($stmt->fetch()) {
                $error = "Bu telefon numarası zaten kayıtlı!";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO users (name, phone, status) VALUES (?, ?, 'active')");
                    $stmt->execute([$name, $phone]);
                    $success = "Müşteri başarıyla eklendi.";
                } catch (PDOException $e) {
                    $error = "Hata: " . $e->getMessage();
                }
            }
        } elseif ($_POST['action'] === 'reset_device') {
            try {
                $stmt = $pdo->prepare("UPDATE users SET device_id = NULL WHERE id = ?");
                $stmt->execute([$userId]);
                $success = "Cihaz kilidi başarıyla kaldırıldı.";
            } catch (PDOException $e) {
                $error = "Hata: " . $e->getMessage();
            }
        }
    }
}

// Fetch Users (Include Drivers but mark them, or handle as user requested)
// Kullanıcı isteği: "hem müşteri hemde sürücü alanına geliyor veriler"
// Bu nedenle Sürücü olanları BURADA GÖSTERMEMELİYİZ. Sadece "Sadece Müşteri" olanları göstermeliyiz.
try {
    // Sürücü tablosunda kaydı OLMAYAN kullanıcıları getir (Sadece Müşteriler)
    $stmt = $pdo->query("SELECT u.* FROM users u LEFT JOIN drivers d ON u.id = d.user_id WHERE d.id IS NULL ORDER BY u.id DESC");
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Müşteri Yönetimi - Yıldız Taksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { margin-left: 280px; padding: 20px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 60px; } }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .status-badge { font-size: 0.85rem; padding: 0.5em 1em; border-radius: 20px; }
        .btn-action { border-radius: 10px; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?= $success ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?= $error ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0 fw-bold">Müşteri Yönetimi</h4>
                <p class="text-muted mb-0">Toplam <?= count($users) ?> kayıtlı müşteri</p>
            </div>
            <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <i class="fas fa-plus me-2"></i>Yeni Müşteri
            </button>
        </div>

        <div class="row">
            <?php foreach ($users as $user): 
                $statusColor = 'secondary';
                $statusText = 'Bilinmiyor';
                
                if ($user['status'] === 'active') {
                    $statusColor = 'success';
                    $statusText = 'Aktif';
                } elseif ($user['status'] === 'blocked') {
                    $statusColor = 'danger';
                    $statusText = 'Engellendi';
                } elseif ($user['status'] === 'pending') {
                    $statusColor = 'warning';
                    $statusText = 'Onay Bekliyor';
                }
            ?>
            <div class="col-md-6 col-lg-4 col-xl-3 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-light rounded-circle p-3 me-3 text-primary">
                                    <i class="fas fa-user fa-lg"></i>
                                </div>
                                <div>
                                    <h5 class="card-title fw-bold mb-1"><?= htmlspecialchars($user['name'] ?? 'İsimsiz') ?></h5>
                                    <small class="text-muted"><?= htmlspecialchars($user['phone']) ?></small>
                                </div>
                            </div>
                            <span class="badge bg-<?= $statusColor ?> status-badge"><?= $statusText ?></span>
                        </div>
                        
                        <div class="mb-3">
                            <small class="text-muted d-block">Cihaz Durumu:</small>
                            <?php if (!empty($user['device_id'])): ?>
                                <span class="badge bg-info text-dark"><i class="fas fa-lock me-1"></i>Kilitli</span>
                            <?php else: ?>
                                <span class="badge bg-secondary"><i class="fas fa-lock-open me-1"></i>Serbest</span>
                            <?php endif; ?>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <?php if (!empty($user['device_id'])): ?>
                                <form method="POST" class="d-grid">
                                    <input type="hidden" name="action" value="reset_device">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button class="btn btn-outline-warning btn-sm btn-action text-dark">
                                        <i class="fas fa-mobile-alt me-2"></i>Cihaz Kilidini Aç
                                    </button>
                                </form>
                            <?php endif; ?>

                            <?php if ($user['status'] !== 'active'): ?>
                                <form method="POST" class="d-grid">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="status" value="active">
                                    <button class="btn btn-outline-success btn-sm btn-action">
                                        <i class="fas fa-check me-2"></i>Onayla / Aktif Et
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if ($user['status'] !== 'blocked'): ?>
                                <form method="POST" class="d-grid">
                                    <input type="hidden" name="action" value="update_status">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <input type="hidden" name="status" value="blocked">
                                    <button class="btn btn-outline-danger btn-sm btn-action">
                                        <i class="fas fa-ban me-2"></i>Engelle
                                    </button>
                                </form>
                            <?php endif; ?>

                            <button type="button" class="btn btn-light btn-sm btn-action text-danger mt-2" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $user['id'] ?>">
                                <i class="fas fa-trash me-2"></i>Sil
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Delete Modal -->
            <div class="modal fade" id="deleteModal<?= $user['id'] ?>" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Müşteriyi Sil</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <p><strong><?= htmlspecialchars($user['name'] ?? $user['phone']) ?></strong> adlı müşteriyi silmek istediğinize emin misiniz?</p>
                            <p class="text-danger small">Bu işlem geri alınamaz!</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                            <form method="POST">
                                <input type="hidden" name="action" value="delete_user">
                                <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                <button type="submit" class="btn btn-danger">Sil</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>

            <!-- Add User Modal -->
            <div class="modal fade" id="addUserModal" tabindex="-1">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Yeni Müşteri Ekle</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <form method="POST">
                            <div class="modal-body">
                                <input type="hidden" name="action" value="add_user">
                                <div class="mb-3">
                                    <label class="form-label">Ad Soyad</label>
                                    <input type="text" name="name" class="form-control" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Telefon Numarası</label>
                                    <input type="text" name="phone" class="form-control" placeholder="5XXXXXXXXX" required>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                                <button type="submit" class="btn btn-primary">Kaydet</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <?php if (empty($users)): ?>
                <div class="col-12 text-center py-5">
                    <div class="text-muted">
                        <i class="fas fa-users-slash fa-3x mb-3"></i>
                        <p>Henüz kayıtlı müşteri bulunmuyor.</p>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
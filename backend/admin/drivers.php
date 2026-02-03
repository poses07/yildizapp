<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
require_once '../db.php';

// Sürücü Ekleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_driver'])) {
    $fullName = $_POST['full_name'];
    $phone = $_POST['phone'];
    $username = $phone; // Username telefon numarası olarak ayarlandı
    
    // Şifre sistemi kullanılmadığı için rastgele bir değer atıyoruz
    $password = md5(uniqid());

    $carModel = $_POST['car_model'];
    $plateNumber = $_POST['plate_number'];
    $duration = $_POST['duration']; // days

    $subscriptionEnd = date('Y-m-d H:i:s', strtotime("+$duration days"));

    try {
        $pdo->beginTransaction();

        // 1. Kullanıcıyı users tablosuna ekle veya varsa ID'sini al
        $stmt = $pdo->prepare("SELECT id FROM users WHERE phone = ?");
        $stmt->execute([$phone]);
        $existingUser = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($existingUser) {
            $userId = $existingUser['id'];
            // Mevcut kullanıcının ismini de güncelle, böylece tutarlılık sağlanır
            $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$fullName, $userId]);
        } else {
            $stmt = $pdo->prepare("INSERT INTO users (phone, name, status) VALUES (?, ?, 'active')");
            $stmt->execute([$phone, $fullName]);
            $userId = $pdo->lastInsertId();
        }

        // 2. Sürücüyü drivers tablosuna ekle (user_id ile ilişkilendir)
        // Eğer bu kullanıcı zaten sürücü ise hata verebilir veya güncelleyebiliriz.
        // Şimdilik mükerrer kayıt kontrolü yapalım
        $stmt = $pdo->prepare("SELECT id FROM drivers WHERE user_id = ?");
        $stmt->execute([$userId]);
        if ($stmt->fetch()) {
            throw new Exception("Bu telefon numarasına sahip bir sürücü zaten var!");
        }

        $stmt = $pdo->prepare("INSERT INTO drivers (full_name, username, password, car_model, plate_number, subscription_end_date, user_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$fullName, $username, $password, $carModel, $plateNumber, $subscriptionEnd, $userId]);
        
        $pdo->commit();
        $success = "Sürücü başarıyla eklendi!";
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Hata: " . $e->getMessage();
    }
}

// Süre Uzatma
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extend_subscription'])) {
    $driverId = $_POST['driver_id'];
    $days = $_POST['extension_days'];

    // Mevcut bitiş tarihini al
    $stmt = $pdo->prepare("SELECT subscription_end_date FROM drivers WHERE id = ?");
    $stmt->execute([$driverId]);
    $currentEnd = $stmt->fetchColumn();

    $baseDate = (strtotime($currentEnd) > time()) ? $currentEnd : date('Y-m-d H:i:s');
    $newEnd = date('Y-m-d H:i:s', strtotime($baseDate . " +$days days"));

    $stmt = $pdo->prepare("UPDATE drivers SET subscription_end_date = ? WHERE id = ?");
    $stmt->execute([$newEnd, $driverId]);
    $success = "Süre başarıyla uzatıldı!";
}

// Sürücü Düzenleme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_driver'])) {
    $driverId = $_POST['driver_id'];
    $fullName = $_POST['full_name'];
    $carModel = $_POST['car_model'];
    $plateNumber = $_POST['plate_number'];
    $status = $_POST['status'];

    try {
        $stmt = $pdo->prepare("UPDATE drivers SET full_name = ?, car_model = ?, plate_number = ?, status = ? WHERE id = ?");
        $stmt->execute([$fullName, $carModel, $plateNumber, $status, $driverId]);
        
        // Users tablosundaki ismi de güncelle
        $stmt = $pdo->prepare("SELECT user_id FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $userId = $stmt->fetchColumn();
        
        if ($userId) {
            $pdo->prepare("UPDATE users SET name = ? WHERE id = ?")->execute([$fullName, $userId]);
        }

        $success = "Sürücü bilgileri güncellendi!";
    } catch (Exception $e) {
        $error = "Hata: " . $e->getMessage();
    }
}

// Cihaz Kilidi Sıfırlama
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reset_device'])) {
    $driverId = $_POST['driver_id'];
    try {
        // Önce sürücünün user_id'sini bul
        $stmt = $pdo->prepare("SELECT user_id FROM drivers WHERE id = ?");
        $stmt->execute([$driverId]);
        $userId = $stmt->fetchColumn();

        $pdo->beginTransaction();

        // Drivers tablosundan sil
        $stmt = $pdo->prepare("UPDATE drivers SET device_id = NULL WHERE id = ?");
        $stmt->execute([$driverId]);

        // Users tablosundan da sil (Çünkü login işlemi users tablosuna bakıyor)
        if ($userId) {
            $stmt = $pdo->prepare("UPDATE users SET device_id = NULL WHERE id = ?");
            $stmt->execute([$userId]);
        }

        $pdo->commit();
        $success = "Cihaz kilidi başarıyla kaldırıldı (Sürücü ve Kullanıcı tablolarından).";
    } catch (PDOException $e) {
        $pdo->rollBack();
        $error = "Hata: " . $e->getMessage();
    }
}

// Sürücü Onaylama
if (isset($_GET['approve'])) {
    $driverId = $_GET['approve'];
    // Abonelik süresini güncelleme, sadece onay ver
    // Yönetici "Süre Uzat" butonu ile manuel süre eklemelidir
    
    $stmt = $pdo->prepare("UPDATE drivers SET status = 'approved' WHERE id = ?");
    $stmt->execute([$driverId]);
    
    header("Location: drivers.php?success=approved");
    exit;
}

// Sürücü Silme
if (isset($_GET['delete'])) {
    $stmt = $pdo->prepare("DELETE FROM drivers WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    header("Location: drivers.php");
    exit;
}

// Sürücüleri Listele
$drivers = $pdo->query("SELECT d.*, u.phone FROM drivers d LEFT JOIN users u ON d.user_id = u.id ORDER BY d.id DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Sürücüler - Yıldız Taksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .btn-primary { background-color: #ffc107; border: none; color: #000; font-weight: 600; }
        .btn-primary:hover { background-color: #ffca2c; color: #000; }
        .status-badge { font-size: 0.85rem; padding: 5px 10px; border-radius: 20px; }
        
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
            <?php if (isset($success)): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= $success ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>
            <?php if (isset($error)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= $error ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fw-bold">Sürücü Yönetimi</h4>
                <button class="btn btn-primary shadow-sm" data-bs-toggle="modal" data-bs-target="#addDriverModal">
                    <i class="fas fa-plus me-2"></i>Yeni Sürücü
                </button>
            </div>

            <div class="card shadow-sm">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Sürücü Bilgileri</th>
                                    <th>Araç Bilgileri</th>
                                    <th>Durum</th>
                                    <th>Abonelik Bitiş</th>
                                    <th>İşlemler</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($drivers as $driver): 
                                    $timeLeft = strtotime($driver['subscription_end_date']) - time();
                                    $isExpired = $timeLeft < 0;
                                    $daysLeft = floor($timeLeft / (60 * 60 * 24));
                                    
                                    $statusClass = 'bg-secondary';
                                    $statusText = 'Bilinmiyor';
                                    
                                    if ($driver['status'] == 'pending') {
                                        $statusClass = 'bg-warning text-dark';
                                        $statusText = 'Onay Bekliyor';
                                    } elseif ($driver['status'] == 'approved') {
                                        $statusClass = 'bg-success';
                                        $statusText = 'Onaylı';
                                    } elseif ($driver['status'] == 'rejected') {
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Reddedildi';
                                    }
                                    
                                    // Süresi dolmuşsa ve onaylıysa uyar
                                    if ($driver['status'] == 'approved' && $isExpired) {
                                        $statusClass = 'bg-danger';
                                        $statusText = 'Süresi Doldu';
                                    }
                                ?>
                                <tr>
                                    <td>#<?= $driver['id'] ?></td>
                                    <td>
                                        <div class="fw-bold"><?= htmlspecialchars($driver['full_name']) ?></div>
                                        <div class="text-muted small"><i class="fas fa-phone me-1"></i><?= htmlspecialchars($driver['username'] ?? $driver['phone'] ?? 'Belirtilmemiş') ?></div>
                                        <div class="mt-1">
                                            <?php if (!empty($driver['device_id'])): ?>
                                                <span class="badge bg-info text-dark" title="Cihaz Kilitli"><i class="fas fa-lock"></i> Kilitli</span>
                                                <form method="POST" style="display:inline-block; margin-left: 5px;">
                                                    <input type="hidden" name="driver_id" value="<?= $driver['id'] ?>">
                                                    <button type="submit" name="reset_device" class="btn btn-warning btn-sm p-0 px-1" title="Kilidi Aç" onclick="return confirm('Cihaz kilidini kaldırmak istiyor musunuz?')">
                                                        <i class="fas fa-unlock"></i>
                                                    </button>
                                                </form>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border"><i class="fas fa-lock-open"></i> Serbest</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($driver['car_model']) ?></div>
                                        <div class="text-muted small"><?= htmlspecialchars($driver['plate_number']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge <?= $statusClass ?> status-badge"><?= $statusText ?></span>
                                    </td>
                                    <td>
                                        <div><?= date('d.m.Y', strtotime($driver['subscription_end_date'])) ?></div>
                                        <?php if ($driver['status'] == 'approved' && !$isExpired): ?>
                                            <small class="text-success"><?= $daysLeft ?> gün kaldı</small>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <?php if ($driver['status'] == 'pending'): ?>
                                                <a href="?approve=<?= $driver['id'] ?>" class="btn btn-sm btn-success" title="Onayla">
                                                    <i class="fas fa-check"></i>
                                                </a>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-primary" 
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($driver)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button type="button" class="btn btn-sm btn-outline-warning" 
                                                    onclick="openExtendModal(<?= $driver['id'] ?>, '<?= htmlspecialchars($driver['full_name']) ?>')">
                                                <i class="fas fa-clock"></i>
                                            </button>
                                            
                                            <a href="?delete=<?= $driver['id'] ?>" class="btn btn-sm btn-outline-danger" 
                                               onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Driver Modal -->
    <div class="modal fade" id="addDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Yeni Sürücü Ekle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" name="full_name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                    <label class="form-label">Telefon Numarası</label>
                    <input type="text" name="phone" class="form-control" placeholder="5XXXXXXXXX" required>
                  </div>
                  <!-- Kullanıcı Adı alanı kaldırıldı, telefon numarası kullanılacak -->
                    <!-- Şifre alanı kaldırıldı (Sistemde şifre yok) -->
                    <input type="hidden" name="password" value="auto_generated">
                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Araç Modeli</label>
                                <input type="text" name="car_model" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Plaka</label>
                                <input type="text" name="plate_number" class="form-control" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Abonelik Süresi</label>
                            <select name="duration" class="form-select">
                                <option value="7">1 Hafta</option>
                                <option value="30">1 Ay</option>
                                <option value="90">3 Ay</option>
                                <option value="365">1 Yıl</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="add_driver" class="btn btn-primary">Kaydet</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Driver Modal -->
    <div class="modal fade" id="editDriverModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Sürücü Düzenle</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="driver_id" id="editDriverId">
                        
                        <div class="mb-3">
                            <label class="form-label">Ad Soyad</label>
                            <input type="text" name="full_name" id="editFullName" class="form-control" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Telefon Numarası</label>
                            <input type="text" name="phone" id="editPhone" class="form-control" required>
                        </div>

                        <div class="row">
                            <div class="col-6 mb-3">
                                <label class="form-label">Araç Modeli</label>
                                <input type="text" name="car_model" id="editCarModel" class="form-control" required>
                            </div>
                            <div class="col-6 mb-3">
                                <label class="form-label">Plaka</label>
                                <input type="text" name="plate_number" id="editPlateNumber" class="form-control" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Durum</label>
                            <select name="status" id="editStatus" class="form-select">
                                <option value="pending">Onay Bekliyor</option>
                                <option value="approved">Onaylı</option>
                                <option value="rejected">Reddedildi</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="edit_driver" class="btn btn-primary">Güncelle</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Extend Subscription Modal -->
    <div class="modal fade" id="extendModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Süre Uzat</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="driver_id" id="extendDriverId">
                        <p id="extendDriverName" class="fw-bold mb-3"></p>
                        <div class="mb-3">
                            <label class="form-label">Ne kadar uzatılacak?</label>
                            <select name="extension_days" class="form-select">
                                <option value="7">1 Hafta (+7 Gün)</option>
                                <option value="30">1 Ay (+30 Gün)</option>
                                <option value="90">3 Ay (+90 Gün)</option>
                                <option value="365">1 Yıl (+365 Gün)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="extend_subscription" class="btn btn-success">Uzat</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function openExtendModal(id, name) {
            document.getElementById('extendDriverId').value = id;
            document.getElementById('extendDriverName').innerText = name + ' için süre uzatılıyor';
            new bootstrap.Modal(document.getElementById('extendModal')).show();
        }

        function openEditModal(driver) {
            document.getElementById('editDriverId').value = driver.id;
            document.getElementById('editFullName').value = driver.full_name;
            document.getElementById('editPhone').value = driver.phone || driver.username;
            document.getElementById('editCarModel').value = driver.car_model;
            document.getElementById('editPlateNumber').value = driver.plate_number;
            document.getElementById('editStatus').value = driver.status;
            
            new bootstrap.Modal(document.getElementById('editDriverModal')).show();
        }
    </script>
</body>
</html>

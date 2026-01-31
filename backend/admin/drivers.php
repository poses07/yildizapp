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
    $phone = $_POST['phone']; // Telefon numarası eklendi
    $username = $_POST['username'];
    $password = md5($_POST['password']);
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

            <div class="row">
                <?php foreach ($drivers as $driver): 
                    $timeLeft = strtotime($driver['subscription_end_date']) - time();
                    $isExpired = $timeLeft < 0;
                    $daysLeft = floor($timeLeft / (60 * 60 * 24));
                ?>
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div>
                                    <h5 class="card-title fw-bold mb-1"><?= htmlspecialchars($driver['full_name']) ?></h5>
                                    <small class="text-muted">@<?= htmlspecialchars($driver['username']) ?></small>
                                </div>
                                <?php if ($isExpired): ?>
                                    <span class="badge bg-danger status-badge">Süresi Doldu</span>
                                <?php else: ?>
                                    <span class="badge bg-success status-badge"><?= $daysLeft ?> Gün Kaldı</span>
                                <?php endif; ?>
                            </div>
                            
                            <p class="mb-1"><i class="fas fa-phone me-2 text-muted"></i><?= htmlspecialchars($driver['phone'] ?? 'Belirtilmemiş') ?></p>
                            <p class="mb-1"><i class="fas fa-car me-2 text-muted"></i><?= htmlspecialchars($driver['car_model']) ?></p>
                            <p class="mb-3"><i class="fas fa-hashtag me-2 text-muted"></i><?= htmlspecialchars($driver['plate_number']) ?></p>
                            
                            <div class="d-flex justify-content-between align-items-center border-top pt-3 mt-2">
                                <small class="text-muted">Bitiş: <?= date('d.m.Y', strtotime($driver['subscription_end_date'])) ?></small>
                                <div>
                                    <button class="btn btn-sm btn-outline-primary me-1" 
                                            onclick="openExtendModal(<?= $driver['id'] ?>, '<?= htmlspecialchars($driver['full_name']) ?>')">
                                        <i class="fas fa-clock"></i> Uzat
                                    </button>
                                    <a href="?delete=<?= $driver['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Silmek istediğinize emin misiniz?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
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
                        <div class="mb-3">
                            <label class="form-label">Kullanıcı Adı</label>
                            <input type="text" name="username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Şifre</label>
                            <input type="password" name="password" class="form-control" required>
                        </div>
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
    </script>
</body>
</html>

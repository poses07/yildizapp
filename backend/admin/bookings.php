<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
require_once '../db.php';

// Filtreleme parametreleri
$filter_status = isset($_GET['status']) ? $_GET['status'] : '';
$search = isset($_GET['search']) ? $_GET['search'] : '';

// SQL Sorgusu Hazırlama
$sql = "SELECT b.*, u.name as user_name, u.phone as user_phone, 
        d.full_name as driver_name, d.username as driver_phone,
        r.rating, r.comment, r.tags
        FROM bookings b 
        LEFT JOIN users u ON b.user_id = u.id 
        LEFT JOIN drivers d ON b.driver_id = d.id 
        LEFT JOIN booking_ratings r ON b.id = r.booking_id
        WHERE 1=1";

$params = [];

if ($filter_status) {
    $sql .= " AND b.status = ?";
    $params[] = $filter_status;
}

if ($search) {
    $sql .= " AND (u.name LIKE ? OR d.full_name LIKE ? OR b.pickup_address LIKE ? OR b.dropoff_address LIKE ?)";
    $search_term = "%$search%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

$sql .= " ORDER BY b.created_at DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$bookings = $stmt->fetchAll();

// İstatistikler
$totalBookings = $pdo->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
$completedBookings = $pdo->query("SELECT COUNT(*) FROM bookings WHERE status = 'completed'")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Transferler - Yıldız Taksi Yönetim</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f4f6f9; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); margin-bottom: 20px; }
        .card-header { background-color: #fff; border-bottom: 1px solid #f0f0f0; padding: 20px; border-radius: 15px 15px 0 0 !important; }
        .card-header h5 { margin: 0; color: #333; font-weight: 600; }
        .table th { font-weight: 600; color: #555; background-color: #f8f9fa; border-bottom: 2px solid #e9ecef; }
        .table td { vertical-align: middle; }
        .badge-status-pending { background-color: #ffc107; color: #000; }
        .badge-status-accepted { background-color: #17a2b8; color: #fff; }
        .badge-status-on_way { background-color: #007bff; color: #fff; }
        .badge-status-completed { background-color: #28a745; color: #fff; }
        .badge-status-cancelled { background-color: #dc3545; color: #fff; }
        
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
    
    <?php include 'sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid">
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h3 class="fw-bold mb-0 text-dark">Transfer Yönetimi</h3>
                <div>
                    <span class="badge bg-light text-dark border p-2 me-2">
                        <i class="fas fa-route me-1"></i> Toplam: <?= $totalBookings ?>
                    </span>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success p-2">
                        <i class="fas fa-check-circle me-1"></i> Tamamlanan: <?= $completedBookings ?>
                    </span>
                </div>
            </div>

            <!-- Filtreleme ve Arama -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-4">
                            <input type="text" name="search" class="form-control" placeholder="Müşteri, Sürücü veya Adres Ara..." value="<?= htmlspecialchars($search) ?>">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Tüm Durumlar</option>
                                <option value="pending" <?= $filter_status == 'pending' ? 'selected' : '' ?>>Bekleyen</option>
                                <option value="accepted" <?= $filter_status == 'accepted' ? 'selected' : '' ?>>Kabul Edilen</option>
                                <option value="on_way" <?= $filter_status == 'on_way' ? 'selected' : '' ?>>Yolda</option>
                                <option value="completed" <?= $filter_status == 'completed' ? 'selected' : '' ?>>Tamamlanan</option>
                                <option value="cancelled" <?= $filter_status == 'cancelled' ? 'selected' : '' ?>>İptal Edilen</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-filter me-1"></i> Filtrele
                            </button>
                        </div>
                        <?php if ($search || $filter_status): ?>
                        <div class="col-md-2">
                            <a href="bookings.php" class="btn btn-outline-secondary w-100">
                                <i class="fas fa-times me-1"></i> Temizle
                            </a>
                        </div>
                        <?php endif; ?>
                    </form>
                </div>
            </div>

            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead>
                                <tr>
                                    <th class="ps-4">#ID</th>
                                    <th>Müşteri</th>
                                    <th>Sürücü</th>
                                    <th>Değerlendirme</th>
                                    <th>Mesajlar</th>
                                    <th>Güzergah</th>
                                    <th>Tarih</th>
                                    <th>Tutar</th>
                                    <th>Durum</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($bookings)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-5 text-muted">
                                            <i class="fas fa-inbox fa-3x mb-3 opacity-25"></i>
                                            <p class="mb-0">Kayıt bulunamadı.</p>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($bookings as $booking): ?>
                                        <?php 
                                            $statusClass = 'badge-status-' . ($booking['status'] ?? 'pending');
                                            $statusLabel = [
                                                'pending' => 'Bekleniyor',
                                                'accepted' => 'Kabul Edildi',
                                                'on_way' => 'Yolda',
                                                'completed' => 'Tamamlandı',
                                                'cancelled' => 'İptal Edildi'
                                            ][$booking['status']] ?? 'Bilinmiyor';
                                        ?>
                                        <tr>
                                            <td class="ps-4"><strong>#<?= $booking['id'] ?></strong></td>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="bg-light rounded-circle p-2 me-2">
                                                        <i class="fas fa-user text-secondary"></i>
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold"><?= htmlspecialchars($booking['user_name'] ?? 'Bilinmiyor') ?></div>
                                                        <div class="small text-muted"><?= htmlspecialchars($booking['user_phone'] ?? '-') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <?php if ($booking['driver_id']): ?>
                                                    <div class="d-flex align-items-center">
                                                        <div class="bg-warning bg-opacity-10 rounded-circle p-2 me-2">
                                                            <i class="fas fa-taxi text-warning"></i>
                                                        </div>
                                                        <div>
                                                            <div class="fw-bold"><?= htmlspecialchars($booking['driver_name'] ?? 'Bilinmiyor') ?></div>
                                                            <div class="small text-muted"><?= htmlspecialchars($booking['driver_phone'] ?? '-') ?></div>
                                                        </div>
                                                    </div>
                                                <?php else: ?>
                                                    <span class="text-muted fst-italic">- Atanmadı -</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($booking['rating']): ?>
                                                    <div style="color: #ffc107;">
                                                        <?= str_repeat('★', $booking['rating']) ?>
                                                        <?= str_repeat('☆', 5 - $booking['rating']) ?>
                                                    </div>
                                                    <?php if ($booking['tags']): ?>
                                                        <small class="text-muted d-block"><?= htmlspecialchars($booking['tags']) ?></small>
                                                    <?php endif; ?>
                                                    <?php if ($booking['comment']): ?>
                                                        <small class="d-block text-secondary"><i>"<?= htmlspecialchars($booking['comment']) ?>"</i></small>
                                                    <?php endif; ?>
                                                <?php else: ?>
                                                    <span class="text-muted">-</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-sm btn-info text-white" 
                                                        onclick="showMessages(<?= $booking['id'] ?>)">
                                                    Mesajları Gör
                                                </button>
                                            </td>
                                            <td>
                                                <div style="max-width: 250px;">
                                                    <div class="d-flex align-items-center mb-1">
                                                        <i class="fas fa-circle text-success me-2" style="font-size: 8px;"></i>
                                                        <span class="text-truncate me-1" title="<?= htmlspecialchars($booking['pickup_address']) ?>">
                                                            <?= htmlspecialchars($booking['pickup_address']) ?>
                                                        </span>
                                                        <?php if (!empty($booking['pickup_lat']) && !empty($booking['pickup_lng'])): ?>
                                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $booking['pickup_lat'] ?>,<?= $booking['pickup_lng'] ?>" target="_blank" class="text-success ms-auto" title="Haritada Gör">
                                                                <i class="fas fa-map-marked-alt"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                    <div class="d-flex align-items-center">
                                                        <i class="fas fa-circle text-danger me-2" style="font-size: 8px;"></i>
                                                        <span class="text-truncate me-1" title="<?= htmlspecialchars($booking['dropoff_address']) ?>">
                                                            <?= htmlspecialchars($booking['dropoff_address']) ?>
                                                        </span>
                                                        <?php if (!empty($booking['dropoff_lat']) && !empty($booking['dropoff_lng'])): ?>
                                                            <a href="https://www.google.com/maps/search/?api=1&query=<?= $booking['dropoff_lat'] ?>,<?= $booking['dropoff_lng'] ?>" target="_blank" class="text-danger ms-auto" title="Haritada Gör">
                                                                <i class="fas fa-map-marked-alt"></i>
                                                            </a>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="text-muted small">
                                                    <i class="far fa-calendar-alt me-1"></i>
                                                    <?= date('d.m.Y', strtotime($booking['created_at'])) ?>
                                                </div>
                                                <div class="fw-bold small">
                                                    <i class="far fa-clock me-1"></i>
                                                    <?= date('H:i', strtotime($booking['created_at'])) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="fw-bold text-success">
                                                    <?= number_format($booking['price'], 2) ?> ₺
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $statusClass ?> px-3 py-2 rounded-pill">
                                                    <?= $statusLabel ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="card-footer bg-white text-muted small text-center border-0 py-3">
                    Toplam <?= count($bookings) ?> kayıt listeleniyor.
                </div>
            </div>
        </div>
    </div>

    <!-- Değerlendirme Modal -->
    <div class="modal fade" id="messagesModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mesajlaşma Geçmişi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="messagesContent" style="max-height: 400px; overflow-y: auto;">
                        <div class="text-center py-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Yükleniyor...</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function showMessages(bookingId) {
            const modal = new bootstrap.Modal(document.getElementById('messagesModal'));
            modal.show();
            
            const contentDiv = document.getElementById('messagesContent');
            contentDiv.innerHTML = `
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Yükleniyor...</span>
                    </div>
                </div>
            `;
            
            fetch(`../api/get_messages.php?booking_id=${bookingId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.data && data.data.length > 0) {
                        let html = '<div class="d-flex flex-column gap-3 p-3">';
                        
                        data.data.forEach(msg => {
                            const isDriver = msg.sender_type === 'driver';
                            const alignClass = isDriver ? 'align-self-start' : 'align-self-end';
                            const bgClass = isDriver ? 'bg-light border' : 'bg-primary text-white';
                            const senderName = isDriver ? 'Sürücü' : 'Müşteri';
                            
                            html += `
                                <div class="${alignClass}" style="max-width: 75%;">
                                    <div class="small text-muted mb-1 ${isDriver ? 'text-start' : 'text-end'}">
                                        ${senderName} • ${new Date(msg.created_at).toLocaleTimeString('tr-TR', {hour: '2-digit', minute:'2-digit'})}
                                    </div>
                                    <div class="${bgClass} rounded p-3 shadow-sm">
                                        ${msg.message}
                                    </div>
                                </div>
                            `;
                        });
                        
                        html += '</div>';
                        contentDiv.innerHTML = html;
                    } else {
                        contentDiv.innerHTML = `
                            <div class="text-center py-5 text-muted">
                                <i class="bi bi-chat-dots fs-1 d-block mb-3"></i>
                                <p>Bu transfer için mesajlaşma kaydı bulunmuyor.</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    contentDiv.innerHTML = `
                        <div class="alert alert-danger m-3">
                            Mesajlar yüklenirken bir hata oluştu.
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>

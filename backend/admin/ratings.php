<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
require_once '../db.php';

// Değerlendirmeleri Getir (Updated to use booking_ratings)
try {
    $sql = "
        SELECT r.*, 
               u.name as user_name, u.phone as user_phone,
               d.full_name as driver_name, d.plate_number
        FROM booking_ratings r
        LEFT JOIN bookings b ON r.booking_id = b.id
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN drivers d ON b.driver_id = d.id
        ORDER BY r.created_at DESC
    ";
    $ratings = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Değerlendirmeler - Yıldız Taksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { margin-left: 280px; padding: 20px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 60px; } }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .rating-star { color: #ffc107; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold">Sürücü Değerlendirmeleri</h4>
            <span class="badge bg-warning text-dark rounded-pill"><?= count($ratings) ?> Değerlendirme</span>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Müşteri</th>
                                <th>Sürücü</th>
                                <th>Puan</th>
                                <th>Yorum</th>
                                <th>Etiketler</th>
                                <th>Transfer ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($ratings as $rating): ?>
                            <tr>
                                <td class="text-muted" style="width: 150px;">
                                    <small><?= date('d.m.Y H:i', strtotime($rating['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($rating['user_name'] ?? 'Bilinmiyor') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($rating['user_phone'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($rating['driver_name'] ?? 'Bilinmiyor') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($rating['plate_number'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <span class="fs-5 fw-bold me-2"><?= $rating['rating'] ?></span>
                                        <div class="text-warning">
                                            <?php for($i=1; $i<=5; $i++): ?>
                                                <i class="<?= $i <= $rating['rating'] ? 'fas' : 'far' ?> fa-star"></i>
                                            <?php endfor; ?>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <?php if (!empty($rating['comment'])): ?>
                                        <?= htmlspecialchars($rating['comment']) ?>
                                    <?php else: ?>
                                        <span class="text-muted fst-italic">Yorum yok</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php 
                                    if (!empty($rating['tags'])) {
                                        $tags = explode(',', $rating['tags']);
                                        foreach ($tags as $tag) {
                                            echo '<span class="badge bg-light text-dark border me-1 mb-1">' . htmlspecialchars(trim($tag)) . '</span>';
                                        }
                                    } else {
                                        echo '<span class="text-muted">-</span>';
                                    }
                                    ?>
                                </td>
                                <td>
                                    <a href="bookings.php?id=<?= $rating['booking_id'] ?>" class="badge bg-secondary text-decoration-none">
                                        #<?= $rating['booking_id'] ?>
                                    </a>
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

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>

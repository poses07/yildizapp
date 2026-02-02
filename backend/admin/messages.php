<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: index.php");
    exit;
}
require_once '../db.php';

// Mesajları Getir
try {
    $sql = "
        SELECT m.*,
               CASE 
                   WHEN m.sender_type = 'user' THEN u.name
                   WHEN m.sender_type = 'driver' THEN d.full_name
                   ELSE 'Bilinmiyor'
               END as sender_name,
               CASE 
                   WHEN m.sender_type = 'user' THEN u.phone
                   WHEN m.sender_type = 'driver' THEN d.username
                   ELSE ''
               END as sender_phone,
               CASE 
                   WHEN m.sender_type = 'user' THEN d.full_name
                   WHEN m.sender_type = 'driver' THEN u.name
                   ELSE 'Bilinmiyor'
               END as receiver_name,
               CASE 
                   WHEN m.sender_type = 'user' THEN d.username
                   WHEN m.sender_type = 'driver' THEN u.phone
                   ELSE ''
               END as receiver_phone
        FROM booking_messages m
        LEFT JOIN bookings b ON m.booking_id = b.id
        LEFT JOIN users u ON b.user_id = u.id
        LEFT JOIN drivers d ON b.driver_id = d.id
        ORDER BY m.created_at DESC
    ";
    $messages = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Hata: " . $e->getMessage());
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mesajlar - Yıldız Taksi</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        body { background-color: #f8f9fa; }
        .main-content { margin-left: 280px; padding: 20px; }
        @media (max-width: 768px) { .main-content { margin-left: 0; padding-top: 60px; } }
        .card { border: none; border-radius: 15px; box-shadow: 0 4px 6px rgba(0,0,0,0.05); }
        .message-bubble { padding: 10px 15px; border-radius: 15px; background-color: #f1f3f5; display: inline-block; max-width: 80%; }
    </style>
</head>
<body>

<?php include 'sidebar.php'; ?>

<div class="main-content">
    <div class="container-fluid">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h4 class="mb-0 fw-bold">Mesaj Geçmişi</h4>
            <span class="badge bg-primary rounded-pill"><?= count($messages) ?> Mesaj</span>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Tarih</th>
                                <th>Gönderen</th>
                                <th>Alıcı</th>
                                <th>Mesaj</th>
                                <th>Rezervasyon ID</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($messages as $msg): ?>
                            <tr>
                                <td class="text-muted" style="width: 150px;">
                                    <small><?= date('d.m.Y H:i', strtotime($msg['created_at'])) ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($msg['sender_name'] ?? 'Bilinmiyor') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($msg['sender_phone'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="fw-bold"><?= htmlspecialchars($msg['receiver_name'] ?? 'Bilinmiyor') ?></div>
                                    <small class="text-muted"><?= htmlspecialchars($msg['receiver_phone'] ?? '') ?></small>
                                </td>
                                <td>
                                    <div class="message-bubble">
                                        <?= htmlspecialchars($msg['message']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php if ($msg['booking_id']): ?>
                                        <a href="bookings.php?id=<?= $msg['booking_id'] ?>" class="badge bg-secondary text-decoration-none">
                                            #<?= $msg['booking_id'] ?>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted">-</span>
                                    <?php endif; ?>
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

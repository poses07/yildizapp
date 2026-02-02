<?php
$config = require __DIR__ . '/../config.php';
require __DIR__ . '/../evolution_api_client.php';

function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
$inst = (string)($config['evolution']['instance'] ?? '');
$state = $inst !== '' ? evoGetInstanceState($inst) : ['ok'=>false,'error'=>'no_instance'];
$qr = $inst !== '' ? evoGetQRCodeBase64($inst) : ['ok'=>false,'error'=>'no_instance'];
?><!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>WPVoucher Evolution QR</title>
  <link rel="stylesheet" href="style.css">
  </head>
<body>
  <div class="app">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
      <div class="container">
        <?php
          $pageTitle = 'WhatsApp QR Bağlantısı';
          $pageSubtitle = 'Instance: ' . h($inst);
          $breadcrumbs = [ ['label'=>'Panel', 'href'=>'index.php'], ['label'=>'QR'] ];
          require __DIR__ . '/includes/header.php';
        ?>

    <?php
      $d = $state['data'] ?? [];
      $stateRaw = strtolower((string)($d['state'] ?? $d['connectionStatus'] ?? $d['status'] ?? ($d['instance']['state'] ?? '')));
      $ok = in_array($stateRaw, ['connected','online','ready','open']);
      $base64 = $qr['base64'] ?? null;
      $pairingCode = $qr['pairingCode'] ?? ($qr['data']['pairingCode'] ?? null);
      // Only show QR for explicit pairing states, not for 'open'
      $needQr = !$ok && in_array($stateRaw ?: 'pairing', ['pairing','qrcode','connecting']);
      // If pairing and no QR yet, start and poll for QR a few times
      if ($needQr && empty($base64) && empty($pairingCode)) {
        $qr2 = evoStartAndFetchQr($inst, 8, 1000);
        if (!empty($qr2['ok'])) {
          if (!empty($qr2['base64'])) { $base64 = $qr2['base64']; }
          if (!empty($qr2['pairingCode'])) { $pairingCode = $qr2['pairingCode']; }
        }
      }
    ?>

    <div class="card">
      <?php if ($ok): ?>
        <p><span class="badge ok">Bağlı</span> Cihaz bağlı görünüyor.</p>
      <?php elseif ($needQr && ($base64 || $pairingCode)): ?>
        <div class="qr">
          <?php if ($pairingCode): ?>
            <p><strong>Pairing Code:</strong> <?php echo h($pairingCode); ?></p>
          <?php endif; ?>
          <?php if ($base64): ?>
            <img alt="WhatsApp QR" src="data:image/png;base64,<?php echo h($base64); ?>" />
          <?php endif; ?>
          <div class="qr-instructions">
            <h3>Nasıl Bağlanırım?</h3>
            <ol>
              <li>WhatsApp’ı açın.</li>
              <li>Menü → Bağlı cihazlar → Cihaz bağla.</li>
              <li>Bu sayfadaki QR kodunu tarayın veya Pairing Code girin.</li>
            </ol>
            <p class="muted">QR görünmüyorsa birkaç saniye içinde sayfayı yenileyin.</p>
          </div>
        </div>
      <?php else: ?>
        <p><span class="badge warn">Bağlı değil</span> QR üretilemiyor. Instance’ı kontrol edin.</p>
      <?php endif; ?>

      <hr>
      <p class="muted">Durum: <?php echo h($stateRaw ?: 'bilinmiyor'); ?></p>
    </div>
      </div>
    </div>
  </div>
</body>
</html>

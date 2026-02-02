<?php
$config = require __DIR__ . '/../config.php';
require __DIR__ . '/../evolution_api_client.php';
require __DIR__ . '/../db.php';

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

$enabled = (bool)($config['public']['enabled'] ?? false);
if (!$enabled) { http_response_code(403); echo 'forbidden'; exit; }

$inst = (string)($config['evolution']['instance'] ?? '');
$action = (string)($_GET['action'] ?? ($_POST['action'] ?? ''));
$out = [];

if ($action === 'run') {
  $token = (string)($_GET['token'] ?? '');
  $expected = (string)($config['public']['run_token'] ?? '');
  if ($token === '' || !hash_equals($expected, $token)) { http_response_code(403); echo 'forbidden'; exit; }
  require __DIR__ . '/../watcher.php';
  exit;
}

if ($action === 'create' && $inst !== '') { $out['create'] = evoCreateInstance($inst); }
if ($action === 'status' && $inst !== '') { $out['status'] = evoGetStatus($inst); }
if ($action === 'start' && $inst !== '') { $out['start'] = evoStartInstance($inst); }
if ($action === 'delete' && $inst !== '') {
  $out['delete'] = evoDeleteInstance($inst);
  // Instance başarıyla silindiyse config.php içindeki aktif instance'ı boşalt
  if (!empty($out['delete']['ok'])) {
    $cfgPath = __DIR__ . '/../config.php';
    $src = @file_get_contents($cfgPath);
    $old = (string)($config['evolution']['instance'] ?? '');
    $needle = "'instance' => '" . addslashes($old) . "'";
    $replacement = "'instance' => ''";
    $okSet = false; $errSet = '';
    if ($src !== false) {
      if (strpos($src, $needle) !== false) {
        $src2 = str_replace($needle, $replacement, $src);
        $okSet = @file_put_contents($cfgPath, $src2) !== false;
      } else {
        // Regex yedek: 'instance' => '...'
        $src2 = preg_replace("/(\'instance\'\s*=>\s*\')([^\']*)(\')/", "'instance' => ''", $src);
        if ($src2 !== null) { $okSet = @file_put_contents($cfgPath, $src2) !== false; }
      }
    } else {
      $errSet = 'config_read_failed';
    }
    // Bellekteki konfigürasyonu ve başlık inst değerini de boşalt
    if ($okSet) {
      $config['evolution']['instance'] = '';
      $inst = '';
    }
    $out['config_set'] = ['ok' => $okSet, 'name' => '', 'error' => $errSet];
  }
}
if ($action === 'info') { $out['info'] = evoGetInfo(); }

// Yeni instance oluşturma akışı (POST)
if ($action === 'create_new') {
  $newInst = trim((string)($_POST['new_instance'] ?? ''));
  $sanitized = preg_replace('/[^A-Za-z0-9 _\-]/', '', $newInst);
  if ($sanitized === '' || strlen($sanitized) < 2) {
    $out['create_new'] = ['ok' => false, 'error' => 'invalid_name', 'status' => 0];
  } else {
    $res = evoCreateInstance($sanitized);
    $out['create_new'] = $res;
    // Başarılıysa config.php içindeki aktif instance adını güncelle
    if (!empty($res['ok'])) {
      $cfgPath = __DIR__ . '/../config.php';
      $src = @file_get_contents($cfgPath);
      $old = (string)($config['evolution']['instance'] ?? '');
      $needle = "'instance' => '" . addslashes($old) . "'";
      $replacement = "'instance' => '" . addslashes($sanitized) . "'";
      $okSet = false; $errSet = '';
      if ($src !== false) {
        if (strpos($src, $needle) !== false) {
          $src2 = str_replace($needle, $replacement, $src);
          $okSet = @file_put_contents($cfgPath, $src2) !== false;
        } else {
          // Regex yedek: 'instance' => '...'
          $src2 = preg_replace("/(\'instance\'\s*=>\s*\')([^\']*)(\')/", "'$1" . addslashes($sanitized) . "'$3", $src);
          if ($src2 !== null) { $okSet = @file_put_contents($cfgPath, $src2) !== false; }
        }
      } else {
        $errSet = 'config_read_failed';
      }
      $out['config_set'] = ['ok' => $okSet, 'name' => $sanitized, 'error' => $errSet];
      if ($okSet) { $inst = $sanitized; }
    }
  }
}

?><!doctype html>
<html lang="tr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>WPVoucher Evolution Instance</title>
  <link rel="stylesheet" href="style.css">
  </head>
<body>
  <div class="app">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
      <div class="container">
    <?php
      $pageTitle = 'Evolution API Instance Yönetimi';
      $pageSubtitle = 'Instance: ' . h($inst);
      $breadcrumbs = [ ['label'=>'Panel'] ];
      require __DIR__ . '/includes/header.php';
    ?>

    <?php
      // Mesaj istatistikleri
      $stats = ['total'=>0,'new'=>0,'approved'=>0,'canceled'=>0,'ok'=>true,'error'=>''];
      try {
        $pdo = db();
        $row = $pdo->query("SELECT COUNT(*) AS total,
                                    SUM(stage='new') AS new,
                                    SUM(stage='approved') AS approved,
                                    SUM(stage='canceled') AS canceled
                             FROM sent_logs")->fetch();
        if ($row) {
          $stats['total'] = (int)($row['total'] ?? 0);
          $stats['new'] = (int)($row['new'] ?? 0);
          $stats['approved'] = (int)($row['approved'] ?? 0);
          $stats['canceled'] = (int)($row['canceled'] ?? 0);
        }
      } catch (Throwable $e) {
        $stats['ok'] = false;
        $stats['error'] = $e->getMessage();
      }
    ?>

    <div class="grid-2">
      <div class="card">
        <h3>İşlemler</h3>
        <div class="action-list">
          <a class="btn block" href="?action=create">✨ Instance Oluştur</a>
          <a class="btn block" href="?action=status">🔎 Durumu Kontrol Et</a>
          <a class="btn block" href="?action=start">⚡ Bağlantıyı Başlat</a>
          <a class="btn secondary block" href="qr.php">🧾 QR Kodu Göster</a>
          <a class="btn secondary block" href="messages.php">✉️ Mesaj Şablonları</a>
          <a class="btn secondary block" href="?action=delete" onclick="return confirm('Instance silinsin mi? Bu işlem geri alınamaz.');">🗑️ Instance Sil</a>
          <a class="btn secondary block" href="?action=info">ℹ️ API Bilgisi</a>
        </div>

        <hr>
        <h4>Yeni Instance Oluştur</h4>
        <form method="post" action="">
          <input type="hidden" name="action" value="create_new">
          <div class="action-list">
            <input class="input" type="text" name="new_instance" placeholder="Örn: OrionPanel" required>
            <button type="submit" class="btn block">✨ Oluştur ve Aktif Et</button>
          </div>
          <p class="muted">API URL ve anahtar <code>wpvoucher/config.php</code> içinde ayarlı.</p>
        </form>
      </div>

      <div class="card">
        <h3>Sonuç</h3>
        <?php if (isset($out['status'])): $s=$out['status']; $d=$s['data'] ?? []; $stateRaw=strtolower((string)($d['state'] ?? $d['connectionStatus'] ?? $d['status'] ?? ($d['instance']['state'] ?? ''))); $ok=in_array($stateRaw,['connected','online','ready','open']); ?>
          <p><span class="badge <?php echo $ok? 'ok' : ($stateRaw? 'warn':'err'); ?>">Durum: <?php echo h($stateRaw ?: 'bilinmiyor'); ?></span></p>
        <?php endif; ?>

        <?php if (isset($out['create'])): $c=$out['create']; ?>
          <p><span class="badge <?php echo !empty($c['ok'])? 'ok':'err'; ?>">Instance <?php echo !empty($c['ok'])? 'hazır' : 'başarısız'; ?></span></p>
          <?php if (!empty($c['status'])): ?><p class="muted">HTTP: <?php echo h($c['status']); ?></p><?php endif; ?>
          
          <?php if (!empty($c['error'])): ?><p class="muted">Hata: <?php echo h($c['error']); ?></p><?php endif; ?>
          <?php if (!empty($c['route'])): ?><p class="muted">Deneme rota: <?php echo h($c['route']); ?></p><?php endif; ?>
          <p class="muted">Instance mevcutsa <a href="?action=start">başlat</a> ve <a href="qr.php">QR</a> sayfasına geç.</p>
        <?php endif; ?>

        <?php if (isset($out['start'])): $st=$out['start']; ?>
          <p><span class="badge <?php echo !empty($st['ok'])? 'ok':'warn'; ?>">Başlat: <?php echo !empty($st['ok'])? 'başlatıldı' : 'başlatılamadı'; ?></span></p>
          <?php if (!empty($st['status'])): ?><p class="muted">HTTP: <?php echo h($st['status']); ?></p><?php endif; ?>
          
          <?php if (!empty($st['error'])): ?><p class="muted">Hata: <?php echo h($st['error']); ?></p><?php endif; ?>
          <p class="muted">QR gerekiyorsa <a href="qr.php">QR sayfasına</a> gidin.</p>
        <?php endif; ?>

        <?php if (isset($out['delete'])): $del=$out['delete']; ?>
          <p><span class="badge <?php echo !empty($del['ok'])? 'ok':'err'; ?>">Sil: <?php echo !empty($del['ok'])? 'silindi' : 'başarısız'; ?></span></p>
          <?php if (!empty($del['status'])): ?><p class="muted">HTTP: <?php echo h($del['status']); ?></p><?php endif; ?>
          <?php if (!empty($del['error'])): ?><p class="muted">Hata: <?php echo h($del['error']); ?></p><?php endif; ?>
          <p class="muted">Instance yoksa bu sonuç başarı olarak kabul edilir.</p>
        <?php endif; ?>

        <?php if (isset($out['create_new'])): $cn=$out['create_new']; ?>
          <p><span class="badge <?php echo !empty($cn['ok'])? 'ok':'err'; ?>">Yeni Instance: <?php echo !empty($cn['ok'])? 'oluşturuldu' : 'başarısız'; ?></span></p>
          <?php if (!empty($cn['status'])): ?><p class="muted">HTTP: <?php echo h($cn['status']); ?></p><?php endif; ?>
          <?php if (!empty($cn['error'])): ?><p class="muted">Hata: <?php echo h($cn['error']); ?></p><?php endif; ?>
          <?php if (!empty($cn['route'])): ?><p class="muted">Deneme rota: <?php echo h($cn['route']); ?></p><?php endif; ?>
        <?php endif; ?>

        <?php if (isset($out['config_set'])): $cs=$out['config_set']; ?>
          <p><span class="badge <?php echo !empty($cs['ok'])? 'ok':'warn'; ?>">Aktif Instance Ayarı: <?php echo !empty($cs['ok'])? 'güncellendi' : 'değiştirilemedi'; ?></span></p>
          <?php if (!empty($cs['error'])): ?><p class="muted">Hata: <?php echo h($cs['error']); ?></p><?php endif; ?>
        <?php endif; ?>

        <?php if (isset($out['info'])): $inf=$out['info']; $d=$inf['data'] ?? []; ?>
          <p><span class="badge <?php echo !empty($inf['ok'])? 'ok':'warn'; ?>">API Bilgisi: <?php echo !empty($inf['ok'])? 'erişildi' : 'alınamadı'; ?></span></p>
          <?php if (!empty($inf['status'])): ?><p class="muted">HTTP: <?php echo h($inf['status']); ?></p><?php endif; ?>
          <?php if (!empty($inf['route'])): ?><p class="muted">Rota: <?php echo h($inf['route']); ?></p><?php endif; ?>
          <?php if (!empty($d['message'])): ?><p class="muted">Mesaj: <?php echo h($d['message']); ?></p><?php endif; ?>
          <?php if (!empty($d['version'])): ?><p class="muted">Versiyon: <?php echo h($d['version']); ?></p><?php endif; ?>
          <?php if (!empty($d['swagger'])): ?><p class="muted">Swagger: <a href="<?php echo h($d['swagger']); ?>" target="_blank"><?php echo h($d['swagger']); ?></a></p><?php endif; ?>
        <?php endif; ?>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <h3>Mesaj İstatistikleri</h3>
      <?php if (!$stats['ok']): ?>
        <p class="muted">İstatistikler yüklenemedi: <?php echo h($stats['error']); ?></p>
      <?php endif; ?>
      <div class="kpi" style="margin-top:8px">
        <div class="pill">Toplam: <?php echo h((string)$stats['total']); ?></div>
        <div class="pill">Yeni: <?php echo h((string)$stats['new']); ?></div>
        <div class="pill">Onay: <?php echo h((string)$stats['approved']); ?></div>
        <div class="pill">İptal: <?php echo h((string)$stats['canceled']); ?></div>
      </div>
    </div>

    <div class="card" style="margin-top:16px">
      <h3>Watcher’ı Çalıştır</h3>
      <p class="muted">Güvenli çalıştırma için token gereklidir.</p>
      <p><a class="btn" href="?action=run&amp;token=<?php echo h($config['public']['run_token'] ?? ''); ?>">Watcher’ı Başlat</a></p>
    </div>
      </div>
    </div>
  </div>
</body>
</html>

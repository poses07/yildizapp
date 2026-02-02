<?php
require_once __DIR__ . '/../config.php';

$title = 'Mesaj Şablonları';
$file = __DIR__ . '/../messages.json';
$statusMsg = '';
$statusErr = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $newTpl = [
    'new' => isset($_POST['new']) ? trim((string)$_POST['new']) : '',
    'approved' => isset($_POST['approved']) ? trim((string)$_POST['approved']) : '',
    'canceled' => isset($_POST['canceled']) ? trim((string)$_POST['canceled']) : '',
  ];
  // Serbest metin: doğrulama yok, doğrudan kaydet
  // Boş olmayan alanları kaydet; boşlar mevcut değeri korusun
  $current = [];
  if (file_exists($file)) {
    $curr = json_decode(@file_get_contents($file), true);
    if (is_array($curr)) { $current = $curr; }
  }
  foreach ($newTpl as $k => $v) {
    if ($v !== '') { $current[$k] = $v; }
  }
  $ok = @file_put_contents($file, json_encode($current, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
  if ($ok !== false) {
    $statusMsg = 'Şablon kaydedildi.';
  } else {
    $statusMsg = 'Kaydetme sırasında hata oluştu.';
  }
}

// Görüntülemek için birleşik değer: config + dosya
$values = $config['messages'] ?? [];
if (file_exists($file)) {
  $fromFile = json_decode(@file_get_contents($file), true);
  if (is_array($fromFile)) { $values = array_merge($values, $fromFile); }
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
?>
<!DOCTYPE html>
<html lang="tr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo h($title); ?></title>
  <link rel="stylesheet" href="style.css">
  <style>
    .status { margin-top:8px; color:#12b981; }
    label { font-weight:600; display:block; margin-bottom:6px; }
  </style>
  <link rel="icon" href="favicon.ico">
  <link rel="apple-touch-icon" sizes="180x180" href="apple-touch-icon.png">
  <link rel="manifest" href="site.webmanifest">
  <meta name="theme-color" content="#111827">
  <meta name="robots" content="noindex, nofollow">
  </head>
<body>
  <div class="app">
    <?php require __DIR__ . '/includes/sidebar.php'; ?>
    <div class="main">
      <div class="container">
        <?php
          $pageTitle = 'Mesaj Şablonları';
          $pageSubtitle = '';
          $breadcrumbs = [];
          require __DIR__ . '/includes/header.php';
        ?>

    <?php if ($statusErr !== ''): ?>
    <div class="status" style="color:#dc2626;"><?php echo $statusErr; ?></div>
    <?php endif; ?>

    <?php if ($statusMsg !== ''): ?>
    <div class="status"><?php echo h($statusMsg); ?></div>
    <?php endif; ?>

      <div class="form-grid">
        <form method="post" class="card">
          <h2>Yeni Rezervasyon Mesajı</h2>
          <div class="label-row">
            <label for="new">Şablon</label>
            <span class="action toggle-preview" data-target="#prev-new">Önizleme</span>
          </div>
          <textarea class="textarea" id="new" name="new" placeholder="Mesaj şablonu..." data-preview="#prev-new" data-count="#count-new"><?php echo h((string)($values['new'] ?? '')); ?></textarea>
          <div class="help">Kısa ve net bir dille yazın.</div>
          <div class="char-count" id="count-new"></div>
          <div class="preview-box" id="prev-new"></div>
          <div class="footer-actions">
            <button class="btn" type="submit">Kaydet</button>
          </div>
        </form>

        <form method="post" class="card">
          <h2>Onay Mesajı</h2>
          <div class="label-row">
            <label for="approved">Şablon</label>
            <span class="action toggle-preview" data-target="#prev-approved">Önizleme</span>
          </div>
          <textarea class="textarea" id="approved" name="approved" placeholder="Mesaj şablonu..." data-preview="#prev-approved" data-count="#count-approved"><?php echo h((string)($values['approved'] ?? '')); ?></textarea>
          <div class="help">Müşteriye güven veren bir ton kullanın.</div>
          <div class="char-count" id="count-approved"></div>
          <div class="preview-box" id="prev-approved"></div>
          <div class="footer-actions">
            <button class="btn" type="submit">Kaydet</button>
          </div>
        </form>

        <form method="post" class="card">
          <h2>İptal Mesajı</h2>
          <div class="label-row">
            <label for="canceled">Şablon</label>
            <span class="action toggle-preview" data-target="#prev-canceled">Önizleme</span>
          </div>
          <textarea class="textarea" id="canceled" name="canceled" placeholder="Mesaj şablonu..." data-preview="#prev-canceled" data-count="#count-canceled"><?php echo h((string)($values['canceled'] ?? '')); ?></textarea>
          <div class="help">Empati kuran, bilgilendirici bir metin önerilir.</div>
          <div class="char-count" id="count-canceled"></div>
          <div class="preview-box" id="prev-canceled"></div>
          <div class="footer-actions">
            <button class="btn" type="submit">Kaydet</button>
          </div>
        </form>
      </div>
      </div>
    </div>
  </div>
  <script>
    (function(){
      function updatePreview(t){
        var sel = t.getAttribute('data-preview');
        if(!sel) return;
        var box = document.querySelector(sel);
        if(!box) return;
        box.textContent = t.value.trim();
      }
      function autoGrow(t){
        t.style.height = 'auto';
        t.style.height = Math.min(600, t.scrollHeight+2) + 'px';
      }
      function updateCount(t){
        var sel = t.getAttribute('data-count');
        if(!sel) return;
        var el = document.querySelector(sel);
        if(!el) return;
        el.textContent = 'Karakter: ' + t.value.length;
      }
      document.querySelectorAll('textarea.textarea').forEach(function(t){
        updatePreview(t);
        autoGrow(t);
        updateCount(t);
        t.addEventListener('input', function(){ updatePreview(t); autoGrow(t); });
        t.addEventListener('input', function(){ updateCount(t); });
      });
      document.querySelectorAll('.toggle-preview').forEach(function(btn){
        btn.addEventListener('click', function(){
          var sel = btn.getAttribute('data-target');
          var box = document.querySelector(sel);
          if(!box) return;
          box.classList.toggle('open');
        });
      });
    })();
  </script>
</body>
</html>

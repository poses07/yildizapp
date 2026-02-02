<?php
$config = $config ?? (require __DIR__ . '/../../config.php');
$inst = (string)($config['evolution']['instance'] ?? '');
$current = basename($_SERVER['PHP_SELF']);
function navItem($href, $label, $current) {
  $isActive = basename($href) === $current;
  $cls = 'nav-link' . ($isActive ? ' active' : '');
  echo '<a class="' . $cls . '" href="' . htmlspecialchars($href, ENT_QUOTES) . '">' . htmlspecialchars($label) . '</a>';
}
?>
<aside class="sidebar">
  <div class="brand">
    <div class="logo">🟢</div>
    <div class="brand-text">
      <div class="brand-title">WPVoucher Panel</div>
      <div class="brand-sub">Instance: <?php echo htmlspecialchars($inst, ENT_QUOTES); ?></div>
    </div>
  </div>
  <nav class="nav">
    <?php navItem('index.php', 'Panel', $current); ?>
    <?php navItem('messages.php', 'Mesajlar', $current); ?>
    <?php navItem('qr.php', 'QR', $current); ?>
  </nav>
  <div class="sidebar-foot">
    <div class="muted">© Milat SOFT</div>
  </div>
</aside>

<?php
// Ortak sayfa başlığı ve breadcrumb bileşeni
// Beklenen değişkenler (opsiyonel):
// $pageTitle (string), $pageSubtitle (string|null), $breadcrumbs (array of ['label'=>..., 'href'=>...?])
if (!function_exists('h')) {
  function h($s){ return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
}
$pageTitle = isset($pageTitle) ? (string)$pageTitle : '';
$pageSubtitle = isset($pageSubtitle) ? (string)$pageSubtitle : '';
$breadcrumbs = isset($breadcrumbs) && is_array($breadcrumbs) ? $breadcrumbs : [];
?>
<?php if (!empty($breadcrumbs)): ?>
  <div class="breadcrumbs">
    <?php foreach ($breadcrumbs as $i => $bc): $isLast = ($i === count($breadcrumbs)-1); ?>
      <?php if (!empty($bc['href']) && !$isLast): ?>
        <a href="<?php echo h($bc['href']); ?>"><?php echo h($bc['label'] ?? ''); ?></a>
        <span> / </span>
      <?php else: ?>
        <span class="current"><?php echo h($bc['label'] ?? ''); ?></span>
      <?php endif; ?>
    <?php endforeach; ?>
  </div>
<?php endif; ?>

<header>
  <div>
    <div class="title"><?php echo h($pageTitle ?: 'Panel'); ?></div>
    <?php if ($pageSubtitle !== ''): ?>
      <div class="subtitle"><?php echo h($pageSubtitle); ?></div>
    <?php endif; ?>
  </div>
</header>

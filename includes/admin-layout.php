<?php

require_once __DIR__ . '/admin-metrics.php';

function admin_logo(): void
{
    $src = store_asset_src(store_asset_path('asset_logo'), true);
    echo '<img class="ss-logo-img ss-admin-bar__logo" src="' . ss_escape($src) . '" alt="" width="36" height="36">';
}

function admin_nav_items(): array
{
    return [
        ['id' => 'dashboard', 'label' => 'Dashboard', 'href' => 'index.php', 'icon' => 'grid'],
        ['id' => 'customers', 'label' => 'Customers', 'href' => 'customers.php', 'icon' => 'users'],
        ['id' => 'orders', 'label' => 'Orders', 'href' => 'orders.php', 'icon' => 'cart'],
        ['id' => 'products', 'label' => 'Products', 'href' => 'products.php', 'icon' => 'box'],
        ['id' => 'store', 'label' => 'Storefront', 'href' => '../index.php', 'icon' => 'store', 'external' => true],
        ['id' => 'settings', 'label' => 'Settings', 'href' => 'settings.php', 'icon' => 'gear'],
        ['id' => 'logs', 'label' => 'Logs', 'href' => 'logs.php', 'icon' => 'list'],
    ];
}

function admin_icon(string $name): string
{
    $icons = [
        'grid' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>',
        'users' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M22 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>',
        'cart' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>',
        'box' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg>',
        'store' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>',
        'life' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>',
        'gear' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="3"/><path d="M12 1v2M12 21v2M4.22 4.22l1.42 1.42M18.36 18.36l1.42 1.42M1 12h2M21 12h2M4.22 19.78l1.42-1.42M18.36 5.64l1.42-1.42"/></svg>',
        'list' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
        'info' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>',
        'logout' => '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    ];
    return $icons[$name] ?? '';
}

function admin_page_start(string $title, string $activeNav = 'dashboard', ?string $period = null): void
{
    $admin = admin_user();
    $tag = admin_user_tag($admin);
    $period = admin_valid_period($period ?? ($_GET['period'] ?? 'overall'));
    $metrics = admin_dashboard_metrics($period);
    $pendingBadge = $metrics['pending_orders'] > 0 ? (string) min($metrics['pending_orders'], 99) : '';
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= ss_escape($title) ?> — <?= ss_escape(ss_brand_name()) ?> Admin</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
  <meta name="csrf-token" content="<?= csrf_token() ?>">
</head>
<body class="ss-page ss-admin-page">
<div class="ss-admin-bar-wrap" id="ssAdminBarWrap">
<header class="ss-admin-bar">
  <a href="index.php" class="ss-admin-bar__brand ss-logo" aria-label="<?= ss_escape(ss_brand_name()) ?> Admin"><?php admin_logo(); ?><span class="ss-logo-text"><?= ss_escape(ss_brand_name()) ?></span></a>
  <nav class="ss-admin-bar__nav" id="ssAdminMobileMenu" aria-label="Admin primary">
    <?php foreach (admin_nav_items() as $item):
        $isActive = ($item['id'] === $activeNav);
        $classes = 'ss-admin-bar__link' . ($isActive ? ' is-active' : '');
        $ext = !empty($item['external']);
        ?>
    <a class="<?= $classes ?>" href="<?= ss_escape($item['href']) ?>"<?= $ext ? ' target="_blank" rel="noopener"' : '' ?>>
      <span class="ss-admin-bar__icon"><?= admin_icon($item['icon']) ?></span>
      <span><?= ss_escape($item['label']) ?></span>
      <?php if (!empty($item['badge']) && $pendingBadge !== ''): ?>
        <span class="ss-admin-bar__badge"><?= ss_escape($pendingBadge) ?></span>
      <?php endif; ?>
    </a>
    <?php endforeach; ?>
  </nav>
  <div class="ss-admin-bar__tools">
    <div class="ss-admin-bar__right">
      <a class="ss-admin-bar__action" href="index.php#admin-info">
        <?= admin_icon('info') ?>
        <span class="ss-admin-bar__action-label">Information</span>
      </a>
      <a class="ss-admin-bar__action" href="logout.php">
        <?= admin_icon('logout') ?>
        <span class="ss-admin-bar__action-label">Logout</span>
      </a>
    </div>
    <button type="button" class="ss-burger ss-admin-burger" id="ssAdminBurger" aria-expanded="false" aria-controls="ssAdminMobileMenu" aria-label="Menu">
      <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" aria-hidden="true"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
    </button>
  </div>
</header>
</div>
<main class="ss-admin-main">
<?php
    $GLOBALS['ss_admin_period'] = $period;
    $GLOBALS['ss_admin_metrics'] = $metrics;
}

function admin_page_header(
    string $badge,
    string $heading,
    string $description,
    bool $showPeriodFilters = true
): void {
    $period = $GLOBALS['ss_admin_period'] ?? 'overall';
    $filters = [
        'overall' => 'Overall',
        'today' => 'Today',
        '7d' => '7 Days',
        '30d' => '30 Days',
    ];
    ?>
<section class="ss-admin-hero">
  <div class="ss-admin-hero__copy">
    <span class="ss-admin-badge"><?= ss_escape($badge) ?></span>
    <h1 class="ss-admin-hero__title"><?= ss_escape($heading) ?></h1>
    <p class="ss-admin-hero__desc"><?= ss_escape($description) ?></p>
    <p class="ss-admin-hero__window">Reporting window <strong><?= ss_escape(admin_period_label($period)) ?></strong></p>
  </div>
  <?php if ($showPeriodFilters): ?>
  <div class="ss-admin-period" role="group" aria-label="Reporting period">
    <?php foreach ($filters as $key => $label):
        $qs = $key === 'overall' ? '' : '?period=' . urlencode($key);
        $active = $period === $key ? ' is-active' : '';
        ?>
    <a class="ss-admin-period__btn<?= $active ?>" href="index.php<?= ss_escape($qs) ?>"><?= ss_escape($label) ?></a>
    <?php endforeach; ?>
  </div>
  <?php endif; ?>
</section>
<?php
}

function admin_metric_card(string $label, string $value, string $hint): void
{
    ?>
<article class="ss-admin-card">
  <p class="ss-admin-card__label"><?= ss_escape($label) ?></p>
  <p class="ss-admin-card__value"><?= $value ?></p>
  <p class="ss-admin-card__hint"><?= ss_escape($hint) ?></p>
</article>
<?php
}

function admin_page_end(): void
{
    ?>
</main>
<?php require __DIR__ . '/admin-footer.php'; ?>
<script src="../assets/js/main.js"></script>
<script src="../assets/js/admin-orders.js"></script>
<script src="../assets/js/admin-products.js?v=<?= (int) @filemtime(dirname(__DIR__) . '/assets/js/admin-products.js') ?>"></script>
</body>
</html>
<?php
}

function admin_orders_tabs(string $activeTab, int $userId = 0): void
{
    $userQuery = $userId > 0 ? '&user_id=' . $userId : '';
    $tabs = [
        'all' => ['label' => 'All Orders', 'href' => 'orders.php' . ($userId > 0 ? '?user_id=' . $userId : '')],
        'pending' => ['label' => 'Pending', 'href' => 'orders.php?tab=pending' . $userQuery],
        'in_progress' => ['label' => 'In progress', 'href' => 'orders.php?tab=in_progress' . $userQuery],
        'delivered' => ['label' => 'Delivered', 'href' => 'orders.php?tab=delivered' . $userQuery],
    ];
    ?>
<div class="ss-admin-tabs" role="tablist">
    <?php foreach ($tabs as $key => $tab): ?>
        <a href="<?= ss_escape($tab['href']) ?>" class="ss-admin-tab<?= $key === $activeTab ? ' is-active' : '' ?>" role="tab">
            <?= ss_escape($tab['label']) ?>
        </a>
    <?php endforeach; ?>
</div>
<?php
}

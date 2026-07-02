<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$period = admin_valid_period($_GET['period'] ?? 'overall');
admin_page_start('Dashboard', 'dashboard', $period);
admin_page_header(
    'Store overview',
    'Operational dashboard with order and catalog metrics.',
    'Revenue reflects delivered orders in the selected period. Pending revenue includes orders not yet delivered.'
);

$m = $GLOBALS['ss_admin_metrics'];
$successRate = $m['success_rate'] . '%';
?>

<section class="ss-admin-grid ss-admin-grid--4" aria-label="Key metrics">
  <?php
  admin_metric_card('Revenue', ss_escape(ss_format_money($m['revenue_cents'])), 'Total from delivered orders in the selected period.');
  admin_metric_card('Pending revenue', ss_escape(ss_format_money($m['pending_cents'])), 'Orders pending, approved, or out for delivery.');
  admin_metric_card('Success rate', ss_escape($successRate), 'Delivered orders divided by all orders in the period.');
  admin_metric_card('Completed orders', ss_escape((string) $m['completed_orders']), 'Orders marked delivered in the selected period.');
  ?>
</section>

<section class="ss-admin-grid ss-admin-grid--4" aria-label="Secondary metrics">
  <?php
  admin_metric_card('New customers', ss_escape((string) $m['customers']), 'Customer accounts registered in the selected period.');
  admin_metric_card('Total orders', ss_escape((string) $m['order_count']), 'All orders placed in the selected period.');
  admin_metric_card('Refunded amount', ss_escape(ss_format_money($m['refunded_cents'])), 'Sum of orders with refunded status in the selected period.');
  admin_metric_card('Average order value', ss_escape(ss_format_money($m['avg_order_cents'])), 'Delivered-order total divided by delivered orders.');
  ?>
</section>

<section class="ss-admin-grid ss-admin-grid--3" aria-label="Detail metrics">
  <article class="ss-admin-card ss-admin-card--tall">
    <div class="ss-admin-card__row">
      <p class="ss-admin-card__label">In progress</p>
      <span class="ss-admin-pill">Value <?= ss_escape(ss_format_money($m['in_progress_value_cents'])) ?></span>
    </div>
    <p class="ss-admin-card__value ss-admin-card__value--lg"><?= ss_escape((string) $m['in_progress_orders']) ?></p>
    <p class="ss-admin-card__hint">Orders out for delivery in the selected period.</p>
  </article>

  <article class="ss-admin-card ss-admin-card--tall">
    <p class="ss-admin-card__label">Catalog value</p>
    <p class="ss-admin-card__value ss-admin-card__value--lg"><?= ss_escape(ss_format_money($m['catalog_cents'])) ?></p>
    <p class="ss-admin-card__hint">Sum of list prices for active products (not order cost).</p>
    <div class="ss-admin-card__meta">
      <div><span>Products</span><strong><?= ss_escape((string) $m['active_products']) ?></strong></div>
      <div><span>Pending orders</span><strong><?= ss_escape((string) $m['pending_orders']) ?></strong></div>
    </div>
  </article>

  <article class="ss-admin-card ss-admin-card--list" id="admin-info">
    <p class="ss-admin-card__label">Gross vs net revenue</p>
    <ul class="ss-admin-kv">
      <li><span>Gross</span><strong><?= ss_escape(ss_format_money($m['gross_cents'])) ?></strong></li>
      <li><span>Net</span><strong><?= ss_escape(ss_format_money($m['net_cents'])) ?></strong></li>
      <li><span>Success rate</span><strong><?= ss_escape($successRate) ?></strong></li>
    </ul>
    <p class="ss-admin-card__hint">Gross is delivered revenue; net subtracts refunds in the period. <a href="products.php">Manage products</a></p>
    <p class="ss-admin-card__hint"><a href="enter-store.php">Enter store as customer</a> · <a href="../index.php" target="_blank" rel="noopener">View storefront</a></p>
  </article>
</section>

<?php admin_page_end(); ?>

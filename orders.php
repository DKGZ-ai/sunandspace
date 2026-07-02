<?php
require_once __DIR__ . '/includes/bootstrap.php';

require_customer();

$tab = strtolower(trim($_GET['tab'] ?? 'all'));
if (!in_array($tab, ['all', 'cod', 'bank'], true)) {
    $tab = 'all';
}

$sql = 'SELECT id, status, tracking_number, total_cents, subtotal_cents, shipping_cents, shipping_address, delivery_method, payment_method, payment_receipt_path, created_at
        FROM orders
        WHERE user_id = ?';
$params = [(int) $_SESSION['user_id']];

if ($tab === 'cod') {
    $sql .= ' AND payment_method = ?';
    $params[] = 'cod';
} elseif ($tab === 'bank') {
    $sql .= ' AND payment_method = ?';
    $params[] = 'bank';
}

$sql .= ' ORDER BY created_at DESC, id DESC';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

$orderTabs = [
    'all' => ['label' => 'All orders', 'href' => 'orders.php'],
    'cod' => ['label' => 'Cash on delivery', 'href' => 'orders.php?tab=cod'],
    'bank' => ['label' => 'Bank transfer', 'href' => 'orders.php?tab=bank'],
];

ss_page_start('Your orders — Sun and Space', 'View your purchase history.', true);
?>
<main class="ss-page-main">
  <h1 class="ss-page-title">Your orders</h1>

  <div class="ss-checkout-steps">
    <a class="ss-step ss-step-done" href="cart.php">Cart</a>
    <a class="ss-step ss-step-active" href="orders.php">Orders</a>
    <a class="ss-step ss-step-done" href="checkout.php?step=account">Account</a>
  </div>

  <div class="ss-order-tabs" role="tablist">
    <?php foreach ($orderTabs as $key => $orderTab): ?>
      <a href="<?= ss_escape($orderTab['href']) ?>" class="ss-order-tab<?= $key === $tab ? ' is-active' : '' ?>" role="tab">
        <?= ss_escape($orderTab['label']) ?>
      </a>
    <?php endforeach; ?>
  </div>

  <?php if (empty($orders)): ?>
    <div class="ss-empty-cart">
      <?php if ($tab === 'all'): ?>
        <p>You have no orders yet.</p>
        <a href="index.php#products" class="ss-btn-primary ss-btn-link">Shop products</a>
      <?php elseif ($tab === 'cod'): ?>
        <p>No cash on delivery orders yet.</p>
        <a href="orders.php" class="ss-btn-text">View all orders</a>
      <?php else: ?>
        <p>No bank transfer orders yet.</p>
        <a href="orders.php" class="ss-btn-text">View all orders</a>
      <?php endif; ?>
    </div>
  <?php else: ?>
    <div class="ss-cart-layout" style="grid-template-columns: 1fr;">
      <table class="ss-cart-table">
        <thead>
          <tr>
            <th>Order</th>
            <th>Status</th>
            <th>Type</th>
            <th>Delivery</th>
            <th>Tracking</th>
            <th>Address</th>
            <th>Total</th>
            <th>Date</th>
            <th>Receipt</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td class="ss-cart-product">
                <div>
                  <strong>#<?= (int) $o['id'] ?></strong>
                </div>
              </td>
              <td><?= ss_escape(order_status_label((string) $o['status'])) ?></td>
              <td><?= ss_escape(ss_payment_method_label((string) ($o['payment_method'] ?? 'cod'))) ?></td>
              <td><?= ss_escape(order_delivery_display_label(order_parse_delivery_method($o['delivery_method'] ?? ''))) ?></td>
              <td><?= !empty($o['tracking_number']) ? ss_escape((string) $o['tracking_number']) : '—' ?></td>
              <td><?= ss_escape(ss_address_summary((string) $o['shipping_address'])) ?></td>
              <td>
                <?= ss_escape(ss_format_price((int) $o['total_cents'])) ?>
                <?php if ((int) ($o['shipping_cents'] ?? 0) > 0): ?>
                  <span class="ss-order-shipping-note">incl. <?= ss_escape(ss_format_price((int) $o['shipping_cents'])) ?> ship</span>
                <?php elseif (order_is_pickup_delivery($o['delivery_method'] ?? '')): ?>
                  <span class="ss-order-delivery-note">pickup — no shipping fee</span>
                <?php elseif (order_parse_delivery_method($o['delivery_method'] ?? '') === 'same_day_local'): ?>
                  <span class="ss-order-delivery-note">shipping arranged separately</span>
                <?php endif; ?>
              </td>
              <td><?= ss_escape(date('Y-m-d', strtotime((string) $o['created_at']))) ?></td>
              <td>
                <?php if (!empty($o['payment_receipt_path'])): ?>
                  <a class="ss-btn-text" href="receipt.php?id=<?= (int) $o['id'] ?>" target="_blank" rel="noopener">View receipt</a>
                <?php else: ?>
                  —
                <?php endif; ?>
              </td>
              <td>
                <a class="ss-btn-text" href="order-confirmation.php?id=<?= (int) $o['id'] ?>">View</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout-footer.php'; ss_page_end(true); ?>

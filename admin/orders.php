<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$tab = trim($_GET['tab'] ?? 'all');
$userId = isset($_GET['user_id']) ? (int) $_GET['user_id'] : 0;
$filterCustomer = $userId > 0 ? admin_customer_by_id($userId) : null;

$sql = 'SELECT id, status, tracking_number, total_cents, subtotal_cents, shipping_cents, delivery_method, shipping_name, shipping_address, payment_method, payment_receipt_path, created_at FROM orders';
$params = [];
$conditions = [];

if ($userId > 0) {
    $conditions[] = 'user_id = ?';
    $params[] = $userId;
}

if ($tab === 'pending') {
    $conditions[] = 'status = ?';
    $params[] = 'pending';
} elseif ($tab === 'in_progress') {
    $conditions[] = 'status = ?';
    $params[] = 'in_progress';
} elseif ($tab === 'delivered') {
    $conditions[] = 'status = ?';
    $params[] = 'delivered';
}

if ($conditions !== []) {
    $sql .= ' WHERE ' . implode(' AND ', $conditions);
}

$sql .= ' ORDER BY created_at DESC, id DESC LIMIT 100';
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$orders = $stmt->fetchAll();

admin_page_start('Orders', 'orders');
$ordersSubtitle = 'Recent storefront orders.';
if ($filterCustomer) {
    $ordersSubtitle = 'Orders for ' . $filterCustomer['name'] . '.';
}
admin_page_header(
    'Orders',
    $ordersSubtitle,
    'Update order status and add a tracking number when marking an order in progress.',
    false
);
?>

<section class="ss-admin-panel" style="max-width:none">
  <?php if ($filterCustomer): ?>
    <p><a class="ss-btn-text" href="customer-edit.php?id=<?= $userId ?>">&larr; Back to customer</a> · <a class="ss-btn-text" href="orders.php">All orders</a></p>
  <?php endif; ?>
  <?php admin_orders_tabs($tab, $userId); ?>

  <?php if (!$orders): ?>
    <p>No orders yet.</p>
  <?php else: ?>
  <div class="ss-admin-table-wrap">
  <table class="ss-cart-table" style="width:100%" id="adminOrderTable">
    <thead>
      <tr>
        <th>ID</th>
        <th>Customer</th>
        <th>Address</th>
        <th>Status</th>
        <th>Tracking</th>
        <th>Delivery</th>
        <th>Payment</th>
        <th>Receipt</th>
        <th>Shipping</th>
        <th>Total</th>
        <th>Date</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($orders as $o): ?>
      <tr>
        <td>#<?= (int) $o['id'] ?></td>
        <td><?= ss_escape($o['shipping_name']) ?></td>
        <td><?= ss_escape(ss_address_summary((string) $o['shipping_address'])) ?></td>
        <td>
          <select class="ss-admin-status-select" data-order-id="<?= (int) $o['id'] ?>" data-current-status="<?= ss_escape($o['status']) ?>">
            <?php foreach (order_statuses() as $status): ?>
              <option value="<?= ss_escape($status) ?>"<?= $o['status'] === $status ? ' selected' : '' ?>><?= ss_escape(order_status_label($status)) ?></option>
            <?php endforeach; ?>
          </select>
        </td>
        <td data-tracking-cell><?= !empty($o['tracking_number']) ? ss_escape((string) $o['tracking_number']) : '—' ?></td>
        <td><?= ss_escape(order_delivery_display_label(order_parse_delivery_method($o['delivery_method'] ?? ''))) ?></td>
        <td><?= ss_escape(ss_payment_method_label((string) ($o['payment_method'] ?? 'cod'))) ?></td>
        <td>
          <?php if (!empty($o['payment_receipt_path'])): ?>
            <a class="ss-btn-text" href="receipt.php?id=<?= (int) $o['id'] ?>" target="_blank" rel="noopener">View</a>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td>
          <?php if (order_parse_delivery_method($o['delivery_method'] ?? '') === 'same_day_local'): ?>
            Arranged separately
          <?php elseif (order_is_pickup_delivery($o['delivery_method'] ?? '')): ?>
            Pickup — no fee
          <?php elseif ((int) ($o['shipping_cents'] ?? 0) > 0): ?>
            <?= ss_escape(ss_format_money((int) $o['shipping_cents'])) ?>
          <?php else: ?>
            —
          <?php endif; ?>
        </td>
        <td><?= ss_escape(ss_format_money((int) $o['total_cents'])) ?></td>
        <td><?= ss_escape(date('M j, Y', strtotime($o['created_at']))) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</section>

<?php admin_page_end(); ?>

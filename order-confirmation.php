<?php
require_once __DIR__ . '/includes/bootstrap.php';

require_customer();

$orderId = (int) ($_GET['id'] ?? 0);
$order = assert_order_belongs_to_customer($orderId);
if (!$order) {
    ss_redirect('index.php');
}

$itemStmt = $pdo->prepare(
    'SELECT oi.qty, oi.unit_price_cents, p.name
     FROM order_items oi
     JOIN products p ON p.id = oi.product_id
     WHERE oi.order_id = ?'
);
$itemStmt->execute([$orderId]);
$items = $itemStmt->fetchAll();

$subtotalCents = (int) ($order['subtotal_cents'] ?? 0);
$shippingCents = (int) ($order['shipping_cents'] ?? 0);
$deliveryMethod = order_parse_delivery_method($order['delivery_method'] ?? '');
$isSameDayDelivery = $deliveryMethod === 'same_day_local';
$isPickupDelivery = order_is_pickup_delivery($deliveryMethod);
if ($subtotalCents === 0) {
    foreach ($items as $item) {
        $subtotalCents += (int) $item['unit_price_cents'] * (int) $item['qty'];
    }
}
if ($shippingCents === 0 && $subtotalCents > 0 && (int) $order['total_cents'] > $subtotalCents) {
    $shippingCents = (int) $order['total_cents'] - $subtotalCents;
}

ss_page_start('Order confirmed — Sun and Space');
?>
<main class="ss-page-main">
  <div class="ss-auth-card ss-confirm-card">
    <h1>Thank you!</h1>
    <p class="ss-auth-sub">Your order <strong>#<?= (int) $order['id'] ?></strong> has been placed.</p>
    <div class="ss-alert ss-alert-success">We will contact you at <?= ss_escape($order['shipping_email']) ?> about <?= $isPickupDelivery ? 'your pickup' : 'delivery' ?>.</div>
    <?php if ($isSameDayDelivery): ?>
    <div class="ss-alert ss-alert-success">For same-day delivery, call <a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a> or <a href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">message us on Facebook</a> to arrange your delivery time and shipping fee.</div>
    <?php elseif ($isPickupDelivery): ?>
    <div class="ss-alert ss-alert-success">For cash on pickup, call <a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a> or <a href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">message us on Facebook</a> to arrange your pickup time. Pay in cash when you collect your order at <strong><?= ss_escape(jt_shipping_origin_label()) ?></strong>.</div>
    <?php endif; ?>
    <h2>Order details</h2>
    <ul class="ss-summary-list">
      <?php foreach ($items as $item): ?>
      <li>
        <span><?= ss_escape($item['name']) ?> &times; <?= (int) $item['qty'] ?></span>
        <span><?= ss_escape(ss_format_price((int) $item['unit_price_cents'] * (int) $item['qty'])) ?></span>
      </li>
      <?php endforeach; ?>
    </ul>
    <ul class="ss-summary-list ss-summary-totals">
      <li>
        <span>Subtotal</span>
        <span><?= ss_escape(ss_format_price($subtotalCents)) ?></span>
      </li>
      <li>
        <span>Delivery</span>
        <span><?= ss_escape(order_delivery_display_label($deliveryMethod)) ?></span>
      </li>
      <?php if (jt_shipping_enabled()): ?>
      <li class="ss-summary-origin">
        <span>Ships from</span>
        <span><?= ss_escape(jt_shipping_origin_label()) ?></span>
      </li>
      <?php endif; ?>
      <?php if ($isSameDayDelivery): ?>
      <li>
        <span>Shipping</span>
        <span>Arranged separately</span>
      </li>
      <?php elseif ($isPickupDelivery): ?>
      <li>
        <span>Shipping</span>
        <span>No shipping fee</span>
      </li>
      <?php elseif ($shippingCents > 0): ?>
      <li>
        <span>Shipping (<?= ss_escape(order_delivery_carrier_label($deliveryMethod)) ?>)</span>
        <span><?= ss_escape(ss_format_price($shippingCents)) ?></span>
      </li>
      <?php endif; ?>
    </ul>
    <p class="ss-summary-total">Total: <strong><?= ss_escape(ss_format_price((int) $order['total_cents'])) ?></strong></p>
    <p><strong>Status:</strong> <?= ss_escape(order_status_label((string) ($order['status'] ?? 'pending'))) ?></p>
    <?php if (!empty($order['tracking_number'])): ?>
    <p><strong>Tracking number:</strong> <?= ss_escape((string) $order['tracking_number']) ?></p>
    <?php endif; ?>
    <p><strong>Payment:</strong> <?= ss_escape(ss_payment_method_label((string) $order['payment_method'])) ?></p>
    <?php if (($order['payment_method'] ?? '') === 'bank'): ?>
    <div class="ss-alert ss-alert-success">Your payment receipt was submitted. We will verify your transfer before processing your order.</div>
    <?php if (!empty($order['payment_receipt_path'])): ?>
    <p><a class="ss-btn-text" href="receipt.php?id=<?= (int) $order['id'] ?>" target="_blank" rel="noopener">View submitted receipt</a></p>
    <?php endif; ?>
    <?php endif; ?>
    <p><strong><?= $isPickupDelivery ? 'Pickup at' : 'Ship to' ?>:</strong><br>
      <?= ss_escape($order['shipping_name']) ?><br>
      <?= nl2br(ss_escape($order['shipping_address'])) ?>
    </p>
    <a href="index.php" class="ss-btn-primary ss-btn-link">Continue shopping</a>
  </div>
</main>
<?php require __DIR__ . '/includes/layout-footer.php'; ss_page_end(); ?>

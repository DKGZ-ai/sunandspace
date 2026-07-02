<?php
require_once __DIR__ . '/includes/bootstrap.php';

$message = '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $action = $_POST['action'] ?? '';
    $productId = (int) ($_POST['product_id'] ?? 0);
    $qty = (int) ($_POST['qty'] ?? 1);
    if ($action === 'update') {
        cart_set_qty($productId, $qty);
        $message = 'Cart updated.';
    } elseif ($action === 'remove') {
        cart_remove($productId);
        $message = 'Item removed.';
    }
}

$lines = cart_lines();
$selection = cart_checkout_selection();
$checkoutTotalCents = cart_total_cents_for_checkout();

ss_page_start('Your cart — Sun and Space', 'Review items in your cart.', true);
?>
<main class="ss-page-main">
  <h1 class="ss-page-title">Your cart</h1>
  <div class="ss-checkout-steps">
    <a class="ss-step ss-step-active" href="cart.php">Cart</a>
    <a class="ss-step ss-step-done" href="orders.php">Orders</a>
    <a class="ss-step ss-step-done" href="checkout.php?step=account">Account</a>
  </div>
  <?php if ($flashError): ?>
    <div class="ss-alert ss-alert-error"><?= ss_escape($flashError) ?></div>
  <?php endif; ?>
  <?php if ($message): ?>
    <div class="ss-alert ss-alert-success"><?= ss_escape($message) ?></div>
  <?php endif; ?>
  <?php if (empty($lines)): ?>
    <div class="ss-empty-cart">
      <p>Your cart is empty.</p>
      <a href="index.php#products" class="ss-btn-primary ss-btn-link">Shop products</a>
    </div>
  <?php else: ?>
    <div class="ss-cart-layout">
      <div class="ss-cart-items">
        <table class="ss-cart-table">
          <thead>
            <tr>
              <th class="ss-cart-col-select">
                <label class="ss-cart-select-all-label">
                  <input type="checkbox" id="ssSelectAll" class="ss-cart-select" checked aria-label="Select all items">
                </label>
              </th>
              <th>Product</th>
              <th>Price</th>
              <th>Qty</th>
              <th>Subtotal</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lines as $line):
              $p = $line['product'];
              $pid = (int) $p['id'];
              $checked = in_array($pid, $selection, true);
            ?>
            <tr data-line-cents="<?= (int) $line['line_cents'] ?>">
              <td class="ss-cart-col-select">
                <input type="checkbox" name="checkout_items[]" value="<?= $pid ?>" form="ssCartCheckoutForm" class="ss-cart-select ss-cart-line-select"<?= $checked ? ' checked' : '' ?> aria-label="Include <?= ss_escape($p['name']) ?> in checkout">
              </td>
              <td class="ss-cart-product">
                <img src="<?= ss_escape(product_image_src((string) $p['image_path'])) ?>" alt="" width="64" height="64">
                <div>
                  <strong><?= ss_escape($p['name']) ?></strong>
                  <span class="ss-product-cat"><?= ss_escape($p['category']) ?></span>
                  <?= product_estimated_arrival_html($p) ?>
                </div>
              </td>
              <td><?= product_price_display_html($p) ?></td>
              <td>
                <form method="post" class="ss-qty-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="update">
                  <input type="hidden" name="product_id" value="<?= $pid ?>">
                  <input type="number" name="qty" min="1" max="99" value="<?= (int) $line['qty'] ?>" class="ss-qty-input">
                </form>
              </td>
              <td class="ss-line-subtotal"><?= ss_escape(ss_format_price($line['line_cents'])) ?></td>
              <td>
                <form method="post">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="remove">
                  <input type="hidden" name="product_id" value="<?= $pid ?>">
                  <button type="submit" class="ss-btn-text ss-btn-danger">Remove</button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <aside class="ss-cart-summary">
        <form method="post" action="checkout.php" id="ssCartCheckoutForm">
          <?= csrf_field() ?>
          <h2>Order summary</h2>
          <?php
          $wholesaleNoteContext = 'cart';
          require __DIR__ . '/includes/wholesale-note.php';
          ?>
          <p class="ss-summary-total">Selected total: <strong id="ssSelectedTotal"><?= ss_escape(ss_format_price($checkoutTotalCents)) ?></strong></p>
          <button type="submit" class="ss-btn-primary ss-btn-block">Proceed to checkout</button>
        </form>
        <a href="index.php#products" class="ss-btn-text">Continue shopping</a>
      </aside>
    </div>
  <?php endif; ?>
</main>
<?php require __DIR__ . '/includes/layout-footer.php'; ss_page_end(true); ?>

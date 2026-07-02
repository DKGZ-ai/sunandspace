<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$flash = '';
if (!empty($_GET['saved'])) {
    $flash = 'Customer saved.';
} elseif (!empty($_GET['error'])) {
    $flash = (string) $_GET['error'];
}

$customers = admin_customers_list();

admin_page_start('Customers', 'customers');
admin_page_header(
    'Customers',
    'Registered customer accounts.',
    'View details, edit profiles, and reset passwords.',
    false
);
?>

<section class="ss-admin-panel ss-admin-panel--wide">
  <?php if ($flash !== ''): ?>
    <div class="ss-alert<?= !empty($_GET['error']) ? ' ss-alert-error' : ' ss-alert-success' ?>"><?= ss_escape($flash) ?></div>
  <?php endif; ?>

  <?php if (!$customers): ?>
    <p>No customers yet.</p>
  <?php else: ?>
  <div class="ss-admin-table-wrap">
  <table class="ss-cart-table ss-admin-products-table">
    <thead>
      <tr>
        <th>Name</th>
        <th>Email</th>
        <th>Phone</th>
        <th>Address</th>
        <th>Orders</th>
        <th>Cart</th>
        <th>Joined</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($customers as $c):
        $address = admin_customer_display_address($c);
        $cartQty = (int) $c['cart_qty'];
        $cartLines = (int) $c['cart_lines'];
        $cartTitle = $cartLines > 0
            ? $cartQty . ' item' . ($cartQty === 1 ? '' : 's') . ' (' . $cartLines . ' product' . ($cartLines === 1 ? '' : 's') . ')'
            : '';
        ?>
      <tr>
        <td><?= ss_escape($c['name']) ?></td>
        <td><?= ss_escape($c['email']) ?></td>
        <td><?= ss_escape($c['phone'] ?: '—') ?></td>
        <td><?= $address !== '' ? ss_escape(ss_address_summary($address)) : '—' ?></td>
        <td><?= (int) $c['order_count'] ?></td>
        <td<?= $cartTitle !== '' ? ' title="' . ss_escape($cartTitle) . '"' : '' ?>><?= $cartQty ?></td>
        <td><?= ss_escape(date('M j, Y', strtotime($c['created_at']))) ?></td>
        <td><a class="ss-btn-text" href="customer-edit.php?id=<?= (int) $c['id'] ?>">Edit</a></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</section>

<?php admin_page_end(); ?>

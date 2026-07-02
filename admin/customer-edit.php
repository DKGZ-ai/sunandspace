<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$customer = admin_customer_by_id($id);

if (!$customer) {
    ss_redirect('customers.php?error=' . urlencode('Customer not found.'));
}

$stats = admin_customer_stats($id);
$recentOrders = admin_customer_recent_orders($id);
$error = '';

$form = customer_account_form_data($id);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $postId = (int) ($_POST['id'] ?? 0);
        if ($postId !== $id) {
            $error = 'Invalid customer.';
        } else {
            $form = [
                'name' => trim((string) ($_POST['name'] ?? '')),
                'email' => trim((string) ($_POST['email'] ?? '')),
                'phone' => trim((string) ($_POST['phone'] ?? '')),
                'address' => trim((string) ($_POST['address'] ?? '')),
                'notes' => trim((string) ($_POST['notes'] ?? '')),
            ];
            $password = (string) ($_POST['password'] ?? '');
            $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');

            $result = admin_customer_save(
                $id,
                $form['name'],
                $form['email'],
                $form['phone'],
                $form['address'],
                $form['notes']
            );
            if ($result['ok']) {
                $pwResult = admin_customer_set_password($id, $password, $passwordConfirm);
                if ($pwResult['ok']) {
                    ss_redirect('customers.php?saved=1');
                }
                $error = $pwResult['error'];
            } else {
                $error = $result['error'];
            }
        }
    }
}

admin_page_start('Edit customer', 'customers');
admin_page_header(
    'Edit customer',
    $customer['name'] . ' · ' . $customer['email'],
    'Update profile, billing address, or reset the storefront password.',
    false
);
?>

<section class="ss-admin-panel ss-admin-panel--wide">
  <?php if ($error): ?>
    <div class="ss-alert ss-alert-error"><?= ss_escape($error) ?></div>
  <?php endif; ?>

  <p><a class="ss-btn-text" href="customers.php">&larr; Back to customers</a></p>

  <dl class="ss-admin-customer-stats">
    <div><dt>Member since</dt><dd><?= ss_escape(date('M j, Y', strtotime($customer['created_at'] ?? 'now'))) ?></dd></div>
    <div><dt>Orders</dt><dd><?= (int) $stats['order_count'] ?></dd></div>
    <div><dt>Cart</dt><dd><?= (int) $stats['cart_qty'] ?><?php if ($stats['cart_lines'] > 0): ?> <span class="ss-admin-form__hint">(<?= (int) $stats['cart_lines'] ?> product<?= $stats['cart_lines'] === 1 ? '' : 's' ?>)</span><?php endif; ?></dd></div>
    <div><dt>Total spent</dt><dd><?= ss_escape(ss_format_money($stats['total_spent_cents'])) ?></dd></div>
    <div><dt>Last order</dt><dd><?= $stats['last_order_at'] ? ss_escape(date('M j, Y', strtotime($stats['last_order_at']))) : '—' ?></dd></div>
    <?php if ($stats['order_count'] > 0): ?>
    <div><dt>All orders</dt><dd><a class="ss-btn-text" href="orders.php?user_id=<?= $id ?>">View in Orders</a></dd></div>
    <?php endif; ?>
  </dl>

  <?php if ($recentOrders): ?>
  <h2 class="ss-admin-subheading">Recent orders</h2>
  <table class="ss-cart-table" style="width:100%;margin-bottom:1.5rem">
    <thead>
      <tr><th>ID</th><th>Status</th><th>Total</th><th>Date</th></tr>
    </thead>
    <tbody>
      <?php foreach ($recentOrders as $o): ?>
      <tr>
        <td><a class="ss-btn-text" href="orders.php?user_id=<?= $id ?>">#<?= (int) $o['id'] ?></a></td>
        <td><?= ss_escape(order_status_label((string) $o['status'])) ?></td>
        <td><?= ss_escape(ss_format_money((int) $o['total_cents'])) ?></td>
        <td><?= ss_escape(date('M j, Y', strtotime($o['created_at']))) ?></td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php endif; ?>

  <form method="post" class="ss-form ss-admin-form">
    <?= csrf_field() ?>
    <input type="hidden" name="id" value="<?= $id ?>">

    <label>Full name
      <input type="text" name="name" required maxlength="255" value="<?= ss_escape($form['name']) ?>">
    </label>

    <label>Email
      <input type="email" name="email" required maxlength="255" value="<?= ss_escape($form['email']) ?>">
    </label>

    <label>Phone
      <input type="tel" name="phone" maxlength="50" value="<?= ss_escape($form['phone']) ?>">
    </label>

    <label>Complete address
      <textarea name="address" rows="3" placeholder="Billing / default shipping address"><?= ss_escape($form['address']) ?></textarea>
    </label>

    <label>Notes
      <textarea name="notes" rows="2"><?= ss_escape($form['notes']) ?></textarea>
    </label>

    <fieldset class="ss-admin-form__image">
      <legend>Reset password</legend>
      <p class="ss-admin-form__hint">Leave blank to keep the current password.</p>
      <label>New password <span class="ss-hint">(min. 8 characters)</span>
        <input type="password" name="password" autocomplete="new-password" minlength="8">
      </label>
      <label>Confirm new password
        <input type="password" name="password_confirm" autocomplete="new-password" minlength="8">
      </label>
    </fieldset>

    <div class="ss-admin-form__actions">
      <button type="submit" class="ss-btn-primary">Save changes</button>
      <a class="ss-btn-secondary ss-btn-link" href="customers.php">Cancel</a>
    </div>
  </form>
</section>

<?php admin_page_end(); ?>

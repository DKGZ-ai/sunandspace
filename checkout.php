<?php
require_once __DIR__ . '/includes/bootstrap.php';

$step = strtolower((string) ($_GET['step'] ?? ''));
$isAccountStep = ($step === 'account' || $step === 'details');

if ($isAccountStep) {
    $error = '';
    $success = '';
    $redirectAccount = 'checkout.php?step=account';

    if (!customer_logged_in()) {
        $_SESSION['checkout_redirect'] = $redirectAccount;
        $loginUrl = 'login.php?redirect=' . urlencode($redirectAccount);
        $registerUrl = 'register.php?redirect=' . urlencode($redirectAccount);

        ss_page_start('Account — Sign in — Sun and Space', '', true);
        ?>
<main class="ss-page-main">
  <h1 class="ss-page-title">Account</h1>
  <div class="ss-checkout-steps">
    <a class="ss-step ss-step-done" href="cart.php">Cart</a>
    <a class="ss-step ss-step-done" href="orders.php">Orders</a>
    <a class="ss-step ss-step-active" href="checkout.php?step=account">Account</a>
  </div>
  <div class="ss-checkout-layout">
    <div class="ss-auth-card ss-auth-card--inline">
      <h2>Sign in or create an account</h2>
      <p class="ss-auth-sub">Sign in to manage your personal information and billing address.</p>
      <div class="ss-auth-actions">
        <a href="<?= ss_escape($loginUrl) ?>" class="ss-btn-primary ss-btn-link">Sign in</a>
        <a href="<?= ss_escape($registerUrl) ?>" class="ss-btn-secondary ss-btn-link">Create account</a>
      </div>
    </div>
  </div>
</main>
        <?php
        require __DIR__ . '/includes/layout-footer.php';
        ss_page_end(true);
        exit;
    }

    require_customer();
    unset($_SESSION['checkout_redirect']);
    $user = auth_user();
    if ($user === null) {
        logout_user();
        ss_redirect('login.php?redirect=' . urlencode($redirectAccount));
    }

    $userId = (int) $user['id'];
    $profile = customer_account_form_data($userId);

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!csrf_verify()) {
            $error = 'Invalid request. Please try again.';
        } else {
            $profile = [
                'name' => trim($_POST['name'] ?? ''),
                'email' => trim($_POST['email'] ?? ''),
                'phone' => trim($_POST['phone'] ?? ''),
                'address' => trim($_POST['address'] ?? ''),
                'notes' => trim($_POST['notes'] ?? ''),
            ];
            //save customer account
            $result = save_customer_account(
                $userId,
                $profile['name'],
                $profile['email'],
                $profile['phone'],
                $profile['address'],
                $profile['notes']
            );
            if (!$result['ok']) {
                $error = $result['error'];
            } else {
                $profile = customer_account_form_data($userId);
                $success = 'Your account details have been saved.';
            }
        }
    }

    ss_page_start('Account — Sun and Space', '', true);
    ?>
<main class="ss-page-main">
  <h1 class="ss-page-title">Account</h1>
  <div class="ss-checkout-steps">
    <a class="ss-step ss-step-done" href="cart.php">Cart</a>
    <a class="ss-step ss-step-done" href="orders.php">Orders</a>
    <a class="ss-step ss-step-active" href="checkout.php?step=account">Account</a>
  </div>
  <?php if ($error): ?>
    <div class="ss-alert ss-alert-error"><?= ss_escape($error) ?></div>
  <?php endif; ?>
  <?php if ($success): ?>
    <div class="ss-alert ss-alert-success"><?= ss_escape($success) ?></div>
  <?php endif; ?>
  <div class="ss-checkout-layout">
    <form method="post" class="ss-form ss-checkout-form">
      <?= csrf_field() ?>
      <h2>Personal information &amp; billing address</h2>
      <label>Full name <span class="ss-required">*</span>
        <input type="text" name="name" required value="<?= ss_escape($profile['name']) ?>">
      </label>
      <label>Email <span class="ss-required">*</span>
        <input type="email" name="email" required value="<?= ss_escape($profile['email']) ?>">
      </label>
      <label>Phone
        <input type="tel" name="phone" value="<?= ss_escape($profile['phone']) ?>">
      </label>
      <label>Complete address
        <textarea name="address" rows="3" placeholder="Add when ready"><?= ss_escape($profile['address']) ?></textarea>
      </label>
      <label>Notes
        <textarea name="notes" rows="2"><?= ss_escape($profile['notes']) ?></textarea>
      </label>
      <button type="submit" class="ss-btn-primary ss-btn-block">Save account details</button>
      <p class="ss-auth-sub"><a href="cart.php" class="ss-btn-text">Go to cart to place an order</a></p>
    </form>
  </div>
</main>
    <?php
    require __DIR__ . '/includes/layout-footer.php';
    ss_page_end(true);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout_items'])) {
    if (!csrf_verify()) {
        $_SESSION['flash_error'] = 'Invalid request. Please try again.';
        ss_redirect('cart.php');
    }
    cart_accept_checkout_selection_from_request();
    ss_redirect('checkout.php');
}

if (cart_is_empty()) {
    ss_redirect('cart.php');
}

if (cart_lines_for_checkout() === []) {
    $_SESSION['flash_error'] = 'Select at least one item to checkout.';
    ss_redirect('cart.php');
}

$lines = cart_lines_for_checkout();
$subtotalCents = cart_total_cents_for_checkout();
$totalCents = $subtotalCents;
$selectedProductIds = cart_checkout_selection();
$error = '';
$redirectCheckout = 'checkout.php';
$shippingDefaults = [
    'shipping_province_id' => '',
    'shipping_province' => '',
    'shipping_city' => '',
    'shipping_cents' => 0,
    'delivery_method' => order_default_delivery_method(),
];
$shippingOriginLabel = jt_shipping_enabled() ? jt_shipping_origin_label() : '';
$shippingCarrierLabel = order_delivery_carrier_label($shippingDefaults['delivery_method']);

if (!customer_logged_in()) {
    $_SESSION['checkout_redirect'] = $redirectCheckout;
    $loginUrl = 'login.php?redirect=' . urlencode($redirectCheckout);
    $registerUrl = 'register.php?redirect=' . urlencode($redirectCheckout);

    ss_page_start('Checkout — Sign in — Sun and Space', '', true);
    ?>
<main class="ss-page-main">
  <h1 class="ss-page-title">Checkout</h1>
  <div class="ss-checkout-steps">
    <a class="ss-step ss-step-active" href="cart.php">Cart</a>
    <a class="ss-step ss-step-done" href="orders.php">Orders</a>
    <a class="ss-step ss-step-done" href="checkout.php?step=account">Account</a>
  </div>
  <div class="ss-checkout-layout">
    <div class="ss-auth-card ss-auth-card--inline">
      <h2>Sign in or create an account</h2>
      <p class="ss-auth-sub">You can add items without an account. To complete your order, please sign in or register.</p>
      <div class="ss-auth-actions">
        <a href="<?= ss_escape($loginUrl) ?>" class="ss-btn-primary ss-btn-link">Sign in</a>
        <a href="<?= ss_escape($registerUrl) ?>" class="ss-btn-secondary ss-btn-link">Create account</a>
      </div>
      <p class="ss-auth-sub"><a href="cart.php" class="ss-btn-text">&larr; Back to cart</a></p>
    </div>
    <aside class="ss-cart-summary">
      <h2>Order summary</h2>
      <ul class="ss-summary-list">
        <?php foreach ($lines as $line): ?>
        <li>
          <span>
            <?= ss_escape($line['product']['name']) ?> &times; <?= (int) $line['qty'] ?>
            <?= product_estimated_arrival_html($line['product']) ?>
          </span>
          <span><?= ss_escape(ss_format_price($line['line_cents'])) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <p class="ss-summary-total">Subtotal: <strong><?= ss_escape(ss_format_price($subtotalCents)) ?></strong></p>
      <?php if ($shippingOriginLabel !== ''): ?>
      <p class="ss-summary-note">Ships from <?= ss_escape($shippingOriginLabel) ?>. Shipping calculated after sign-in.</p>
      <?php else: ?>
      <p class="ss-summary-note">Shipping calculated after sign-in.</p>
      <?php endif; ?>
      <a href="cart.php" class="ss-btn-text">&larr; Back to cart</a>
    </aside>
  </div>
</main>
    <?php
    require __DIR__ . '/includes/layout-footer.php';
    ss_page_end(true);
    exit;
}

require_customer();
unset($_SESSION['checkout_redirect']);

$user = auth_user();
if ($user === null) {
    logout_user();
    require_customer_or_checkout_redirect();
}

$defaults = customer_checkout_defaults($user);
$paymentConfig = ss_payment_config();
$showBankTransfer = !empty($paymentConfig['bank_transfer_enabled']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_order'])) {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $shippingName = trim($_POST['shipping_name'] ?? '');
        $shippingEmail = trim($_POST['shipping_email'] ?? '');
        $shippingPhone = trim($_POST['shipping_phone'] ?? '');
        $shippingStreet = trim($_POST['shipping_address'] ?? '');
        $shippingNotes = trim($_POST['shipping_notes'] ?? '');
        $shippingProvinceId = (int) ($_POST['shipping_province_id'] ?? 0);
        $shippingProvince = strtoupper(trim($_POST['shipping_province'] ?? ''));
        $shippingCity = strtoupper(trim($_POST['shipping_city'] ?? ''));
        $postedShippingCents = (int) ($_POST['shipping_cents'] ?? 0);
        $postedDeliveryMethod = trim((string) ($_POST['delivery_method'] ?? ''));
        $deliveryMethod = order_delivery_method_is_valid($postedDeliveryMethod)
            ? $postedDeliveryMethod
            : '';
        $paymentMethod = $_POST['payment_method'] ?? 'cod';
        if ($deliveryMethod === 'cash_on_pickup') {
            $paymentMethod = 'cop';
        } elseif (!ss_payment_method_is_valid($paymentMethod) || $paymentMethod === 'cop') {
            $paymentMethod = 'cod';
        }
        if ($paymentMethod === 'bank' && !$showBankTransfer) {
            $paymentMethod = 'cod';
        }

        $defaults = [
            'shipping_name' => $shippingName,
            'shipping_email' => $shippingEmail,
            'shipping_phone' => $shippingPhone,
            'shipping_address' => $shippingStreet,
            'shipping_notes' => $shippingNotes,
            'payment_method' => $paymentMethod,
        ];
        $shippingDefaults = [
            'shipping_province_id' => $shippingProvinceId > 0 ? (string) $shippingProvinceId : '',
            'shipping_province' => $shippingProvince,
            'shipping_city' => $shippingCity,
            'shipping_cents' => $postedShippingCents,
            'delivery_method' => $deliveryMethod,
        ];

        $lines = cart_lines_for_checkout();
        $subtotalCents = cart_total_cents_for_checkout();
        $selectedProductIds = cart_checkout_selection();

        if ($shippingName === '') {
            $error = 'Please fill in all required shipping fields.';
        } elseif ($deliveryMethod !== 'cash_on_pickup' && $shippingStreet === '') {
            $error = 'Please fill in all required shipping fields.';
        } elseif ($deliveryMethod === '') {
            $error = 'Please select a delivery method.';
        } elseif (!filter_var($shippingEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email.';
        } elseif ($lines === []) {
            $_SESSION['flash_error'] = 'Select at least one item to checkout.';
            ss_redirect('cart.php');
        } elseif ($paymentMethod === 'bank' && (int) ($_FILES['payment_receipt']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            $error = 'Please upload your payment receipt for bank transfer orders.';
        } elseif ($deliveryMethod === 'cash_on_pickup') {
            $paymentMethod = 'cop';
            $shippingCents = 0;
            $orderTotalCents = $subtotalCents;
            $provinceName = '';
            $shippingCity = '';
            $shippingAddress = "Cash on pickup\n" . jt_shipping_origin_label();
            if ($shippingStreet !== '') {
                $shippingAddress .= "\n\nCustomer address:\n" . $shippingStreet;
            }

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, status, shipping_name, shipping_email, shipping_phone,
                     shipping_address, shipping_notes, shipping_province, shipping_city, shipping_cents,
                     subtotal_cents, delivery_method, payment_method, payment_receipt_path, total_cents)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    (int) $_SESSION['user_id'],
                    'pending',
                    $shippingName,
                    $shippingEmail,
                    $shippingPhone,
                    $shippingAddress,
                    $shippingNotes,
                    $provinceName,
                    $shippingCity,
                    $shippingCents,
                    $subtotalCents,
                    $deliveryMethod,
                    $paymentMethod,
                    null,
                    $orderTotalCents,
                ]);
                $orderId = (int) $pdo->lastInsertId();
                $itemStmt = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, qty, unit_price_cents) VALUES (?, ?, ?, ?)'
                );
                foreach ($lines as $line) {
                    $itemStmt->execute([
                        $orderId,
                        (int) $line['product']['id'],
                        (int) $line['qty'],
                        product_effective_price_cents($line['product']),
                    ]);
                }

                $pdo->commit();
                customer_billing_session_save($shippingStreet !== '' ? $shippingStreet : jt_shipping_origin_label(), $shippingNotes);
                cart_remove_product_ids($selectedProductIds);
                unset($_SESSION['checkout_selection']);
                ss_redirect('order-confirmation.php?id=' . $orderId);
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e instanceof RuntimeException && $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Could not place order. Please try again.';
            }
        } elseif ($deliveryMethod === 'same_day_local') {
            $shippingCents = 0;
            $orderTotalCents = $subtotalCents;
            $provinceName = '';
            $shippingCity = '';
            $shippingAddress = jt_build_shipping_address(
                $shippingStreet,
                'Same-day delivery (Makati & nearby)',
                ''
            );

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, status, shipping_name, shipping_email, shipping_phone,
                     shipping_address, shipping_notes, shipping_province, shipping_city, shipping_cents,
                     subtotal_cents, delivery_method, payment_method, payment_receipt_path, total_cents)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    (int) $_SESSION['user_id'],
                    'pending',
                    $shippingName,
                    $shippingEmail,
                    $shippingPhone,
                    $shippingAddress,
                    $shippingNotes,
                    $provinceName,
                    $shippingCity,
                    $shippingCents,
                    $subtotalCents,
                    $deliveryMethod,
                    $paymentMethod,
                    null,
                    $orderTotalCents,
                ]);
                $orderId = (int) $pdo->lastInsertId();
                $itemStmt = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, qty, unit_price_cents) VALUES (?, ?, ?, ?)'
                );
                foreach ($lines as $line) {
                    $itemStmt->execute([
                        $orderId,
                        (int) $line['product']['id'],
                        (int) $line['qty'],
                        product_effective_price_cents($line['product']),
                    ]);
                }

                if ($paymentMethod === 'bank') {
                    $receipt = ss_save_payment_receipt($_FILES['payment_receipt'] ?? [], $orderId);
                    if (!$receipt['ok']) {
                        throw new RuntimeException($receipt['error']);
                    }
                    $updateReceipt = $pdo->prepare('UPDATE orders SET payment_receipt_path = ? WHERE id = ?');
                    $updateReceipt->execute([$receipt['path'], $orderId]);
                }

                $pdo->commit();
                customer_billing_session_save($shippingAddress, $shippingNotes);
                cart_remove_product_ids($selectedProductIds);
                unset($_SESSION['checkout_selection']);
                ss_redirect('order-confirmation.php?id=' . $orderId);
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e instanceof RuntimeException && $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Could not place order. Please try again.';
            }
        } elseif ($shippingProvinceId < 1 || $shippingCity === '') {
            $error = 'Please select your province and city for shipping.';
        } elseif (!jt_validate_city($shippingCity, $shippingProvinceId)) {
            $error = 'Invalid shipping destination. Please select your province and city again.';
        } elseif (!jt_shipping_enabled()) {
            $error = 'Shipping quotes are not available right now. Please try again later.';
        } else {
            $quote = jt_quote_local($shippingCity, cart_checkout_weight_kg());
            if (!$quote['ok']) {
                $error = $quote['error'];
            } elseif ((int) $quote['shippingCents'] !== $postedShippingCents) {
                $error = 'Shipping fee changed. Please wait for the updated quote and try again.';
            } else {
                $shippingCents = (int) $quote['shippingCents'];
                $orderTotalCents = $subtotalCents + $shippingCents;
                $provinceName = jt_province_label($shippingProvinceId);
                if ($provinceName === '') {
                    $provinceName = $shippingProvince;
                }
                $shippingAddress = jt_build_shipping_address($shippingStreet, $provinceName, $shippingCity);

            try {
                $pdo->beginTransaction();
                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, status, shipping_name, shipping_email, shipping_phone,
                     shipping_address, shipping_notes, shipping_province, shipping_city, shipping_cents,
                     subtotal_cents, delivery_method, payment_method, payment_receipt_path, total_cents)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                $stmt->execute([
                    (int) $_SESSION['user_id'],
                    'pending',
                    $shippingName,
                    $shippingEmail,
                    $shippingPhone,
                    $shippingAddress,
                    $shippingNotes,
                    $provinceName,
                    $shippingCity,
                    $shippingCents,
                    $subtotalCents,
                    $deliveryMethod,
                    $paymentMethod,
                    null,
                    $orderTotalCents,
                ]);
                $orderId = (int) $pdo->lastInsertId();
                $itemStmt = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, qty, unit_price_cents) VALUES (?, ?, ?, ?)'
                );
                foreach ($lines as $line) {
                    $itemStmt->execute([
                        $orderId,
                        (int) $line['product']['id'],
                        (int) $line['qty'],
                        product_effective_price_cents($line['product']),
                    ]);
                }

                if ($paymentMethod === 'bank') {
                    $receipt = ss_save_payment_receipt($_FILES['payment_receipt'] ?? [], $orderId);
                    if (!$receipt['ok']) {
                        throw new RuntimeException($receipt['error']);
                    }
                    $updateReceipt = $pdo->prepare('UPDATE orders SET payment_receipt_path = ? WHERE id = ?');
                    $updateReceipt->execute([$receipt['path'], $orderId]);
                }

                $pdo->commit();
                customer_billing_session_save($shippingAddress, $shippingNotes);
                cart_remove_product_ids($selectedProductIds);
                unset($_SESSION['checkout_selection']);
                ss_redirect('order-confirmation.php?id=' . $orderId);
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = $e instanceof RuntimeException && $e->getMessage() !== ''
                    ? $e->getMessage()
                    : 'Could not place order. Please try again.';
            }
            }
        }
    }
}

$isPickupDelivery = order_is_pickup_delivery($shippingDefaults['delivery_method']);
$initialShippingDisplay = match ($shippingDefaults['delivery_method']) {
    'same_day_local' => 'Arranged separately',
    'cash_on_pickup' => 'Free',
    default => 'Select city to calculate',
};
$addressRequired = !$isPickupDelivery;

ss_page_start('Checkout — Sun and Space', '', true);
?>
<main class="ss-page-main">
  <h1 class="ss-page-title">Checkout</h1>
  <div class="ss-checkout-steps">
    <a class="ss-step ss-step-active" href="cart.php">Cart</a>
    <a class="ss-step ss-step-done" href="orders.php">Orders</a>
    <a class="ss-step ss-step-done" href="checkout.php?step=account">Account</a>
  </div>
  <?php if ($error): ?>
    <div class="ss-alert ss-alert-error"><?= ss_escape($error) ?></div>
  <?php endif; ?>
  <div class="ss-checkout-layout">
    <form method="post" class="ss-form ss-checkout-form" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>
      <p class="ss-checkout-back">
        <a href="cart.php" class="ss-btn-secondary ss-btn-link">&larr; Back to cart</a>
      </p>
      <h2>Shipping &amp; payment</h2>
      <div id="ssCheckoutValidationAlert" class="ss-alert ss-alert-warning hidden" role="alert" aria-live="polite"></div>
      <label>Full name <span class="ss-required">*</span>
        <input type="text" name="shipping_name" required value="<?= ss_escape($defaults['shipping_name']) ?>">
      </label>
      <label>Email <span class="ss-required">*</span>
        <input type="email" name="shipping_email" required value="<?= ss_escape($defaults['shipping_email']) ?>">
      </label>
      <label>Phone
        <input type="tel" name="shipping_phone" value="<?= ss_escape($defaults['shipping_phone']) ?>">
      </label>
      <div id="ssCheckoutShippingRoot"
           data-subtotal-cents="<?= (int) $subtotalCents ?>"
           data-carrier-same-day="<?= ss_escape(order_delivery_carrier_label('same_day_local')) ?>"
           data-carrier-jt="<?= ss_escape(order_delivery_carrier_label('jt_nationwide')) ?>"
           data-carrier-pickup="<?= ss_escape(order_delivery_carrier_label('cash_on_pickup')) ?>">
        <fieldset class="ss-delivery-method">
          <legend>Delivery method <span class="ss-required">*</span></legend>
          <div class="ss-delivery-method__options">
            <label class="ss-delivery-method__option">
              <input type="radio" name="delivery_method" value="same_day_local"<?= $shippingDefaults['delivery_method'] === 'same_day_local' ? ' checked' : '' ?>>
              <span class="ss-delivery-method__card">
                <strong>Same-day delivery</strong>
                <span>Lalamove — Makati &amp; nearby, call or message us to arrange</span>
              </span>
            </label>
            <label class="ss-delivery-method__option">
              <input type="radio" name="delivery_method" value="jt_nationwide"<?= $shippingDefaults['delivery_method'] === 'jt_nationwide' ? ' checked' : '' ?>>
              <span class="ss-delivery-method__card">
                <strong>Nationwide shipping</strong>
                <span>J&amp;T Express — rate calculated by city</span>
              </span>
            </label>
            <label class="ss-delivery-method__option">
              <input type="radio" name="delivery_method" value="cash_on_pickup"<?= $shippingDefaults['delivery_method'] === 'cash_on_pickup' ? ' checked' : '' ?>>
              <span class="ss-delivery-method__card">
                <strong>Cash on pickup</strong>
                <span>Pay in person and pick up at <?= ss_escape(jt_shipping_origin_label()) ?></span>
              </span>
            </label>
          </div>
        </fieldset>

        <?php if ($shippingOriginLabel !== ''): ?>
        <p class="ss-shipping-origin">Ships from: <strong><?= ss_escape($shippingOriginLabel) ?></strong></p>
        <?php endif; ?>
        <?php if ($shippingCarrierLabel !== ''): ?>
        <p class="ss-shipping-carrier" id="ssCheckoutCarrierDisplay">Carrier: <strong id="ssCheckoutCarrierValue"><?= ss_escape($shippingCarrierLabel) ?></strong></p>
        <?php endif; ?>

        <div id="ssCheckoutSameDayPanel" class="ss-same-day-panel<?= $shippingDefaults['delivery_method'] === 'same_day_local' ? '' : ' hidden' ?>">
          <p class="ss-same-day-panel__lead">Shipping fee will be arranged by phone after your order. Call or message us to confirm your same-day delivery time.</p>
          <div class="ss-same-day-panel__actions">
            <a class="ss-btn-secondary ss-btn-link" href="<?= ss_escape(ss_support_phone_tel()) ?>">Call <?= ss_escape(ss_support_phone()) ?></a>
            <a class="ss-btn-text" href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">Message on Facebook</a>
          </div>
        </div>

        <div id="ssCheckoutPickupPanel" class="ss-pickup-panel<?= $shippingDefaults['delivery_method'] === 'cash_on_pickup' ? '' : ' hidden' ?>">
          <p class="ss-pickup-panel__lead">Pay cash when you collect your order at our warehouse. Call or message us to arrange your pickup time.</p>
          <div class="ss-pickup-panel__actions">
            <a class="ss-btn-secondary ss-btn-link" href="<?= ss_escape(ss_support_phone_tel()) ?>">Call <?= ss_escape(ss_support_phone()) ?></a>
            <a class="ss-btn-text" href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">Message on Facebook</a>
          </div>
          <p class="ss-pickup-panel__location"><strong>Pickup location:</strong> <?= ss_escape(jt_shipping_origin_label()) ?></p>
        </div>

        <div id="ssCheckoutJtShipping" class="ss-jt-shipping<?= $shippingDefaults['delivery_method'] === 'jt_nationwide' ? '' : ' hidden' ?>">
          <label>Province <span class="ss-required">*</span>
            <select id="ssShippingProvince" name="shipping_province_id" data-selected-id="<?= ss_escape($shippingDefaults['shipping_province_id']) ?>"<?= $shippingDefaults['delivery_method'] === 'jt_nationwide' ? ' required' : '' ?>>
              <option value="">Select province</option>
            </select>
          </label>
          <label>City <span class="ss-required">*</span>
            <select id="ssShippingCity" name="shipping_city_select" disabled data-selected-city="<?= ss_escape($shippingDefaults['shipping_city']) ?>"<?= $shippingDefaults['delivery_method'] === 'jt_nationwide' ? ' required' : '' ?>>
              <option value="">Select city</option>
            </select>
          </label>
        </div>
        <input type="hidden" name="shipping_city" id="ssShippingCityValue" value="<?= ss_escape($shippingDefaults['shipping_city']) ?>">
        <input type="hidden" name="shipping_province" id="ssShippingProvinceValue" value="<?= ss_escape($shippingDefaults['shipping_province']) ?>">
        <input type="hidden" id="ssShippingProvinceId" value="<?= ss_escape($shippingDefaults['shipping_province_id']) ?>">
        <input type="hidden" name="shipping_cents" id="ssShippingCents" value="<?= (int) $shippingDefaults['shipping_cents'] ?>">
      </div>
      <label id="ssShippingAddressLabel">Street address <span class="ss-required" id="ssShippingAddressRequired"<?= $addressRequired ? '' : ' hidden' ?>>*</span><span class="ss-hint" id="ssShippingAddressHint"<?= $addressRequired ? ' hidden' : '' ?>> (optional for pickup)</span>
        <textarea name="shipping_address" id="ssShippingAddress" rows="3"<?= $addressRequired ? ' required' : '' ?> placeholder="House no., street, barangay"><?= ss_escape($defaults['shipping_address']) ?></textarea>
      </label>
      <label>Order notes
        <textarea name="shipping_notes" rows="2"><?= ss_escape($defaults['shipping_notes']) ?></textarea>
      </label>
      <div id="ssPaymentMethodWrap" class="ss-payment-method-wrap<?= $isPickupDelivery ? ' hidden' : '' ?>">
        <label>Payment method
          <select name="payment_method" id="ssPaymentMethod"<?= $isPickupDelivery ? ' disabled' : '' ?>>
            <option value="cod" <?= $defaults['payment_method'] === 'cod' ? 'selected' : '' ?>>Cash on delivery</option>
            <?php if ($showBankTransfer): ?>
            <option value="bank" <?= $defaults['payment_method'] === 'bank' ? 'selected' : '' ?>>Bank transfer</option>
            <?php endif; ?>
          </select>
        </label>
      </div>
      <input type="hidden" name="payment_method" id="ssPaymentMethodPickup" value="cop"<?= $isPickupDelivery ? '' : ' disabled' ?>>
      <?php if ($showBankTransfer): ?>
      <div id="ss-bank-panel" class="ss-bank-panel<?= $defaults['payment_method'] === 'bank' ? '' : ' hidden' ?>">
        <h3><?= ss_escape((string) $paymentConfig['receiver_label']) ?></h3>
        <p class="ss-bank-instructions"><?= ss_escape((string) $paymentConfig['instructions']) ?></p>
        <p class="ss-bank-total">Amount to pay: <strong id="ssBankTotal"><?= ss_escape(ss_format_price($subtotalCents)) ?></strong></p>
        <img class="ss-bank-qr" src="<?= ss_escape(ss_media_url((string) $paymentConfig['qr_image'])) ?>" alt="<?= ss_escape((string) $paymentConfig['receiver_label']) ?> QR code">
        <label class="ss-bank-receipt">Payment receipt <span class="ss-required">*</span>
          <input type="file" name="payment_receipt" id="ssPaymentReceipt" accept="image/jpeg,image/png,image/webp,application/pdf"<?= $defaults['payment_method'] === 'bank' ? ' required' : '' ?>>
          <span class="ss-hint">Upload a screenshot or PDF of your transfer (max 5 MB).</span>
        </label>
      </div>
      <?php endif; ?>
      <input type="hidden" name="place_order" value="1">
      <button type="submit" class="ss-btn-primary ss-btn-block">Place order</button>
      <p class="ss-auth-sub"><a href="checkout.php?step=account" class="ss-btn-text">Edit saved account details</a></p>
    </form>
    <aside class="ss-cart-summary">
      <h2>Order summary</h2>
      <ul class="ss-summary-list">
        <?php foreach ($lines as $line): ?>
        <li>
          <span>
            <?= ss_escape($line['product']['name']) ?> &times; <?= (int) $line['qty'] ?>
            <?= product_estimated_arrival_html($line['product']) ?>
          </span>
          <span><?= ss_escape(ss_format_price($line['line_cents'])) ?></span>
        </li>
        <?php endforeach; ?>
      </ul>
      <ul class="ss-summary-list ss-summary-totals">
        <li>
          <span>Subtotal</span>
          <span id="ssCheckoutSubtotal"><?= ss_escape(ss_format_price($subtotalCents)) ?></span>
        </li>
        <?php if ($shippingOriginLabel !== ''): ?>
        <li id="ssCheckoutShippingOrigin" class="ss-summary-origin">
          <span>Ships from</span>
          <span><?= ss_escape($shippingOriginLabel) ?></span>
        </li>
        <?php endif; ?>
        <?php if ($shippingCarrierLabel !== ''): ?>
        <li id="ssCheckoutShippingCarrier" class="ss-summary-carrier">
          <span>Carrier</span>
          <span id="ssCheckoutSummaryCarrier"><?= ss_escape($shippingCarrierLabel) ?></span>
        </li>
        <?php endif; ?>
        <li id="ssCheckoutShippingRow">
          <span id="ssCheckoutShippingLabel">Shipping</span>
          <span id="ssCheckoutShipping"><?= ss_escape($initialShippingDisplay) ?></span>
        </li>
      </ul>
      <p class="ss-summary-total">Total: <strong id="ssCheckoutTotal"><?= ss_escape(ss_format_price($subtotalCents)) ?></strong></p>
      <a href="cart.php" class="ss-btn-text">&larr; Edit cart</a>
    </aside>
  </div>
</main>
<?php require __DIR__ . '/includes/layout-footer.php'; ss_page_end(true, false, true); ?>

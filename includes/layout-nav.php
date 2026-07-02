<?php
$cartCount = cart_count();
$isCustomer = customer_logged_in();
$isAdmin = admin_logged_in();
?>
<div class="ss-nav-wrap">
  <nav class="ss-nav" aria-label="Main">
    <a href="index.php" class="ss-logo"><?php ss_logo(); ?><span class="ss-logo-text"><?= ss_escape(ss_brand_name()) ?></span></a>
    <div class="ss-nav-links">
      <a href="index.php">Home</a>
      <a href="index.php#products">Products</a>
      <a href="index.php#products">Solar Kits</a>
      <a href="index.php#why">About</a>
      <a href="index.php#footer">Contact</a>
    </div>
    <div class="ss-nav-right">
      <a href="cart.php" class="ss-icon-btn ss-cart-link" aria-label="Cart">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.7 13.4a2 2 0 0 0 2 1.6h9.7a2 2 0 0 0 2-1.6L23 6H6"/></svg>
        <?php if ($cartCount > 0): ?>
        <span class="ss-cart-badge" id="ssCartBadge"><?= (int) $cartCount ?></span>
        <?php else: ?>
        <span class="ss-cart-badge ss-cart-badge--hidden" id="ssCartBadge">0</span>
        <?php endif; ?>
      </a>
      <?php if ($isAdmin): ?>
        <a href="admin/index.php" class="ss-nav-text">Admin</a>
      <?php endif; ?>
      <?php if ($isCustomer): ?>
        <a href="checkout.php?step=account" class="ss-nav-text">Account</a>
        <a href="logout.php" class="ss-btn-primary ss-btn-link">Log out</a>
      <?php else: ?>
        <a href="login.php" class="ss-nav-text">Sign in</a>
        <a href="register.php" class="ss-btn-primary ss-btn-link">Sign up</a>
      <?php endif; ?>
      <button class="ss-burger" id="ssBurger" aria-label="Menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"><line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/></svg>
      </button>
    </div>
  </nav>
  <div class="ss-mobile-menu hidden" id="ssMobileMenu">
    <a href="index.php">Home</a>
    <a href="index.php#products">Products</a>
    <a href="cart.php">Cart<?= $cartCount > 0 ? ' (' . $cartCount . ')' : '' ?></a>
    <?php if ($isAdmin): ?>
      <a href="admin/index.php">Admin</a>
    <?php endif; ?>
    <?php if ($isCustomer): ?>
      <a href="checkout.php?step=account">Account</a>
      <a href="logout.php">Log out</a>
    <?php else: ?>
      <a href="login.php">Sign in</a>
      <a href="register.php">Sign up</a>
    <?php endif; ?>
  </div>
</div>

<footer class="ss-footer ss-admin-footer">
  <div class="ss-footer-grid">
    <div>
      <a class="ss-logo" href="index.php"><?php admin_logo(); ?><span class="ss-logo-text"><?= ss_escape(ss_brand_name()) ?> Admin</span></a>
      <p class="ss-footer-tag">Manage orders, customers, and catalog for the <?= ss_escape(ss_brand_name()) ?> online store.</p>
    </div>
    <div>
      <h4>Admin</h4>
      <ul>
        <li><a href="index.php">Dashboard</a></li>
        <li><a href="orders.php">Orders</a></li>
        <li><a href="customers.php">Customers</a></li>
        <li><a href="products.php">Products</a></li>
      </ul>
    </div>
    <div>
      <h4>Store</h4>
      <ul>
        <li><a href="../index.php" target="_blank" rel="noopener">View storefront</a></li>
        <li><a href="enter-store.php">Enter as customer</a></li>
        <li><a href="../cart.php" target="_blank" rel="noopener">Store cart</a></li>
      </ul>
    </div>
    <div>
      <h4>Account</h4>
      <ul>
        <li><a href="settings.php">Settings</a></li>
        <li><a href="logs.php">Logs</a></li>
        <li><a href="logout.php">Log out</a></li>
      </ul>
    </div>
  </div>
  <div class="ss-footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= ss_escape(ss_brand_name_full()) ?>. Admin area.</span>
    <span class="ss-legal"><a href="../index.php">Back to store</a></span>
  </div>
</footer>

<footer class="ss-footer" id="footer">
  <div class="ss-footer-grid">
    <div>
      <a class="ss-logo" href="index.php"><?php ss_logo(); ?><span class="ss-logo-text"><?= ss_escape(ss_brand_name()) ?></span></a>
      <p class="ss-footer-tag">Trusted online store for power stations, solar panels, and portable energy products at affordable prices.</p>
      <?php
      $wholesaleNoteContext = 'footer';
      require __DIR__ . '/wholesale-note.php';
      ?>
    </div>
    <div><h4>Contact</h4><ul>
      <li><a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a></li>
      <li><a href="mailto:<?= ss_escape(ss_support_email()) ?>"><?= ss_escape(ss_support_email()) ?></a></li>
      <li><a href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">Facebook page</a></li>
    </ul></div>
    <div><h4>Products</h4><ul><li><a href="index.php#products">Power Stations</a></li><li><a href="index.php#products">Solar Panels</a></li><li><a href="index.php#products">Solar Kits</a></li></ul></div>
    <div><h4>Policies</h4><ul>
      <li><a href="return-refund-policy.php">Return &amp; Refund</a></li>
      <li><a href="terms.php">Terms &amp; Conditions</a></li>
      <li><a href="privacy.php">Privacy Policy</a></li>
      <li><a href="cart.php">Cart</a></li>
      <li><a href="login.php">Sign in</a></li>
    </ul></div>
  </div>
  <div class="ss-footer-bottom">
    <span>&copy; <?= date('Y') ?> <?= ss_escape(ss_brand_name_full()) ?>. All rights reserved.</span>
    <span class="ss-legal">
      <a href="terms.php">Terms &amp; Conditions</a>
      <a href="privacy.php">Privacy Policy</a>
      <a href="return-refund-policy.php">Return &amp; Refund</a>
    </span>
  </div>
</footer>

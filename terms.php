<?php
require_once __DIR__ . '/includes/bootstrap.php';

ss_page_start('Terms & Conditions — ' . ss_brand_name(), 'Terms and conditions for ' . ss_brand_name_full() . '.', false);
?>

<article class="ss-policy-page">
  <h1>Terms &amp; Conditions</h1>
  <p class="ss-policy-lead">Our full terms and conditions are being prepared. By using <?= ss_escape(ss_brand_name_full()) ?> you agree to purchase items in good faith and provide accurate shipping and contact information at checkout.</p>
  <p>For order questions, contact us at <a href="mailto:<?= ss_escape(ss_support_email()) ?>"><?= ss_escape(ss_support_email()) ?></a> or <a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a>.</p>
  <p><a href="return-refund-policy.php">Return &amp; Refund Policy</a></p>
</article>

<?php
require __DIR__ . '/includes/layout-footer.php';
ss_page_end(false);

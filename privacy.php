<?php
require_once __DIR__ . '/includes/bootstrap.php';

ss_page_start('Privacy Policy — ' . ss_brand_name(), 'Privacy policy for ' . ss_brand_name_full() . '.', false);
?>

<article class="ss-policy-page">
  <h1>Privacy Policy</h1>
  <p class="ss-policy-lead">We respect your privacy. This page will be updated with our complete privacy policy soon.</p>
  <p>We collect information you provide at checkout (name, email, phone, shipping address) and payment details only as needed to fulfill your order. We do not sell your personal data to third parties.</p>
  <p>To request access or correction of your data, email <a href="mailto:<?= ss_escape(ss_support_email()) ?>"><?= ss_escape(ss_support_email()) ?></a>.</p>
</article>

<?php
require __DIR__ . '/includes/layout-footer.php';
ss_page_end(false);

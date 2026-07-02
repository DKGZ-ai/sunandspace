<?php
require_once __DIR__ . '/includes/bootstrap.php';

ss_page_start(
    'Return & Refund Policy — ' . ss_brand_name(),
    'Returns, exchanges, and refunds for ' . ss_brand_name_full() . '.',
    false
);
?>

<article class="ss-policy-page">
  <h1>Return &amp; Refund Policy</h1>
  <?php require __DIR__ . '/includes/policies/return-refund-full.php'; ?>
</article>

<button type="button" class="ss-scroll-top" id="ssScrollTop" hidden aria-label="Back to top">&uarr;</button>

<?php
require __DIR__ . '/includes/layout-footer.php';
ss_page_end(false);

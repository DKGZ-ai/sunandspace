<?php
/** @var string $supportEmail */
$supportEmail = ss_support_email();
?>
<p>We offer <strong>wholesale and reseller pricing</strong> for bulk orders, dealers, and business buyers. Retail prices shown on this site are for individual customers.</p>
<p>To request a quote, contact us with the product name, quantity, and your business details:</p>
<ul>
  <li>Phone: <a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a></li>
  <li>Email: <a href="mailto:<?= ss_escape($supportEmail) ?>"><?= ss_escape($supportEmail) ?></a></li>
  <li>Facebook: <a href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">Message us on Facebook</a></li>
</ul>

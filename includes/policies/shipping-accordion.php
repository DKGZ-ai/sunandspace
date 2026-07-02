<?php
/** @var string $supportEmail */
/** @var array<string, mixed>|null $product */
$supportEmail = ss_support_email();
$isProductPreOrder = isset($product) && is_array($product) && product_is_preorder($product);
?>
<p>Orders ship from our Philippines fulfillment center at <strong><?= ss_escape(jt_shipping_origin_label()) ?></strong>.</p>
<?php if ($isProductPreOrder): ?>
<p><strong>Pre-order item:</strong> This product ships when stock arrives. <?= ss_escape(product_estimated_arrival_text($product) ?? '') ?>.</p>
<?php endif; ?>
<p><strong>Same-day delivery</strong> via <strong>Lalamove</strong> is available within Makati and nearby areas. Choose this option at checkout and call <a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a> or <a href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">message us on Facebook</a> to arrange your delivery time and shipping fee.</p>
<p><strong>Cash on pickup</strong> is available at our warehouse at <strong><?= ss_escape(jt_shipping_origin_label()) ?></strong>. Choose this option at checkout, pay in cash when you collect your order, and call or message us to arrange your pickup time.</p>
<p><strong>Nationwide shipping</strong> is via J&amp;T Express. The fee is calculated at checkout based on your destination city and the total weight of your items. <?php if (!$isProductPreOrder): ?>Typical delivery time is <strong><?= ss_escape(ss_delivery_window()) ?></strong> after your order is confirmed and packed.<?php else: ?>Delivery timing follows the pre-order estimate above once your order is packed and dispatched.<?php endif; ?> Remote areas may take a little longer.</p>
<p>Questions about your shipment? Email <a href="mailto:<?= ss_escape($supportEmail) ?>"><?= ss_escape($supportEmail) ?></a> or call <a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a>.</p>

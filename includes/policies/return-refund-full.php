<?php
$brand = ss_brand_name_full();
$days = ss_return_window_days();
$supportEmail = ss_support_email();
?>
<p class="ss-policy-lead">At <?= ss_escape($brand) ?>, we want you to be happy with your power stations, solar panels, and accessories. This policy explains how returns, exchanges, and refunds work.</p>

<h2>Eligibility for returns and exchanges</h2>
<p>To qualify for a return or exchange, your item must meet all of the following:</p>
<ol>
  <li><strong>Time frame:</strong> Contact us within <strong><?= (int) $days ?> days</strong> of receiving the item.</li>
  <li><strong>Condition:</strong> The item must be unused, in original condition, and include all tags, packaging (box, wraps), cables, and accessories.</li>
  <li><strong>Proof of purchase:</strong> Provide your order number and the email used at checkout.</li>
</ol>

<h2>Returns and exchanges we cannot accept</h2>
<ol>
  <li>Items that have been used, installed, or damaged for reasons unrelated to product defects (for example, change of mind after use, incorrect model ordered if the listing was accurate).</li>
  <li>Items with missing, opened, or damaged original packaging when resale condition is affected.</li>
  <li>Custom or final-sale items if marked as such at purchase.</li>
</ol>

<h2>How to request a return or exchange</h2>
<p>Email <a href="mailto:<?= ss_escape($supportEmail) ?>"><?= ss_escape($supportEmail) ?></a> with the subject line <strong>Return request — Order #[your order number]</strong> and include:</p>
<ol>
  <li>Your order number.</li>
  <li>Your full name and contact number.</li>
  <li>Product name and quantity to return or exchange.</li>
  <li>A clear description of the issue (defective unit, wrong item received, etc.).</li>
  <li>Clear photos or a short video showing the issue and serial labels if applicable.</li>
</ol>

<h2>Step 2: Review and authorization</h2>
<p>Our team reviews requests and replies by email within <strong>24 working hours</strong>. If approved, we will send return instructions and the return address. <strong>Do not ship items back without authorization;</strong> unauthorized returns may not be processed.</p>

<h2>Step 3: Return the item (if applicable)</h2>
<p>Once you receive a return authorization email, pack the product securely in its original packaging with all accessories. Include a copy of your order confirmation inside the box when possible.</p>

<h2>Step 4: Refund or exchange processing</h2>
<ul>
  <li><strong>Refund:</strong> After we receive and inspect the item, refunds are processed within <strong><?= ss_escape(ss_refund_processing_days()) ?></strong> to your original payment method.</li>
  <li><strong>Exchange:</strong> If you requested an exchange for a defective or incorrect item, we ship the replacement after we receive and verify the returned product.</li>
</ul>

<h2>Shipping costs</h2>
<p><strong>Faulty products or our error</strong> (for example, wrong item shipped, confirmed defect): We cover return shipping. We will provide a prepaid label or reimburse reasonable courier costs with proof of payment.</p>
<p><strong>Change of mind or other non-quality reasons</strong> (when eligible under the <?= (int) $days ?>-day window): Return shipping is paid by the buyer unless we state otherwise in writing.</p>

<h2>Contact us</h2>
<p>If you have questions about this policy, reach out anytime:</p>
<ul>
  <li>Email: <a href="mailto:<?= ss_escape($supportEmail) ?>"><?= ss_escape($supportEmail) ?></a></li>
  <li>Phone: <a href="<?= ss_escape(ss_support_phone_tel()) ?>"><?= ss_escape(ss_support_phone()) ?></a></li>
  <li>Facebook: <a href="<?= ss_escape(ss_facebook_url()) ?>" target="_blank" rel="noopener noreferrer">Message us on Facebook</a></li>
</ul>
<p>Thank you for shopping with <?= ss_escape(ss_brand_name()) ?>.</p>

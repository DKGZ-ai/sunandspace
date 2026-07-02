<div class="ss-accordion" data-accordion>
  <details class="ss-accordion__item" name="product-info" open>
    <summary class="ss-accordion__summary">
      <span class="ss-accordion__icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 3h15v13H1zM16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
      </span>
      <span class="ss-accordion__title">About Shipping</span>
      <span class="ss-accordion__chevron" aria-hidden="true"></span>
    </summary>
    <div class="ss-accordion__panel">
      <?php require __DIR__ . '/policies/shipping-accordion.php'; ?>
    </div>
  </details>

  <details class="ss-accordion__item" name="product-info">
    <summary class="ss-accordion__summary">
      <span class="ss-accordion__icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
      </span>
      <span class="ss-accordion__title">Wholesale &amp; Reseller Pricing</span>
      <span class="ss-accordion__chevron" aria-hidden="true"></span>
    </summary>
    <div class="ss-accordion__panel">
      <?php require __DIR__ . '/policies/wholesale-accordion.php'; ?>
    </div>
  </details>

  <details class="ss-accordion__item" name="product-info">
    <summary class="ss-accordion__summary">
      <span class="ss-accordion__icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/><polyline points="3.27 6.96 12 12.01 20.73 6.96"/><line x1="12" y1="22.08" x2="12" y2="12"/></svg>
      </span>
      <span class="ss-accordion__title">Return and Refund</span>
      <span class="ss-accordion__chevron" aria-hidden="true"></span>
    </summary>
    <div class="ss-accordion__panel">
      <?php require __DIR__ . '/policies/return-accordion.php'; ?>
    </div>
  </details>

  <details class="ss-accordion__item" name="product-info">
    <summary class="ss-accordion__summary">
      <span class="ss-accordion__icon" aria-hidden="true">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/></svg>
      </span>
      <span class="ss-accordion__title">Warranty</span>
      <span class="ss-accordion__chevron" aria-hidden="true"></span>
    </summary>
    <div class="ss-accordion__panel">
      <?php require __DIR__ . '/policies/warranty-accordion.php'; ?>
    </div>
  </details>
</div>

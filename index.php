<?php
require_once __DIR__ . '/includes/bootstrap.php';

$products = get_active_products();

$categories = store_shop_categories();

ss_page_start(
    ss_brand_name_full() . ' — Power Stations & Solar Products',
    'Reliable power stations, solar panels, and portable energy solutions for home, travel, and emergencies.',
    true
);
?>

<section class="ss-hero">
  <a href="#products" class="ss-hero-banner" aria-label="Shop power stations and solar products">
    <picture>
      <source media="(max-width: 768px)" srcset="<?= ss_escape(ss_media_url('images/hero-banner-mobile.png')) ?>">
      <img src="<?= ss_escape(ss_media_url('images/hero-banner-desktop.png')) ?>" alt="Power Your Life Anywhere — power stations, solar panels and portable energy solutions">
    </picture>
  </a>
</section>

<section class="ss-why" id="why">
  <h2>Why Choosing<br>Us</h2>
  <div class="ss-features">
    <?php
    $feats = [
      ['Affordable Power Solutions','Get dependable energy products at competitive prices without sacrificing safety and quality.'],
      ['Solar & Backup Products','Find portable power stations, solar panels, inverters, and complete solar kits in one place.'],
      ['Secure Shopping','Shop with confidence using a simple, safe, and user-friendly online store experience.'],
    ];
    foreach ($feats as $f): ?>
      <div class="ss-feature">
        <h3><?= ss_escape($f[0]) ?></h3>
        <p><?= ss_escape($f[1]) ?></p>
        <a class="ss-more" href="#products">More Info &rarr;</a>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="ss-best" id="products">
  <h2 class="ss-best-title">Best Selling Product</h2>
  <?php
  $wholesaleNoteContext = 'home';
  require __DIR__ . '/includes/wholesale-note.php';
  ?>
  <div class="ss-carousel">
    <button class="ss-arrow ss-arrow-left" id="ssPrev" aria-label="Previous">&larr;</button>
    <div class="ss-products" id="ssCarousel">
      <?php foreach ($products as $p):
        $productUrl = 'product.php?id=' . (int) $p['id'];
      ?>
        <article class="ss-product">
          <a class="ss-product-link" href="<?= ss_escape($productUrl) ?>">
            <div class="ss-product-img"><img src="<?= ss_escape(product_image_src((string) $p['image_path'])) ?>" alt="<?= ss_escape($p['name']) ?>" loading="lazy"></div>
            <p class="ss-product-cat"><?= ss_escape($p['category']) ?></p>
            <h3 class="ss-product-name"><?= ss_escape($p['name']) ?></h3>
          </a>
          <div class="ss-product-body">
            <div class="ss-price-row">
              <?= product_price_display_html($p) ?>
              <?= product_availability_badge_html($p) ?>
              <div class="ss-card-actions" aria-label="Quick actions">
                <button type="button" class="ss-plus ss-cart-icon-btn" data-add-cart data-product-id="<?= (int) $p['id'] ?>" aria-label="Add to cart">
                  <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                    <path fill="currentColor" d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2Zm10 0c-1.1 0-1.99.9-1.99 2S15.9 22 17 22s2-.9 2-2-.9-2-2-2ZM7.17 14h9.66c.75 0 1.4-.41 1.74-1.03l3.24-5.88c.34-.62-.08-1.39-.79-1.39H6.21L5.27 3H2v2h2l3.6 7.59-1.35 2.44C5.52 18.37 6.48 20 8 20h12v-2H8l1.17-2Z"/>
                  </svg>
                </button>
                <button type="button" class="ss-btn-primary ss-buy-card-btn" data-buy-now data-product-id="<?= (int) $p['id'] ?>" aria-label="Buy now">Buy now</button>
              </div>
            </div>
            <?= product_estimated_arrival_html($p) ?>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <button class="ss-arrow ss-arrow-right" id="ssNext" aria-label="Next">&rarr;</button>
  </div>
  <a class="ss-view-all" href="cart.php">View cart &rarr;</a>
</section>

<section class="ss-cat-section">
  <h2>Shop By Category</h2>
  <div class="ss-cats">
    <?php foreach ($categories as $c): ?>
      <a class="ss-cat" href="#products">
        <img src="<?= ss_escape(store_asset_src($c['img'])) ?>" alt="<?= ss_escape($c['label']) ?>" loading="lazy">
        <span class="ss-cat-label"><?= ss_escape($c['label']) ?></span>
      </a>
    <?php endforeach; ?>
  </div>
</section>

<?php
$freebieTrigger = freebie_modal_trigger();
if ($freebieTrigger !== null) {
    require __DIR__ . '/includes/freebie-modal.php';
}
require __DIR__ . '/includes/layout-footer.php';
ss_page_end(true, $freebieTrigger !== null);

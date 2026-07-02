<?php
require_once __DIR__ . '/includes/bootstrap.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$product = $id > 0 ? product_by_id($id) : null;

if (!$product || !(int) ($product['active'] ?? 0)) {
    http_response_code(404);
    ss_page_start('Product not found — ' . ss_brand_name(), 'This product is unavailable.', true);
    ?>
    <section class="ss-pdp ss-pdp--missing">
      <h1>Product not found</h1>
      <p>This item may have been removed or is no longer available.</p>
      <p><a class="ss-btn-primary ss-btn-link" href="index.php#products">Back to shop</a></p>
    </section>
    <?php
    require __DIR__ . '/includes/layout-footer.php';
    ss_page_end(true);
    exit;
}

$name = (string) $product['name'];
$description = trim((string) ($product['description'] ?? ''));
if ($description === '') {
    $cat = (string) ($product['category'] ?? '');
    $description = $cat !== ''
        ? 'Reliable ' . strtolower($cat) . ' from Sun & Space.'
        : 'Quality portable energy product from Sun & Space.';
}

ss_page_start(
    $name . ' — ' . ss_brand_name(),
    $description,
    true
);
?>

<section class="ss-pdp">
  <p class="ss-pdp__back"><a href="index.php#products">&larr; Back to products</a></p>

  <div class="ss-pdp__grid">
    <div class="ss-pdp__media">
      <div class="ss-product-img ss-pdp__img">
        <img src="<?= ss_escape(product_image_src((string) $product['image_path'])) ?>" alt="<?= ss_escape($name) ?>" width="600" height="600">
      </div>
    </div>

    <div class="ss-pdp__info">
      <p class="ss-product-cat"><?= ss_escape($product['category']) ?></p>
      <h1 class="ss-pdp__title"><?= ss_escape($name) ?></h1>
      <p class="ss-pdp__price"><?= product_price_display_html($product) ?></p>
      <?= product_availability_block_html($product) ?>
      <?php
      $wholesaleNoteContext = 'pdp';
      require __DIR__ . '/includes/wholesale-note.php';
      ?>
      <div class="ss-pdp__desc"><?= nl2br(ss_escape($description)) ?></div>

      <div class="ss-pdp__actions">
        <button type="button" class="ss-btn-secondary" data-add-cart data-product-id="<?= (int) $product['id'] ?>">Add to cart</button>
        <button type="button" class="ss-btn-primary" data-buy-now data-product-id="<?= (int) $product['id'] ?>">Buy now</button>
      </div>

      <?php require __DIR__ . '/includes/product-accordions.php'; ?>
      <?php require __DIR__ . '/includes/product-reviews-shell.php'; ?>
    </div>
  </div>
</section>

<button type="button" class="ss-scroll-top" id="ssScrollTop" hidden aria-label="Back to top">&uarr;</button>

<?php
require __DIR__ . '/includes/layout-footer.php';
ss_page_end(true);

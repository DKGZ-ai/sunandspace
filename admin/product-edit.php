<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$isEdit = $id > 0;
$product = $isEdit ? product_by_id($id) : null;

if ($isEdit && !$product) {
    ss_redirect('products.php?error=' . urlencode('Product not found.'));
}

$error = '';
$categories = product_categories();

$salePriceCents = $product ? ($product['sale_price_cents'] ?? null) : null;
$form = [
    'name' => $product['name'] ?? '',
    'category' => $product['category'] ?? '',
    'price' => $product ? number_format((int) $product['price_cents'] / 100, 2, '.', '') : '',
    'sale_price' => ($product && $salePriceCents !== null && $salePriceCents !== '')
        ? number_format((int) $salePriceCents / 100, 2, '.', '')
        : '',
    'weight_kg' => $product
        ? (string) (int) product_normalize_weight_kg($product['weight_kg'] ?? null)
        : (string) (int) product_default_weight_kg(),
    'description' => $product['description'] ?? '',
    'active' => $product ? (int) $product['active'] : 1,
    'pre_order' => $product ? (int) product_is_preorder($product) : 0,
    'estimated_arrival' => $product && product_is_preorder($product)
        ? (product_estimated_arrival_text($product) ?? '')
        : '',
    'image_path' => $product['image_path'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $postId = (int) ($_POST['id'] ?? 0);
        if ($isEdit && $postId !== $id) {
            $error = 'Invalid product.';
        } else {
            $priceCents = product_parse_price_cents((string) ($_POST['price'] ?? ''));
            if ($priceCents === null) {
                $error = 'Enter a valid price.';
            } else {
                $saleInput = trim((string) ($_POST['sale_price'] ?? ''));
                $salePriceCents = null;
                if ($saleInput !== '') {
                    $parsedSale = product_parse_price_cents($saleInput);
                    if ($parsedSale === null) {
                        $error = 'Enter a valid discount price.';
                    } else {
                        $salePriceCents = $parsedSale;
                    }
                }

                if ($error === '') {
                    $weightKg = product_parse_weight_kg($_POST['weight_kg'] ?? null);
                    if ($weightKg === null) {
                        $error = 'Select a product weight (5 kg or 8 kg).';
                    }
                }

                if ($error === '') {
                    $isPreOrder = isset($_POST['pre_order']);
                    $estimatedArrival = trim((string) ($_POST['estimated_arrival'] ?? ''));
                    if ($isPreOrder && $estimatedArrival === '') {
                        $error = 'Enter an estimated arrival for pre-order products.';
                    }
                }

                if ($error === '') {
                    $saveData = [
                        'name' => (string) ($_POST['name'] ?? ''),
                        'category' => (string) ($_POST['category'] ?? ''),
                        'price_cents' => $priceCents,
                        'sale_price_cents' => $salePriceCents,
                        'weight_kg' => $weightKg,
                        'description' => (string) ($_POST['description'] ?? ''),
                        'active' => isset($_POST['active']) ? 1 : 0,
                        'availability_status' => $isPreOrder ? 'pre_order' : 'in_stock',
                        'estimated_arrival' => $isPreOrder ? $estimatedArrival : null,
                        'remove_image' => isset($_POST['remove_image']),
                    ];
                    if ($isEdit) {
                        $saveData['id'] = $id;
                    }

                    $file = isset($_FILES['image']) && is_array($_FILES['image']) ? $_FILES['image'] : null;
                    $result = product_save($saveData, $file);

                    if ($result['ok']) {
                        ss_redirect('products.php?saved=1');
                    }
                    $error = $result['error'];
                }
            }
        }
    }

    $form = [
        'name' => (string) ($_POST['name'] ?? $form['name']),
        'category' => (string) ($_POST['category'] ?? $form['category']),
        'price' => (string) ($_POST['price'] ?? $form['price']),
        'sale_price' => (string) ($_POST['sale_price'] ?? $form['sale_price']),
        'weight_kg' => (string) ($_POST['weight_kg'] ?? $form['weight_kg']),
        'description' => (string) ($_POST['description'] ?? $form['description']),
        'active' => isset($_POST['active']) ? 1 : 0,
        'pre_order' => isset($_POST['pre_order']) ? 1 : 0,
        'estimated_arrival' => (string) ($_POST['estimated_arrival'] ?? $form['estimated_arrival']),
        'image_path' => $form['image_path'],
    ];
    if (!empty($_POST['remove_image'])) {
        $form['image_path'] = '';
    }
}

$pageTitle = $isEdit ? 'Edit product' : 'Add product';
admin_page_start($pageTitle, 'products');
admin_page_header(
    $pageTitle,
    $isEdit ? 'Update catalog details and image.' : 'Create a new store catalog item.',
    'Changes apply to the storefront when the product is active.',
    false
);
?>

<section class="ss-admin-panel ss-admin-panel--wide">
  <?php if ($error): ?>
    <div class="ss-alert ss-alert-error"><?= ss_escape($error) ?></div>
  <?php endif; ?>

  <p>
    <a class="ss-btn-text" href="products.php">&larr; Back to products</a>
    <?php if ($isEdit && $form['active']): ?>
      &nbsp;·&nbsp;<a class="ss-btn-text" href="../product.php?id=<?= (int) $id ?>" target="_blank" rel="noopener noreferrer">View on store</a>
    <?php endif; ?>
  </p>

  <form method="post" class="ss-form ss-admin-form" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($isEdit): ?>
      <input type="hidden" name="id" value="<?= (int) $id ?>">
    <?php endif; ?>

    <label>Name
      <input type="text" name="name" required maxlength="255" value="<?= ss_escape($form['name']) ?>">
    </label>

    <label>Category
      <input type="text" name="category" maxlength="100" list="product-categories" value="<?= ss_escape($form['category']) ?>">
    </label>
    <datalist id="product-categories">
      <?php foreach ($categories as $cat): ?>
        <option value="<?= ss_escape($cat) ?>">
      <?php endforeach; ?>
    </datalist>

    <label>Regular price (₱)
      <input type="text" name="price" required inputmode="decimal" placeholder="8999.00" value="<?= ss_escape($form['price']) ?>">
    </label>

    <label>Discount price (₱)
      <input type="text" name="sale_price" inputmode="decimal" placeholder="2499.00" value="<?= ss_escape($form['sale_price']) ?>">
      <span class="ss-admin-form__hint">Optional. Leave blank to remove discount.</span>
    </label>

    <label>Weight
      <select name="weight_kg" required>
        <?php foreach (product_weight_kg_options() as $optionKg): ?>
          <?php $optionValue = (string) (int) $optionKg; ?>
          <option value="<?= ss_escape($optionValue) ?>"<?= $form['weight_kg'] === $optionValue ? ' selected' : '' ?>><?= ss_escape($optionValue) ?> kg</option>
        <?php endforeach; ?>
      </select>
      <span class="ss-admin-form__hint">Used for J&amp;T shipping quotes at checkout.</span>
    </label>

    <label>Description
      <textarea name="description" rows="5"><?= ss_escape($form['description']) ?></textarea>
    </label>

    <fieldset class="ss-admin-form__image">
      <legend>Product image</legend>
      <?php if ($form['image_path'] !== ''): ?>
        <div class="ss-admin-product-preview">
          <img src="<?= ss_escape(product_admin_image_src($form['image_path'])) ?>" alt="">
        </div>
        <label class="ss-admin-form__checkbox">
          <input type="checkbox" name="remove_image" value="1"<?= !empty($_POST['remove_image']) ? ' checked' : '' ?>>
          Remove current image
        </label>
      <?php else: ?>
        <p class="ss-admin-form__hint">No image yet.</p>
      <?php endif; ?>
      <label>Upload image (JPG, PNG, or WebP, max 5 MB)
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
      </label>
    </fieldset>

    <label class="ss-admin-form__checkbox">
      <input type="checkbox" name="pre_order" value="1" id="productPreOrder"<?= $form['pre_order'] ? ' checked' : '' ?>>
      Pre-order
    </label>

    <label id="productEstimatedArrivalWrap"<?= $form['pre_order'] ? '' : ' hidden' ?>>Estimated arrival
      <input type="text" name="estimated_arrival" id="productEstimatedArrival" maxlength="255" placeholder="<?= ss_escape(product_default_estimated_arrival()) ?>" value="<?= ss_escape($form['estimated_arrival']) ?>">
      <span class="ss-admin-form__hint">Shown on the storefront for pre-order items.</span>
    </label>

    <label class="ss-admin-form__checkbox">
      <input type="checkbox" name="active" value="1"<?= $form['active'] ? ' checked' : '' ?>>
      Active (visible on storefront)
    </label>

    <div class="ss-admin-form__actions">
      <button type="submit" class="ss-btn-primary"><?= $isEdit ? 'Save changes' : 'Create product' ?></button>
      <a class="ss-btn-secondary ss-btn-link" href="products.php">Cancel</a>
    </div>
  </form>
</section>

<script>
(function () {
  var preOrder = document.getElementById('productPreOrder');
  var etaWrap = document.getElementById('productEstimatedArrivalWrap');
  if (!preOrder || !etaWrap) return;
  preOrder.addEventListener('change', function () {
    etaWrap.hidden = !preOrder.checked;
  });
})();
</script>

<?php admin_page_end(); ?>

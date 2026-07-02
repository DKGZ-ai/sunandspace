<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$flash = '';
if (!empty($_GET['saved'])) {
    $flash = 'Product saved.';
} elseif (!empty($_GET['deleted'])) {
    $flash = 'Product deleted.';
} elseif (!empty($_GET['error'])) {
    $flash = (string) $_GET['error'];
}

$stmt = $pdo->query(
    'SELECT id, name, category, price_cents, sale_price_cents, image_path, description, active, weight_kg, availability_status, estimated_arrival FROM products ' . product_catalog_order_sql()
);
$products = $stmt->fetchAll();

admin_page_start('Products', 'products');
admin_page_header(
    'Products',
    'Store catalog items.',
    'Add, edit, or remove products. Drag rows to change storefront order.',
    false
);
?>

<section class="ss-admin-panel ss-admin-panel--wide">
  <div class="ss-admin-products-toolbar">
    <p class="ss-admin-sort-hint">Click and hold the grip on the left, then drag up or down to reorder.</p>
    <a class="ss-btn-primary" href="product-edit.php">Add product</a>
  </div>

  <p class="ss-admin-sort-status" id="adminProductSortStatus" hidden aria-live="polite"></p>

  <?php if ($flash !== ''): ?>
    <div class="ss-alert<?= !empty($_GET['error']) ? ' ss-alert-error' : ' ss-alert-success' ?>"><?= ss_escape($flash) ?></div>
  <?php endif; ?>

  <?php if (!$products): ?>
    <p>No products yet. <a href="product-edit.php">Add your first product</a>.</p>
  <?php else: ?>
  <div class="ss-admin-table-wrap">
  <table class="ss-cart-table ss-admin-products-table">
    <thead>
      <tr>
        <th class="ss-admin-drag-col" aria-label="Reorder"><span class="ss-admin-drag-grip" aria-hidden="true">⋮⋮</span></th>
        <th>Image</th>
        <th>Name</th>
        <th>Category</th>
        <th>Price</th>
        <th>Weight</th>
        <th>Status</th>
        <th>Description</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody id="adminProductSortable">
      <?php foreach ($products as $p): ?>
      <tr data-product-id="<?= (int) $p['id'] ?>">
        <td class="ss-admin-drag-cell">
          <span class="ss-admin-drag-handle" role="button" tabindex="0" aria-grabbed="false" aria-label="Drag to reorder <?= ss_escape($p['name']) ?>">
            <span class="ss-admin-drag-grip" aria-hidden="true">⋮⋮</span>
          </span>
        </td>
        <td>
          <?php if (!empty($p['image_path'])): ?>
            <img class="ss-admin-product-thumb" src="<?= ss_escape(product_admin_image_src((string) $p['image_path'])) ?>" alt="">
          <?php else: ?>
            <span class="ss-admin-product-thumb ss-admin-product-thumb--empty" aria-hidden="true">—</span>
          <?php endif; ?>
        </td>
        <td><?= ss_escape($p['name']) ?></td>
        <td><?= ss_escape($p['category']) ?></td>
        <td><?= product_price_display_html($p) ?></td>
        <td class="ss-admin-product-weight"><?= ss_escape(product_weight_kg_label((float) ($p['weight_kg'] ?? product_default_weight_kg()))) ?></td>
        <td>
          <?= (int) $p['active'] ? 'Active' : 'Hidden' ?>
          · <?= ss_escape(product_admin_availability_label($p)) ?>
          <?php if (product_is_preorder($p)): ?>
            <span class="ss-admin-product-eta"><?= ss_escape(product_truncate_description(product_estimated_arrival_text($p), 48)) ?></span>
          <?php endif; ?>
        </td>
        <td class="ss-admin-product-desc"><?= ss_escape(product_truncate_description($p['description'] ?? '')) ?></td>
        <td>
          <div class="ss-admin-actions">
            <a class="ss-btn-text" href="product-edit.php?id=<?= (int) $p['id'] ?>">Edit</a>
            <form method="post" action="product-delete.php" class="ss-admin-delete-form" onsubmit="return confirm('Delete this product permanently?');">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int) $p['id'] ?>">
              <button type="submit" class="ss-btn-text ss-btn-danger">Delete</button>
            </form>
          </div>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  </div>
  <?php endif; ?>
</section>

<?php admin_page_end(); ?>

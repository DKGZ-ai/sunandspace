<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();
require_once dirname(__DIR__) . '/includes/admin-layout.php';

$error = '';
$saved = isset($_GET['saved']);

$labels = [];
foreach (store_category_slots() as $slot) {
    $labels[$slot['label_key']] = store_setting($slot['label_key']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = store_settings_save_branding($_POST, $_FILES);
    if ($result['ok']) {
        ss_redirect('settings.php?saved=1');
    }
    $error = $result['error'];
    foreach (store_category_slots() as $slot) {
        $labels[$slot['label_key']] = trim((string) ($_POST[$slot['label_key']] ?? $labels[$slot['label_key']]));
    }
}

admin_page_start('Settings', 'settings');
admin_page_header(
    'Settings',
    'Store branding.',
    'Update the storefront logo, homepage hero image, and shop-by-category blocks.',
    false
);
?>

<section class="ss-admin-panel ss-admin-panel--wide ss-admin-settings">
  <?php if ($saved): ?>
    <div class="ss-alert ss-alert-success">Branding settings saved.</div>
  <?php endif; ?>
  <?php if ($error): ?>
    <div class="ss-alert ss-alert-error"><?= ss_escape($error) ?></div>
  <?php endif; ?>

  <form method="post" class="ss-form ss-admin-form ss-admin-settings-form" enctype="multipart/form-data">
    <?= csrf_field() ?>

    <div class="ss-admin-settings__group">
      <h2 class="ss-admin-settings__heading">Branding assets</h2>
      <p class="ss-admin-form__hint">Logo and hero image used across the storefront header and homepage.</p>
      <div class="ss-admin-settings__grid ss-admin-settings__grid--2">
        <?php foreach (store_branding_asset_slots() as $slot):
            $path = store_asset_path($slot['key']);
            $isCustom = store_setting_raw($slot['key']) !== '';
            $imageMod = $slot['key'] === 'asset_hero' ? ' ss-admin-form__image--hero' : ' ss-admin-form__image--logo';
            ?>
          <fieldset class="ss-admin-form__image<?= $imageMod ?>">
            <legend><?= ss_escape($slot['title']) ?></legend>
            <div class="ss-admin-product-preview">
              <img src="<?= ss_escape(store_asset_src($path, true)) ?>" alt="">
            </div>
            <?php if ($isCustom): ?>
              <label class="ss-admin-form__checkbox">
                <input type="checkbox" name="<?= ss_escape($slot['remove_field']) ?>" value="1"<?= !empty($_POST[$slot['remove_field']]) ? ' checked' : '' ?>>
                Remove custom <?= ss_escape(strtolower($slot['title'])) ?> (revert to default)
              </label>
            <?php endif; ?>
            <?php if ($slot['key'] === 'asset_hero'): ?>
              <p class="ss-admin-form__hint">PNG with a transparent background works well on the orange hero card.</p>
            <?php endif; ?>
            <label class="ss-admin-settings__file">Upload image (JPG, PNG, or WebP, max 5 MB)
              <input type="file" name="<?= ss_escape($slot['file_field']) ?>" accept="image/jpeg,image/png,image/webp">
            </label>
          </fieldset>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="ss-admin-settings__group">
      <h2 class="ss-admin-settings__heading">Shop by category</h2>
      <p class="ss-admin-form__hint">Three homepage category tiles shown below the product carousel.</p>
      <div class="ss-admin-settings__grid ss-admin-settings__grid--3">
        <?php foreach (store_category_slots() as $slot):
            $path = store_asset_path($slot['asset_key']);
            $isCustom = store_setting_raw($slot['asset_key']) !== '';
            ?>
          <fieldset class="ss-admin-form__image ss-admin-form__image--category">
            <legend><?= ss_escape($slot['title']) ?></legend>
            <label>Label
              <input type="text" name="<?= ss_escape($slot['label_key']) ?>" required maxlength="50" value="<?= ss_escape($labels[$slot['label_key']]) ?>">
            </label>
            <div class="ss-admin-product-preview">
              <img src="<?= ss_escape(store_asset_src($path, true)) ?>" alt="">
            </div>
            <?php if ($isCustom): ?>
              <label class="ss-admin-form__checkbox">
                <input type="checkbox" name="<?= ss_escape($slot['remove_field']) ?>" value="1"<?= !empty($_POST[$slot['remove_field']]) ? ' checked' : '' ?>>
                Remove custom image (revert to default)
              </label>
            <?php endif; ?>
            <label class="ss-admin-settings__file">Upload image (JPG, PNG, or WebP, max 5 MB)
              <input type="file" name="<?= ss_escape($slot['file_field']) ?>" accept="image/jpeg,image/png,image/webp">
            </label>
          </fieldset>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="ss-admin-form__actions ss-admin-settings__actions">
      <button type="submit" class="ss-btn-primary">Save branding</button>
    </div>
  </form>
</section>

<?php admin_page_end(); ?>

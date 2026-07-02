<?php

/** @return array<string, string> */
function store_setting_defaults(): array
{
    return [
        'asset_logo' => 'images/logo.png',
        'asset_hero' => 'images/hero-power-station.png',
        'asset_category_power_stations' => 'images/category-power-stations.jpg',
        'asset_category_solar_panels' => 'images/category-solar-panels.jpg',
        'asset_category_solar_kits' => 'images/category-solar-kits.jpg',
        'category_label_power_stations' => 'POWER STATIONS',
        'category_label_solar_panels' => 'SOLAR PANELS',
        'category_label_solar_kits' => 'SOLAR KITS',
    ];
}

/** @return array<int, array{id: string, title: string, label_key: string, asset_key: string, file_field: string, remove_field: string, upload_slug: string}> */
function store_category_slots(): array
{
    return [
        [
            'id' => 'power_stations',
            'title' => 'Power stations',
            'label_key' => 'category_label_power_stations',
            'asset_key' => 'asset_category_power_stations',
            'file_field' => 'image_category_power_stations',
            'remove_field' => 'remove_category_power_stations',
            'upload_slug' => 'category-power-stations',
        ],
        [
            'id' => 'solar_panels',
            'title' => 'Solar panels',
            'label_key' => 'category_label_solar_panels',
            'asset_key' => 'asset_category_solar_panels',
            'file_field' => 'image_category_solar_panels',
            'remove_field' => 'remove_category_solar_panels',
            'upload_slug' => 'category-solar-panels',
        ],
        [
            'id' => 'solar_kits',
            'title' => 'Solar kits',
            'label_key' => 'category_label_solar_kits',
            'asset_key' => 'asset_category_solar_kits',
            'file_field' => 'image_category_solar_kits',
            'remove_field' => 'remove_category_solar_kits',
            'upload_slug' => 'category-solar-kits',
        ],
    ];
}

/** @return array<int, array{key: string, title: string, file_field: string, remove_field: string, upload_slug: string}> */
function store_branding_asset_slots(): array
{
    return [
        [
            'key' => 'asset_logo',
            'title' => 'Logo',
            'file_field' => 'image_logo',
            'remove_field' => 'remove_logo',
            'upload_slug' => 'logo',
        ],
        [
            'key' => 'asset_hero',
            'title' => 'Hero image',
            'file_field' => 'image_hero',
            'remove_field' => 'remove_hero',
            'upload_slug' => 'hero',
        ],
    ];
}

function store_settings_reset_cache(): void
{
    $GLOBALS['store_settings_cache'] = null;
}

/** @return array<string, string> */
function store_settings_cache(): array
{
    if (array_key_exists('store_settings_cache', $GLOBALS) && is_array($GLOBALS['store_settings_cache'])) {
        return $GLOBALS['store_settings_cache'];
    }

    $cache = [];
    global $pdo;

    try {
        $stmt = $pdo->query('SELECT setting_key, setting_value FROM store_settings');
        while ($row = $stmt->fetch()) {
            $cache[(string) $row['setting_key']] = (string) $row['setting_value'];
        }
    } catch (PDOException $e) {
        // Table may not exist until migration is applied.
    }

    $GLOBALS['store_settings_cache'] = $cache;
    return $cache;
}

function store_setting_raw(string $key): string
{
    $cache = store_settings_cache();
    return trim($cache[$key] ?? '');
}

function store_setting(string $key): string
{
    $value = store_setting_raw($key);
    if ($value !== '') {
        return $value;
    }

    return store_setting_defaults()[$key] ?? '';
}

function store_asset_path(string $key): string
{
    return store_setting($key);
}

function store_asset_src(string $path, bool $adminRelative = false): string
{
    return ss_media_url($path, $adminRelative);
}

/** @return list<array{label: string, img: string}> */
function store_shop_categories(): array
{
    $categories = [];
    foreach (store_category_slots() as $slot) {
        $categories[] = [
            'label' => store_setting($slot['label_key']),
            'img' => store_asset_path($slot['asset_key']),
        ];
    }

    return $categories;
}

function store_setting_write(string $key, string $value): void
{
    global $pdo;

    $value = trim($value);
    if ($value === '') {
        $pdo->prepare('DELETE FROM store_settings WHERE setting_key = ?')->execute([$key]);
    } else {
        $pdo->prepare(
            'INSERT INTO store_settings (setting_key, setting_value) VALUES (?, ?)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        )->execute([$key, $value]);
    }

    store_settings_reset_cache();
}

function store_replace_asset(string $key, ?array $file, bool $remove, string $uploadSlug): array
{
    $defaults = store_setting_defaults();
    if (!isset($defaults[$key])) {
        return ['ok' => false, 'error' => 'Unknown asset.'];
    }

    $current = store_setting_raw($key);
    $hasUpload = is_array($file)
        && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    if ($remove) {
        if ($current !== '' && ss_is_site_upload_path($current)) {
            ss_delete_site_image_file($current);
        }
        store_setting_write($key, '');
        return ['ok' => true];
    }

    if (!$hasUpload) {
        return ['ok' => true];
    }

    $upload = ss_save_site_image($file, $uploadSlug);
    if (!$upload['ok']) {
        return $upload;
    }

    if ($current !== '' && $current !== $upload['path'] && ss_is_site_upload_path($current)) {
        ss_delete_site_image_file($current);
    }

    store_setting_write($key, $upload['path']);
    return ['ok' => true];
}

/** @return array{ok: true}|array{ok: false, error: string} */
function store_settings_save_branding(array $post, array $files): array
{
    if (!csrf_verify()) {
        return ['ok' => false, 'error' => 'Invalid request. Please try again.'];
    }

    foreach (store_category_slots() as $slot) {
        $label = trim((string) ($post[$slot['label_key']] ?? ''));
        if ($label === '') {
            return ['ok' => false, 'error' => $slot['title'] . ' label is required.'];
        }
        if (strlen($label) > 50) {
            return ['ok' => false, 'error' => $slot['title'] . ' label is too long.'];
        }
    }

    try {
        foreach (store_category_slots() as $slot) {
            $label = trim((string) ($post[$slot['label_key']] ?? ''));
            $defaultLabel = store_setting_defaults()[$slot['label_key']] ?? '';
            if ($label === $defaultLabel) {
                store_setting_write($slot['label_key'], '');
            } else {
                store_setting_write($slot['label_key'], $label);
            }
        }

        foreach (store_branding_asset_slots() as $slot) {
            $file = isset($files[$slot['file_field']]) && is_array($files[$slot['file_field']])
                ? $files[$slot['file_field']]
                : null;
            $remove = !empty($post[$slot['remove_field']]);
            $result = store_replace_asset($slot['key'], $file, $remove, $slot['upload_slug']);
            if (!$result['ok']) {
                return $result;
            }
        }

        foreach (store_category_slots() as $slot) {
            $file = isset($files[$slot['file_field']]) && is_array($files[$slot['file_field']])
                ? $files[$slot['file_field']]
                : null;
            $remove = !empty($post[$slot['remove_field']]);
            $result = store_replace_asset($slot['asset_key'], $file, $remove, $slot['upload_slug']);
            if (!$result['ok']) {
                return $result;
            }
        }

        return ['ok' => true];
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'Could not save settings. Run sunandspace_data/sql/migrate_store_settings.sql and try again.'];
    }
}

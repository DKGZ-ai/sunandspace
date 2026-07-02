<?php

/** SQL fragment: catalog display order (storefront and admin product list). */
function product_catalog_order_sql(): string
{
    product_ensure_sort_order_column();

    return 'ORDER BY sort_order ASC, id ASC';
}

function product_ensure_sort_order_column(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $pdo;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'sort_order'");
        if ($stmt->fetch()) {
            return;
        }

        $pdo->exec('ALTER TABLE products ADD COLUMN sort_order INT UNSIGNED NOT NULL DEFAULT 0 AFTER active');
        $pdo->exec('SET @row := 0');
        $pdo->exec('UPDATE products SET sort_order = (@row := @row + 1) ORDER BY id ASC');
    } catch (PDOException $e) {
        // Leave ordering on id if migration cannot run.
    }
}

function product_ensure_weight_kg_column(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $pdo;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'weight_kg'");
        if ($stmt->fetch()) {
            return;
        }

        $pdo->exec('ALTER TABLE products ADD COLUMN weight_kg DECIMAL(8,2) NOT NULL DEFAULT 5.00 AFTER sort_order');
    } catch (PDOException $e) {
        // Column may be added via migrate_jt_shipping.sql.
    }
}

function product_default_estimated_arrival(): string
{
    return 'Estimated arrival in PH: July 2nd-3rd week';
}

function product_normalize_estimated_arrival(string $eta): string
{
    // Fix en-dash mojibake when UTF-8 was stored/read with the wrong charset.
    return str_replace(['ÔÇô', 'â€"', '–', '—'], '-', $eta);
}

/** @return list<string> */
function product_in_stock_bundle_names(): array
{
    return [
        '650W/400Wh power station with free 100W Premium Foldable Solar Panel',
        '300W/225W portable power station with free 60W/18V premium foldable solar panel',
    ];
}

function product_ensure_preorder_columns(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $pdo;

    try {
        $addedColumns = false;

        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'availability_status'");
        if (!$stmt->fetch()) {
            $pdo->exec(
                "ALTER TABLE products ADD COLUMN availability_status ENUM('in_stock', 'pre_order') NOT NULL DEFAULT 'in_stock' AFTER weight_kg"
            );
            $addedColumns = true;
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM products LIKE 'estimated_arrival'");
        if (!$stmt->fetch()) {
            $pdo->exec(
                'ALTER TABLE products ADD COLUMN estimated_arrival VARCHAR(255) NULL DEFAULT NULL AFTER availability_status'
            );
            $addedColumns = true;
        }

        if ($addedColumns) {
            product_apply_preorder_bulk_defaults();
        }
    } catch (PDOException $e) {
        // Columns may be added via migrate_product_preorder.sql.
    }
}

function product_apply_preorder_bulk_defaults(): void
{
    global $pdo;

    try {
        $eta = product_default_estimated_arrival();
        $pdo->exec(
            "UPDATE products SET availability_status = 'pre_order', estimated_arrival = " . $pdo->quote($eta)
        );

        $placeholders = implode(',', array_fill(0, count(product_in_stock_bundle_names()), '?'));
        $stmt = $pdo->prepare(
            "UPDATE products SET availability_status = 'in_stock', estimated_arrival = NULL WHERE name IN ($placeholders)"
        );
        $stmt->execute(product_in_stock_bundle_names());
    } catch (PDOException $e) {
        // Best-effort catalog defaults.
    }
}

function product_normalize_availability_status(mixed $status): string
{
    return $status === 'pre_order' ? 'pre_order' : 'in_stock';
}

function product_is_preorder(array $product): bool
{
    return product_normalize_availability_status($product['availability_status'] ?? 'in_stock') === 'pre_order';
}

function product_availability_label(array $product): ?string
{
    return product_is_preorder($product) ? 'Pre-order' : null;
}

function product_estimated_arrival_text(array $product): ?string
{
    if (!product_is_preorder($product)) {
        return null;
    }

    $eta = trim((string) ($product['estimated_arrival'] ?? ''));
    if ($eta !== '') {
        return product_normalize_estimated_arrival($eta);
    }

    return product_default_estimated_arrival();
}

function product_availability_badge_html(array $product): string
{
    $label = product_availability_label($product);
    if ($label === null) {
        return '';
    }

    return '<span class="ss-product-badge ss-product-badge--preorder">' . ss_escape($label) . '</span>';
}

function product_estimated_arrival_html(array $product): string
{
    $eta = product_estimated_arrival_text($product);
    if ($eta === null) {
        return '';
    }

    return '<span class="ss-product-eta">' . ss_escape($eta) . '</span>';
}

function product_availability_block_html(array $product): string
{
    $badge = product_availability_badge_html($product);
    $eta = product_estimated_arrival_html($product);
    if ($badge === '' && $eta === '') {
        return '';
    }

    return '<div class="ss-product-availability">' . $badge . $eta . '</div>';
}

function product_admin_availability_label(array $product): string
{
    return product_is_preorder($product) ? 'Pre-order' : 'In stock';
}

/** @return list<float> */
function product_weight_kg_options(): array
{
    return [5.0, 8.0];
}

function product_default_weight_kg(): float
{
    return 5.0;
}

function product_weight_kg_is_allowed(float $weightKg): bool
{
    foreach (product_weight_kg_options() as $allowed) {
        if (abs($weightKg - $allowed) < 0.001) {
            return true;
        }
    }

    return false;
}

function product_normalize_weight_kg(mixed $weightKg): float
{
    if (!is_numeric($weightKg)) {
        return product_default_weight_kg();
    }

    $weightKg = round((float) $weightKg, 2);
    if (!product_weight_kg_is_allowed($weightKg)) {
        return product_default_weight_kg();
    }

    return $weightKg;
}

function product_parse_weight_kg(mixed $weightKg): ?float
{
    if (!is_numeric($weightKg)) {
        return null;
    }

    $weightKg = round((float) $weightKg, 2);
    if (!product_weight_kg_is_allowed($weightKg)) {
        return null;
    }

    return $weightKg;
}

function product_weight_kg_label(float $weightKg): string
{
    return number_format(product_normalize_weight_kg($weightKg), 0, '.', '') . ' kg';
}

function product_next_sort_order(): int
{
    global $pdo;
    product_ensure_sort_order_column();

    return ((int) $pdo->query('SELECT COALESCE(MAX(sort_order), 0) FROM products')->fetchColumn()) + 1;
}

/**
 * @param list<int|string> $ids Product IDs in desired display order
 * @return array{ok: true}|array{ok: false, error: string}
 */
function product_reorder(array $ids): array
{
    global $pdo;
    product_ensure_sort_order_column();

    $ids = array_values(array_unique(array_filter(array_map('intval', $ids), static fn (int $id): bool => $id > 0)));
    if ($ids === []) {
        return ['ok' => false, 'error' => 'No products to reorder.'];
    }

    $total = (int) $pdo->query('SELECT COUNT(*) FROM products')->fetchColumn();
    if (count($ids) !== $total) {
        return ['ok' => false, 'error' => 'Product list is incomplete. Refresh and try again.'];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id FROM products WHERE id IN ($placeholders)");
    $stmt->execute($ids);
    if (count($stmt->fetchAll()) !== count($ids)) {
        return ['ok' => false, 'error' => 'Invalid product list.'];
    }

    try {
        $pdo->beginTransaction();
        $update = $pdo->prepare('UPDATE products SET sort_order = ? WHERE id = ?');
        foreach ($ids as $index => $id) {
            $update->execute([$index + 1, $id]);
        }
        $pdo->commit();

        return ['ok' => true];
    } catch (PDOException $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        return ['ok' => false, 'error' => 'Could not save product order.'];
    }
}

function product_by_id(int $id): ?array
{
    global $pdo;
    if ($id < 1) {
        return null;
    }
    product_ensure_preorder_columns();
    $stmt = $pdo->prepare(
        'SELECT id, name, category, price_cents, sale_price_cents, image_path, description, active, weight_kg, availability_status, estimated_arrival FROM products WHERE id = ? LIMIT 1'
    );
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

/** @return list<string> */
function product_categories(): array
{
    global $pdo;
    $stmt = $pdo->query(
        'SELECT DISTINCT category FROM products WHERE category <> \'\' ORDER BY category ASC'
    );
    return array_column($stmt->fetchAll(), 'category');
}

function product_has_order_history(int $id): bool
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT 1 FROM order_items WHERE product_id = ? LIMIT 1');
    $stmt->execute([$id]);
    return (bool) $stmt->fetchColumn();
}

function product_on_sale(array $product): bool
{
    if (!isset($product['sale_price_cents']) || $product['sale_price_cents'] === null || $product['sale_price_cents'] === '') {
        return false;
    }
    $saleCents = (int) $product['sale_price_cents'];
    if ($saleCents <= 0) {
        return false;
    }
    return $saleCents < (int) ($product['price_cents'] ?? 0);
}

function product_effective_price_cents(array $product): int
{
    if (product_on_sale($product)) {
        return (int) $product['sale_price_cents'];
    }
    return (int) ($product['price_cents'] ?? 0);
}

function product_price_display_html(array $product): string
{
    if (product_on_sale($product)) {
        $was = ss_escape(ss_format_price((int) $product['price_cents']));
        $now = ss_escape(ss_format_price((int) $product['sale_price_cents']));
        return '<span class="ss-price-wrap">'
            . '<span class="ss-price ss-price--was">' . $was . '</span>'
            . '<span class="ss-price ss-price--sale">' . $now . '</span>'
            . '</span>';
    }
    return '<span class="ss-price">' . ss_escape(ss_format_price((int) $product['price_cents'])) . '</span>';
}

function product_parse_price_cents(string $input): ?int
{
    $input = trim(str_replace([',', '₱', ' '], '', $input));
    if ($input === '' || !is_numeric($input)) {
        return null;
    }
    $pesos = (float) $input;
    if ($pesos < 0) {
        return null;
    }
    return (int) round($pesos * 100);
}

function product_admin_image_src(string $imagePath): string
{
    return ss_media_url($imagePath, true);
}

function product_image_src(string $imagePath): string
{
    return ss_media_url($imagePath);
}

function product_truncate_description(?string $description, int $maxLength = 80): string
{
    $description = trim((string) $description);
    if ($description === '') {
        return '—';
    }
    if (strlen($description) <= $maxLength) {
        return $description;
    }
    return substr($description, 0, $maxLength - 1) . '…';
}

/**
 * @param array{name: string, category: string, price_cents: int, sale_price_cents?: int|null, description: string, active: int, availability_status?: string, estimated_arrival?: string|null, id?: int, remove_image?: bool} $data
 * @param array<string, mixed>|null $file $_FILES['image'] when present
 * @return array{ok: true, id: int}|array{ok: false, error: string}
 */
function product_save(array $data, ?array $file = null): array
{
    global $pdo;

    $name = trim($data['name'] ?? '');
    if ($name === '') {
        return ['ok' => false, 'error' => 'Product name is required.'];
    }
    if (strlen($name) > 255) {
        return ['ok' => false, 'error' => 'Product name is too long.'];
    }

    $category = trim($data['category'] ?? '');
    if (strlen($category) > 100) {
        return ['ok' => false, 'error' => 'Category is too long.'];
    }

    $priceCents = $data['price_cents'] ?? null;
    if (!is_int($priceCents) || $priceCents < 0) {
        return ['ok' => false, 'error' => 'Enter a valid price.'];
    }

    $salePriceCents = null;
    if (array_key_exists('sale_price_cents', $data)) {
        $saleRaw = $data['sale_price_cents'];
        if ($saleRaw !== null) {
            if (!is_int($saleRaw) || $saleRaw < 0) {
                return ['ok' => false, 'error' => 'Enter a valid discount price.'];
            }
            if ($saleRaw >= $priceCents) {
                return ['ok' => false, 'error' => 'Discount price must be less than regular price.'];
            }
            $salePriceCents = $saleRaw;
        }
    }

    $description = trim($data['description'] ?? '');
    $active = !empty($data['active']) ? 1 : 0;
    $availabilityStatus = product_normalize_availability_status($data['availability_status'] ?? 'in_stock');
    $estimatedArrival = trim((string) ($data['estimated_arrival'] ?? ''));
    if ($availabilityStatus === 'pre_order') {
        if ($estimatedArrival === '') {
            $estimatedArrival = product_default_estimated_arrival();
        }
        if (strlen($estimatedArrival) > 255) {
            return ['ok' => false, 'error' => 'Estimated arrival text is too long.'];
        }
    } else {
        $estimatedArrival = '';
    }
    $removeImage = !empty($data['remove_image']);
    $productId = isset($data['id']) ? (int) $data['id'] : 0;
    $isEdit = $productId > 0;

    $existing = $isEdit ? product_by_id($productId) : null;
    if ($isEdit && !$existing) {
        return ['ok' => false, 'error' => 'Product not found.'];
    }

    $weightKg = product_parse_weight_kg($data['weight_kg'] ?? null);
    if ($weightKg === null && $isEdit && $existing) {
        $weightKg = product_parse_weight_kg($existing['weight_kg'] ?? null);
    }
    if ($weightKg === null) {
        $weightKg = product_default_weight_kg();
    }

    $imagePath = $isEdit ? (string) ($existing['image_path'] ?? '') : '';

    if ($removeImage && $imagePath !== '') {
        ss_delete_product_image_file($imagePath);
        $imagePath = '';
    }

    $hasUpload = is_array($file)
        && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

    try {
        if ($isEdit) {
            $stmt = $pdo->prepare(
                'UPDATE products SET name = ?, category = ?, price_cents = ?, sale_price_cents = ?, description = ?, active = ?, weight_kg = ?, availability_status = ?, estimated_arrival = ? WHERE id = ?'
            );
            $stmt->execute([
                $name,
                $category,
                $priceCents,
                $salePriceCents,
                $description,
                $active,
                $weightKg,
                $availabilityStatus,
                $estimatedArrival !== '' ? $estimatedArrival : null,
                $productId,
            ]);
        } else {
            $sortOrder = product_next_sort_order();
            $stmt = $pdo->prepare(
                'INSERT INTO products (name, category, price_cents, sale_price_cents, image_path, description, active, sort_order, weight_kg, availability_status, estimated_arrival) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $stmt->execute([
                $name,
                $category,
                $priceCents,
                $salePriceCents,
                '',
                $description,
                $active,
                $sortOrder,
                $weightKg,
                $availabilityStatus,
                $estimatedArrival !== '' ? $estimatedArrival : null,
            ]);
            $productId = (int) $pdo->lastInsertId();
        }

        if ($hasUpload) {
            $upload = ss_save_product_image($file, $productId);
            if (!$upload['ok']) {
                return $upload;
            }
            if ($imagePath !== '' && $imagePath !== $upload['path']) {
                ss_delete_product_image_file($imagePath);
            }
            $imagePath = $upload['path'];
            $pdo->prepare('UPDATE products SET image_path = ? WHERE id = ?')->execute([$imagePath, $productId]);
        } elseif ($removeImage || (!$isEdit && $imagePath === '')) {
            $pdo->prepare('UPDATE products SET image_path = ? WHERE id = ?')->execute([$imagePath, $productId]);
        }

        return ['ok' => true, 'id' => $productId];
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'Could not save product. Please try again.'];
    }
}

/** @return array{ok: true}|array{ok: false, error: string} */
function product_delete(int $id): array
{
    global $pdo;

    if ($id < 1) {
        return ['ok' => false, 'error' => 'Product not found.'];
    }

    $product = product_by_id($id);
    if (!$product) {
        return ['ok' => false, 'error' => 'Product not found.'];
    }

    if (product_has_order_history($id)) {
        return [
            'ok' => false,
            'error' => 'This product appears in orders and cannot be deleted. Edit it and uncheck Active to hide it from the store.',
        ];
    }

    try {
        $stmt = $pdo->prepare('DELETE FROM products WHERE id = ?');
        $stmt->execute([$id]);
        if ($stmt->rowCount() < 1) {
            return ['ok' => false, 'error' => 'Product not found.'];
        }
        ss_delete_product_image_file((string) ($product['image_path'] ?? ''));
        return ['ok' => true];
    } catch (PDOException $e) {
        return ['ok' => false, 'error' => 'Could not delete product. Please try again.'];
    }
}

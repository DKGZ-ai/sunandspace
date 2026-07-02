<?php

function cart_count(): int
{
    if (customer_logged_in()) {
        global $pdo;
        $userId = (int) $_SESSION['user_id'];
        $cartId = get_user_cart_id($userId);
        if ($cartId === null) {
            return 0;
        }
        $stmt = $pdo->prepare('SELECT SUM(qty) FROM cart_items WHERE cart_id = ?');
        $stmt->execute([$cartId]);
        return (int) $stmt->fetchColumn();
    } else {
        $total = 0;
        foreach ($_SESSION['cart'] as $qty) {
            $total += (int) $qty;
        }
        return $total;
    }
}

function cart_contains_product(int $productId): bool
{
    if ($productId < 1) {
        return false;
    }
    if (customer_logged_in()) {
        global $pdo;
        $userId = (int) $_SESSION['user_id'];
        $cartId = get_user_cart_id($userId);
        if ($cartId === null) {
            return false;
        }
        $stmt = $pdo->prepare('SELECT 1 FROM cart_items WHERE cart_id = ? AND product_id = ? LIMIT 1');
        $stmt->execute([$cartId, $productId]);

        return (bool) $stmt->fetchColumn();
    }

    return isset($_SESSION['cart'][$productId]) && (int) $_SESSION['cart'][$productId] > 0;
}

function cart_add(int $productId, int $qty = 1): bool
{
    global $pdo;
    if ($qty < 1) {
        return false;
    }
    $stmt = $pdo->prepare('SELECT id, price_cents, sale_price_cents FROM products WHERE id = ? AND active = 1');
    $stmt->execute([$productId]);
    $product = $stmt->fetch();
    if (!$product) {
        return false;
    }
    $unitPrice = product_effective_price_cents($product);

    if (customer_logged_in()) {
        $userId = (int) $_SESSION['user_id'];
        $cartId = get_or_create_user_cart_id($userId);
        add_db_cart_item($cartId, $productId, $qty, $unitPrice);
    } else {
        $current = (int) ($_SESSION['cart'][$productId] ?? 0);
        $_SESSION['cart'][$productId] = $current + $qty;
    }
    return true;
}

function cart_set_qty(int $productId, int $qty): bool
{
    global $pdo;
    $productStmt = $pdo->prepare('SELECT id, price_cents, sale_price_cents FROM products WHERE id = ? AND active = 1');
    $productStmt->execute([$productId]);
    $product = $productStmt->fetch();

    if (!$product) {
        return false;
    }
    $unitPrice = product_effective_price_cents($product);

    if (customer_logged_in()) {
        $userId = (int) $_SESSION['user_id'];
        $cartId = get_or_create_user_cart_id($userId);
        if ($qty < 1) {
            remove_db_cart_item($cartId, $productId);
        } else {
            update_db_cart_item($cartId, $productId, $qty, $unitPrice);
        }
    } else {
        if ($qty < 1) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $qty;
        }
    }
    return true;
}

function cart_remove(int $productId): void
{
    if (customer_logged_in()) {
        $userId = (int) $_SESSION['user_id'];
        $cartId = get_user_cart_id($userId);
        if ($cartId !== null) {
            remove_db_cart_item($cartId, $productId);
        }
    } else {
        unset($_SESSION['cart'][$productId]);
    }
}

function cart_clear(): void
{
    if (customer_logged_in()) {
        $userId = (int) $_SESSION['user_id'];
        $cartId = get_user_cart_id($userId);
        if ($cartId !== null) {
            clear_db_cart($cartId);
        }
    } else {
        cart_clear_session();
    }
}

function cart_is_empty(): bool
{
    if (customer_logged_in()) {
        global $pdo;
        $userId = (int) $_SESSION['user_id'];
        $cartId = get_user_cart_id($userId);
        if ($cartId === null) {
            return true;
        }
        $stmt = $pdo->prepare('SELECT COUNT(*) FROM cart_items WHERE cart_id = ?');
        $stmt->execute([$cartId]);
        return (int) $stmt->fetchColumn() === 0;
    } else {
        return empty($_SESSION['cart']);
    }
}

/** @return list<array{product: array, qty: int, line_cents: int}> */
function cart_lines(): array
{
    global $pdo;
    $items = [];

    if (customer_logged_in()) {
        $userId = (int) $_SESSION['user_id'];
        $cartId = get_user_cart_id($userId);
        if ($cartId === null) {
            return [];
        }
        $dbItems = get_db_cart_items($cartId);
        foreach ($dbItems as $item) {
            $items[(int) $item['product_id']] = (int) $item['qty'];
        }
    } else {
        $items = $_SESSION['cart'] ?? [];
    }

    if (empty($items)) {
        return [];
    }

    $ids = array_keys($items);
    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT * FROM products WHERE id IN ($placeholders) AND active = 1");
    $stmt->execute($ids);
    $products = [];
    while ($row = $stmt->fetch()) {
        $products[(int) $row['id']] = $row;
    }

    $lines = [];
    foreach ($items as $productId => $qty) {
        $productId = (int) $productId;
        $qty = (int) $qty;
        if ($qty < 1 || !isset($products[$productId])) {
            continue;
        }
        $product = $products[$productId];
        $lineCents = product_effective_price_cents($product) * $qty;
        $lines[] = [
            'product' => $product,
            'qty' => $qty,
            'line_cents' => $lineCents,
        ];
    }
    return $lines;
}

function cart_total_cents(): int
{
    $total = 0;
    foreach (cart_lines() as $line) {
        $total += $line['line_cents'];
    }
    return $total;
}

function cart_set_checkout_selection(array $productIds): void
{
    $cartProductIds = [];
    foreach (cart_lines() as $line) {
        $cartProductIds[] = (int) $line['product']['id'];
    }
    $validIds = [];
    foreach ($productIds as $id) {
        $id = (int) $id;
        if ($id > 0 && in_array($id, $cartProductIds, true)) {
            $validIds[] = $id;
        }
    }
    $_SESSION['checkout_selection'] = array_values(array_unique($validIds));
}

/** @return list<int> */
function cart_checkout_selection(): array
{
    $lines = cart_lines();
    if ($lines === []) {
        return [];
    }
    $cartProductIds = array_map(static fn(array $line): int => (int) $line['product']['id'], $lines);

    if (!empty($_SESSION['checkout_selection']) && is_array($_SESSION['checkout_selection'])) {
        $selected = array_map('intval', $_SESSION['checkout_selection']);
        return array_values(array_intersect($cartProductIds, $selected));
    }

    return $cartProductIds;
}

/** @return list<array{product: array, qty: int, line_cents: int}> */
function cart_lines_for_checkout(): array
{
    $selection = array_flip(cart_checkout_selection());
    if ($selection === []) {
        return [];
    }
    $lines = [];
    foreach (cart_lines() as $line) {
        if (isset($selection[(int) $line['product']['id']])) {
            $lines[] = $line;
        }
    }
    return $lines;
}

function cart_total_cents_for_checkout(): int
{
    $total = 0;
    foreach (cart_lines_for_checkout() as $line) {
        $total += $line['line_cents'];
    }
    return $total;
}

function cart_checkout_weight_kg(): float
{
    product_ensure_weight_kg_column();

    $weight = 0.0;
    foreach (cart_lines_for_checkout() as $line) {
        $kg = product_normalize_weight_kg($line['product']['weight_kg'] ?? null);
        if ($kg < 0.1) {
            $kg = 1.0;
        }
        $weight += $kg * (int) $line['qty'];
    }

    $config = jt_shipping_config();
    $minWeight = (float) ($config['min_weight_kg'] ?? 0.5);

    return max($minWeight, round($weight, 2));
}

function cart_remove_product_ids(array $productIds): void
{
    foreach ($productIds as $productId) {
        cart_remove((int) $productId);
    }
}

function cart_accept_checkout_selection_from_request(): void
{
    if (!isset($_POST['checkout_items'])) {
        return;
    }
    $raw = $_POST['checkout_items'];
    if (!is_array($raw)) {
        $raw = [$raw];
    }
    cart_set_checkout_selection($raw);
}

function get_active_products(): array
{
    global $pdo;
    $stmt = $pdo->query('SELECT * FROM products WHERE active = 1 ' . product_catalog_order_sql());
    return $stmt->fetchAll();
}

// Database cart helpers

function get_user_cart_id(int $userId): ?int
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id FROM carts WHERE user_id = ?');
    $stmt->execute([$userId]);
    $cartId = $stmt->fetchColumn();
    return $cartId !== false ? (int) $cartId : null;
}

function create_user_cart(int $userId): int
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO carts (user_id) VALUES (?)');
    $stmt->execute([$userId]);
    return (int) $pdo->lastInsertId();
}

function get_or_create_user_cart_id(int $userId): int
{
    $cartId = get_user_cart_id($userId);
    if ($cartId === null) {
        $cartId = create_user_cart($userId);
    }
    return $cartId;
}

function add_db_cart_item(int $cartId, int $productId, int $qty, int $unitPriceCents): void
{
    global $pdo;
    $stmt = $pdo->prepare('INSERT INTO cart_items (cart_id, product_id, qty, unit_price_cents) VALUES (?, ?, ?, ?) ON DUPLICATE KEY UPDATE qty = qty + VALUES(qty)');
    $stmt->execute([$cartId, $productId, $qty, $unitPriceCents]);
}

function update_db_cart_item(int $cartId, int $productId, int $qty, int $unitPriceCents): void
{
    global $pdo;
    $stmt = $pdo->prepare('UPDATE cart_items SET qty = ?, unit_price_cents = ? WHERE cart_id = ? AND product_id = ?');
    $stmt->execute([$qty, $unitPriceCents, $cartId, $productId]);
}

function remove_db_cart_item(int $cartId, int $productId): void
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ? AND product_id = ?');
    $stmt->execute([$cartId, $productId]);
}

function clear_db_cart(int $cartId): void
{
    global $pdo;
    $stmt = $pdo->prepare('DELETE FROM cart_items WHERE cart_id = ?');
    $stmt->execute([$cartId]);
}

function get_db_cart_items(int $cartId): array
{
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT ci.product_id, ci.qty, p.name, p.category, p.price_cents, p.sale_price_cents, p.image_path
         FROM cart_items ci JOIN products p ON ci.product_id = p.id
         WHERE ci.cart_id = ?'
    );
    $stmt->execute([$cartId]);
    return $stmt->fetchAll();
}

function cart_migrate_session_to_db(int $userId): void
{
    global $pdo;
    if (empty($_SESSION['cart'])) {
        return;
    }

    $cartId = get_or_create_user_cart_id($userId);
    $productIds = array_keys($_SESSION['cart']);
    $placeholders = implode(',', array_fill(0, count($productIds), '?'));

    $stmt = $pdo->prepare("SELECT id, price_cents, sale_price_cents FROM products WHERE id IN ($placeholders) AND active = 1");
    $stmt->execute($productIds);
    $products = [];
    while ($row = $stmt->fetch()) {
        $products[(int) $row['id']] = $row;
    }

    foreach ($_SESSION['cart'] as $productId => $qty) {
        $productId = (int) $productId;
        $qty = (int) $qty;
        if ($qty > 0 && isset($products[$productId])) {
            $unitPrice = product_effective_price_cents($products[$productId]);
            // Check if item already exists in DB cart, if so, update quantity
            $checkStmt = $pdo->prepare('SELECT qty FROM cart_items WHERE cart_id = ? AND product_id = ?');
            $checkStmt->execute([$cartId, $productId]);
            $existingQty = $checkStmt->fetchColumn();

            if ($existingQty !== false) {
                update_db_cart_item($cartId, $productId, $existingQty + $qty, $unitPrice);
            } else {
                add_db_cart_item($cartId, $productId, $qty, $unitPrice);
            }
        }
    }
    cart_clear_session();
}

function cart_clear_session(): void
{
    unset($_SESSION['cart']);
}

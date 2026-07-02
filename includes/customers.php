<?php

function admin_customer_by_id(int $id): ?array
{
    if ($id < 1) {
        return null;
    }
    return customer_by_id($id);
}

function admin_customer_display_address(array $row): string
{
    $billing = trim((string) ($row['billing_address'] ?? ''));
    if ($billing !== '') {
        return $billing;
    }
    return trim((string) ($row['last_shipping_address'] ?? ''));
}

/** @return list<array<string, mixed>> */
function admin_customers_list(): array
{
    global $pdo;
    $billingCols = users_billing_columns_available()
        ? 'u.billing_address, u.billing_notes,'
        : '';

    $sql = "SELECT u.id, u.name, u.email, u.phone, u.created_at,
            {$billingCols}
            COALESCE(oc.cnt, 0) AS order_count,
            COALESCE(ci.cart_qty, 0) AS cart_qty,
            COALESCE(ci.cart_lines, 0) AS cart_lines,
            lo.shipping_address AS last_shipping_address
        FROM users u
        LEFT JOIN (
            SELECT user_id, COUNT(*) AS cnt FROM orders GROUP BY user_id
        ) oc ON oc.user_id = u.id
        LEFT JOIN carts c ON c.user_id = u.id
        LEFT JOIN (
            SELECT cart_id, SUM(qty) AS cart_qty, COUNT(*) AS cart_lines
            FROM cart_items GROUP BY cart_id
        ) ci ON ci.cart_id = c.id
        LEFT JOIN (
            SELECT o1.user_id, o1.shipping_address
            FROM orders o1
            INNER JOIN (
                SELECT user_id, MAX(id) AS max_id FROM orders GROUP BY user_id
            ) x ON x.user_id = o1.user_id AND x.max_id = o1.id
        ) lo ON lo.user_id = u.id
        WHERE u.role = ?
        ORDER BY u.created_at DESC
        LIMIT 100";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['customer']);
    return $stmt->fetchAll();
}

/** @return array{order_count: int, cart_qty: int, cart_lines: int, total_spent_cents: int, last_order_at: ?string} */
function admin_customer_stats(int $userId): array
{
    global $pdo;

    $stmt = $pdo->prepare('SELECT COUNT(*) AS order_count, COALESCE(SUM(total_cents), 0) AS total_spent_cents, MAX(created_at) AS last_order_at FROM orders WHERE user_id = ?');
    $stmt->execute([$userId]);
    $orderRow = $stmt->fetch() ?: [];

    $stmt = $pdo->prepare(
        'SELECT COALESCE(SUM(ci.qty), 0) AS cart_qty, COUNT(ci.id) AS cart_lines
         FROM carts c
         LEFT JOIN cart_items ci ON ci.cart_id = c.id
         WHERE c.user_id = ?'
    );
    $stmt->execute([$userId]);
    $cartRow = $stmt->fetch() ?: [];

    return [
        'order_count' => (int) ($orderRow['order_count'] ?? 0),
        'cart_qty' => (int) ($cartRow['cart_qty'] ?? 0),
        'cart_lines' => (int) ($cartRow['cart_lines'] ?? 0),
        'total_spent_cents' => (int) ($orderRow['total_spent_cents'] ?? 0),
        'last_order_at' => isset($orderRow['last_order_at']) && $orderRow['last_order_at'] !== null
            ? (string) $orderRow['last_order_at']
            : null,
    ];
}

/** @return list<array<string, mixed>> */
function admin_customer_recent_orders(int $userId, int $limit = 5): array
{
    global $pdo;
    $stmt = $pdo->prepare(
        'SELECT id, status, total_cents, created_at
         FROM orders WHERE user_id = ?
         ORDER BY created_at DESC, id DESC
         LIMIT ' . (int) $limit
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function admin_customer_save(
    int $userId,
    string $name,
    string $email,
    string $phone,
    string $address,
    string $notes
): array {
    $result = update_customer_profile($userId, $name, $email, $phone);
    if (!$result['ok']) {
        return $result;
    }
    update_customer_billing($userId, trim($address), trim($notes));
    return ['ok' => true];
}

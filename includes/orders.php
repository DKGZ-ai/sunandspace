<?php

/** @return list<string> */
function order_statuses(): array
{
    return ['pending', 'approved', 'in_progress', 'delivered'];
}

function order_status_label(string $status): string
{
    return match ($status) {
        'pending' => 'Pending',
        'approved' => 'Approved',
        'in_progress' => 'Out for Delivery',
        'delivered' => 'Delivered',
        default => ucfirst(str_replace('_', ' ', $status)),
    };
}

function order_status_is_valid(string $status): bool
{
    return in_array($status, order_statuses(), true);
}

function order_sanitize_tracking_number(string $trackingNumber): string
{
    $trackingNumber = trim($trackingNumber);
    if ($trackingNumber === '') {
        return '';
    }
    if (strlen($trackingNumber) > 100) {
        $trackingNumber = substr($trackingNumber, 0, 100);
    }
    return $trackingNumber;
}

function order_ensure_shipping_columns(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $pdo;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'shipping_cents'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_province VARCHAR(100) NOT NULL DEFAULT '' AFTER shipping_notes");
            $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_city VARCHAR(100) NOT NULL DEFAULT '' AFTER shipping_province");
            $pdo->exec("ALTER TABLE orders ADD COLUMN shipping_cents INT UNSIGNED NOT NULL DEFAULT 0 AFTER shipping_city");
            $pdo->exec("ALTER TABLE orders ADD COLUMN subtotal_cents INT UNSIGNED NOT NULL DEFAULT 0 AFTER shipping_cents");
        }

        $stmt = $pdo->query("SHOW COLUMNS FROM orders LIKE 'delivery_method'");
        if (!$stmt->fetch()) {
            $pdo->exec("ALTER TABLE orders ADD COLUMN delivery_method VARCHAR(30) NOT NULL DEFAULT 'jt_nationwide' AFTER subtotal_cents");
        }
    } catch (PDOException $e) {
        // Columns may be added via migrate_jt_shipping.sql.
    }
}

/** @return list<string> */
function order_delivery_methods(): array
{
    return ['same_day_local', 'jt_nationwide', 'cash_on_pickup'];
}

function order_is_pickup_delivery(string $method): bool
{
    return order_parse_delivery_method($method) === 'cash_on_pickup';
}

function order_delivery_method_is_valid(string $method): bool
{
    return in_array($method, order_delivery_methods(), true);
}

function order_default_delivery_method(): string
{
    return 'jt_nationwide';
}

function order_delivery_method_label(string $method): string
{
    return match ($method) {
        'same_day_local' => 'Same-day (Makati & nearby)',
        'jt_nationwide' => 'Nationwide',
        'cash_on_pickup' => 'Cash on pickup',
        default => ucfirst(str_replace('_', ' ', $method)),
    };
}

function order_delivery_carrier_label(string $method): string
{
    return match ($method) {
        'same_day_local' => 'Lalamove',
        'jt_nationwide' => 'J&T Express',
        'cash_on_pickup' => 'Store pickup',
        default => '',
    };
}

function order_delivery_display_label(string $method): string
{
    $method = order_parse_delivery_method($method);
    $carrier = order_delivery_carrier_label($method);
    $label = order_delivery_method_label($method);

    return $carrier !== '' ? $label . ' (' . $carrier . ')' : $label;
}

function order_parse_delivery_method(mixed $method): string
{
    $method = trim((string) $method);
    if (!order_delivery_method_is_valid($method)) {
        return order_default_delivery_method();
    }

    return $method;
}

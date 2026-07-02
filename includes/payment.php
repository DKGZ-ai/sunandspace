<?php

/** @return array<string, mixed> */
function ss_payment_config(): array
{
    static $config = null;
    if ($config === null) {
        $config = require ss_data_root() . '/config/payment.php';
    }
    return $config;
}

/** @return list<string> */
function ss_payment_methods(): array
{
    return ['cod', 'bank', 'cop'];
}

function ss_payment_method_is_valid(string $method): bool
{
    return in_array($method, ss_payment_methods(), true);
}

function ss_payment_method_label(string $method): string
{
    return match ($method) {
        'bank' => 'Bank transfer',
        'cod' => 'Cash on delivery',
        'cop' => 'Cash on pickup',
        default => ucfirst($method),
    };
}

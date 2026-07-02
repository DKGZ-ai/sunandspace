<?php

function ss_escape(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function ss_redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/** Web root path for the storefront (e.g. /sunandspace or empty at domain root). */
function ss_app_base_path(): string
{
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? ''));
    $dir = dirname($script);
    while ($dir !== '/' && $dir !== '.' && preg_match('#/(admin|auth|api)$#', $dir)) {
        $dir = dirname($dir);
    }
    if ($dir === '/' || $dir === '.') {
        return '';
    }

    return $dir;
}

function ss_app_base_url(): string
{
    $scheme = 'http';
    $forwardedProto = strtolower(trim((string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
    if ($forwardedProto === 'https') {
        $scheme = 'https';
    } elseif (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        $scheme = 'https';
    }

    $host = trim((string) ($_SERVER['HTTP_HOST'] ?? ''));
    if ($host === '') {
        $host = trim((string) ($_SERVER['SERVER_NAME'] ?? ''));
    }
    if ($host === '') {
        $host = 'localhost';
    }

    return $scheme . '://' . $host . ss_app_base_path();
}

/** Storefront path from site root (e.g. /sunandspace/login.php). */
function ss_app_url(string $path): string
{
    $path = ltrim(str_replace('\\', '/', $path), '/');
    $base = ss_app_base_path();
    if ($base === '' || $base === '.') {
        return '/' . $path;
    }

    return rtrim($base, '/') . '/' . $path;
}

function ss_format_price(int $cents): string
{
    return '₱' . number_format($cents / 100, 0, '.', ',');
}

function ss_format_money(int $cents): string
{
    return '₱' . number_format($cents / 100, 2, '.', ',');
}

function ss_address_summary(string $address, int $maxLength = 48): string
{
    $line = trim(strtok(str_replace(["\r\n", "\r"], "\n", $address), "\n"));
    if ($line === '') {
        return '';
    }
    if (strlen($line) <= $maxLength) {
        return $line;
    }
    return substr($line, 0, $maxLength - 1) . '…';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . ss_escape(csrf_token()) . '">';
}

function csrf_verify(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return $token !== '' && hash_equals(csrf_token(), $token);
}

function ss_brand_name(): string
{
    return 'Sun & Space';
}

function ss_brand_name_full(): string
{
    return ss_brand_name() . ' Online Store';
}

function ss_logo(): void
{
    $src = store_asset_src(store_asset_path('asset_logo'));
    echo '<img class="ss-logo-img" src="' . ss_escape($src) . '" alt="" width="48" height="48">';
}

function ss_page_start(string $title, string $description = '', bool $loadCartJs = false): void
{
    $desc = $description !== '' ? $description : ss_brand_name_full();
    ?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title><?= ss_escape($title) ?></title>
  <meta name="description" content="<?= ss_escape($desc) ?>">
  <meta name="csrf-token" content="<?= ss_escape(csrf_token()) ?>">
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="ss-page">
<?php
    require __DIR__ . '/layout-nav.php';
}

function freebie_modal_consume_login_flag(): bool
{
    if (empty($_SESSION['freebie_after_login'])) {
        return false;
    }
    unset($_SESSION['freebie_after_login']);

    return true;
}

/** @return 'guest'|'login'|null */
function freebie_modal_trigger(): ?string
{
    if (customer_logged_in()) {
        return freebie_modal_consume_login_flag() ? 'login' : null;
    }

    return 'guest';
}

function ss_page_end(bool $loadCartJs = false, bool $loadFreebieJs = false, bool $loadCheckoutShippingJs = false): void
{
    ?>
<script src="assets/js/main.js"></script>
<?php if ($loadCartJs): ?>
<script src="assets/js/cart.js"></script>
<?php endif; ?>
<?php if ($loadFreebieJs): ?>
<script src="assets/js/freebie-modal.js"></script>
<?php endif; ?>
<?php if ($loadCheckoutShippingJs): ?>
<script src="assets/js/checkout-shipping.js?v=5"></script>
<?php endif; ?>
</body>
</html>
<?php
}

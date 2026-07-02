<?php

declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', '1');
session_start();

}

require_once __DIR__ . '/paths.php';
require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/store-config.php';

$dbConfig = require ss_data_root() . '/config/database.php';

$dsn = sprintf(
    'mysql:host=%s;dbname=%s;charset=%s',
    $dbConfig['host'],
    $dbConfig['dbname'],
    $dbConfig['charset']
);

try {
    $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo '<!doctype html><html><body style="font-family:sans-serif;padding:2rem">';
    echo '<h1>Database connection failed</h1>';
    echo '<p>Import <code>sunandspace_data/sql/schema.sql</code> in phpMyAdmin and check <code>sunandspace_data/config/database.php</code>.</p>';
    echo '</body></html>';
    exit;
}

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/google-oauth.php';
require_once __DIR__ . '/cart.php';
require_once __DIR__ . '/payment.php';
require_once __DIR__ . '/uploads.php';
require_once __DIR__ . '/store-settings.php';
require_once __DIR__ . '/products.php';
product_ensure_sort_order_column();
product_ensure_weight_kg_column();
product_ensure_preorder_columns();
require_once __DIR__ . '/orders.php';
order_ensure_shipping_columns();
require_once __DIR__ . '/customers.php';
require_once __DIR__ . '/jt-shipping.php';

user_ensure_google_id_column();

$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$inAdminArea = strpos($scriptName, '/admin/') !== false;
if ($inAdminArea && customer_logged_in() && !admin_logged_in()) {
    customer_enter_admin();
} elseif (!$inAdminArea && admin_logged_in() && !customer_logged_in()) {
    admin_enter_storefront();
}

if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

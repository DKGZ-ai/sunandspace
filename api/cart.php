<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'error' => 'Method not allowed']);
    exit;
}

if (!csrf_verify()) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'error' => 'Invalid request']);
    exit;
}

$action = $_POST['action'] ?? 'add';
$productId = (int) ($_POST['product_id'] ?? 0);
$qty = (int) ($_POST['qty'] ?? 1);

$redirect = null;

switch ($action) {
    case 'add':
        if (cart_contains_product($productId)) {
            echo json_encode([
                'ok' => true,
                'alreadyInCart' => true,
                'cartCount' => cart_count(),
                'totalCents' => cart_total_cents(),
            ]);
            exit;
        }
        $ok = cart_add($productId, max(1, $qty));
        break;
    case 'buy_now':
        $ok = cart_add($productId, max(1, $qty));
        if ($ok) {
            cart_set_checkout_selection([$productId]);
            $redirect = 'checkout.php';
        }
        break;
    case 'set':
        $ok = cart_set_qty($productId, $qty);
        break;
    case 'remove':
        cart_remove($productId);
        $ok = true;
        break;
    default:
        http_response_code(400);
        echo json_encode(['ok' => false, 'error' => 'Unknown action']);
        exit;
}

if (!$ok) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'error' => 'Product not found']);
    exit;
}

$response = [
    'ok' => true,
    'added' => true,
    'cartCount' => cart_count(),
    'totalCents' => cart_total_cents(),
];
if ($redirect !== null) {
    $response['redirect'] = $redirect;
}

echo json_encode($response);

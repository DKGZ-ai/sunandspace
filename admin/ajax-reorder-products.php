<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

header('Content-Type: application/json');

$response = ['ok' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $raw = (string) ($_POST['product_ids'] ?? '');
    $ids = json_decode($raw, true);

    if (!is_array($ids)) {
        $response = ['ok' => false, 'message' => 'Invalid product list.'];
    } else {
        $result = product_reorder($ids);
        if ($result['ok']) {
            $response = ['ok' => true, 'message' => 'Product order saved.'];
        } else {
            $response = ['ok' => false, 'message' => $result['error'] ?? 'Could not save order.'];
        }
    }
} else {
    $response = ['ok' => false, 'message' => 'Invalid request.'];
}

echo json_encode($response);

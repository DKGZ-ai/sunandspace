<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

$orderId = (int) ($_GET['id'] ?? 0);
if ($orderId < 1) {
    http_response_code(404);
    echo 'Receipt not found.';
    exit;
}

$stmt = $pdo->prepare('SELECT payment_receipt_path FROM orders WHERE id = ? LIMIT 1');
$stmt->execute([$orderId]);
$order = $stmt->fetch();
if (!$order || empty($order['payment_receipt_path'])) {
    http_response_code(404);
    echo 'Receipt not found.';
    exit;
}

ss_stream_payment_receipt((string) $order['payment_receipt_path']);

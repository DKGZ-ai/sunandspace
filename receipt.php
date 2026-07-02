<?php
require_once __DIR__ . '/includes/bootstrap.php';

require_customer();

$orderId = (int) ($_GET['id'] ?? 0);
$order = assert_order_belongs_to_customer($orderId);
if (!$order || empty($order['payment_receipt_path'])) {
    http_response_code(404);
    echo 'Receipt not found.';
    exit;
}

ss_stream_payment_receipt((string) $order['payment_receipt_path']);

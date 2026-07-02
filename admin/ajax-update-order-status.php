<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

header('Content-Type: application/json');

$response = ['ok' => false];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_verify()) {
    $orderId = (int) ($_POST['order_id'] ?? 0);
    $newStatus = trim($_POST['status'] ?? '');
    $trackingNumber = order_sanitize_tracking_number((string) ($_POST['tracking_number'] ?? ''));

    if ($orderId > 0 && order_status_is_valid($newStatus)) {
        try {
            if ($newStatus === 'in_progress') {
                $stmt = $pdo->prepare(
                    'UPDATE orders SET status = ?, tracking_number = COALESCE(NULLIF(?, \'\'), tracking_number) WHERE id = ?'
                );
                $stmt->execute([$newStatus, $trackingNumber, $orderId]);
            } else {
                $stmt = $pdo->prepare('UPDATE orders SET status = ? WHERE id = ?');
                $stmt->execute([$newStatus, $orderId]);
            }

            if ($stmt->rowCount() > 0) {
                $fetch = $pdo->prepare('SELECT status, tracking_number FROM orders WHERE id = ? LIMIT 1');
                $fetch->execute([$orderId]);
                $order = $fetch->fetch();
                $response = [
                    'ok' => true,
                    'message' => 'Order status updated.',
                    'status' => (string) ($order['status'] ?? $newStatus),
                    'tracking_number' => (string) ($order['tracking_number'] ?? ''),
                ];
            } else {
                $fetch = $pdo->prepare('SELECT status, tracking_number FROM orders WHERE id = ? LIMIT 1');
                $fetch->execute([$orderId]);
                $order = $fetch->fetch();
                if ($order && (string) $order['status'] === $newStatus) {
                    $response = [
                        'ok' => true,
                        'message' => 'Order status updated.',
                        'status' => (string) $order['status'],
                        'tracking_number' => (string) ($order['tracking_number'] ?? ''),
                    ];
                } else {
                    $response = ['ok' => false, 'message' => 'Order not found.'];
                }
            }
        } catch (PDOException $e) {
            $response = ['ok' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    } else {
        $response = ['ok' => false, 'message' => 'Invalid order or status.'];
    }
} else {
    $response = ['ok' => false, 'message' => 'Missing order ID or status.'];
}

echo json_encode($response);

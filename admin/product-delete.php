<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !csrf_verify()) {
    ss_redirect('products.php?error=' . urlencode('Invalid request.'));
}

$id = (int) ($_POST['id'] ?? 0);
$result = product_delete($id);

if ($result['ok']) {
    ss_redirect('products.php?deleted=1');
}

ss_redirect('products.php?error=' . urlencode($result['error']));

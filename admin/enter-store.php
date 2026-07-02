<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

require_admin();

$to = $_GET['to'] ?? 'index.php';
if ($to === '' || !preg_match('#^[a-z0-9_\-\.]+\.php(\?.*)?$#i', $to)) {
    $to = 'index.php';
}

if (!admin_enter_storefront()) {
    $_SESSION['flash_error'] = 'No customer account uses the same email as this admin. Run sunandspace_data/sql/migrate_admin_customer_account.sql or add a matching customer row.';
    ss_redirect('index.php');
}

ss_redirect('../' . $to);

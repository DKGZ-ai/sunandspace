<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (customer_logged_in()) {
    ss_redirect(ss_app_url(auth_redirect_after_login()));
}

if (!google_oauth_enabled()) {
    ss_redirect(ss_app_url('login.php?oauth_error=' . urlencode('Google sign-in is not configured yet.')));
}

$redirect = $_GET['redirect'] ?? '';
if ($redirect !== '' && !preg_match('#^[a-z0-9_\-\.]+\.php(\?.*)?$#i', $redirect)) {
    $redirect = '';
}

ss_redirect(google_oauth_authorize_url($redirect));

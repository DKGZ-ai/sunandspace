<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';

if (customer_logged_in()) {
    ss_redirect(ss_app_url(auth_redirect_after_login()));
}

$error = (string) ($_GET['error'] ?? '');
if ($error !== '') {
    $message = $error === 'access_denied'
        ? 'Google sign-in was cancelled.'
        : 'Google sign-in failed. Please try again.';
    ss_redirect(ss_app_url('login.php?oauth_error=' . urlencode($message)));
}

$code = (string) ($_GET['code'] ?? '');
$state = (string) ($_GET['state'] ?? '');

if ($code === '' || $state === '') {
    ss_redirect(ss_app_url('login.php?oauth_error=' . urlencode('Google sign-in was incomplete. Please try again.')));
}

$result = google_oauth_handle_callback($code, $state);
if (!$result['ok']) {
    ss_redirect(ss_app_url('login.php?oauth_error=' . urlencode($result['error'])));
}

login_customer($result['user']);
ss_redirect(ss_app_url(auth_redirect_after_login()));

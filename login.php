<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (customer_logged_in()) {
    ss_redirect(auth_redirect_after_login());
}

$error = '';
$redirect = $_GET['redirect'] ?? '';
$oauthError = trim((string) ($_GET['oauth_error'] ?? ''));
if ($oauthError !== '') {
    $error = $oauthError;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = attempt_customer_login($email, $password);
        if ($result['ok']) {
            login_customer($result['user']);
            ss_redirect(auth_redirect_after_login());
        }
        $error = $result['error'];
        $redirect = $_POST['redirect'] ?? $redirect;
    }
}

ss_page_start('Sign in — Sun and Space');
?>
<main class="ss-page-main">
  <div class="ss-auth-card">
    <h1>Sign in</h1>
    <p class="ss-auth-sub">Sign in to complete your order and track purchases.</p>
    <?php if ($error): ?>
      <div class="ss-alert ss-alert-error"><?= ss_escape($error) ?></div>
    <?php endif; ?>
    <form method="post" class="ss-form">
      <?= csrf_field() ?>
      <?php if ($redirect): ?>
        <input type="hidden" name="redirect" value="<?= ss_escape($redirect) ?>">
      <?php endif; ?>
      <label>Email
        <input type="email" name="email" required autocomplete="email" value="<?= ss_escape($_POST['email'] ?? '') ?>">
      </label>
      <label>Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button type="submit" class="ss-btn-primary ss-btn-block">Sign in</button>
    </form>
    <?php google_oauth_button($redirect); ?>
    <p class="ss-auth-footer">No account? <a href="register.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">Create one</a></p>
    <p class="ss-auth-footer"><a href="index.php">&larr; Back to store</a></p>
  </div>
</main>
<?php require __DIR__ . '/includes/layout-footer.php'; ss_page_end(); ?>

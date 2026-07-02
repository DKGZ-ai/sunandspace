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
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $phone = trim($_POST['phone'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password_confirm'] ?? '';
        if ($name === '') {
            $error = 'Please enter your name.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email.';
        } elseif ($password !== $password2) {
            $error = 'Passwords do not match.';
        } else {
            $result = register_customer($email, $password, $name, $phone);
            if ($result['ok']) {
                login_customer($result['user']);
                ss_redirect(auth_redirect_after_login());
            }
            $error = $result['error'];
        }
        $redirect = $_POST['redirect'] ?? $redirect;
    }
}

ss_page_start('Create account — Sun and Space');
?>
<main class="ss-page-main">
  <div class="ss-auth-card">
    <h1>Create account</h1>
    <p class="ss-auth-sub">Register to check out and save your details for next time.</p>
    <?php if ($error): ?>
      <div class="ss-alert ss-alert-error"><?= ss_escape($error) ?></div>
    <?php endif; ?>
    <form method="post" class="ss-form">
      <?= csrf_field() ?>
      <?php if ($redirect): ?>
        <input type="hidden" name="redirect" value="<?= ss_escape($redirect) ?>">
      <?php endif; ?>
      <label>Full name
        <input type="text" name="name" required value="<?= ss_escape($_POST['name'] ?? '') ?>">
      </label>
      <label>Email
        <input type="email" name="email" required autocomplete="email" value="<?= ss_escape($_POST['email'] ?? '') ?>">
      </label>
      <label>Phone
        <input type="tel" name="phone" value="<?= ss_escape($_POST['phone'] ?? '') ?>">
      </label>
      <label>Password <span class="ss-hint">(min. 8 characters)</span>
        <input type="password" name="password" required autocomplete="new-password" minlength="8">
      </label>
      <label>Confirm password
        <input type="password" name="password_confirm" required autocomplete="new-password" minlength="8">
      </label>
      <button type="submit" class="ss-btn-primary ss-btn-block">Create account</button>
    </form>
    <?php google_oauth_button($redirect); ?>
    <p class="ss-auth-footer">Already have an account? <a href="login.php<?= $redirect ? '?redirect=' . urlencode($redirect) : '' ?>">Sign in</a></p>
    <p class="ss-auth-footer"><a href="index.php">&larr; Back to store</a></p>
  </div>
</main>
<?php require __DIR__ . '/includes/layout-footer.php'; ss_page_end(); ?>

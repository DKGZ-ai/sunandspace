<?php
require_once dirname(__DIR__) . '/includes/bootstrap.php';
require_once dirname(__DIR__) . '/includes/admin-layout.php';

if (admin_logged_in()) {
    ss_redirect('index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify()) {
        $error = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $result = attempt_admin_login($email, $password);
        if ($result['ok']) {
            login_admin($result['user']);
            ss_redirect('index.php');
        }
        $error = $result['error'];
    }
}
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Admin sign in — <?= ss_escape(ss_brand_name()) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body class="ss-page ss-admin-page">
<header class="ss-admin-bar ss-admin-bar--simple">
  <a href="../index.php" class="ss-admin-bar__brand ss-logo" aria-label="<?= ss_escape(ss_brand_name()) ?>">
    <?php admin_logo(); ?>
    <span class="ss-logo-text"><?= ss_escape(ss_brand_name()) ?></span>
  </a>
  <a href="../index.php" class="ss-admin-bar__action">Back to store</a>
</header>
<main class="ss-page-main">
  <div class="ss-auth-card">
    <h1>Admin sign in</h1>
    <?php if ($error): ?>
      <div class="ss-alert ss-alert-error"><?= ss_escape($error) ?></div>
    <?php endif; ?>
    <form method="post" class="ss-form">
      <?= csrf_field() ?>
      <label>Email
        <input type="email" name="email" required autocomplete="username">
      </label>
      <label>Password
        <input type="password" name="password" required autocomplete="current-password">
      </label>
      <button type="submit" class="ss-btn-primary ss-btn-block">Sign in</button>
    </form>
    <p class="ss-auth-footer"><a href="../index.php">&larr; Back to store</a></p>
  </div>
</main>
</body>
</html>

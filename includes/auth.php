<?php

function customer_logged_in(): bool
{
    return !empty($_SESSION['user_id']);
}

function admin_logged_in(): bool
{
    return !empty($_SESSION['admin_id']);
}

function admin_user(): ?array
{
    global $pdo;
    if (!admin_logged_in()) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT id, email, name FROM users WHERE id = ? AND role = ?');
    $stmt->execute([(int) $_SESSION['admin_id'], 'admin']);
    $user = $stmt->fetch();
    return $user ?: null;
}

function admin_user_tag(?array $user): string
{
    if (!$user) {
        return 'admin';
    }
    $local = strstr($user['email'], '@', true);
    if ($local !== false && $local !== '') {
        return substr($local, 0, 12);
    }
    return 'admin' . (int) $user['id'];
}

function require_customer(): void
{
    if (!customer_logged_in()) {
        ss_redirect('login.php');
    }
}

function require_customer_or_checkout_redirect(): void
{
    if (customer_logged_in()) {
        return;
    }
    $_SESSION['checkout_redirect'] = 'checkout.php';
    ss_redirect('login.php?redirect=' . urlencode('checkout.php'));
}

/** @return array<string, mixed>|null */
function assert_order_belongs_to_customer(int $orderId): ?array
{
    global $pdo;
    if (!customer_logged_in() || $orderId < 1) {
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = ? AND user_id = ? LIMIT 1');
    $stmt->execute([$orderId, (int) $_SESSION['user_id']]);
    $order = $stmt->fetch();
    return $order ?: null;
}

function require_admin(): void
{
    if (!admin_logged_in()) {
        $inAdmin = strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false;
        ss_redirect($inAdmin ? 'login.php' : 'admin/login.php');
    }
}

function auth_user(): ?array
{
    if (!customer_logged_in()) {
        return null;
    }
    return customer_by_id((int) $_SESSION['user_id']);
}

function users_billing_columns_available(): bool
{
    static $available = null;
    if ($available !== null) {
        return $available;
    }
    global $pdo;
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'billing_address'");
        $available = (bool) $stmt->fetch();
    } catch (Throwable $e) {
        $available = false;
    }
    return $available;
}

function customer_by_id(int $userId): ?array
{
    global $pdo;
    $cols = 'id, email, name, phone, role, created_at';
    if (users_billing_columns_available()) {
        $cols .= ', billing_address, billing_notes';
    }
    $stmt = $pdo->prepare("SELECT {$cols} FROM users WHERE id = ? AND role = ?");
    $stmt->execute([$userId, 'customer']);
    $user = $stmt->fetch();
    return $user ?: null;
}

function customer_by_email(string $email): ?array
{
    global $pdo;
    $cols = 'id, email, name, phone, role';
    if (users_billing_columns_available()) {
        $cols .= ', billing_address, billing_notes';
    }
    $stmt = $pdo->prepare("SELECT {$cols} FROM users WHERE email = ? AND role = ? LIMIT 1");
    $stmt->execute([$email, 'customer']);
    $user = $stmt->fetch();
    return $user ?: null;
}

function customer_for_admin(?array $adminUser): ?array
{
    if (!$adminUser) {
        return null;
    }
    return customer_by_email((string) $adminUser['email']);
}

function admin_by_email(string $email): ?array
{
    global $pdo;
    $stmt = $pdo->prepare('SELECT id, email, name FROM users WHERE email = ? AND role = ? LIMIT 1');
    $stmt->execute([$email, 'admin']);
    $user = $stmt->fetch();
    return $user ?: null;
}

function admin_for_customer(?array $customerUser): ?array
{
    if (!$customerUser) {
        return null;
    }
    return admin_by_email((string) $customerUser['email']);
}

function admin_enter_storefront(): bool
{
    if (!admin_logged_in()) {
        return false;
    }
    $admin = admin_user();
    if (!$admin) {
        return false;
    }
    $customer = customer_for_admin($admin);
    if (!$customer) {
        return false;
    }
    $_SESSION['user_id'] = (int) $customer['id'];
    cart_migrate_session_to_db((int) $customer['id']);
    return true;
}

function customer_enter_admin(): bool
{
    if (!customer_logged_in()) {
        return false;
    }
    $customer = customer_by_id((int) $_SESSION['user_id']);
    if (!$customer) {
        return false;
    }
    $admin = admin_for_customer($customer);
    if (!$admin) {
        return false;
    }
    $_SESSION['admin_id'] = (int) $admin['id'];
    return true;
}

function login_customer(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['user_id'] = (int) $user['id'];
    unset($_SESSION['role']);

    if (!empty($_SESSION['admin_id'])) {
        $admin = admin_user();
        if (!$admin || strcasecmp((string) $admin['email'], (string) $user['email']) !== 0) {
            unset($_SESSION['admin_id']);
        }
    }

    customer_enter_admin();
    cart_migrate_session_to_db((int) $user['id']);
    $_SESSION['freebie_after_login'] = true;
}

function login_admin(array $user): void
{
    session_regenerate_id(true);
    $_SESSION['admin_id'] = (int) $user['id'];
    unset($_SESSION['role']);
    admin_enter_storefront();
}

function logout_customer(): void
{
    unset(
        $_SESSION['user_id'],
        $_SESSION['billing_address'],
        $_SESSION['billing_notes']
    );
}

function logout_user(): void
{
    logout_customer();
    unset($_SESSION['admin_id'], $_SESSION['role']);
}

function auth_redirect_after_login(): string
{
    if (!empty($_SESSION['checkout_redirect'])) {
        $url = $_SESSION['checkout_redirect'];
        unset($_SESSION['checkout_redirect']);
        return $url;
    }
    if (!empty($_SESSION['oauth_redirect'])) {
        return google_oauth_consume_redirect();
    }
    $redirect = $_GET['redirect'] ?? $_POST['redirect'] ?? '';
    if ($redirect !== '' && preg_match('#^[a-z0-9_\-\.]+\.php(\?.*)?$#i', $redirect)) {
        return $redirect;
    }
    return 'index.php';
}

function user_ensure_google_id_column(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    global $pdo;

    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM users LIKE 'google_id'");
        if ($stmt->fetch()) {
            return;
        }

        $pdo->exec('ALTER TABLE users ADD COLUMN google_id VARCHAR(255) NULL DEFAULT NULL AFTER password_hash');
        $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_google_role (google_id, role)');
    } catch (PDOException $e) {
        // Column may be added via migrate_google_oauth.sql.
    }
}

function customer_by_google_id(string $googleId): ?array
{
    global $pdo;
    user_ensure_google_id_column();
    $cols = 'id, email, name, phone, role, google_id';
    $stmt = $pdo->prepare("SELECT {$cols} FROM users WHERE google_id = ? AND role = ? LIMIT 1");
    $stmt->execute([$googleId, 'customer']);
    $user = $stmt->fetch();

    return $user ?: null;
}

function link_customer_google_id(int $userId, string $googleId): void
{
    global $pdo;
    user_ensure_google_id_column();
    $stmt = $pdo->prepare('UPDATE users SET google_id = ? WHERE id = ? AND role = ?');
    $stmt->execute([$googleId, $userId, 'customer']);
}

/** @return array{ok: true, user: array<string, mixed>}|array{ok: false, error: string} */
function login_or_register_with_google(string $googleId, string $email, string $name): array
{
    user_ensure_google_id_column();

    $existing = customer_by_google_id($googleId);
    if ($existing) {
        return ['ok' => true, 'user' => $existing];
    }

    $byEmail = customer_by_email($email);
    if ($byEmail) {
        link_customer_google_id((int) $byEmail['id'], $googleId);
        $byEmail['google_id'] = $googleId;

        return ['ok' => true, 'user' => $byEmail];
    }

    return register_customer_google($googleId, $email, $name);
}

/** @return array{ok: true, user: array<string, mixed>}|array{ok: false, error: string} */
function register_customer_google(string $googleId, string $email, string $name): array
{
    global $pdo;
    user_ensure_google_id_column();

    if (customer_by_google_id($googleId)) {
        return ['ok' => false, 'error' => 'This Google account is already linked.'];
    }
    if (find_user_by_email($email, 'customer')) {
        return ['ok' => false, 'error' => 'An account with this email already exists. Sign in with email first.'];
    }

    $hash = password_hash(bin2hex(random_bytes(32)), PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, google_id, name, phone, role) VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$email, $hash, $googleId, $name, '', 'customer']);
    $id = (int) $pdo->lastInsertId();
    cart_migrate_session_to_db($id);

    return [
        'ok' => true,
        'user' => [
            'id' => $id,
            'email' => $email,
            'name' => $name,
            'phone' => '',
            'role' => 'customer',
            'google_id' => $googleId,
        ],
    ];
}

function find_user_by_email(string $email, ?string $role = null): ?array
{
    global $pdo;
    if ($role !== null) {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? AND role = ? LIMIT 1');
        $stmt->execute([$email, $role]);
    } else {
        $stmt = $pdo->prepare('SELECT * FROM users WHERE email = ? LIMIT 1');
        $stmt->execute([$email]);
    }
    $row = $stmt->fetch();
    return $row ?: null;
}

function register_customer(string $email, string $password, string $name, string $phone): array
{
    global $pdo;
    if (find_user_by_email($email, 'customer')) {
        return ['ok' => false, 'error' => 'An account with this email already exists.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare(
        'INSERT INTO users (email, password_hash, name, phone, role) VALUES (?, ?, ?, ?, ?)'
    );
    $stmt->execute([$email, $hash, $name, $phone, 'customer']);
    $id = (int) $pdo->lastInsertId();
    cart_migrate_session_to_db($id);
    return ['ok' => true, 'user' => ['id' => $id, 'email' => $email, 'name' => $name, 'phone' => $phone, 'role' => 'customer']];
}

function attempt_customer_login(string $email, string $password): array
{
    $user = find_user_by_email($email, 'customer');
    if (!$user) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid email or password.'];
    }
    return ['ok' => true, 'user' => $user];
}

function attempt_admin_login(string $email, string $password): array
{
    $user = find_user_by_email($email, 'admin');
    if (!$user) {
        return ['ok' => false, 'error' => 'Invalid admin credentials.'];
    }
    if (!password_verify($password, $user['password_hash'])) {
        return ['ok' => false, 'error' => 'Invalid admin credentials.'];
    }
    return ['ok' => true, 'user' => $user];
}

function customer_billing_session(): array
{
    return [
        'address' => (string) ($_SESSION['billing_address'] ?? ''),
        'notes' => (string) ($_SESSION['billing_notes'] ?? ''),
    ];
}

function customer_billing_from_user(?array $user): array
{
    if (!$user || !users_billing_columns_available()) {
        return ['address' => '', 'notes' => ''];
    }
    return [
        'address' => trim((string) ($user['billing_address'] ?? '')),
        'notes' => trim((string) ($user['billing_notes'] ?? '')),
    ];
}

function update_customer_billing(int $userId, string $address, string $notes): void
{
    if (!users_billing_columns_available()) {
        return;
    }
    global $pdo;
    $stmt = $pdo->prepare(
        'UPDATE users SET billing_address = ?, billing_notes = ? WHERE id = ? AND role = ?'
    );
    $stmt->execute([$address, $notes, $userId, 'customer']);
}

function customer_billing_session_save(string $address, string $notes): void
{
    $_SESSION['billing_address'] = $address;
    $_SESSION['billing_notes'] = $notes;

    if (!customer_logged_in()) {
        return;
    }
    update_customer_billing((int) $_SESSION['user_id'], $address, $notes);
}

function admin_customer_set_password(int $userId, string $password, string $passwordConfirm): array
{
    $password = trim($password);
    $passwordConfirm = trim($passwordConfirm);
    if ($password === '' && $passwordConfirm === '') {
        return ['ok' => true];
    }
    if ($password === '' || $passwordConfirm === '') {
        return ['ok' => false, 'error' => 'Enter and confirm the new password.'];
    }
    if ($password !== $passwordConfirm) {
        return ['ok' => false, 'error' => 'Passwords do not match.'];
    }
    if (strlen($password) < 8) {
        return ['ok' => false, 'error' => 'Password must be at least 8 characters.'];
    }
    if (!customer_by_id($userId)) {
        return ['ok' => false, 'error' => 'Customer not found.'];
    }
    global $pdo;
    $hash = password_hash($password, PASSWORD_DEFAULT);
    $stmt = $pdo->prepare('UPDATE users SET password_hash = ? WHERE id = ? AND role = ?');
    $stmt->execute([$hash, $userId, 'customer']);
    return ['ok' => true];
}

/** Account page defaults: name/email/phone from users; billing from DB then session only. */
function customer_account_form_data(int $userId): array
{
    $user = customer_by_id($userId);
    if (!$user) {
        return [
            'name' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
            'notes' => '',
        ];
    }

    $name = trim((string) ($user['name'] ?? ''));
    $email = trim((string) ($user['email'] ?? ''));
    $phone = trim((string) ($user['phone'] ?? ''));

    $billing = customer_billing_from_user($user);
    if ($billing['address'] === '') {
        $sessionBilling = customer_billing_session();
        $billing = $sessionBilling;
    }

    return [
        'name' => $name,
        'email' => $email,
        'phone' => $phone,
        'address' => $billing['address'],
        'notes' => $billing['notes'],
    ];
}

function update_customer_profile(int $userId, string $name, string $email, string $phone): array
{
    global $pdo;
    $name = trim($name);
    $email = trim($email);
    $phone = trim($phone);
    if ($name === '') {
        return ['ok' => false, 'error' => 'Name is required.'];
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Please enter a valid email.'];
    }
    $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ? AND role = ? AND id != ?');
    $stmt->execute([$email, 'customer', $userId]);
    if ($stmt->fetch()) {
        return ['ok' => false, 'error' => 'An account with this email already exists.'];
    }
    $stmt = $pdo->prepare('UPDATE users SET name = ?, email = ?, phone = ? WHERE id = ? AND role = ?');
    $stmt->execute([$name, $email, $phone, $userId, 'customer']);
    return ['ok' => true];
}

function save_customer_account(
    int $userId,
    string $name,
    string $email,
    string $phone,
    string $address,
    string $notes
): array {
    $result = update_customer_profile($userId, $name, $email, $phone);
    if (!$result['ok']) {
        return $result;
    }
    customer_billing_session_save($address, $notes);
    return ['ok' => true];
}

function customer_checkout_defaults(?array $user): array
{
    global $pdo;
    $billing = $user ? customer_billing_from_user($user) : ['address' => '', 'notes' => ''];
    if ($billing['address'] === '') {
        $billing = customer_billing_session();
    }
    $defaults = [
        'shipping_name' => trim((string) ($user['name'] ?? '')),
        'shipping_email' => trim((string) ($user['email'] ?? '')),
        'shipping_phone' => trim((string) ($user['phone'] ?? '')),
        'shipping_address' => $billing['address'],
        'shipping_notes' => $billing['notes'],
        'payment_method' => 'cod',
    ];
    if ($user && $defaults['shipping_address'] === '') {
        $stmt = $pdo->prepare(
            'SELECT shipping_address, shipping_notes
             FROM orders WHERE user_id = ? ORDER BY created_at DESC, id DESC LIMIT 1'
        );
        $stmt->execute([(int) $user['id']]);
        $last = $stmt->fetch();
        if ($last) {
            $defaults['shipping_address'] = $last['shipping_address'];
            $defaults['shipping_notes'] = (string) ($last['shipping_notes'] ?? '');
        }
    }
    return $defaults;
}

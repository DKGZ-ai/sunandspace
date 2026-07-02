<?php

/** @return array<string, mixed> */
function google_oauth_config(): array
{
    static $config = null;
    if ($config !== null) {
        return $config;
    }

    $path = ss_data_root() . '/config/google-oauth.php';
    if (!is_file($path)) {
        $path = ss_data_root() . '/config/google-oauth.example.php';
    }

    $loaded = is_file($path) ? require $path : [];
    $config = is_array($loaded) ? $loaded : [];

    return $config;
}

function google_oauth_enabled(): bool
{
    $config = google_oauth_config();

    return !empty($config['enabled'])
        && trim((string) ($config['client_id'] ?? '')) !== ''
        && trim((string) ($config['client_secret'] ?? '')) !== '';
}

function google_oauth_redirect_uri(): string
{
    $config = google_oauth_config();
    $configured = trim((string) ($config['redirect_uri'] ?? ''));
    if ($configured !== '') {
        return $configured;
    }

    $base = rtrim(ss_app_base_url(), '/');
    $parts = parse_url($base);
    if (is_array($parts) && !empty($parts['host'])) {
        $host = strtolower((string) $parts['host']);
        if ($host === '127.0.0.1' || $host === '[::1]') {
            $parts['host'] = 'localhost';
            $scheme = $parts['scheme'] ?? 'http';
            $port = isset($parts['port']) ? ':' . $parts['port'] : '';
            $path = $parts['path'] ?? '';
            $base = $scheme . '://' . $parts['host'] . $port . $path;
        }
    }

    return $base . '/auth/google-callback.php';
}

function google_oauth_authorize_url(string $redirectAfter = ''): string
{
    $config = google_oauth_config();
    $state = bin2hex(random_bytes(16));
    $_SESSION['oauth_state'] = $state;
    $_SESSION['oauth_redirect'] = $redirectAfter;

    $params = [
        'client_id' => (string) $config['client_id'],
        'redirect_uri' => google_oauth_redirect_uri(),
        'response_type' => 'code',
        'scope' => 'openid email profile',
        'state' => $state,
        'prompt' => 'select_account',
    ];

    return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
}

/** @return array{ok: true, user: array<string, mixed>}|array{ok: false, error: string} */
function google_oauth_handle_callback(string $code, string $state): array
{
    $expectedState = (string) ($_SESSION['oauth_state'] ?? '');
    unset($_SESSION['oauth_state']);

    if ($expectedState === '' || !hash_equals($expectedState, $state)) {
        return ['ok' => false, 'error' => 'Sign-in session expired. Please try again.'];
    }

    $config = google_oauth_config();
    $tokenResponse = google_oauth_http_post('https://oauth2.googleapis.com/token', [
        'code' => $code,
        'client_id' => (string) $config['client_id'],
        'client_secret' => (string) $config['client_secret'],
        'redirect_uri' => google_oauth_redirect_uri(),
        'grant_type' => 'authorization_code',
    ]);

    if (!$tokenResponse['ok']) {
        return ['ok' => false, 'error' => $tokenResponse['error']];
    }

    $accessToken = (string) ($tokenResponse['data']['access_token'] ?? '');
    if ($accessToken === '') {
        return ['ok' => false, 'error' => 'Google did not return an access token.'];
    }

    $profileResponse = google_oauth_http_get(
        'https://openidconnect.googleapis.com/v1/userinfo',
        ['Authorization: Bearer ' . $accessToken]
    );

    if (!$profileResponse['ok']) {
        return ['ok' => false, 'error' => $profileResponse['error']];
    }

    $profile = $profileResponse['data'];
    $googleId = trim((string) ($profile['sub'] ?? ''));
    $email = trim((string) ($profile['email'] ?? ''));
    $name = trim((string) ($profile['name'] ?? ''));
    $emailVerified = !empty($profile['email_verified']);

    if ($googleId === '') {
        return ['ok' => false, 'error' => 'Google profile is missing a user id.'];
    }
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return ['ok' => false, 'error' => 'Google did not provide a valid email address.'];
    }
    if (!$emailVerified) {
        return ['ok' => false, 'error' => 'Your Google email must be verified before signing in.'];
    }
    if ($name === '') {
        $name = strstr($email, '@', true) ?: $email;
    }

    return login_or_register_with_google($googleId, $email, $name);
}

function google_oauth_consume_redirect(): string
{
    $redirect = (string) ($_SESSION['oauth_redirect'] ?? '');
    unset($_SESSION['oauth_redirect']);

    if ($redirect !== '' && preg_match('#^[a-z0-9_\-\.]+\.php(\?.*)?$#i', $redirect)) {
        return $redirect;
    }

    return 'index.php';
}

/** @return array{ok: true, data: array<string, mixed>}|array{ok: false, error: string} */
function google_oauth_http_post(string $url, array $params): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL extension is required for Google sign-in.'];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Could not connect to Google.'];
    }

    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => $curlError !== '' ? $curlError : 'Google request failed.'];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Unexpected response from Google.'];
    }

    if ($status >= 400 || isset($data['error'])) {
        $message = (string) ($data['error_description'] ?? $data['error'] ?? 'Google sign-in failed.');
        return ['ok' => false, 'error' => $message];
    }

    return ['ok' => true, 'data' => $data];
}

/** @return array{ok: true, data: array<string, mixed>}|array{ok: false, error: string} */
function google_oauth_http_get(string $url, array $headers = []): array
{
    if (!function_exists('curl_init')) {
        return ['ok' => false, 'error' => 'PHP cURL extension is required for Google sign-in.'];
    }

    $ch = curl_init($url);
    if ($ch === false) {
        return ['ok' => false, 'error' => 'Could not connect to Google.'];
    }

    $requestHeaders = array_merge(['Accept: application/json'], $headers);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_HTTPHEADER => $requestHeaders,
    ]);

    $body = curl_exec($ch);
    $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);

    if ($body === false) {
        return ['ok' => false, 'error' => $curlError !== '' ? $curlError : 'Google request failed.'];
    }

    $data = json_decode($body, true);
    if (!is_array($data)) {
        return ['ok' => false, 'error' => 'Unexpected response from Google.'];
    }

    if ($status >= 400 || isset($data['error'])) {
        $message = (string) ($data['error_description'] ?? $data['error'] ?? 'Google sign-in failed.');
        return ['ok' => false, 'error' => $message];
    }

    return ['ok' => true, 'data' => $data];
}

function google_oauth_button(string $redirect = ''): void
{
    if (!google_oauth_enabled()) {
        return;
    }

    $url = 'auth/google.php';
    if ($redirect !== '') {
        $url .= '?redirect=' . rawurlencode($redirect);
    }
    ?>
    <div class="ss-auth-divider" aria-hidden="true"><span>or</span></div>
    <a href="<?= ss_escape($url) ?>" class="ss-btn-google ss-btn-block">
      <svg class="ss-btn-google-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 48 48" aria-hidden="true" focusable="false">
        <path fill="#EA4335" d="M24 9.5c3.54 0 6.71 1.22 9.21 3.6l6.85-6.85C35.9 2.38 30.47 0 24 0 14.62 0 6.51 5.38 2.56 13.22l7.98 6.19C12.43 13.72 17.74 9.5 24 9.5z"/>
        <path fill="#4285F4" d="M46.98 24.55c0-1.57-.15-3.09-.38-4.55H24v9.02h12.94c-.56 2.95-2.26 5.48-4.78 7.18l7.73 6c4.51-4.18 7.09-10.36 7.09-17.65z"/>
        <path fill="#FBBC05" d="M10.53 28.59c-.48-1.45-.76-2.99-.76-4.59s.27-3.14.76-4.59l-7.98-6.19C.92 16.46 0 20.12 0 24c0 3.88.92 7.54 2.56 10.78l7.97-6.19z"/>
        <path fill="#34A853" d="M24 48c6.48 0 11.93-2.13 15.89-5.81l-7.73-6c-2.15 1.45-4.92 2.3-8.16 2.3-6.26 0-11.57-4.22-13.47-9.91l-7.98 6.19C6.51 42.62 14.62 48 24 48z"/>
      </svg>
      Continue with Google
    </a>
    <?php
}

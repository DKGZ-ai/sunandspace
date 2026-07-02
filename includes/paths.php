<?php

function ss_app_root(): string
{
    return dirname(__DIR__);
}

function ss_data_root(): string
{
    static $root = null;
    if ($root !== null) {
        return $root;
    }

    $candidate = dirname(ss_app_root()) . DIRECTORY_SEPARATOR . 'sunandspace_data';
    $resolved = realpath($candidate);
    $root = $resolved !== false ? $resolved : $candidate;

    return $root;
}

function ss_normalize_media_path(string $path): string
{
    $path = trim(str_replace('\\', '/', $path));
    if ($path === '' || str_contains($path, '..')) {
        return '';
    }

    if (str_starts_with($path, 'assets/images/')) {
        return 'images/' . substr($path, strlen('assets/images/'));
    }

    return ltrim($path, '/');
}

function ss_data_path(string $logicalPath): string
{
    $normalized = ss_normalize_media_path($logicalPath);
    if ($normalized === '') {
        return '';
    }

    return ss_data_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
}

/** Bundled images shipped with the app (repo images/ folder). */
function ss_app_media_path(string $logicalPath): string
{
    $normalized = ss_normalize_media_path($logicalPath);
    if ($normalized === '' || !str_starts_with($normalized, 'images/')) {
        return '';
    }

    return ss_app_root() . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized);
}

function ss_media_url(string $logicalPath, bool $adminRelative = false): string
{
    $normalized = ss_normalize_media_path($logicalPath);
    if ($normalized === '') {
        return '';
    }

    $url = 'media.php?p=' . rawurlencode($normalized);
    if ($adminRelative) {
        return '../' . $url;
    }

    return $url;
}

/** @return array{ok: true, absolute: string, mime: string}|array{ok: false, error: string} */
function ss_media_file(string $logicalPath): array
{
    $normalized = ss_normalize_media_path($logicalPath);
    if ($normalized === '') {
        return ['ok' => false, 'error' => 'File not found.'];
    }

    $allowedPrefixes = ['uploads/products/', 'uploads/site/', 'images/'];
    $allowed = false;
    foreach ($allowedPrefixes as $prefix) {
        if (str_starts_with($normalized, $prefix)) {
            $allowed = true;
            break;
        }
    }
    if (!$allowed) {
        return ['ok' => false, 'error' => 'File not found.'];
    }

    $absolute = ss_data_path($normalized);
    if ($absolute === '' || !is_file($absolute)) {
        $absolute = ss_app_media_path($normalized);
    }
    if ($absolute === '' || !is_file($absolute)) {
        return ['ok' => false, 'error' => 'File not found.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $absolute) : 'application/octet-stream';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
    if (!in_array($mime, $allowedMimes, true)) {
        return ['ok' => false, 'error' => 'File not found.'];
    }

    return ['ok' => true, 'absolute' => $absolute, 'mime' => $mime];
}

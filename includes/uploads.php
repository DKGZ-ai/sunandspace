<?php

/** @return array{ok: true, path: string}|array{ok: false, error: string} */
function ss_save_payment_receipt(array $file, int $orderId): array
{
    if ($orderId < 1) {
        return ['ok' => false, 'error' => 'Invalid order.'];
    }

    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'Please upload your payment receipt.'];
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Could not upload receipt. Please try again.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Invalid receipt upload.'];
    }

    $maxBytes = 5 * 1024 * 1024;
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > $maxBytes) {
        return ['ok' => false, 'error' => 'Receipt must be 5 MB or smaller.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpPath) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Receipt must be a JPG, PNG, WebP, or PDF file.'];
    }

    $uploadDir = ss_data_path('uploads/receipts');
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return ['ok' => false, 'error' => 'Could not save receipt. Please try again.'];
    }

    $ext = $allowed[$mime];
    $filename = 'order-' . $orderId . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $absolutePath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        return ['ok' => false, 'error' => 'Could not save receipt. Please try again.'];
    }

    return ['ok' => true, 'path' => 'uploads/receipts/' . $filename];
}

/** @return array{ok: true, absolute: string, mime: string}|array{ok: false, error: string} */
function ss_payment_receipt_file(string $relativePath): array
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    if ($relativePath === '' || str_contains($relativePath, '..')) {
        return ['ok' => false, 'error' => 'Receipt not found.'];
    }

    $prefix = 'uploads/receipts/';
    if (!str_starts_with($relativePath, $prefix)) {
        return ['ok' => false, 'error' => 'Receipt not found.'];
    }

    $absolute = ss_data_path($relativePath);
    if ($absolute === '' || !is_file($absolute)) {
        return ['ok' => false, 'error' => 'Receipt not found.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $absolute) : 'application/octet-stream';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    if (!in_array($mime, $allowed, true)) {
        return ['ok' => false, 'error' => 'Receipt not found.'];
    }

    return ['ok' => true, 'absolute' => $absolute, 'mime' => $mime];
}

function ss_stream_payment_receipt(string $relativePath): void
{
    $file = ss_payment_receipt_file($relativePath);
    if (!$file['ok']) {
        http_response_code(404);
        echo 'Receipt not found.';
        exit;
    }

    header('Content-Type: ' . $file['mime']);
    header('Content-Length: ' . (string) filesize($file['absolute']));
    header('Content-Disposition: inline; filename="' . basename($file['absolute']) . '"');
    readfile($file['absolute']);
    exit;
}

function ss_is_product_upload_path(string $relativePath): bool
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    return $relativePath !== ''
        && !str_contains($relativePath, '..')
        && str_starts_with($relativePath, 'uploads/products/');
}

function ss_delete_product_image_file(string $relativePath): void
{
    if (!ss_is_product_upload_path($relativePath)) {
        return;
    }

    $absolute = ss_data_path($relativePath);
    if ($absolute !== '' && is_file($absolute)) {
        @unlink($absolute);
    }
}

/** @return array{ok: true, path: string}|array{ok: false, error: string} */
function ss_save_product_image(array $file, int $productId): array
{
    if ($productId < 1) {
        return ['ok' => false, 'error' => 'Invalid product.'];
    }

    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No image selected.'];
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Could not upload image. Please try again.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Invalid image upload.'];
    }

    $maxBytes = 5 * 1024 * 1024;
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > $maxBytes) {
        return ['ok' => false, 'error' => 'Image must be 5 MB or smaller.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpPath) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Image must be a JPG, PNG, or WebP file.'];
    }

    $uploadDir = ss_data_path('uploads/products');
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return ['ok' => false, 'error' => 'Could not save image. Please try again.'];
    }

    $ext = $allowed[$mime];
    $filename = 'product-' . $productId . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $absolutePath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        return ['ok' => false, 'error' => 'Could not save image. Please try again.'];
    }

    return ['ok' => true, 'path' => 'uploads/products/' . $filename];
}

function ss_is_site_upload_path(string $relativePath): bool
{
    $relativePath = str_replace('\\', '/', trim($relativePath));
    return $relativePath !== ''
        && !str_contains($relativePath, '..')
        && str_starts_with($relativePath, 'uploads/site/');
}

function ss_delete_site_image_file(string $relativePath): void
{
    if (!ss_is_site_upload_path($relativePath)) {
        return;
    }

    $absolute = ss_data_path($relativePath);
    if ($absolute !== '' && is_file($absolute)) {
        @unlink($absolute);
    }
}

/** @return array{ok: true, path: string}|array{ok: false, error: string} */
function ss_save_site_image(array $file, string $slug): array
{
    $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower(trim($slug))) ?? '';
    if ($slug === '') {
        return ['ok' => false, 'error' => 'Invalid image slot.'];
    }

    $errorCode = (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($errorCode === UPLOAD_ERR_NO_FILE) {
        return ['ok' => false, 'error' => 'No image selected.'];
    }
    if ($errorCode !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'Could not upload image. Please try again.'];
    }

    $tmpPath = (string) ($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Invalid image upload.'];
    }

    $maxBytes = 5 * 1024 * 1024;
    $size = (int) ($file['size'] ?? 0);
    if ($size < 1 || $size > $maxBytes) {
        return ['ok' => false, 'error' => 'Image must be 5 MB or smaller.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpPath) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!is_string($mime) || !isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Image must be a JPG, PNG, or WebP file.'];
    }

    $uploadDir = ss_data_path('uploads/site');
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        return ['ok' => false, 'error' => 'Could not save image. Please try again.'];
    }

    $ext = $allowed[$mime];
    $filename = $slug . '-' . bin2hex(random_bytes(8)) . '.' . $ext;
    $absolutePath = $uploadDir . '/' . $filename;
    if (!move_uploaded_file($tmpPath, $absolutePath)) {
        return ['ok' => false, 'error' => 'Could not save image. Please try again.'];
    }

    return ['ok' => true, 'path' => 'uploads/site/' . $filename];
}

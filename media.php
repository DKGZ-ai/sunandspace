<?php
require_once __DIR__ . '/includes/paths.php';

$path = (string) ($_GET['p'] ?? '');
$file = ss_media_file($path);
if (!$file['ok']) {
    http_response_code(404);
    echo 'Not found.';
    exit;
}

header('Content-Type: ' . $file['mime']);
header('Content-Length: ' . (string) filesize($file['absolute']));
header('Cache-Control: public, max-age=86400');
readfile($file['absolute']);
exit;

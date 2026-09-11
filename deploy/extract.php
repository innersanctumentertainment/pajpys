<?php
/**
 * One-shot release extractor for Hostinger shared hosting.
 * Uploaded to public_html/pajpys/_deploy/extract.php — extracts release.zip into public_html.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

// A full Laravel release is ~10k files; the default shared-hosting time limit
// is not enough to finish extracting and leaves a half-written tree behind.
@set_time_limit(0);
@ini_set('memory_limit', '512M');
ignore_user_abort(true);

function fail(int $code, string $error, array $extra = []): never
{
    http_response_code($code);
    echo json_encode(['error' => $error] + $extra);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    fail(405, 'POST required');
}

$secret = (string) (getenv('PAJPYS_DEPLOY_SECRET') ?: '');
if ($secret === '') {
    $secretFile = __DIR__ . '/secret.txt';
    if (is_file($secretFile)) {
        $secret = trim((string) file_get_contents($secretFile));
    }
}

$provided = (string) ($_POST['secret'] ?? $_GET['secret'] ?? '');
if ($secret === '' || ! hash_equals($secret, $provided)) {
    fail(403, 'Forbidden');
}

$publicHtml = dirname(__DIR__, 2);
$zipPath = __DIR__ . '/release.zip';

if (! is_file($zipPath)) {
    fail(404, 'release.zip not found');
}

if (! class_exists(ZipArchive::class)) {
    fail(500, 'ZipArchive unavailable', ['php' => PHP_VERSION]);
}

$zip = new ZipArchive();
$opened = $zip->open($zipPath);
if ($opened !== true) {
    fail(500, 'Cannot open release.zip', ['zip_code' => $opened]);
}

$count = $zip->numFiles;

if (! $zip->extractTo($publicHtml)) {
    $zip->close();
    fail(500, 'Extract failed', ['files' => $count, 'target' => $publicHtml]);
}

$zip->close();
@unlink($zipPath);

// Laravel writes caches, compiled views and logs at runtime.
foreach (['pajpys_app/storage', 'pajpys_app/bootstrap/cache'] as $writable) {
    $dir = $publicHtml . '/' . $writable;
    if (is_dir($dir)) {
        @chmod($dir, 0775);
    }
}

echo json_encode([
    'status' => 'ok',
    'files' => $count,
    'php' => PHP_VERSION,
    'extracted_to' => $publicHtml,
]);

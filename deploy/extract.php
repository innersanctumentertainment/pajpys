<?php
/**
 * One-shot release extractor for Hostinger shared hosting.
 * Uploaded to public_html/pajpys/_deploy/extract.php — extracts release.zip into public_html.
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'POST required']);
    exit;
}

$secret = (string) (getenv('PAJPYS_DEPLOY_SECRET') ?: '');
if ($secret === '') {
    $secretFile = __DIR__ . '/secret.txt';
    if (is_file($secretFile)) {
        $secret = trim((string) file_get_contents($secretFile));
    }
}

$provided = (string) ($_POST['secret'] ?? $_GET['secret'] ?? '');
if ($secret === '' || !hash_equals($secret, $provided)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$publicHtml = dirname(__DIR__, 2);
$zipPath = __DIR__ . '/release.zip';

if (!is_file($zipPath)) {
    http_response_code(404);
    echo json_encode(['error' => 'release.zip not found']);
    exit;
}

if (!class_exists(ZipArchive::class)) {
    http_response_code(500);
    echo json_encode(['error' => 'ZipArchive unavailable']);
    exit;
}

$zip = new ZipArchive();
if ($zip->open($zipPath) !== true) {
    http_response_code(500);
    echo json_encode(['error' => 'Cannot open release.zip']);
    exit;
}

if (!$zip->extractTo($publicHtml)) {
    $zip->close();
    http_response_code(500);
    echo json_encode(['error' => 'Extract failed']);
    exit;
}

$zip->close();
@unlink($zipPath);

echo json_encode(['status' => 'ok', 'extracted_to' => $publicHtml]);

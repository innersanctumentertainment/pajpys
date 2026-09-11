<?php
/**
 * One-shot database setup for Hostinger shared hosting.
 * Uploaded to public_html/pajpys/_deploy/setup.php — bypasses Laravel routing.
 */
declare(strict_types=1);

@set_time_limit(0);
@ini_set('memory_limit', '512M');
ignore_user_abort(true);

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
if ($secret === '' || ! hash_equals($secret, $provided)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$root = dirname(__DIR__, 2) . '/pajpys_app';

foreach ([
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
] as $dir) {
    $path = $root . '/' . $dir;
    if (! is_dir($path)) {
        mkdir($path, 0775, true);
    }
    @chmod($path, 0775);
}

try {
    require $root . '/vendor/autoload.php';
    $app = require $root . '/bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();

    // A failed earlier run can leave tables behind without recording the
    // migration. Fresh is safe here: this database is dedicated to PAJPYS.
    Illuminate\Support\Facades\Artisan::call('migrate:fresh', ['--force' => true]);
    $migrate = trim(Illuminate\Support\Facades\Artisan::output());

    Illuminate\Support\Facades\Artisan::call('config:cache');
    $configCache = trim(Illuminate\Support\Facades\Artisan::output());

    echo json_encode([
        'status' => 'ok',
        'php' => PHP_VERSION,
        'migrate' => $migrate,
        'config_cache' => $configCache,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode([
        'error' => get_class($e) . ': ' . $e->getMessage(),
        'file' => $e->getFile() . ':' . $e->getLine(),
        'trace' => array_slice(explode("\n", $e->getTraceAsString()), 0, 15),
    ]);
}
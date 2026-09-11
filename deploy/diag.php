<?php
/**
 * Secret-protected deploy diagnostics for Hostinger shared hosting.
 * Uploaded to public_html/pajpys/_deploy/diag.php.
 *
 * Production runs with APP_DEBUG=false, so a boot failure reaches the browser
 * as a bare "Server Error". This reports the underlying exception to whoever
 * holds the deploy secret, without exposing traces publicly.
 */
declare(strict_types=1);

@set_time_limit(120);

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
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$root = dirname(__DIR__, 2) . '/pajpys_app';

$report = [
    'php' => PHP_VERSION,
    'app_root' => $root,
    'env_present' => is_file($root . '/.env'),
    'vendor_present' => is_file($root . '/vendor/autoload.php'),
    'writable' => [
        'storage' => is_writable($root . '/storage'),
        'storage/framework/views' => is_writable($root . '/storage/framework/views'),
        'storage/logs' => is_writable($root . '/storage/logs'),
        'bootstrap/cache' => is_writable($root . '/bootstrap/cache'),
    ],
];

$logFile = $root . '/storage/logs/laravel.log';
if (is_file($logFile)) {
    $lines = explode("\n", (string) file_get_contents($logFile));
    $report['laravel_log_tail'] = array_slice($lines, -60);
}

try {
    require $root . '/vendor/autoload.php';
    $app = require $root . '/bootstrap/app.php';
    $app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
    $report['boot'] = 'ok';

    try {
        Illuminate\Support\Facades\DB::connection()->getPdo();
        $report['database'] = 'connected';
        $report['migrations_table'] = Illuminate\Support\Facades\Schema::hasTable('migrations');
    } catch (Throwable $e) {
        $report['database'] = get_class($e) . ': ' . $e->getMessage();
    }
} catch (Throwable $e) {
    $report['boot'] = get_class($e) . ': ' . $e->getMessage();
    $report['boot_at'] = $e->getFile() . ':' . $e->getLine();
    $report['boot_trace'] = array_slice(explode("\n", $e->getTraceAsString()), 0, 20);
}

header('Content-Type: application/json');
echo json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'CLI only';
    exit(1);
}

/**
 * Trigger production migrations via the deployed PAJPYS HTTP endpoint.
 */
function read_deploy_secret(): string
{
    $direct = getenv('DEPLOY_SECRET');
    if (is_string($direct) && $direct !== '') {
        return $direct;
    }

    $envB64 = getenv('APP_ENV_B64') ?: getenv('ENV_B64');
    if (is_string($envB64) && $envB64 !== '') {
        $env = base64_decode($envB64, true);
        if ($env !== false && preg_match('/^DEPLOY_SECRET=(.+)$/m', $env, $m)) {
            return trim($m[1], " \t\"'");
        }
    }

    fwrite(STDERR, "Missing DEPLOY_SECRET (set DEPLOY_SECRET or ENV_B64 with DEPLOY_SECRET=...)\n");
    exit(1);
}

function post_migrate(string $url, string $secret): array
{
    $jsonBody = json_encode(['secret' => $secret], JSON_THROW_ON_ERROR);
    $formBody = 'secret=' . rawurlencode($secret);
    $curlBin = PHP_OS_FAMILY === 'Windows' ? 'curl.exe' : 'curl';

    foreach ([['Content-Type: application/json', $jsonBody], ['Content-Type: application/x-www-form-urlencoded', $formBody]] as [$header, $bodyContent]) {
        $tmpBody = tempnam(sys_get_temp_dir(), 'pajpys-body-');
        if ($tmpBody === false) {
            continue;
        }
        file_put_contents($tmpBody, $bodyContent);
        $cmd = sprintf(
            '%s -s -w "HTTP_CODE:%%{http_code}" -X POST -H %s --data-binary @%s %s',
            $curlBin,
            escapeshellarg($header),
            escapeshellarg($tmpBody),
            escapeshellarg($url)
        );
        $raw = shell_exec($cmd);
        unlink($tmpBody);
        if (!is_string($raw) || $raw === '') {
            continue;
        }
        if (preg_match('/HTTP_CODE:(\d+)\s*$/', $raw, $m)) {
            $code = (int) $m[1];
            $body = trim(preg_replace('/HTTP_CODE:\d+\s*$/', '', $raw) ?? $raw);
            if ($code >= 200 && $code < 300) {
                return ['code' => $code, 'body' => $body];
            }
            if ($code !== 403 && $code !== 404) {
                return ['code' => $code, 'body' => $body];
            }
        }
    }

    fwrite(STDERR, "Migration request failed\n");
    exit(1);
}

$base = getenv('PAJPYS_DEPLOY_URL') ?: 'https://pajpys.agapetech.org';
$url = rtrim($base, '/') . '/deploy/migrate';
$secret = read_deploy_secret();
$result = post_migrate($url, $secret);

echo 'HTTP ' . $result['code'] . PHP_EOL;
echo $result['body'] . PHP_EOL;
exit($result['code'] >= 200 && $result['code'] < 300 ? 0 : 1);

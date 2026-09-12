#!/usr/bin/env node
/**
 * Deploy PAJPYS to agapetech.org/pajpys/ using Hostinger Files API.
 * Designed to run inside bemysupporter GitHub Actions (HOSTINGER_API_TOKEN available).
 */
import fs from 'fs';
import path from 'path';
import os from 'os';
import crypto from 'crypto';
import { execSync } from 'child_process';
import axios from 'axios';

const USERNAME = 'u508215107';
const DOMAIN = 'agapetech.org';
const PREFIX = 'pajpys';
const REPO = 'https://github.com/innersanctumentertainment/pajpys.git';
const BASE_URL = 'https://developers.hostinger.com/';

function hostingHeaders(token) {
  return {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };
}

async function withRetries(fn, { attempts = 3, delayMs = 4000, label = 'request' } = {}) {
  let lastErr;
  for (let i = 1; i <= attempts; i++) {
    try {
      return await fn();
    } catch (err) {
      lastErr = err;
      if (i >= attempts) throw err;
      await new Promise((r) => setTimeout(r, delayMs * i));
      console.warn(`${label} retry ${i}: ${err.message || err}`);
    }
  }
  throw lastErr;
}

async function getUploadCredentials(token) {
  const { data, status } = await axios.post(
    `${BASE_URL}api/hosting/v1/files/upload-urls`,
    { username: USERNAME, domain: DOMAIN },
    { headers: hostingHeaders(token), validateStatus: () => true, timeout: 60000 }
  );
  if (status !== 200) throw new Error(`upload-urls ${status}: ${JSON.stringify(data)}`);
  return data;
}

function uploadRemoteFile(filePath, remotePath, creds) {
  return new Promise((resolve, reject) => {
    import('tus-js-client').then(({ Upload }) => {
      const stats = fs.statSync(filePath);
      const cleanUploadUrl = creds.url.replace(/\/$/, '');
      const uploadUrlWithFile = `${cleanUploadUrl}/${remotePath.replace(/^\/+/, '')}?override=true`;
      const requestHeaders = {
        'X-Auth': creds.auth_key,
        'X-Auth-Rest': creds.rest_auth_key,
        'upload-length': String(stats.size),
        'upload-offset': '0',
      };
      axios
        .post(uploadUrlWithFile, '', {
          headers: requestHeaders,
          timeout: 120000,
          validateStatus: (s) => s === 201,
        })
        .then(() => {
          const upload = new Upload(fs.createReadStream(filePath), {
            uploadUrl: uploadUrlWithFile,
            retryDelays: [1000, 2000, 4000, 8000],
            uploadDataDuringCreation: false,
            parallelUploads: 1,
            chunkSize: 10485760,
            headers: requestHeaders,
            removeFingerprintOnSuccess: true,
            uploadSize: stats.size,
            metadata: { filename: path.basename(remotePath) },
            onError: (error) => reject(new Error(`Upload failed: ${error.message}`)),
            onSuccess: () => resolve({ path: remotePath }),
          });
          upload.start();
        })
        .catch(reject);
    });
  });
}

async function ensureDatabase(token) {
  const dbPass = crypto.randomBytes(18).toString('base64url');
  const body = {
    name: 'pajpys',
    user: 'pajpys',
    password: dbPass,
    website_domain: DOMAIN,
  };
  const { status, data } = await axios.post(
    `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/databases`,
    body,
    { headers: hostingHeaders(token), validateStatus: () => true, timeout: 60000 }
  );
  if (status >= 200 && status < 300) {
    console.log('Created MySQL database for PAJPYS');
    return {
      database: `u508215107_pajpys`,
      username: `u508215107_pajpys`,
      password: dbPass,
    };
  }
  console.log(`Database create status=${status} (may already exist): ${JSON.stringify(data)}`);
  if (process.env.PAJPYS_DB_PASSWORD) {
    return {
      database: 'u508215107_pajpys',
      username: 'u508215107_pajpys',
      password: process.env.PAJPYS_DB_PASSWORD,
    };
  }
  throw new Error(
    'Could not create PAJPYS database and PAJPYS_DB_PASSWORD not set. Create DB in hPanel or set secret.'
  );
}

function buildProductionEnv({ appKey, deploySecret, db }) {
  return `APP_NAME=PAJPYS
APP_ENV=production
APP_KEY=${appKey}
APP_DEBUG=false
APP_URL=https://agapetech.org/pajpys

DEPLOY_SECRET=${deploySecret}

LOG_CHANNEL=stack
LOG_LEVEL=error

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=${db.database}
DB_USERNAME=${db.username}
DB_PASSWORD=${db.password}

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=true
SESSION_PATH=/pajpys
SESSION_DOMAIN=.agapetech.org

CACHE_STORE=database
QUEUE_CONNECTION=database

MAIL_MAILER=log
MAIL_FROM_ADDRESS=hello@agapetech.org
MAIL_FROM_NAME=PAJPYS

WIPAY_ENVIRONMENT=sandbox
WIPAY_DEFAULT_CURRENCY=TTD
WIPAY_RESPONSE_URL=https://agapetech.org/pajpys/payments/wipay/response
WIPAY_WEBHOOK_URL=https://agapetech.org/pajpys/payments/wipay/webhook

SEEDER_ADMIN_PASSWORD=change-me-after-first-login
`;
}

const SKIP_TOP = new Set([
  '.git', '.github', 'node_modules', '.cursor', 'tests', 'agent-tools', 'deploy', 'tools', 'scripts',
  '.env', '.env.example', 'phpunit.xml', 'README.md', 'AGENTS.md', 'CLAUDE.md',
]);

function copyTree(srcRoot, destRoot, rel = '') {
  const abs = rel ? path.join(srcRoot, rel) : srcRoot;
  for (const entry of fs.readdirSync(abs, { withFileTypes: true })) {
    const childRel = rel ? path.join(rel, entry.name) : entry.name;
    const top = childRel.split(/[\\/]/)[0];
    if (!rel && SKIP_TOP.has(top)) continue;
    if (childRel.replace(/\\/g, '/') === 'public') continue;
    const from = path.join(srcRoot, childRel);
    const to = path.join(destRoot, 'pajpys_app', childRel);
    if (entry.isDirectory()) {
      fs.mkdirSync(to, { recursive: true });
      copyTree(srcRoot, destRoot, childRel);
    } else {
      fs.mkdirSync(path.dirname(to), { recursive: true });
      fs.copyFileSync(from, to);
    }
  }
}

function writePublicFiles(stage) {
  const index = `<?php

use Illuminate\\Foundation\\Application;
use Illuminate\\Http\\Request;

define('LARAVEL_START', microtime(true));

$appRoot = dirname(__DIR__) . '/pajpys_app';

if (file_exists($maintenance = $appRoot.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

require $appRoot.'/vendor/autoload.php';

/** @var Application $app */
$app = require_once $appRoot.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
`;
  fs.writeFileSync(path.join(stage, PREFIX, 'index.php'), index);
  fs.writeFileSync(
    path.join(stage, PREFIX, '.htaccess'),
    `<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteBase /${PREFIX}/
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
`
  );
  fs.writeFileSync(path.join(stage, 'pajpys_app', '.htaccess'), 'Deny from all\n');
}

function buildRelease(root) {
  const id = crypto.randomBytes(8).toString('hex');
  const stage = path.join(os.tmpdir(), `pajpys-stage-${id}`);
  const zipPath = path.join(os.tmpdir(), `pajpys_release_${id}.zip`);
  fs.rmSync(stage, { recursive: true, force: true });
  fs.mkdirSync(path.join(stage, PREFIX), { recursive: true });
  fs.mkdirSync(path.join(stage, 'pajpys_app'), { recursive: true });

  try {
    execSync('composer install --no-dev --optimize-autoloader --no-interaction', {
      cwd: root,
      stdio: 'inherit',
    });
  } catch {
    console.log('Composer not in PATH — using Docker composer image...');
    execSync(
      `docker run --rm -v "${root}:/app" -w /app composer:2 composer install --no-dev --optimize-autoloader --no-interaction`,
      { stdio: 'inherit' }
    );
  }
  execSync('npm ci --ignore-scripts && npm run build', { cwd: root, stdio: 'inherit' });

  copyTree(root, stage);
  writePublicFiles(stage);

  const publicSrc = path.join(root, 'public');
  for (const entry of fs.readdirSync(publicSrc, { withFileTypes: true })) {
    if (entry.name === 'index.php' || entry.name === '.htaccess') continue;
    const from = path.join(publicSrc, entry.name);
    const to = path.join(stage, PREFIX, entry.name);
    if (entry.isDirectory()) fs.cpSync(from, to, { recursive: true });
    else fs.copyFileSync(from, to);
  }

  execSync(`cd "${stage}" && zip -rq "${zipPath}" .`, { stdio: 'inherit' });
  return { zipPath, stage };
}

async function triggerExtract(deploySecret) {
  const url = `https://${DOMAIN}/${PREFIX}/_deploy/extract.php`;
  const { data, status } = await axios.post(
    url,
    new URLSearchParams({ secret: deploySecret }),
    { headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, validateStatus: () => true, timeout: 300000 }
  );
  if (status < 200 || status >= 300) throw new Error(`Extract failed ${status}: ${JSON.stringify(data)}`);
  return data;
}

async function runMigrate(deploySecret) {
  const url = `https://${DOMAIN}/${PREFIX}/deploy/migrate`;
  const attempts = [
    { headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ secret: deploySecret }) },
    { headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: new URLSearchParams({ secret: deploySecret }).toString() },
  ];
  for (const { headers, body } of attempts) {
    const { data, status } = await axios.post(url, body, {
      headers,
      validateStatus: () => true,
      timeout: 120000,
    });
    if (status >= 200 && status < 300) {
      console.log('Migrations OK', data);
      return;
    }
  }
  throw new Error('Migration request failed');
}

export async function deployPajpysToAgapetech(token) {
  if (!token) throw new Error('HOSTINGER_API_TOKEN required');

  const cloneDir = path.join(os.tmpdir(), `pajpys-src-${Date.now()}`);
  console.log('Cloning PAJPYS from GitHub...');
  execSync(`git clone --depth 1 ${REPO} ${cloneDir}`, { stdio: 'inherit' });

  const deploySecret = process.env.DEPLOY_SECRET || crypto.randomBytes(24).toString('hex');
  const appKey = `base64:${crypto.randomBytes(32).toString('base64')}`;
  const db = await ensureDatabase(token);
  const envContent = buildProductionEnv({ appKey, deploySecret, db });

  const { zipPath, stage } = buildRelease(cloneDir);
  const creds = await getUploadCredentials(token);

  const extractPhp = path.join(cloneDir, 'deploy', 'extract.php');
  const secretTmp = path.join(os.tmpdir(), `pajpys-secret-${Date.now()}.txt`);
  fs.writeFileSync(secretTmp, deploySecret);

  console.log('Uploading PAJPYS deploy bootstrap...');
  await withRetries(() => uploadRemoteFile(extractPhp, `${PREFIX}/_deploy/extract.php`, creds));
  await withRetries(() => uploadRemoteFile(secretTmp, `${PREFIX}/_deploy/secret.txt`, creds));

  console.log('Uploading PAJPYS release zip...');
  await withRetries(
    () => uploadRemoteFile(zipPath, `${PREFIX}/_deploy/release.zip`, creds),
    { attempts: 3, delayMs: 8000 }
  );

  console.log('Extracting PAJPYS on server...');
  await withRetries(() => triggerExtract(deploySecret), { attempts: 2, delayMs: 10000 });

  const envTmp = path.join(os.tmpdir(), `pajpys-env-${Date.now()}`);
  fs.writeFileSync(envTmp, envContent);
  await withRetries(() => uploadRemoteFile(envTmp, 'pajpys_app/.env', creds));

  await axios.delete(
    `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/cache/clear`,
    { headers: hostingHeaders(token), validateStatus: () => true }
  );

  fs.rmSync(cloneDir, { recursive: true, force: true });
  fs.rmSync(stage, { recursive: true, force: true });
  fs.unlinkSync(zipPath);
  fs.unlinkSync(secretTmp);
  fs.unlinkSync(envTmp);

  await new Promise((r) => setTimeout(r, 8000));
  await runMigrate(deploySecret);

  const { status, data } = await axios.get(`https://${DOMAIN}/${PREFIX}/`, { validateStatus: () => true });
  if (status !== 200 || !String(data).includes('PAJPYS')) {
    throw new Error(`PAJPYS smoke test failed status=${status}`);
  }
  console.log('PAJPYS_DEPLOY_OK https://agapetech.org/pajpys/');
}

if (process.argv[1]?.includes('hostinger-deploy-pajpys')) {
  deployPajpysToAgapetech(process.env.HOSTINGER_API_TOKEN).catch((e) => {
    console.error('PAJPYS_DEPLOY_FAIL', e.message || e);
    process.exit(1);
  });
}

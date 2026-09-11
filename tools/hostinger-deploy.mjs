#!/usr/bin/env node
/**
 * Deploy PAJPYS to agapetech.org/pajpys/ without replacing the marketing site.
 *
 *   HOSTINGER_API_TOKEN=... ENV_B64=... DEPLOY_SECRET=... node tools/hostinger-deploy.mjs
 *
 * Builds a release zip (pajpys/ web root + pajpys_app/ Laravel core), uploads via
 * Hostinger Files API, extracts on-server, then uploads production .env separately.
 */
import fs from 'fs';
import path from 'path';
import os from 'os';
import crypto from 'crypto';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import axios from 'axios';
import {
  DEFAULT_DOMAIN,
  DEFAULT_USERNAME,
  DEPLOY_PREFIX,
  clearWebsiteCache,
  getUploadCredentials,
  uploadRemoteFile,
  verifySiteLive,
  withRetries,
} from './hostinger-lib.mjs';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const root = path.resolve(__dirname, '..');
const token = process.env.HOSTINGER_API_TOKEN;
const username = DEFAULT_USERNAME;
const domain = DEFAULT_DOMAIN;
const prefix = DEPLOY_PREFIX.replace(/^\/+|\/+$/g, '');

if (!token) {
  console.error('HOSTINGER_API_TOKEN is required');
  process.exit(1);
}

const SKIP_TOP = new Set([
  '.git',
  '.github',
  'node_modules',
  '.cursor',
  'tests',
  'agent-tools',
  'deploy',
  'tools',
  'scripts',
  '.env',
  '.env.example',
  'phpunit.xml',
  'README.md',
  'AGENTS.md',
  'CLAUDE.md',
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

function writePublicIndex(stage) {
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
  fs.writeFileSync(path.join(stage, prefix, 'index.php'), index);
}

function writePublicHtaccess(stage) {
  const htaccess = `<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On
    RewriteBase /${prefix}/

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
`;
  fs.writeFileSync(path.join(stage, prefix, '.htaccess'), htaccess);
}

function writeAppHtaccess(stage) {
  fs.writeFileSync(path.join(stage, 'pajpys_app', '.htaccess'), 'Deny from all\n');
}

function buildRelease() {
  const id = crypto.randomBytes(8).toString('hex');
  const stage = path.join(os.tmpdir(), `pajpys-stage-${id}`);
  const zipPath = path.join(os.tmpdir(), `pajpys_release_${id}.zip`);

  fs.rmSync(stage, { recursive: true, force: true });
  fs.mkdirSync(path.join(stage, prefix), { recursive: true });
  fs.mkdirSync(path.join(stage, 'pajpys_app'), { recursive: true });

  console.log('Installing PHP dependencies (production)...');
  execSync('composer install --no-dev --optimize-autoloader --no-interaction', {
    cwd: root,
    stdio: 'inherit',
  });

  console.log('Building frontend assets...');
  execSync('npm ci --ignore-scripts && npm run build', { cwd: root, stdio: 'inherit' });

  console.log('Staging application files...');
  copyTree(root, stage);
  writePublicIndex(stage);
  writePublicHtaccess(stage);
  writeAppHtaccess(stage);

  const publicSrc = path.join(root, 'public');
  for (const entry of fs.readdirSync(publicSrc, { withFileTypes: true })) {
    if (entry.name === 'index.php' || entry.name === '.htaccess') continue;
    const from = path.join(publicSrc, entry.name);
    const to = path.join(stage, prefix, entry.name);
    if (entry.isDirectory()) {
      fs.cpSync(from, to, { recursive: true });
    } else {
      fs.copyFileSync(from, to);
    }
  }

  execSync(`cd "${stage}" && zip -rq "${zipPath}" .`, { stdio: 'inherit' });
  const size = fs.statSync(zipPath).size;
  console.log(`Release zip: ${Math.round(size / 1024)} KB`);
  return { zipPath, stage };
}

async function uploadExtractBootstrap(creds, deploySecret) {
  const remoteDir = `${prefix}/_deploy`;
  const extractLocal = path.join(root, 'deploy', 'extract.php');
  const secretTmp = path.join(os.tmpdir(), `pajpys-secret-${Date.now()}.txt`);
  fs.writeFileSync(secretTmp, deploySecret);

  await withRetries(() => uploadRemoteFile(extractLocal, `${remoteDir}/extract.php`, creds), {
    label: 'extract.php upload',
  });
  await withRetries(() => uploadRemoteFile(secretTmp, `${remoteDir}/secret.txt`, creds), {
    label: 'deploy secret upload',
  });
  fs.unlinkSync(secretTmp);
}

async function triggerExtract(deploySecret) {
  const url = `https://${domain}/${prefix}/_deploy/extract.php`;
  const { data, status } = await axios.post(
    url,
    new URLSearchParams({ secret: deploySecret }),
    {
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      validateStatus: () => true,
      timeout: 300000,
    }
  );
  console.log(`extract status=${status}`, data);
  if (status < 200 || status >= 300) {
    throw new Error(`Extract failed: ${JSON.stringify(data)}`);
  }
}

async function uploadEnv(creds, envB64) {
  const envTmp = path.join(os.tmpdir(), `pajpys-env-${Date.now()}`);
  fs.writeFileSync(envTmp, Buffer.from(envB64, 'base64'));
  await withRetries(() => uploadRemoteFile(envTmp, 'pajpys_app/.env', creds), {
    label: '.env upload',
  });
  fs.unlinkSync(envTmp);
}

async function main() {
  const deploySecret = process.env.DEPLOY_SECRET;
  const envB64 = process.env.ENV_B64;

  if (!deploySecret) {
    console.error('DEPLOY_SECRET is required');
    process.exit(1);
  }
  if (!envB64) {
    console.error('ENV_B64 is required (base64-encoded production .env)');
    process.exit(1);
  }

  const { zipPath, stage } = buildRelease();
  const creds = await getUploadCredentials(token, username, domain);

  console.log('Uploading extract bootstrap...');
  await uploadExtractBootstrap(creds, deploySecret);

  console.log('Uploading release zip...');
  await withRetries(
    () => uploadRemoteFile(zipPath, `${prefix}/_deploy/release.zip`, creds),
    { label: 'release zip upload', attempts: 3, delayMs: 8000 }
  );

  console.log('Extracting on server...');
  await withRetries(() => triggerExtract(deploySecret), {
    label: 'remote extract',
    attempts: 2,
    delayMs: 10000,
  });

  console.log('Uploading production .env...');
  await uploadEnv(creds, envB64);

  await clearWebsiteCache(token, username, domain);

  fs.rmSync(stage, { recursive: true, force: true });
  fs.unlinkSync(zipPath);

  const live = await verifySiteLive();
  if (!live) {
    console.error('DEPLOY_VERIFY_FAIL: site not live after deploy');
    process.exit(1);
  }

  console.log('DEPLOY_OK');
}

main().catch((e) => {
  console.error('DEPLOY_FAIL', e.message || e);
  process.exit(1);
});

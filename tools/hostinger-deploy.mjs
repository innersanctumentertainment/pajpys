#!/usr/bin/env node
/**
 * Deploy PAJPYS to Hostinger under public_html/{DEPLOY_PREFIX}/ via TUS uploads,
 * same pattern as tactile, compo-compre, and quickinvoice.
 *
 * Subdomain pajpys.agapetech.org uses the public/ folder as document root.
 *
 *   DEPLOY_PREFIX=pajpys HOSTINGER_API_TOKEN=... APP_ENV_B64=... node tools/hostinger-deploy.mjs
 *
 * APP_ENV_B64 (or legacy ENV_B64) uploads production .env. Server-side .env is never
 * deleted when omitted — only overwritten when the secret is set.
 */
import fs from 'fs';
import path from 'path';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import axios from 'axios';
import * as tus from 'tus-js-client';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const TOKEN = process.env.HOSTINGER_API_TOKEN;
const USERNAME = process.env.HOSTINGER_USERNAME || 'u508215107';
const DOMAIN = process.env.HOSTINGER_DOMAIN || 'agapetech.org';
const PREFIX = (process.env.DEPLOY_PREFIX || 'pajpys').replace(/^\/+|\/+$/g, '');
const BASE_URL = 'https://developers.hostinger.com/';
const LIVE_URL = process.env.PAJPYS_LIVE_URL || `https://${PREFIX}.${DOMAIN}/`;

if (!TOKEN) {
  console.error('HOSTINGER_API_TOKEN is required');
  process.exit(1);
}
if (!PREFIX) {
  console.error('DEPLOY_PREFIX is required');
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
  'docs',
]);
const SKIP_NAMES = new Set([
  '.env',
  '.env.local',
  '.env.production',
  '.env.example',
  'package.json',
  'package-lock.json',
  'phpunit.xml',
  'README.md',
  'AGENTS.md',
  'CLAUDE.md',
]);
const SKIP_PREFIXES = [
  'database/database.sqlite',
  'storage/logs/',
  'storage/framework/sessions/',
  'storage/framework/cache/data/',
  'storage/framework/views/',
  'bootstrap/cache/',
];

function shouldSkip(rel) {
  const norm = rel.replace(/\\/g, '/');
  const top = norm.split('/')[0];
  if (SKIP_TOP.has(top)) return true;
  if (SKIP_NAMES.has(path.basename(norm))) return true;
  return SKIP_PREFIXES.some((p) => norm === p || norm.startsWith(p));
}

function walkFiles(dir, base = '') {
  const out = [];
  for (const ent of fs.readdirSync(dir, { withFileTypes: true })) {
    const rel = base ? `${base}/${ent.name}` : ent.name;
    if (shouldSkip(rel)) continue;
    const abs = path.join(dir, ent.name);
    if (ent.isDirectory()) out.push(...walkFiles(abs, rel));
    else out.push(rel.replace(/\\/g, '/'));
  }
  return out;
}

const headers = {
  Authorization: `Bearer ${TOKEN}`,
  Accept: 'application/json',
  'Content-Type': 'application/json',
};

async function getCreds() {
  const { data, status } = await axios.post(
    `${BASE_URL}api/hosting/v1/files/upload-urls`,
    { username: USERNAME, domain: DOMAIN },
    { headers, validateStatus: () => true, timeout: 60000 }
  );
  if (status !== 200) throw new Error(`upload-urls ${status}: ${JSON.stringify(data)}`);
  return data;
}

function uploadFile(localPath, remotePath, creds) {
  return new Promise((resolve, reject) => {
    const stats = fs.statSync(localPath);
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
        const upload = new tus.Upload(fs.createReadStream(localPath), {
          uploadUrl: uploadUrlWithFile,
          retryDelays: [1000, 2000, 4000, 8000],
          uploadDataDuringCreation: false,
          parallelUploads: 1,
          chunkSize: 1048576,
          headers: requestHeaders,
          removeFingerprintOnSuccess: true,
          uploadSize: stats.size,
          metadata: { filename: path.basename(remotePath) },
          onError: (error) => reject(error),
          onSuccess: () => resolve(remotePath),
        });
        upload.start();
      })
      .catch(reject);
  });
}

function buildApp() {
  console.log('Installing PHP dependencies (production)...');
  execSync('composer install --no-dev --optimize-autoloader --no-interaction', {
    cwd: ROOT,
    stdio: 'inherit',
  });

  console.log('Building frontend assets...');
  execSync('npm ci --ignore-scripts && npm run build', { cwd: ROOT, stdio: 'inherit' });
}

async function ensureSubdomain() {
  const listUrl = `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/subdomains`;
  const { data: listData, status: listStatus } = await axios.get(listUrl, {
    headers,
    validateStatus: () => true,
    timeout: 60000,
  });

  const rows = Array.isArray(listData?.data) ? listData.data : Array.isArray(listData) ? listData : [];
  const exists = rows.some((row) => {
    const name = String(row?.subdomain || row?.name || row?.domain || '').toLowerCase();
    return name === PREFIX || name === `${PREFIX}.${DOMAIN}`.toLowerCase();
  });

  if (exists) {
    console.log(`subdomain ${PREFIX}.${DOMAIN} already configured`);
    return;
  }

  const { data, status } = await axios.post(
    listUrl,
    {
      subdomain: PREFIX,
      directory: PREFIX,
      is_using_public_directory: true,
    },
    { headers, validateStatus: () => true, timeout: 60000 }
  );

  if (status >= 200 && status < 300) {
    console.log(`created subdomain ${PREFIX}.${DOMAIN} → public_html/${PREFIX}/public`);
    return;
  }

  if (status === 409 || status === 422) {
    console.log(`subdomain create returned ${status}; assuming it already exists`);
    return;
  }

  throw new Error(`create subdomain ${status}: ${JSON.stringify(data)}`);
}

function readEnvB64() {
  return process.env.APP_ENV_B64 || process.env.ENV_B64 || '';
}

async function main() {
  buildApp();
  await ensureSubdomain();

  const creds = await getCreds();
  const files = walkFiles(ROOT).sort();
  console.log(`Deploying ${files.length} files to ${DOMAIN}/${PREFIX}/`);

  const envB64 = readEnvB64();
  if (envB64) {
    const envPath = path.join(ROOT, '.env.deploy');
    fs.writeFileSync(envPath, Buffer.from(envB64, 'base64'));
    const remote = `${PREFIX}/.env`;
    process.stdout.write(`upload ${remote} (from APP_ENV_B64) ... `);
    await uploadFile(envPath, remote, creds);
    fs.unlinkSync(envPath);
    console.log('ok');
  }

  let n = 0;
  for (const rel of files) {
    const local = path.join(ROOT, rel);
    const remote = `${PREFIX}/${rel}`;
    process.stdout.write(`[${++n}/${files.length}] ${remote} ... `);
    await uploadFile(local, remote, creds);
    console.log('ok');
  }

  await axios.delete(
    `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/cache/clear`,
    { headers, validateStatus: () => true }
  );
  console.log('cache cleared');
  console.log(`DEPLOY_OK ${PREFIX} ${LIVE_URL}`);
}

main().catch((e) => {
  console.error(e.message || e);
  process.exit(1);
});

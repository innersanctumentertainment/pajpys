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
import crypto from 'crypto';
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

function phpVersion() {
  try {
    return execSync('php -r "echo PHP_MAJOR_VERSION.\\".\\".PHP_MINOR_VERSION;"', { encoding: 'utf8' }).trim();
  } catch {
    return '0.0';
  }
}

function hasComposer() {
  try {
    execSync('composer --version', { stdio: 'pipe' });
    return true;
  } catch {
    return false;
  }
}

function ensurePhpComposer() {
  if (hasComposer() && parseFloat(phpVersion()) >= 8.3) return;
  console.log('Installing PHP 8.3 and Composer on the runner...');
  execSync('sudo add-apt-repository -y ppa:ondrej/php', { stdio: 'inherit' });
  execSync('sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq', { stdio: 'inherit' });
  execSync(
    'sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq php8.3-cli php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-sqlite3 php8.3-mysql unzip curl git zip software-properties-common',
    { stdio: 'inherit' }
  );
  execSync('sudo update-alternatives --set php /usr/bin/php8.3', { stdio: 'inherit' });
  if (!hasComposer()) {
    execSync('curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer', {
      stdio: 'inherit',
    });
  }
  if (parseFloat(phpVersion()) < 8.3 || !hasComposer()) {
    throw new Error(`PHP/Composer setup failed (php ${phpVersion()})`);
  }
}

function buildApp() {
  ensurePhpComposer();
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
  const existing = rows.find((row) => {
    const name = String(row?.subdomain || row?.name || row?.domain || '').toLowerCase();
    return name === PREFIX || name === `${PREFIX}.${DOMAIN}`.toLowerCase();
  });

  const expectedRoot = `/public_html/${PREFIX}/public`;
  if (existing) {
    const root = String(existing.root_directory || '');
    if (root.endsWith(expectedRoot)) {
      console.log(`subdomain ${PREFIX}.${DOMAIN} already configured`);
      return;
    }
    console.log(`subdomain ${PREFIX}.${DOMAIN} has wrong root (${root}); recreating`);
    await axios.delete(`${listUrl}/${PREFIX}`, { headers, validateStatus: () => true, timeout: 60000 });
    await new Promise((r) => setTimeout(r, 5000));
  }

  const { data, status } = await axios.post(
    listUrl,
    {
      subdomain: PREFIX,
      directory: `${PREFIX}/public`,
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

function normalizeProductionEnv(raw) {
  let env = raw.toString('utf8');
  const replacements = [
    ['APP_URL=https://agapetech.org/pajpys/', 'APP_URL=https://pajpys.agapetech.org/'],
    ['APP_URL=https://agapetech.org/pajpys', 'APP_URL=https://pajpys.agapetech.org'],
    ['SESSION_PATH=/pajpys', 'SESSION_PATH=/'],
    ['https://agapetech.org/pajpys/', 'https://pajpys.agapetech.org/'],
    ['DB_CONNECTION=mysql', 'DB_CONNECTION=sqlite'],
  ];
  for (const [from, to] of replacements) {
    env = env.split(from).join(to);
  }
  env = env.replace(/^DB_HOST=.*$/gm, '# DB_HOST=');
  env = env.replace(/^DB_PORT=.*$/gm, '# DB_PORT=');
  env = env.replace(/^DB_DATABASE=.*$/gm, '# DB_DATABASE=');
  env = env.replace(/^DB_USERNAME=.*$/gm, '# DB_USERNAME=');
  env = env.replace(/^DB_PASSWORD=.*$/gm, '# DB_PASSWORD=');
  return env;
}

function buildFallbackEnvText() {
  const deploySecret = process.env.DEPLOY_SECRET || crypto.randomBytes(24).toString('hex');
  const appKey = process.env.PAJPYS_APP_KEY || `base64:${crypto.randomBytes(32).toString('base64')}`;
  process.env.DEPLOY_SECRET = deploySecret;
  return normalizeProductionEnv(
    Buffer.from(
      [
        'APP_NAME=PAJPYS',
        'APP_ENV=production',
        `APP_KEY=${appKey}`,
        'APP_DEBUG=false',
        `APP_URL=https://${PREFIX}.${DOMAIN}`,
        `DEPLOY_SECRET=${deploySecret}`,
        'LOG_CHANNEL=stack',
        'LOG_LEVEL=error',
        'DB_CONNECTION=sqlite',
        'DB_DATABASE=database/database.sqlite',
        'SESSION_DRIVER=database',
        'SESSION_LIFETIME=120',
        'SESSION_ENCRYPT=true',
        'SESSION_PATH=/',
        `SESSION_DOMAIN=.${DOMAIN}`,
        'CACHE_STORE=database',
        'QUEUE_CONNECTION=database',
        'MAIL_MAILER=log',
        'MAIL_FROM_ADDRESS=hello@agapetech.org',
        'MAIL_FROM_NAME=PAJPYS',
        'WIPAY_ENVIRONMENT=sandbox',
        'WIPAY_DEFAULT_CURRENCY=TTD',
        'SEEDER_ADMIN_PASSWORD=change-me-after-first-login',
        '',
      ].join('\n'),
      'utf8'
    )
  );
}

function resolveProductionEnvBytes() {
  const b64 = process.env.APP_ENV_B64 || process.env.ENV_B64 || '';
  if (b64) {
    const env = normalizeProductionEnv(Buffer.from(b64, 'base64'));
    const match = env.match(/^DEPLOY_SECRET=(.+)$/m);
    if (match) process.env.DEPLOY_SECRET = match[1].trim().replace(/^["']|["']$/g, '');
    return Buffer.from(env, 'utf8');
  }
  console.log('No APP_ENV_B64/ENV_B64; using generated production .env');
  return Buffer.from(buildFallbackEnvText(), 'utf8');
}

const RUNTIME_DIR_MARKERS = [
  'bootstrap/cache/.gitkeep',
  'storage/framework/views/.gitkeep',
  'storage/framework/cache/data/.gitkeep',
  'storage/framework/sessions/.gitkeep',
  'storage/logs/.gitkeep',
];

async function ensureRuntimeDirs(creds) {
  const marker = path.join(ROOT, '.runtime-marker');
  fs.writeFileSync(marker, '');
  for (const rel of RUNTIME_DIR_MARKERS) {
    const remote = `${PREFIX}/${rel}`;
    process.stdout.write(`ensure ${remote} ... `);
    await uploadFile(marker, remote, creds);
    console.log('ok');
  }
  fs.unlinkSync(marker);

  fs.writeFileSync(marker, '');
  process.stdout.write(`ensure ${PREFIX}/database/.gitkeep ... `);
  await uploadFile(marker, `${PREFIX}/database/.gitkeep`, creds);
  console.log('ok');
  fs.unlinkSync(marker);
  // Never upload an empty database.sqlite — it would wipe production data.
  // Migrations in runProductionMigrate() create/populate the DB when needed.
}

async function runProductionMigrate() {
  const secret = process.env.DEPLOY_SECRET;
  if (!secret) {
    console.log('DEPLOY_SECRET not set; skipping migrate (run tools/run-migrate.php after deploy)');
    return;
  }
  console.log('Running production migrations ...');
  const { status, data } = await axios.post(
    `${LIVE_URL.replace(/\/$/, '')}/deploy/migrate`,
    { secret },
    { validateStatus: () => true, timeout: 300000 }
  );
  console.log(`migrate status=${status}`, typeof data === 'string' ? data : JSON.stringify(data));
  if (status < 200 || status >= 300) {
    throw new Error(`Migration failed with status ${status}`);
  }
}

async function main() {
  buildApp();
  await ensureSubdomain();

  const creds = await getCreds();
  const files = walkFiles(ROOT).sort();
  console.log(`Deploying ${files.length} files to ${DOMAIN}/${PREFIX}/`);

  const envPath = path.join(ROOT, '.env.deploy');
  fs.writeFileSync(envPath, resolveProductionEnvBytes());
  const remote = `${PREFIX}/.env`;
  process.stdout.write(`upload ${remote} ... `);
  await uploadFile(envPath, remote, creds);
  fs.unlinkSync(envPath);
  console.log('ok');

  let n = 0;
  for (const rel of files) {
    const local = path.join(ROOT, rel);
    const remote = `${PREFIX}/${rel}`;
    process.stdout.write(`[${++n}/${files.length}] ${remote} ... `);
    await uploadFile(local, remote, creds);
    console.log('ok');
  }

  await ensureRuntimeDirs(creds);
  await runProductionMigrate();

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

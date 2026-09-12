#!/usr/bin/env node
/**
 * Deploy PAJPYS to agapetech.org/pajpys/ using the Hostinger Files API.
 *
 * Layout on the server:
 *   public_html/pajpys/      document root (Laravel's public/ contents)
 *   public_html/pajpys_app/  framework code, denied to the web
 *
 * GitHub Actions logs are not readable from every context that maintains this
 * script, so every step is mirrored into public_html/pajpys/_deploy/status.txt,
 * which can be fetched over HTTP to diagnose a failed run.
 */
import fs from 'fs';
import path from 'path';
import os from 'os';
import crypto from 'crypto';
import { execSync } from 'child_process';
import { fileURLToPath } from 'url';
import axios from 'axios';

const __dirname = path.dirname(fileURLToPath(import.meta.url));

const USERNAME = 'u508215107';
const DOMAIN = 'agapetech.org';
const PREFIX = 'pajpys';
const APP_DIR = 'pajpys_app';
const REPO = 'https://github.com/innersanctumentertainment/pajpys.git';
const BASE_URL = 'https://developers.hostinger.com/';

// Composer resolves against this instead of the CI runner's PHP so the packages
// we ship can never require a newer PHP than the shared host actually runs.
const TARGET_PHP = '8.3.6';

const LOG = [];

function log(msg) {
  const line = `[${new Date().toISOString()}] ${msg}`;
  LOG.push(line);
  console.log(line);
}

function run(cmd, opts = {}) {
  log(`$ ${cmd}`);
  try {
    const out = execSync(cmd, { encoding: 'utf8', stdio: ['ignore', 'pipe', 'pipe'], ...opts });
    if (out && out.trim()) LOG.push(out.trim().split('\n').slice(-40).join('\n'));
    return out;
  } catch (err) {
    const detail = [err.stdout, err.stderr].filter(Boolean).join('\n').trim();
    LOG.push(detail.split('\n').slice(-60).join('\n'));
    throw new Error(`command failed: ${cmd}\n${detail.split('\n').slice(-20).join('\n')}`);
  }
}

function hostingHeaders(token) {
  return { Authorization: `Bearer ${token}`, Accept: 'application/json', 'Content-Type': 'application/json' };
}

async function withRetries(fn, { attempts = 3, delayMs = 4000, label = 'request' } = {}) {
  let lastErr;
  for (let i = 1; i <= attempts; i++) {
    try {
      return await fn();
    } catch (err) {
      lastErr = err;
      if (i >= attempts) throw err;
      log(`${label} attempt ${i} failed: ${err.message || err}`);
      await new Promise((r) => setTimeout(r, delayMs * i));
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
        .post(uploadUrlWithFile, '', { headers: requestHeaders, timeout: 120000, validateStatus: (s) => s === 201 })
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
            onError: (e) => reject(new Error(e.message)),
            onSuccess: () => resolve({ path: remotePath }),
          });
          upload.start();
        })
        .catch(reject);
    });
  });
}

async function uploadText(content, remotePath, creds) {
  const tmp = path.join(os.tmpdir(), `pajpys-up-${crypto.randomBytes(6).toString('hex')}`);
  fs.writeFileSync(tmp, content);
  try {
    return await uploadRemoteFile(tmp, remotePath, creds);
  } finally {
    try { fs.unlinkSync(tmp); } catch {}
  }
}

async function publishStatus(creds, outcome) {
  if (!creds) return;
  const body = `PAJPYS deploy ${outcome}\ngenerated ${new Date().toISOString()}\n\n${LOG.join('\n')}\n`;
  try {
    await uploadText(body, `${PREFIX}/_deploy/status.txt`, creds);
    console.log(`status published: https://${DOMAIN}/${PREFIX}/_deploy/status.txt`);
  } catch (err) {
    console.error(`could not publish status: ${err.message || err}`);
  }
}

/** Report the shared host's PHP build, then neutralise the probe. */
async function probeServerPhp(creds) {
  const probe = `<?php\nheader('Content-Type: application/json');\necho json_encode([\n  'php' => PHP_VERSION,\n  'max_execution_time' => ini_get('max_execution_time'),\n  'memory_limit' => ini_get('memory_limit'),\n  'zip' => class_exists('ZipArchive'),\n  'extensions' => get_loaded_extensions(),\n]);\n`;
  const remote = `${PREFIX}/_deploy/probe.php`;
  try {
    await uploadText(probe, remote, creds);
    const { status, data } = await axios.get(`https://${DOMAIN}/${remote}`, { validateStatus: () => true, timeout: 60000 });
    log(`server probe ${status}: ${typeof data === 'string' ? data.slice(0, 900) : JSON.stringify(data).slice(0, 900)}`);
    return (typeof data === 'object' && data && data.php) || '0.0';
  } catch (err) {
    log(`server probe unavailable: ${err.message || err}`);
    return '0.0';
  } finally {
    try { await uploadText('<?php http_response_code(404);\n', remote, creds); } catch {}
  }
}

async function reportPhpSettings(token) {
  for (const [label, url] of [
    ['php details', `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/php/details`],
    ['subdomains', `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/subdomains`],
  ]) {
    const { status, data } = await axios.get(url, { headers: hostingHeaders(token), validateStatus: () => true, timeout: 60000 });
    log(`${label} status=${status}: ${JSON.stringify(data).slice(0, 1200)}`);
  }
}

const PHP_HANDLERS = [
  ['FilesMatch SetHandler lsphp83', '<FilesMatch "\\.(php|phtml)$">\n  SetHandler application/x-lsphp83\n</FilesMatch>\n'],
  ['FilesMatch SetHandler lsphp84', '<FilesMatch "\\.(php|phtml)$">\n  SetHandler application/x-lsphp84\n</FilesMatch>\n'],
  ['AddHandler lsphp83', 'AddHandler application/x-lsphp83 .php .phtml\n'],
  ['AddHandler alt-php83 lsphp', '<IfModule mime_module>\n  AddHandler application/x-httpd-alt-php83___lsphp .php .phtml\n</IfModule>\n'],
  ['AddHandler httpd-php83', 'AddHandler application/x-httpd-php83 .php .phtml\n'],
];

const REQUIRED_EXTENSIONS = ['pdo_mysql', 'mbstring', 'openssl', 'tokenizer', 'xml', 'ctype', 'fileinfo', 'curl'];

async function selectPhpHandler(creds, currentVersion) {
  if (parseFloat(currentVersion) >= 8.3) {
    log(`directory already runs PHP ${currentVersion}; no handler override needed`);
    return '';
  }
  const probe = `<?php\nheader('Content-Type: application/json');\necho json_encode(['php' => PHP_VERSION, 'extensions' => get_loaded_extensions()]);\n`;
  for (const [name, snippet] of PHP_HANDLERS) {
    const dir = `${PREFIX}/_deploy/phpsel-${crypto.randomBytes(4).toString('hex')}`;
    try {
      await uploadText(snippet, `${dir}/.htaccess`, creds);
      await uploadText(probe, `${dir}/probe.php`, creds);
      const { status, data } = await axios.get(`https://${DOMAIN}/${dir}/probe.php`, { validateStatus: () => true, timeout: 60000 });
      const payload = typeof data === 'object' && data ? data : null;
      const version = payload?.php ?? '';
      log(`handler "${name}" -> status=${status} php=${version || 'n/a'}`);
      if (status === 200 && parseFloat(version) >= 8.3) {
        const missing = REQUIRED_EXTENSIONS.filter((e) => !(payload.extensions || []).map((x) => x.toLowerCase()).includes(e));
        if (missing.length) {
          log(`handler "${name}" reached PHP ${version} but is missing: ${missing.join(', ')}`);
          continue;
        }
        log(`selected handler "${name}" (PHP ${version})`);
        return snippet;
      }
    } catch (err) {
      log(`handler "${name}" probe error: ${err.message || err}`);
    } finally {
      try { await uploadText('<?php http_response_code(404);\n', `${dir}/probe.php`, creds); } catch {}
      try { await uploadText('', `${dir}/.htaccess`, creds); } catch {}
    }
  }
  throw new Error('no working PHP 8.3+ handler for /pajpys/.');
}

async function readStoredDbPassword(token) {
  if (process.env.PAJPYS_DB_PASSWORD) {
    log('using PAJPYS_DB_PASSWORD from environment');
    return process.env.PAJPYS_DB_PASSWORD;
  }
  try {
    const { status, data } = await axios.get(
      `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/domains/${DOMAIN}/files/content`,
      {
        headers: hostingHeaders(token),
        params: { path: `${PREFIX}/_deploy/dbpass.txt`, maxLines: 5 },
        validateStatus: () => true,
        timeout: 60000,
      }
    );
    const content = data?.content ?? data?.data ?? (typeof data === 'string' ? data : '');
    const pass = String(content).trim();
    if (status === 200 && pass) {
      log('reusing database password from _deploy/dbpass.txt');
      return pass;
    }
    log(`dbpass.txt read status=${status}`);
  } catch (err) {
    log(`dbpass.txt unavailable: ${err.message || err}`);
  }
  return null;
}

async function ensureDatabase(token) {
  const dbName = `${USERNAME}_pajpys`;
  const brief = (d) => (typeof d === 'string' ? d.slice(0, 300) : JSON.stringify(d).slice(0, 300));
  const dbPass = process.env.PAJPYS_DB_PASSWORD || crypto.randomBytes(18).toString('base64url');
  const created = await axios.post(
    `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/databases`,
    { name: 'pajpys', user: 'pajpys', password: dbPass, website_domain: DOMAIN },
    { headers: hostingHeaders(token), validateStatus: () => true, timeout: 60000 }
  );
  log(`database create status=${created.status} ${brief(created.data)}`);
  if (created.status >= 200 && created.status < 300) {
    log('created new database');
    return { database: dbName, username: dbName, password: dbPass };
  }
  let stored = await readStoredDbPassword(token);
  if (!stored) {
    log('no stored DB password; resetting via Hostinger API (one-time recovery)');
    let resetOk = false;
    for (const name of ['pajpys', dbName]) {
      const reset = await axios.patch(
        `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/databases/${name}/change-password`,
        { password: dbPass },
        { headers: hostingHeaders(token), validateStatus: () => true, timeout: 60000 }
      );
      log(`change-password (${name}) status=${reset.status} ${brief(reset.data)}`);
      if (reset.status >= 200 && reset.status < 300) resetOk = true;
    }
    if (!resetOk) throw new Error('database exists but password could not be reset or recovered');
    log('waiting 45s for database password reset to propagate');
    await new Promise((r) => setTimeout(r, 45000));
    stored = dbPass;
  } else {
    log('database already exists; reusing stored password');
  }
  return { database: dbName, username: dbName, password: stored };
}

function buildProductionEnv({ appKey, deploySecret }) {
  const base = `https://${DOMAIN}/${PREFIX}`;
  return [
    'APP_NAME=PAJPYS',
    'APP_ENV=production',
    `APP_KEY=${appKey}`,
    'APP_DEBUG=false',
    `APP_URL=${base}`,
    `ASSET_URL=${base}`,
    `DEPLOY_SECRET=${deploySecret}`,
    'LOG_CHANNEL=stack',
    'LOG_LEVEL=error',
    'DB_CONNECTION=sqlite',
    'DB_DATABASE=database/database.sqlite',
    'SESSION_DRIVER=file',
    'SESSION_LIFETIME=120',
    'SESSION_ENCRYPT=true',
    `SESSION_PATH=/${PREFIX}`,
    `SESSION_DOMAIN=.${DOMAIN}`,
    'CACHE_STORE=file',
    'QUEUE_CONNECTION=sync',
    'MAIL_MAILER=log',
    'MAIL_FROM_ADDRESS=hello@agapetech.org',
    'MAIL_FROM_NAME=PAJPYS',
    'WIPAY_ENVIRONMENT=sandbox',
    'WIPAY_DEFAULT_CURRENCY=TTD',
    'SEEDER_ADMIN_PASSWORD=change-me-after-first-login',
    '',
  ].join('\n');
}

const SKIP_TOP = new Set([
  '.git', '.github', 'node_modules', '.cursor', 'tests', 'agent-tools', 'deploy',
  'tools', 'scripts', '.env', '.env.example', 'phpunit.xml', 'README.md', 'AGENTS.md', 'CLAUDE.md',
]);

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
  log('installing PHP 8.3 and Composer on the runner');
  run('sudo add-apt-repository -y ppa:ondrej/php');
  run('sudo DEBIAN_FRONTEND=noninteractive apt-get update -qq');
  run(
    'sudo DEBIAN_FRONTEND=noninteractive apt-get install -y -qq ' +
      'php8.3-cli php8.3-xml php8.3-mbstring php8.3-curl php8.3-zip php8.3-sqlite3 php8.3-mysql ' +
      'unzip curl git zip software-properties-common'
  );
  run('sudo update-alternatives --set php /usr/bin/php8.3');
  if (!hasComposer()) {
    run('curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer');
  }
  if (parseFloat(phpVersion()) < 8.3 || !hasComposer()) throw new Error(`PHP/Composer setup failed (php ${phpVersion()})`);
  log(`runner PHP ${phpVersion()} ready`);
}

function installDependencies(root) {
  ensurePhpComposer();
  for (const name of ['composer.lock', 'package-lock.json']) {
    const p = path.join(root, name);
    if (fs.existsSync(p)) {
      fs.unlinkSync(p);
      log(`removed ${name}; resolving fresh`);
    }
  }
  run(`composer config platform.php ${TARGET_PHP}`, { cwd: root });
  run('composer update --no-dev --optimize-autoloader --no-interaction --no-progress', { cwd: root });
  run('npm install --no-audit --no-fund --ignore-scripts', { cwd: root });
  run('npm run build', { cwd: root });
  const manifest = path.join(root, 'public/build/manifest.json');
  if (!fs.existsSync(manifest)) throw new Error('vite build produced no public/build/manifest.json');
  log('dependencies installed and assets built');
}

function copyTree(srcRoot, destRoot, rel = '') {
  const abs = rel ? path.join(srcRoot, rel) : srcRoot;
  for (const entry of fs.readdirSync(abs, { withFileTypes: true })) {
    const childRel = rel ? path.join(rel, entry.name) : entry.name;
    const top = childRel.split(/[\\/]/)[0];
    if (!rel && SKIP_TOP.has(top)) continue;
    if (childRel.replace(/\\/g, '/') === 'public') continue;
    const from = path.join(srcRoot, childRel);
    const to = path.join(destRoot, APP_DIR, childRel);
    if (entry.isDirectory()) {
      fs.mkdirSync(to, { recursive: true });
      copyTree(srcRoot, destRoot, childRel);
    } else {
      if (childRel.replace(/\\/g, '/').startsWith('bootstrap/cache/') && entry.name.endsWith('.php')) continue;
      fs.mkdirSync(path.dirname(to), { recursive: true });
      fs.copyFileSync(from, to);
    }
  }
}

function writePublicFiles(stage, phpHandler) {
  const index = `<?php\n\nuse Illuminate\\Http\\Request;\n\ndefine('LARAVEL_START', microtime(true));\n\n$appRoot = dirname(__DIR__) . '/${APP_DIR}';\n\nrequire $appRoot . '/vendor/autoload.php';\n\n$app = require_once $appRoot . '/bootstrap/app.php';\n\n$app->handleRequest(Request::capture());\n`;
  fs.writeFileSync(path.join(stage, PREFIX, 'index.php'), index);
  const htaccess = `${phpHandler}${phpHandler ? '\n' : ''}DirectoryIndex index.php\n\n<IfModule mod_negotiation.c>\n  Options -MultiViews -Indexes\n</IfModule>\n\n<IfModule mod_rewrite.c>\n  RewriteEngine On\n  RewriteBase /${PREFIX}/\n\n  RewriteCond %{HTTP:Authorization} .\n  RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]\n\n  RewriteCond %{REQUEST_FILENAME} !-d\n  RewriteCond %{REQUEST_URI} (.+)/$\n  RewriteRule ^ %1 [L,R=301]\n\n  RewriteCond %{REQUEST_FILENAME} !-d\n  RewriteCond %{REQUEST_FILENAME} !-f\n  RewriteRule ^ index.php [L]\n</IfModule>\n`;
  fs.writeFileSync(path.join(stage, PREFIX, '.htaccess'), htaccess);
  fs.writeFileSync(
    path.join(stage, APP_DIR, '.htaccess'),
    `<IfModule mod_authz_core.c>\n  Require all denied\n</IfModule>\n<IfModule !mod_authz_core.c>\n  Deny from all\n</IfModule>\n`
  );
}

function buildRelease(root, phpHandler) {
  installDependencies(root);
  const id = crypto.randomBytes(8).toString('hex');
  const stage = path.join(os.tmpdir(), `pajpys-stage-${id}`);
  const zipPath = path.join(os.tmpdir(), `pajpys_release_${id}.zip`);
  fs.rmSync(stage, { recursive: true, force: true });
  fs.mkdirSync(path.join(stage, PREFIX), { recursive: true });
  fs.mkdirSync(path.join(stage, APP_DIR), { recursive: true });
  copyTree(root, stage);
  writePublicFiles(stage, phpHandler);
  for (const entry of fs.readdirSync(path.join(root, 'public'), { withFileTypes: true })) {
    if (entry.name === 'index.php' || entry.name === '.htaccess') continue;
    const from = path.join(root, 'public', entry.name);
    const to = path.join(stage, PREFIX, entry.name);
    if (entry.isDirectory()) fs.cpSync(from, to, { recursive: true });
    else fs.copyFileSync(from, to);
  }
  const buildSrc = path.join(root, 'public', 'build');
  const buildDest = path.join(stage, APP_DIR, 'public', 'build');
  fs.mkdirSync(path.dirname(buildDest), { recursive: true });
  fs.cpSync(buildSrc, buildDest, { recursive: true });
  log('copied Vite build into app public/build for Laravel manifest lookup');
  const sqlite = path.join(stage, APP_DIR, 'database', 'database.sqlite');
  fs.mkdirSync(path.dirname(sqlite), { recursive: true });
  if (!fs.existsSync(sqlite)) fs.writeFileSync(sqlite, '');
  run(`cd "${stage}" && zip -rqy "${zipPath}" .`);
  const mb = (fs.statSync(zipPath).size / 1048576).toFixed(1);
  log(`release archive built: ${mb} MB`);
  return { zipPath, stage };
}

async function triggerExtract(deploySecret) {
  const { data, status } = await axios.post(
    `https://${DOMAIN}/${PREFIX}/_deploy/extract.php`,
    new URLSearchParams({ secret: deploySecret }),
    { headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, validateStatus: () => true, timeout: 600000 }
  );
  const body = typeof data === 'string' ? data.slice(0, 600) : JSON.stringify(data).slice(0, 600);
  log(`extract status=${status} body=${body}`);
  if (status < 200 || status >= 300) throw new Error(`extract failed ${status}`);
}

async function reportAppDiagnostics(deploySecret) {
  try {
    const { status, data } = await axios.get(`https://${DOMAIN}/${PREFIX}/_deploy/diag.php`, {
      params: { secret: deploySecret },
      validateStatus: () => true,
      timeout: 120000,
    });
    const body = typeof data === 'string' ? data : JSON.stringify(data, null, 2);
    log(`diagnostics status=${status}:\n${body.slice(0, 6000)}`);
  } catch (err) {
    log(`diagnostics unavailable: ${err.message || err}`);
  }
}

async function runSetup(deploySecret) {
  const url = `https://${DOMAIN}/${PREFIX}/_deploy/setup.php`;
  const { data, status } = await axios.post(
    url,
    new URLSearchParams({ secret: deploySecret }),
    { headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, validateStatus: () => true, timeout: 600000 }
  );
  const body = typeof data === 'string' ? data.slice(0, 2000) : JSON.stringify(data).slice(0, 2000);
  log(`setup status=${status} body=${body}`);
  if (status < 200 || status >= 300) throw new Error(`setup failed: ${body}`);
}

export async function deployPajpysToAgapetech(token) {
  if (!token) throw new Error('HOSTINGER_API_TOKEN required');
  let creds = null;
  try {
    creds = await getUploadCredentials(token);
    log('obtained Hostinger upload credentials');
    await reportPhpSettings(token);
    const serverPhp = await probeServerPhp(creds);
    const phpHandler = await selectPhpHandler(creds, serverPhp);
    const cloneDir = path.join(os.tmpdir(), `pajpys-src-${Date.now()}`);
    run(`git clone --depth 1 ${REPO} ${cloneDir}`);
    log(`cloned PAJPYS at ${run('git rev-parse --short HEAD', { cwd: cloneDir }).trim()}`);
    const deploySecret = process.env.DEPLOY_SECRET || crypto.randomBytes(24).toString('hex');
    const appKey = `base64:${crypto.randomBytes(32).toString('base64')}`;
    const { zipPath, stage } = buildRelease(cloneDir, phpHandler);
    await withRetries(() => uploadRemoteFile(path.join(cloneDir, 'deploy/extract.php'), `${PREFIX}/_deploy/extract.php`, creds), { label: 'upload extract.php' });
    await withRetries(() => uploadRemoteFile(path.join(cloneDir, 'deploy/diag.php'), `${PREFIX}/_deploy/diag.php`, creds), { label: 'upload diag.php' });
    await withRetries(() => uploadRemoteFile(path.join(cloneDir, 'deploy/setup.php'), `${PREFIX}/_deploy/setup.php`, creds), { label: 'upload setup.php' });
    await withRetries(() => uploadText(deploySecret, `${PREFIX}/_deploy/secret.txt`, creds), { label: 'upload secret' });
    await withRetries(() => uploadRemoteFile(zipPath, `${PREFIX}/_deploy/release.zip`, creds), { attempts: 3, delayMs: 8000, label: 'upload release' });
    log('release uploaded');
    await withRetries(() => triggerExtract(deploySecret), { attempts: 2, delayMs: 10000, label: 'extract' });
    await withRetries(() => uploadText(buildProductionEnv({ appKey, deploySecret }), `${APP_DIR}/.env`, creds), { label: 'upload .env' });
    await axios.delete(
      `${BASE_URL}api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/cache/clear`,
      { headers: hostingHeaders(token), validateStatus: () => true }
    );
    fs.rmSync(cloneDir, { recursive: true, force: true });
    fs.rmSync(stage, { recursive: true, force: true });
    fs.unlinkSync(zipPath);
    await new Promise((r) => setTimeout(r, 8000));
    try {
      await withRetries(() => runSetup(deploySecret), { attempts: 4, delayMs: 15000, label: 'setup' });
    } catch (err) {
      await reportAppDiagnostics(deploySecret);
      throw err;
    }
    const { status, data } = await axios.get(`https://${DOMAIN}/${PREFIX}/`, { validateStatus: () => true, timeout: 60000 });
    const body = typeof data === 'string' ? data : JSON.stringify(data);
    log(`smoke test status=${status} bytes=${body.length}`);
    if (status !== 200 || !body.includes('PAJPYS')) {
      log(`smoke test body head: ${body.slice(0, 800)}`);
      await reportAppDiagnostics(deploySecret);
      throw new Error(`PAJPYS smoke test failed status=${status}`);
    }
    log(`PAJPYS_DEPLOY_OK https://${DOMAIN}/${PREFIX}/`);
    await publishStatus(creds, 'SUCCEEDED');
  } catch (err) {
    log(`DEPLOY FAILED: ${err.message || err}`);
    await publishStatus(creds, 'FAILED');
    throw err;
  }
}

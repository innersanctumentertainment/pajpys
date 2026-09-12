#!/usr/bin/env node
/**
 * Deploy agapetech.org marketing site, then PAJPYS to pajpys.agapetech.org.
 * Re-trigger: 2026-09-11T20:40Z
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import axios from 'axios';
import * as tus from 'tus-js-client';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const TOKEN = process.env.HOSTINGER_API_TOKEN;
const USERNAME = process.env.HOSTINGER_USERNAME || 'u508215107';
const DOMAIN = process.env.HOSTINGER_DOMAIN || 'agapetech.org';

const ROOT_FILES = new Set(['index.html', '404.html', 'robots.txt', 'sitemap.xml', '.htaccess', 'README.md']);
const ROOT_DIRS = new Set(['assets', 'api']);

if (!TOKEN) {
  console.error('HOSTINGER_API_TOKEN is required');
  process.exit(1);
}

function walkMarketing() {
  const out = [];
  for (const name of ROOT_FILES) {
    const abs = path.join(ROOT, name);
    if (fs.existsSync(abs) && fs.statSync(abs).isFile()) out.push(name);
  }
  for (const dir of ROOT_DIRS) {
    const base = path.join(ROOT, dir);
    if (!fs.existsSync(base)) continue;
    (function walk(d, rel) {
      for (const ent of fs.readdirSync(d, { withFileTypes: true })) {
        const r = `${rel}/${ent.name}`;
        const a = path.join(d, ent.name);
        if (ent.isDirectory()) walk(a, r);
        else if (r !== 'config/recaptcha.php') out.push(r.replace(/\\/g, '/'));
      }
    })(base, dir);
  }
  const example = 'config/recaptcha.example.php';
  if (fs.existsSync(path.join(ROOT, example))) out.push(example);
  if (process.env.RECAPTCHA_PHP_B64) out.push('config/recaptcha.php');
  return out.sort();
}

const headers = {
  Authorization: `Bearer ${TOKEN}`,
  Accept: 'application/json',
  'Content-Type': 'application/json',
};

async function getCreds() {
  const { data, status } = await axios.post(
    'https://developers.hostinger.com/api/hosting/v1/files/upload-urls',
    { username: USERNAME, domain: DOMAIN },
    { headers, validateStatus: () => true, timeout: 60000 }
  );
  if (status !== 200) throw new Error(`upload-urls ${status}: ${JSON.stringify(data)}`);
  return data;
}

function uploadBuffer(buf, remotePath, creds) {
  const tmp = path.join(ROOT, '.deploy-tmp');
  fs.writeFileSync(tmp, buf);
  return uploadFile(tmp, remotePath, creds).finally(() => {
    try { fs.unlinkSync(tmp); } catch {}
  });
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
      .post(uploadUrlWithFile, '', { headers: requestHeaders, timeout: 120000, validateStatus: (s) => s === 201 })
      .then(() => {
        const upload = new tus.Upload(fs.createReadStream(localPath), {
          uploadUrl: uploadUrlWithFile,
          retryDelays: [1000, 2000, 4000],
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

async function main() {
  const creds = await getCreds();
  const files = walkMarketing();
  console.log(`Marketing deploy: ${files.length} files to ${DOMAIN}/`);

  if (process.env.RECAPTCHA_PHP_B64) {
    const buf = Buffer.from(process.env.RECAPTCHA_PHP_B64, 'base64');
    process.stdout.write('upload config/recaptcha.php ... ');
    await uploadBuffer(buf, 'config/recaptcha.php', creds);
    console.log('ok');
  }

  for (const rel of files) {
    if (rel === 'config/recaptcha.php' && process.env.RECAPTCHA_PHP_B64) continue;
    const local = path.join(ROOT, rel);
    process.stdout.write(`upload ${rel} ... `);
    await uploadFile(local, rel, creds);
    console.log('ok');
  }

  await axios.delete(
    `https://developers.hostinger.com/api/hosting/v1/accounts/${USERNAME}/websites/${DOMAIN}/cache/clear`,
    { headers, validateStatus: () => true }
  );
  console.log('cache cleared');
  console.log('MARKETING_DEPLOY_OK');

  if (process.env.SKIP_PAJPYS_DEPLOY !== '1') {
    console.log('Deploying PAJPYS to pajpys.agapetech.org ...');
    await import('./deploy-pajpys-from-github.mjs');
  }
}

main().catch((e) => {
  console.error(e.message || e);
  process.exit(1);
});

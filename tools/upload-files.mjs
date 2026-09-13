#!/usr/bin/env node
/**
 * Upload specific files to Hostinger via TUS credentials.
 * Usage: HOSTINGER_TUS_* env vars + node tools/upload-files.mjs <localPath> <remoteRel> ...
 */
import fs from 'fs';
import path from 'path';
import axios from 'axios';
import * as tus from 'tus-js-client';
import { fileURLToPath } from 'url';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const ROOT = path.resolve(__dirname, '..');
const PREFIX = (process.env.DEPLOY_PREFIX || 'pajpys').replace(/^\/+|\/+$/g, '');

const creds = {
  url: process.env.HOSTINGER_TUS_URL,
  auth_key: process.env.HOSTINGER_TUS_AUTH_KEY,
  rest_auth_key: process.env.HOSTINGER_TUS_REST_AUTH_KEY,
};

if (!creds.url || !creds.auth_key || !creds.rest_auth_key) {
  console.error('Set HOSTINGER_TUS_URL, HOSTINGER_TUS_AUTH_KEY, HOSTINGER_TUS_REST_AUTH_KEY');
  process.exit(1);
}

const pairs = process.argv.slice(2);
if (pairs.length === 0 || pairs.length % 2 !== 0) {
  console.error('Usage: node tools/upload-files.mjs <localRel> <remoteRel> ...');
  process.exit(1);
}

function uploadFile(localPath, remoteRel) {
  return new Promise((resolve, reject) => {
    const stats = fs.statSync(localPath);
    const cleanUploadUrl = creds.url.replace(/\/$/, '');
    const remote = `${PREFIX}/${remoteRel}`.replace(/^\/+/, '');
    const uploadUrlWithFile = `${cleanUploadUrl}/${remote}?override=true`;
    const requestHeaders = {
      'X-Auth': creds.auth_key,
      'X-Auth-Rest': creds.rest_auth_key,
      'Tus-Resumable': '1.0.0',
      'upload-length': String(stats.size),
      'upload-offset': '0',
    };
    axios
      .post(uploadUrlWithFile, null, {
        headers: requestHeaders,
        timeout: 120000,
        validateStatus: (s) => s === 201,
      })
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
          metadata: { filename: path.basename(remoteRel) },
          onError: (error) => reject(error),
          onSuccess: () => resolve(remote),
        });
        upload.start();
      })
      .catch(reject);
  });
}

for (let i = 0; i < pairs.length; i += 2) {
  const localRel = pairs[i];
  const remoteRel = pairs[i + 1];
  const localPath = path.join(ROOT, localRel);
  if (!fs.existsSync(localPath)) {
    console.error(`Missing: ${localPath}`);
    process.exit(1);
  }
  process.stdout.write(`upload ${remoteRel} ... `);
  await uploadFile(localPath, remoteRel);
  console.log('ok');
}

console.log('UPLOAD_OK');

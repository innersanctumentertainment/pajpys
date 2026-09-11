/**
 * Shared Hostinger hosting helpers for PAJPYS on agapetech.org/pajpys/.
 *
 * Uses the Files API (TUS upload). Does NOT call the full-site deploy API, so the
 * agapetech.org marketing site at public_html root stays intact.
 */
import fs from 'fs';
import https from 'https';
import axios from 'axios';
import * as tus from 'tus-js-client';

export const DEFAULT_USERNAME = process.env.HOSTINGER_USERNAME || 'u508215107';
export const DEFAULT_DOMAIN = process.env.HOSTINGER_DOMAIN || 'agapetech.org';
export const DEFAULT_ORIGIN_IP = process.env.HOSTINGER_ORIGIN_IP || '195.35.39.198';
export const DEPLOY_PREFIX = process.env.HOSTINGER_DEPLOY_PREFIX || 'pajpys';
export const BASE_URL = 'https://developers.hostinger.com/';

const PARKED_MARKER = 'Parked Domain name on Hostinger';
const LIVE_MARKER = process.env.HOSTINGER_LIVE_MARKER || 'PAJPYS';

export function hostingHeaders(token) {
  return {
    Authorization: `Bearer ${token}`,
    Accept: 'application/json',
    'Content-Type': 'application/json',
  };
}

export async function getUploadCredentials(token, username, domain) {
  return withRetries(
    async () => {
      const { data, status } = await axios.post(
        `${BASE_URL}api/hosting/v1/files/upload-urls`,
        { username, domain },
        { headers: hostingHeaders(token), validateStatus: () => true, timeout: 60000 }
      );
      if (status !== 200) throw new Error(`upload-urls ${status}: ${JSON.stringify(data)}`);
      return data;
    },
    { label: 'Hostinger upload-urls' }
  );
}

export function uploadRemoteFile(filePath, remotePath, creds) {
  return new Promise((resolve, reject) => {
    try {
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
          const upload = new tus.Upload(fs.createReadStream(filePath), {
            uploadUrl: uploadUrlWithFile,
            retryDelays: [1000, 2000, 4000, 8000],
            uploadDataDuringCreation: false,
            parallelUploads: 1,
            chunkSize: 10485760,
            headers: requestHeaders,
            removeFingerprintOnSuccess: true,
            uploadSize: stats.size,
            metadata: { filename: remotePath.split('/').pop() },
            onError: (error) => reject(new Error(`Upload failed: ${error.message}`)),
            onSuccess: () => resolve({ path: remotePath }),
          });
          upload.start();
        })
        .catch(reject);
    } catch (e) {
      reject(e);
    }
  });
}

export async function clearWebsiteCache(token, username, domain) {
  const { status } = await axios.delete(
    `${BASE_URL}api/hosting/v1/accounts/${username}/websites/${domain}/cache/clear`,
    { headers: hostingHeaders(token), validateStatus: () => true }
  );
  console.log(`cache clear status=${status}`);
  return status;
}

function fetchViaOrigin(url, originIp = DEFAULT_ORIGIN_IP) {
  const { hostname, pathname, search } = new URL(url);
  return new Promise((resolve, reject) => {
    const req = https.request(
      {
        host: originIp,
        servername: hostname,
        path: `${pathname}${search}`,
        method: 'GET',
        headers: {
          Host: hostname,
          'Cache-Control': 'no-cache',
          Pragma: 'no-cache',
        },
        timeout: 25000,
      },
      (res) => {
        let data = '';
        res.on('data', (chunk) => {
          data += chunk;
        });
        res.on('end', () => {
          resolve({ status: res.statusCode || 0, data, headers: res.headers });
        });
      }
    );
    req.on('error', reject);
    req.on('timeout', () => req.destroy(new Error('origin request timeout')));
    req.end();
  });
}

export async function verifySiteLive({
  urls = [`https://${DEFAULT_DOMAIN}/${DEPLOY_PREFIX}/`],
  attempts = 12,
  delayMs = 5000,
  originIp = DEFAULT_ORIGIN_IP,
} = {}) {
  for (let i = 1; i <= attempts; i++) {
    let ok = true;
    for (const url of urls) {
      try {
        const { data, status } = await fetchViaOrigin(url, originIp);
        const html = String(data || '');
        if (status !== 200 || html.includes(PARKED_MARKER) || !html.includes(LIVE_MARKER)) {
          console.log(`verify attempt ${i}/${attempts} FAIL ${url} status=${status}`);
          ok = false;
          break;
        }
        console.log(`verify attempt ${i}/${attempts} OK ${url}`);
      } catch (e) {
        console.log(`verify attempt ${i}/${attempts} ERROR ${url}: ${e.message}`);
        ok = false;
        break;
      }
    }
    if (ok) return true;
    if (i < attempts) await sleep(delayMs);
  }
  return false;
}

function sleep(ms) {
  return new Promise((r) => setTimeout(r, ms));
}

export function isTransientNetworkError(err) {
  const code = err?.code || err?.cause?.code || '';
  const msg = String(err?.message || err || '').toLowerCase();
  const transientCodes = new Set([
    'ETIMEDOUT',
    'ECONNRESET',
    'ECONNREFUSED',
    'ENETUNREACH',
    'EAI_AGAIN',
    'ENOTFOUND',
  ]);
  if (transientCodes.has(code)) return true;
  return (
    msg.includes('etimedout') ||
    msg.includes('econnreset') ||
    msg.includes('timeout') ||
    msg.includes('network error')
  );
}

export async function withRetries(fn, { attempts = 3, delayMs = 4000, label = 'request' } = {}) {
  let lastErr;
  for (let i = 1; i <= attempts; i++) {
    try {
      return await fn();
    } catch (err) {
      lastErr = err;
      if (i >= attempts || !isTransientNetworkError(err)) throw err;
      const wait = delayMs * i;
      console.warn(`${label} failed (attempt ${i}/${attempts}): ${err.message || err}. Retrying in ${wait}ms…`);
      await sleep(wait);
    }
  }
  throw lastErr;
}

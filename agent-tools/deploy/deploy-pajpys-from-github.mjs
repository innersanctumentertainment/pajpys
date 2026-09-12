#!/usr/bin/env node
/**
 * Clone innersanctumentertainment/pajpys@main and run its Hostinger deploy script.
 * Used from agapetech-org marketing workflow until pajpys has its own workflow file.
 */
import { execSync } from 'child_process';
import fs from 'fs';
import os from 'os';
import path from 'path';
import axios from 'axios';

const REPO = 'https://github.com/innersanctumentertainment/pajpys.git';
const LIVE_BASE = process.env.PAJPYS_LIVE_URL || 'https://pajpys.agapetech.org';
const dir = path.join(os.tmpdir(), `pajpys-deploy-${Date.now()}`);

console.log(`Cloning PAJPYS into ${dir} ...`);
execSync(`git clone --depth 1 ${REPO} ${JSON.stringify(dir)}`, { stdio: 'inherit' });

const deployScript = path.join(dir, 'tools/hostinger-deploy.mjs');
if (!fs.existsSync(deployScript)) {
  console.error('Missing tools/hostinger-deploy.mjs in pajpys repo');
  process.exit(1);
}

console.log('Running PAJPYS deploy ...');
execSync(`node ${JSON.stringify(deployScript)}`, {
  cwd: dir,
  stdio: 'inherit',
  env: process.env,
});

const secret = process.env.DEPLOY_SECRET;
if (secret) {
  console.log('Running PAJPYS migrations ...');
  const { status, data } = await axios.post(
    `${LIVE_BASE.replace(/\/$/, '')}/deploy/migrate`,
    { secret },
    { validateStatus: () => true, timeout: 300000 }
  );
  console.log(`migrate status=${status}`, typeof data === 'string' ? data : JSON.stringify(data));
  if (status < 200 || status >= 300) {
    throw new Error(`Migration failed with status ${status}`);
  }
} else {
  console.warn('DEPLOY_SECRET not set after deploy; skipping migrate');
}

console.log('PAJPYS_DEPLOY_OK');

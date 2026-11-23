// Headless page check using the locally installed Brave Origin browser.
//
// Usage:
//   node scripts/browse.mjs [--out=DIR] [--admin-login] [path ...]
//
// Env:
//   BASE_URL        default http://localhost:8000
//   BRAVE_PATH      default /opt/brave.com/brave-origin/brave
//   ADMIN_EMAIL / ADMIN_PASSWORD   used with --admin-login

import { chromium } from 'playwright-core';
import { mkdirSync } from 'node:fs';
import { join } from 'node:path';

const args = process.argv.slice(2);
const flag = (name) => args.find((a) => a.startsWith(`--${name}`));
const outDir = flag('out')?.split('=')[1] ?? 'storage/app/screenshots';
const adminLogin = Boolean(flag('admin-login'));
const paths = args.filter((a) => !a.startsWith('--'));
if (paths.length === 0) paths.push('/');

const baseUrl = process.env.BASE_URL ?? 'http://localhost:8000';
const executablePath =
    process.env.BRAVE_PATH ?? '/opt/brave.com/brave-origin/brave';

mkdirSync(outDir, { recursive: true });

// launch() uses a fresh temporary profile, so the user's real Brave profile is never touched.
const browser = await chromium.launch({ executablePath, headless: true });
const context = await browser.newContext({
    viewport: { width: 1440, height: 900 },
});
const page = await context.newPage();

const problems = [];
page.on(
    'console',
    (m) => m.type() === 'error' && problems.push(`console: ${m.text()}`),
);
page.on('pageerror', (e) => problems.push(`pageerror: ${e.message}`));
page.on(
    'response',
    (r) => r.status() >= 400 && problems.push(`HTTP ${r.status()} ${r.url()}`),
);

console.log(`Browser: ${browser.version()} (${executablePath})`);

const slug = (p) =>
    p.replace(/^\/+|\/+$/g, '').replace(/[^a-z0-9]+/gi, '-') || 'home';

if (adminLogin) {
    const { ADMIN_EMAIL, ADMIN_PASSWORD } = process.env;
    if (!ADMIN_EMAIL || !ADMIN_PASSWORD)
        throw new Error('Set ADMIN_EMAIL and ADMIN_PASSWORD');
    await page.goto(`${baseUrl}/admin/login`, { waitUntil: 'networkidle' });
    await page.getByLabel('Email address').fill(ADMIN_EMAIL);
    await page
        .getByLabel('Password', { exact: false })
        .first()
        .fill(ADMIN_PASSWORD);
    await page.getByRole('button', { name: /sign in/i }).click();
    await page.waitForURL((u) => !u.pathname.endsWith('/login'), {
        timeout: 15000,
    });
    console.log(`Admin login OK -> ${page.url()}`);
}

for (const p of paths) {
    const res = await page.goto(baseUrl + p, { waitUntil: 'networkidle' });
    const file = join(outDir, `${slug(p)}.png`);
    await page.screenshot({ path: file, fullPage: true });
    console.log(`${res?.status()} ${p} -> "${await page.title()}" -> ${file}`);
}

if (problems.length) {
    console.log('\nProblems:');
    for (const x of problems) console.log(`  ${x}`);
}

await browser.close();

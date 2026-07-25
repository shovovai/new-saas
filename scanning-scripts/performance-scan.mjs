#!/usr/bin/env node
/**
 * Real browser-based performance measurement using headless Chromium
 * (via Playwright). Captures Core Web Vitals through the same
 * PerformanceObserver APIs the `web-vitals` library uses:
 *   - LCP  (Largest Contentful Paint) — real paint timing observer
 *   - CLS  (Cumulative Layout Shift)  — real layout-shift observer
 *   - TBT  (Total Blocking Time) — long-tasks observer, used as an
 *     automated-lab proxy for INP (INP itself requires a real user
 *     interaction and cannot be measured in a scripted, non-interactive
 *     scan — this is the same limitation every lab tool, including
 *     Lighthouse, has).
 *   - TTFB (Time To First Byte) — Navigation Timing API
 *
 * Usage: node performance-scan.mjs <url>
 * Prints a single JSON line to stdout: { ttfb_ms, lcp_ms, cls, tbt_ms, error }
 */

import { chromium } from 'playwright';

const url = process.argv[2];

if (!url) {
    console.error('Usage: performance-scan.mjs <url>');
    process.exit(1);
}

const executablePath = process.env.PLAYWRIGHT_CHROMIUM_PATH || undefined;

async function main() {
    const browser = await chromium.launch({
        executablePath,
        args: ['--no-sandbox', '--disable-dev-shm-usage'],
    });

    try {
        const page = await browser.newPage({ viewport: { width: 1280, height: 800 } });

        await page.addInitScript(() => {
            window.__vitals = { lcp: null, cls: 0, tbt: 0 };

            try {
                new PerformanceObserver((list) => {
                    const entries = list.getEntries();
                    const last = entries[entries.length - 1];
                    if (last) window.__vitals.lcp = last.startTime;
                }).observe({ type: 'largest-contentful-paint', buffered: true });
            } catch {}

            try {
                new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        if (!entry.hadRecentInput) window.__vitals.cls += entry.value;
                    }
                }).observe({ type: 'layout-shift', buffered: true });
            } catch {}

            try {
                new PerformanceObserver((list) => {
                    for (const entry of list.getEntries()) {
                        const blocking = entry.duration - 50;
                        if (blocking > 0) window.__vitals.tbt += blocking;
                    }
                }).observe({ type: 'longtask', buffered: true });
            } catch {}
        });

        const response = await page.goto(url, { waitUntil: 'load', timeout: 20000 });
        const status = response ? response.status() : null;

        // Let long-task/layout-shift observers pick up post-load activity.
        await page.waitForTimeout(2000);

        const ttfb = await page.evaluate(() => {
            const [nav] = performance.getEntriesByType('navigation');
            return nav ? Math.round(nav.responseStart) : null;
        });

        const vitals = await page.evaluate(() => window.__vitals);

        console.log(JSON.stringify({
            status,
            ttfb_ms: ttfb,
            lcp_ms: vitals.lcp !== null ? Math.round(vitals.lcp) : null,
            cls: Math.round((vitals.cls ?? 0) * 1000) / 1000,
            tbt_ms: Math.round(vitals.tbt ?? 0),
        }));
    } finally {
        await browser.close();
    }
}

main().catch((err) => {
    console.log(JSON.stringify({ error: err.message }));
    process.exit(0);
});

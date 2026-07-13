/**
 * Generic headless-Chrome driver for verifying keystone.guru pages in a real browser.
 * Runs INSIDE the app container (node + mounted node_modules), against http://nginx.
 *
 * Connects to the compose `chrome` service (CDP on chrome:9222, started with
 * `docker compose --profile chrome up -d chrome`); falls back to launching a local
 * chrome-headless-shell binary if the service is unreachable.
 *
 * Usage (from the worktree/checkout root on the host):
 *   docker compose exec -T app sh -c 'cd /var/www && node .chrome-tmp/browse.js <url> [options]'
 *
 * Options:
 *   --screenshot <path>   Save a full-page PNG (use a /var/www/.chrome-tmp/... path so the host can Read it)
 *   --click <selector>    Click an element after load (repeatable)
 *   --wait <ms>           Extra wait after load/click (default 1500)
 *   --eval <js>           Evaluate an expression in the page after everything; JSON result is printed
 *   --viewport <WxH>      Viewport size (default 1600x1000)
 *   --mobile              Use a mobile viewport + user agent instead
 *
 * Output: JSON on stdout: { url, status, chrome, pageErrors, consoleErrors, evalResult }
 * Exit code 1 when the HTTP status is >= 400 or a page (JS) error occurred.
 */
const puppeteer = require('puppeteer');
const http = require('http');
const dns = require('dns').promises;

function arg(name, fallback = null) {
    const i = process.argv.indexOf('--' + name);
    return i === -1 ? fallback : process.argv[i + 1];
}

function args(name) {
    const result = [];
    for (let i = 0; i < process.argv.length; i++) {
        if (process.argv[i] === '--' + name) result.push(process.argv[i + 1]);
    }
    return result;
}

/**
 * Connect to the compose chrome service. Chrome's DevTools endpoint rejects Host headers that are
 * not an IP or localhost, so resolve the service name to an IP first and connect through that.
 */
async function connectToService(host, port) {
    const {address} = await dns.lookup(host);
    const version = await new Promise((resolve, reject) => {
        const req = http.get({host: address, port, path: '/json/version', timeout: 3000}, res => {
            let body = '';
            res.on('data', chunk => body += chunk);
            res.on('end', () => resolve(JSON.parse(body)));
        });
        req.on('error', reject);
        req.on('timeout', () => req.destroy(new Error('timeout')));
    });
    const wsEndpoint = version.webSocketDebuggerUrl.replace(/ws:\/\/[^/]+/, `ws://${address}:${port}`);

    return await puppeteer.connect({browserWSEndpoint: wsEndpoint, defaultViewport: null});
}

(async () => {
    const url = process.argv[2];
    if (!url || url.startsWith('--')) {
        console.error('usage: node browse.js <url> [--screenshot p] [--click sel] [--wait ms] [--eval js] [--viewport WxH] [--mobile]');
        process.exit(2);
    }

    let browser;
    let chromeSource;
    try {
        browser = await connectToService(process.env.CHROME_HOST || 'chrome', parseInt(process.env.CHROME_PORT || '9222', 10));
        chromeSource = 'service';
    } catch (e) {
        // Fallback: local binary (needs the container apt deps - see SKILL.md)
        browser = await puppeteer.launch({
            headless: 'new',
            executablePath: process.env.CHROME_BIN || '/var/www/.chrome-tmp/chrome-headless-shell-linux64/chrome-headless-shell',
            args: ['--no-sandbox', '--disable-dev-shm-usage'],
        });
        chromeSource = 'local launch (service unreachable: ' + e.message + ')';
    }

    const page = await browser.newPage();

    if (process.argv.includes('--mobile')) {
        await page.setViewport({width: 390, height: 844, isMobile: true, hasTouch: true});
        await page.setUserAgent('Mozilla/5.0 (iPhone; CPU iPhone OS 17_0 like Mac OS X) AppleWebKit/605.1.15 (KHTML, like Gecko) Version/17.0 Mobile/15E148 Safari/604.1');
    } else {
        const [w, h] = (arg('viewport', '1600x1000')).split('x').map(Number);
        await page.setViewport({width: w, height: h});
        await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/126.0 Safari/537.36');
    }

    const pageErrors = [];
    const consoleErrors = [];
    page.on('pageerror', e => pageErrors.push(e.message));
    page.on('console', msg => {
        if (msg.type() === 'error') consoleErrors.push(msg.text().substring(0, 300));
    });

    const response = await page.goto(url, {waitUntil: 'networkidle2', timeout: 60000});
    const waitMs = parseInt(arg('wait', '1500'), 10);
    await new Promise(r => setTimeout(r, waitMs));

    for (const selector of args('click')) {
        const clicked = await page.evaluate(sel => {
            const el = document.querySelector(sel);
            if (el) el.click();
            return !!el;
        }, selector);
        if (!clicked) consoleErrors.push(`--click: no element matched ${selector}`);
        await new Promise(r => setTimeout(r, waitMs));
    }

    let evalResult;
    const expression = arg('eval');
    if (expression) {
        try {
            evalResult = await page.evaluate(expression);
        } catch (e) {
            evalResult = {evalError: e.message};
        }
    }

    const screenshot = arg('screenshot');
    if (screenshot) {
        await page.screenshot({path: screenshot, fullPage: !arg('viewport')});
    }

    const status = response ? response.status() : 0;
    console.log(JSON.stringify({url, status, chrome: chromeSource, pageErrors, consoleErrors: consoleErrors.slice(0, 10), evalResult}, null, 2));

    // Close the page but keep a shared service browser running for the next invocation
    await page.close();
    if (chromeSource === 'service') {
        browser.disconnect();
    } else {
        await browser.close();
    }
    process.exit(status >= 400 || pageErrors.length ? 1 : 0);
})();

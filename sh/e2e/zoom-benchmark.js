/**
 * Frame-timing benchmark for the zoom stall on enemy-heavy dungeon maps (issues #4409, #4412).
 * Runs INSIDE the app container (node + mounted node_modules), against http://nginx.
 *
 * Reproduces the measurement PR #4411 used, which was not kept at the time:
 *  - one wheel gesture per zoom step, never a programmatic setZoom(). `zoomSnap: 0` makes a real
 *    gesture land on fractional zoom levels, and measuring on integer levels instead reported a
 *    per-zoom-level cache as hitting when in real use it never does.
 *  - zoom-in and zoom-out reported separately. Zooming out brings every enemy on screen and
 *    zooming in does not, so they are different amounts of work and a median over the mix sits
 *    between two clusters rather than on either.
 *  - "stall" is everything a 60fps budget would not have spent, summed over the frames following
 *    `zoomend`; the median is taken over --steps gestures after discarding --warmup of them.
 *
 * Usage (from the worktree/checkout root on the host):
 *   docker compose exec -T app sh -c 'cd /var/www && node sh/e2e/zoom-benchmark.js'
 *
 * Options:
 *   --url <url>        Page to measure (default the Black Temple facade floor, 552 enemies)
 *   --start-zoom <n>   Zoom level to sit at while measuring (default 5). The default view fits the
 *                      whole floor on screen, which is not where the cost is - at zoom 5 only ~50
 *                      of Black Temple's 552 enemies are on screen, which is where a user reading
 *                      a route actually works. Set programmatically; the measured gestures are
 *                      still real wheel gestures from there.
 *   --steps <n>        Measured gestures per direction (default 7)
 *   --warmup <n>       Leading gestures discarded per direction (default 2)
 *   --viewport <WxH>   Viewport size (default 1920x1080)
 *   --hide-enemies     Detach the enemy layer group before measuring. Gives the floor: the stall
 *                      that is NOT enemy markers, so the budget available to a marker-side fix is
 *                      (normal run - this run).
 *   --ab-css <css>     Measure two conditions alternately within a single page load, toggling this
 *                      stylesheet on and off between gestures ("off" = A, "on" = B). Run-to-run
 *                      noise on a loaded dev machine is comfortably larger than the effects being
 *                      measured here, and it drifts over a run, so comparing two separate runs
 *                      cannot resolve them; interleaving the conditions can.
 *   --json             Print the raw per-gesture samples alongside the summary.
 *
 * CHROME_HOST/CHROME_PORT override the chrome service (default chrome:9222), as in browse.js.
 */
const puppeteer = require('puppeteer');
const http = require('http');
const dns = require('dns').promises;

const DEFAULT_URL = 'http://nginx/explore/classic/black-temple/9';
/** A 60fps frame budget; anything a frame spends beyond this is counted as stall. */
const FRAME_BUDGET_MS = 1000 / 60;
/** How long after zoomend to keep attributing frames to that gesture. */
const MEASURE_WINDOW_MS = 1000;

function arg(name, fallback = null) {
    const i = process.argv.indexOf('--' + name);
    return i === -1 ? fallback : process.argv[i + 1];
}

function flag(name) {
    return process.argv.includes('--' + name);
}

function median(values) {
    if (values.length === 0) {
        return NaN;
    }
    const sorted = [...values].sort((a, b) => a - b);
    const mid = Math.floor(sorted.length / 2);
    return sorted.length % 2 === 0 ? (sorted[mid - 1] + sorted[mid]) / 2 : sorted[mid];
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

const sleep = ms => new Promise(resolve => setTimeout(resolve, ms));

/**
 * Installs a rAF recorder plus a zoomend marker in the page. Kept deliberately dumb - it only
 * timestamps frames; all attribution happens afterwards in collectGesture().
 */
async function installRecorder(page) {
    await page.evaluate(() => {
        const bench = {frames: [], zoomEndAt: null};
        window.__zoomBench = bench;

        const tick = () => {
            bench.frames.push(performance.now());
            window.requestAnimationFrame(tick);
        };
        window.requestAnimationFrame(tick);

        getState().getDungeonMap().leafletMap.on('zoomend', () => {
            bench.zoomEndAt = performance.now();
        });
    });
}

/**
 * Sends one wheel gesture with decaying deltas. A real wheel gesture decelerates; a single large
 * delta is treated differently by Leaflet's scroll-wheel debounce than the burst a mouse produces.
 */
async function wheelGesture(page, cdp, direction, viewport) {
    const x = Math.round(viewport.width / 2);
    const y = Math.round(viewport.height / 2);
    const deltas = [120, 80, 40, 20].map(d => d * direction);

    for (const deltaY of deltas) {
        await cdp.send('Input.dispatchMouseEvent', {
            type: 'mouseWheel', x, y, deltaX: 0, deltaY, pointerType: 'mouse'
        });
        await sleep(16);
    }
}

/**
 * Runs one gesture and returns its frame statistics, or null if the map did not actually zoom
 * (hitting minZoom/maxZoom, which would otherwise be silently recorded as a perfectly fast frame).
 */
async function measureGesture(page, cdp, direction, viewport) {
    await page.evaluate(() => {
        window.__zoomBench.frames = [];
        window.__zoomBench.zoomEndAt = null;
    });

    const zoomBefore = await page.evaluate(() => getState().getDungeonMap().leafletMap.getZoom());

    await wheelGesture(page, cdp, direction, viewport);
    await sleep(MEASURE_WINDOW_MS + 500);

    const zoomAfter = await page.evaluate(() => getState().getDungeonMap().leafletMap.getZoom());
    if (zoomAfter === zoomBefore) {
        return null;
    }

    return await page.evaluate((budget, window_) => {
        const bench = window.__zoomBench;
        if (bench.zoomEndAt === null) {
            return null;
        }

        let stall = 0;
        let longestFrame = 0;
        for (let i = 1; i < bench.frames.length; i++) {
            const start = bench.frames[i - 1];
            if (start < bench.zoomEndAt || start > bench.zoomEndAt + window_) {
                continue;
            }
            const duration = bench.frames[i] - start;
            stall += Math.max(0, duration - budget);
            longestFrame = Math.max(longestFrame, duration);
        }

        return {stall, longestFrame};
    }, FRAME_BUDGET_MS, MEASURE_WINDOW_MS);
}

(async () => {
    const url = arg('url', DEFAULT_URL);
    const startZoom = parseFloat(arg('start-zoom', '5'));
    const steps = parseInt(arg('steps', '7'), 10);
    const warmup = parseInt(arg('warmup', '2'), 10);
    const [width, height] = arg('viewport', '1920x1080').split('x').map(Number);
    const viewport = {width, height};

    const browser = await connectToService(
        process.env.CHROME_HOST || 'chrome',
        parseInt(process.env.CHROME_PORT || '9222', 10)
    );

    const page = await browser.newPage();
    const pageErrors = [];
    page.on('pageerror', error => pageErrors.push(error.message));

    try {
        await page.setViewport(viewport);
        await page.setCookie({name: 'cookieconsent_status', value: 'dismiss', url});
        await page.goto(url, {waitUntil: 'networkidle2', timeout: 60000});

        // The map builds its markers after load; wait for the enemy layer to actually be there
        await page.waitForFunction(
            () => typeof getState === 'function' && getState().getDungeonMap() !== null &&
                document.querySelectorAll('.leaflet-marker-icon').length > 0,
            {timeout: 60000}
        );
        await sleep(2000);

        await page.evaluate(zoom => getState().getDungeonMap().leafletMap.setZoom(zoom), startZoom);
        await sleep(2000);

        const markerCount = await page.evaluate(() => document.querySelectorAll('.leaflet-marker-icon').length);

        if (flag('hide-enemies')) {
            await page.evaluate(() => {
                getState().getDungeonMap().mapObjectGroupManager
                    .getByName(MAP_OBJECT_GROUP_ENEMY).setVisibility(false);
            });
            await sleep(2000);
        }

        const measuredState = await page.evaluate(() => ({
            markers: document.querySelectorAll('.leaflet-marker-icon').length,
            culled: document.querySelectorAll('.map_enemy_visual_culled').length
        }));

        await installRecorder(page);
        const cdp = await page.createCDPSession();

        const abCss = arg('ab-css', null);
        if (abCss !== null) {
            await page.evaluate(css => {
                const style = document.createElement('style');
                style.id = '__zoomBenchAb';
                style.textContent = css;
                document.head.appendChild(style);
                style.disabled = true;
            }, abCss);
        }
        const setAbCondition = async on => {
            if (abCss === null) {
                return;
            }
            await page.evaluate(enabled => {
                document.getElementById('__zoomBenchAb').disabled = !enabled;
            }, on);
            await sleep(300);
        };

        const conditions = abCss === null ? [''] : ['A ', 'B '];
        const samples = {};
        for (const condition of conditions) {
            for (const name of ['out', 'in']) {
                samples[condition + name] = [];
            }
        }
        // Each iteration zooms out and back in again, so the map stays within its zoom range no
        // matter how many steps are asked for.
        for (let i = 0; i < steps + warmup; i++) {
            // Counterbalance the order: whichever condition is measured second inherits whatever
            // the first one left warm, which the null control (--ab-css with a rule that changes
            // nothing) shows as a systematic few ms. Alternating cancels it instead of hiding it.
            const ordered = (i % 2 === 0) ? conditions : [...conditions].reverse();
            for (const condition of ordered) {
                await setAbCondition(condition === 'B ');
                for (const [name, direction] of [['out', 1], ['in', -1]]) {
                    const result = await measureGesture(page, cdp, direction, viewport);
                    if (result === null) {
                        console.error(`warning: gesture ${i} ${condition}${name} did not zoom (zoom limit?) - skipped`);
                        continue;
                    }
                    if (i >= warmup) {
                        samples[condition + name].push(result);
                    }
                }
            }
        }

        const summary = {
            url,
            viewport: `${width}x${height}`,
            startZoom,
            markersOnPage: markerCount,
            markersMeasured: measuredState.markers,
            markersCulled: measuredState.culled,
            enemiesHidden: flag('hide-enemies'),
            steps,
            warmup,
            pageErrors,
            results: {}
        };
        for (const key of Object.keys(samples)) {
            const [condition, name] = key.includes(' ') ? key.split(' ') : ['', key];
            summary.results[`${condition ? condition + ': ' : ''}zoom ${name}`] = {
                samples: samples[key].length,
                stallMs: +median(samples[key].map(s => s.stall)).toFixed(1),
                longestFrameMs: +median(samples[key].map(s => s.longestFrame)).toFixed(1)
            };
        }
        if (flag('json')) {
            summary.raw = samples;
        }

        console.log(JSON.stringify(summary, null, 2));
    } finally {
        await page.close();
        await browser.disconnect();
    }
})().catch(error => {
    console.error(error);
    process.exit(1);
});

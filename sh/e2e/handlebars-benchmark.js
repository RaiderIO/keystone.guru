/**
 * A/B benchmark for the cost of rendering Handlebars templates on real pages: two builds of the
 * front-end bundles, the same stack, the same data, the same browser.
 * Runs INSIDE the app container (node + mounted node_modules), against http://nginx.
 *
 * Every load gets its app-*.js / custom-*.js served from --a-dir or --b-dir through CDP request
 * interception, so both conditions hit the identical server, database and page URL, and loads of
 * the two conditions alternate (A B B A ...) instead of running as two separate batches: run-to-run
 * drift on a dev machine is larger than the effect being measured.
 *
 * Instrumentation is appended to the served custom-*.js, after all templates are defined:
 *  - defaults merge: time inside getHandlebarsDefaultVariables() plus every $.extend() call that
 *    receives an object it returned - the per-render copy of the default variables;
 *  - template path: the merge plus time inside every Handlebars.templates[*] call (outermost only,
 *    so partials are not counted twice), and the number of renders;
 *  - page scripting: CDP Performance ScriptDuration over the whole load and interaction.
 *
 * performance.now() is coarsened to 100µs with jitter here: cross-origin isolation, which would lift
 * that, needs a secure context and http://nginx is not one. A single merge is below that
 * resolution, but a load sums thousands of them and the jitter averages out; ScriptDuration, which
 * is not coarsened, is the cross-check.
 *
 * Usage (from the worktree/checkout root on the host; the chrome service must be up):
 *   docker compose exec -T app sh -c 'cd /var/www && node sh/e2e/handlebars-benchmark.js \
 *       --a-dir tmp/bench-a --b-dir tmp/bench-b --email admin@app.com --password password \
 *       --route20 <public key> --route40 <public key> --dungeon the-blinding-vale'
 *
 * Options:
 *   --a-dir / --b-dir  Directories holding one app-*.js and one custom-*.js each (paths relative
 *                      to /var/www). Build each with `npm run development` and copy both out of
 *                      public/js.
 *   --scenarios <list> Comma separated subset of: explore,view20,edit20,edit40,compendium (default all)
 *   --runs <n>         Measured loads per condition per scenario and throttle (default 10)
 *   --warmup <n>       Leading loads per condition discarded (default 2)
 *   --throttle <list>  CPU throttling rates to run at (default 1,6)
 *   --redraws <n>      Full pull-sidebar redraws in the edit scenarios (default 5)
 *   --dungeon <slug>   Dungeon slug for explore/compendium/route URLs (default the-blinding-vale)
 *   --route20 / --route40  Public keys of the routes for view20/edit20 and edit40
 *   --email / --password   Account that owns the routes (edit pages need it)
 *   --json             Print the raw per-load samples alongside the summary
 *
 * CHROME_HOST/CHROME_PORT override the chrome service (default chrome:9222), as in browse.js.
 */
const puppeteer = require('puppeteer');
const http = require('http');
const dns = require('dns').promises;
const fs = require('fs');
const path = require('path');

function arg(name, fallback = null) {
    const index = process.argv.indexOf(`--${name}`);
    return index === -1 ? fallback : process.argv[index + 1];
}

const flag = name => process.argv.includes(`--${name}`);

const baseUrl = arg('base-url', 'http://nginx');
const dungeon = arg('dungeon', 'the-blinding-vale');
const runs = parseInt(arg('runs', '10'));
const warmup = parseInt(arg('warmup', '2'));
const throttles = arg('throttle', '1,6').split(',').map(Number);
const redraws = parseInt(arg('redraws', '5'));
const scenarioNames = arg('scenarios', 'explore,view20,edit20,edit40,compendium').split(',');

const INSTRUMENTATION = `
;(function () {
    var now = function () { return performance.now(); };
    var stats = window.__hbBench = {mergeMs: 0, merges: 0, renderMs: 0, renders: 0};
    var tagged = new WeakSet();

    var originalDefaults = window.getHandlebarsDefaultVariables;
    window.getHandlebarsDefaultVariables = function () {
        var start = now();
        var result = originalDefaults.apply(this, arguments);
        if (result !== null && typeof result === 'object') {
            tagged.add(result);
        }
        stats.mergeMs += now() - start;
        return result;
    };

    var originalExtend = jQuery.extend;
    jQuery.extend = function () {
        var hit = false;
        for (var i = 0; i < arguments.length; i++) {
            if (arguments[i] !== null && typeof arguments[i] === 'object' && tagged.has(arguments[i])) {
                hit = true;
                break;
            }
        }
        if (!hit) {
            return originalExtend.apply(this, arguments);
        }
        var start = now();
        var result = originalExtend.apply(this, arguments);
        stats.mergeMs += now() - start;
        stats.merges++;
        return result;
    };

    var depth = 0;
    Object.keys(Handlebars.templates).forEach(function (name) {
        var template = Handlebars.templates[name];
        Handlebars.templates[name] = function () {
            if (depth > 0) {
                return template.apply(this, arguments);
            }
            depth++;
            var start = now();
            try {
                return template.apply(this, arguments);
            } finally {
                stats.renderMs += now() - start;
                stats.renders++;
                depth--;
            }
        };
    });
})();
`;

function loadBundles(dir) {
    const full = path.resolve('/var/www', dir);
    const files = fs.readdirSync(full);
    const pick = prefix => {
        const file = files.find(f => f.startsWith(prefix) && f.endsWith('.js'));
        if (file === undefined) {
            throw new Error(`no ${prefix}*.js in ${full}`);
        }
        return fs.readFileSync(path.join(full, file), 'utf8');
    };

    return {
        app: Buffer.from(pick('app-')).toString('base64'),
        custom: Buffer.from(pick('custom-') + INSTRUMENTATION).toString('base64'),
    };
}

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

async function waitForMap(page) {
    await page.waitForFunction(
        () => typeof getState === 'function' && getState() !== false && getState().getDungeonMap() !== null
            && Object.keys(getState().getDungeonMap().mapObjectGroupManager.getEnemyMapObjectGroup().objects).length > 0,
        {timeout: 180000},
    );
    await sleep(1000);
}

async function redrawPullSidebar(page) {
    return await page.evaluate(() => {
        // getInlineCode() unwraps a single match
        const sidebar = _inlineManager.getInlineCode('common/maps/killzonessidebar');
        const killZones = getState().getDungeonMap().mapObjectGroupManager.getKillZoneMapObjectGroup().objects;
        let refreshed = 0;
        for (const killZone of Object.values(killZones)) {
            const rowElement = sidebar._getRowElementKillZone(killZone);
            if (rowElement !== null) {
                rowElement.refresh();
                refreshed++;
            }
        }
        return refreshed;
    });
}

const SCENARIOS = {
    explore: {
        url: () => `${baseUrl}/explore/retail/${dungeon}`,
        run: async page => {
            await waitForMap(page);
            // A floor switch rebinds every enemy tooltip on the new floor
            const floorIds = await page.evaluate(() => getState().getMapContext().getVisibleFloors().map(f => f.id));
            const start = await page.evaluate(() => getState().getCurrentFloor().id);
            for (const floorId of [...floorIds.filter(id => id !== start), start]) {
                await page.evaluate(id => getState().setFloorId(id), floorId);
                await sleep(750);
            }
        },
    },
    view20: {
        url: () => `${baseUrl}/route/${dungeon}/${arg('route20')}`,
        run: async page => {
            await waitForMap(page);
        },
    },
    edit20: {
        url: () => `${baseUrl}/route/${dungeon}/${arg('route20')}/benchmark/edit`,
        run: async page => {
            await waitForMap(page);
            for (let i = 0; i < redraws; i++) {
                await redrawPullSidebar(page);
                await sleep(100);
            }
        },
    },
    edit40: {
        url: () => `${baseUrl}/route/${dungeon}/${arg('route40')}/benchmark/edit`,
        run: async page => {
            await waitForMap(page);
            for (let i = 0; i < redraws; i++) {
                await redrawPullSidebar(page);
                await sleep(100);
            }
        },
    },
    compendium: {
        url: () => `${baseUrl}/compendium/dungeon/${dungeon}/npc`,
        run: async page => {
            await page.waitForFunction(() => document.querySelectorAll('table.dataTable tbody tr').length > 1, {timeout: 180000});
            await sleep(500);
            for (const pageIndex of [1, 0]) {
                await page.evaluate(index => $('table.dataTable').DataTable().page(index).draw('page'), pageIndex);
                await page.waitForFunction(() => document.querySelectorAll('table.dataTable tbody tr').length > 1, {timeout: 180000});
                await sleep(500);
            }
        },
    },
};

async function logIn(browser) {
    const page = await browser.newPage();
    await page.goto(`${baseUrl}/login`, {waitUntil: 'load'});
    const form = (await page.evaluateHandle(() => Array.from(document.querySelectorAll('form')).find(f =>
        f.querySelector('input[name="email"]') && !!(f.offsetWidth || f.offsetHeight || f.getClientRects().length)))).asElement();
    if (form === null) {
        throw new Error('no visible login form on /login');
    }
    await (await form.$('input[name="email"]')).type(arg('email'));
    await (await form.$('input[name="password"]')).type(arg('password'));
    await Promise.all([
        page.waitForNavigation({waitUntil: 'load'}),
        (await form.$('button[type="submit"], input[type="submit"]')).evaluate(button => button.click()),
    ]);
    await page.close();
}

async function measure(browser, scenario, bundles, throttle) {
    const page = await browser.newPage();
    await page.setViewport({width: 1920, height: 1080});
    const client = await page.createCDPSession();
    const pageErrors = [];
    page.on('pageerror', error => pageErrors.push(error.message));

    // The bundle URLs carry a ?t= cache buster, so the patterns cannot end in .js
    await client.send('Fetch.enable', {
        patterns: [
            {urlPattern: '*/js/app-*', requestStage: 'Request'},
            {urlPattern: '*/js/custom-*', requestStage: 'Request'},
        ],
    });
    client.on('Fetch.requestPaused', async event => {
        try {
            if (/\/js\/(app|custom)-[0-9a-f]+\.js/.test(event.request.url)) {
                const kind = /\/js\/app-/.test(event.request.url) ? 'app' : 'custom';
                await client.send('Fetch.fulfillRequest', {
                    requestId: event.requestId,
                    responseCode: 200,
                    responseHeaders: [{name: 'Content-Type', value: 'application/javascript; charset=utf-8'}],
                    body: bundles[kind],
                });
            } else {
                await client.send('Fetch.continueRequest', {requestId: event.requestId});
            }
        } catch (e) {
            // The page was closed while this request was paused
        }
    });

    await client.send('Performance.enable');
    await client.send('Emulation.setCPUThrottlingRate', {rate: throttle});

    await page.goto(scenario.url(), {waitUntil: 'load', timeout: 120000});
    await scenario.run(page);

    const metrics = Object.fromEntries((await client.send('Performance.getMetrics')).metrics.map(m => [m.name, m.value]));
    const stats = await page.evaluate(() => window.__hbBench);
    await page.close();

    if (stats === undefined || stats === null) {
        throw new Error(`instrumentation missing on ${scenario.url()} - was the bundle intercepted?`);
    }

    return {
        mergeMs: stats.mergeMs,
        merges: stats.merges,
        templatePathMs: stats.mergeMs + stats.renderMs,
        renders: stats.renders,
        scriptMs: metrics.ScriptDuration * 1000,
        pageErrors,
    };
}

function quantile(values, q) {
    const sorted = [...values].sort((a, b) => a - b);
    const position = (sorted.length - 1) * q;
    const low = Math.floor(position);
    const high = Math.ceil(position);

    return sorted[low] + (sorted[high] - sorted[low]) * (position - low);
}

function summarise(samples) {
    const pick = key => samples.map(s => s[key]);

    return {
        loads: samples.length,
        renders: quantile(pick('renders'), 0.5),
        merges: quantile(pick('merges'), 0.5),
        mergeMs: {p50: quantile(pick('mergeMs'), 0.5), p90: quantile(pick('mergeMs'), 0.9)},
        templatePathMs: {p50: quantile(pick('templatePathMs'), 0.5), p90: quantile(pick('templatePathMs'), 0.9)},
        scriptMs: {p50: quantile(pick('scriptMs'), 0.5), p90: quantile(pick('scriptMs'), 0.9)},
    };
}

(async () => {
    const bundles = {A: loadBundles(arg('a-dir')), B: loadBundles(arg('b-dir'))};
    const browser = await connectToService(process.env.CHROME_HOST || 'chrome', parseInt(process.env.CHROME_PORT || '9222'));
    const context = await browser.createBrowserContext();
    await context.setCookie({name: 'cookieconsent_status', value: 'dismiss', url: baseUrl});
    if (arg('email') !== null) {
        await logIn(context);
    }

    const results = [];
    const raw = [];
    try {
        for (const name of scenarioNames) {
            const scenario = SCENARIOS[name];
            if (scenario === undefined) {
                throw new Error(`unknown scenario ${name}`);
            }
            for (const throttle of throttles) {
                const samples = {A: [], B: []};
                const errors = new Set();
                for (let i = 0; i < warmup + runs; i++) {
                    for (const condition of (i % 2 === 0 ? ['A', 'B'] : ['B', 'A'])) {
                        const sample = await measure(context, scenario, bundles[condition], throttle);
                        sample.pageErrors.forEach(e => errors.add(`${condition}: ${e}`));
                        if (i >= warmup) {
                            samples[condition].push(sample);
                            raw.push({scenario: name, throttle, condition, ...sample});
                        }
                    }
                    process.stderr.write(`${name} @${throttle}x: ${i + 1}/${warmup + runs}\n`);
                }
                const a = summarise(samples.A);
                const b = summarise(samples.B);
                results.push({
                    scenario: name, throttle, A: a, B: b,
                    savedTemplatePathMs: a.templatePathMs.p50 - b.templatePathMs.p50,
                    savedScriptMs: a.scriptMs.p50 - b.scriptMs.p50,
                    pageErrors: [...errors],
                });
            }
        }
    } finally {
        await context.close();
        browser.disconnect();
    }

    console.log(JSON.stringify(flag('json') ? {results, raw} : {results}, null, 2));
})().catch(error => {
    console.error(error);
    process.exit(1);
});

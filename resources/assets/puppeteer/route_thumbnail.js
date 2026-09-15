const puppeteer = require('puppeteer');

// process.argv
// 0: node path
// 1: script path
// 2: target web page
// 3: resulting screenshot location
// 4: viewport width
// 5: viewport height
function delay(timeout) {
    return new Promise((resolve) => {
        setTimeout(resolve, timeout);
    });
}

// The exit code is the render's outcome; stderr only carries diagnostics. Browser-side events are
// collected rather than printed as they happen, and are written to stderr only for a page load that
// failed - ThumbnailService::doCreateThumbnail() logs a successful render with non-empty stderr as
// recovered, so printing them unconditionally would flag every good render.
const MAX_DIAGNOSTICS = 200;

// #finished_loading is appended synchronously by the map's DOMContentLoaded bootstrap, so it either
// exists once the `load` event has fired or it never will - waiting longer does not help, loading the
// page again does. Three loads of at most 15s + 2s stay inside the 60s Symfony Process default.
const MAX_PAGE_LOADS = 3;
const PAGE_LOAD_TIMEOUT_MS = 15000;
const FINISHED_LOADING_GRACE_MS = 2000;

/**
 * A fresh {diagnostics, recordDiagnostic} pair, so a test can exercise recordDiagnostic() /
 * wireDiagnostics() against an isolated array instead of the one module-level `render()` uses.
 */
function createDiagnosticsCollector() {
    const diagnostics = [];

    /**
     * @returns {number} the index the line was written to, or -1 if it was suppressed (already at
     * MAX_DIAGNOSTICS). Callers that resolve a line's text asynchronously (see wireDiagnostics()'s
     * 'console' handler) reserve their slot with a placeholder up front by calling this
     * synchronously as soon as the event fires, then overwrite diagnostics[index] once resolved -
     * so a message that takes longer to resolve doesn't reorder itself after messages that arrived
     * later but resolved faster.
     */
    function recordDiagnostic(line) {
        if (diagnostics.length < MAX_DIAGNOSTICS) {
            diagnostics.push(line);

            return diagnostics.length - 1;
        } else if (diagnostics.length === MAX_DIAGNOSTICS) {
            diagnostics.push(`... further browser events suppressed after ${MAX_DIAGNOSTICS} entries`);
        }

        return -1;
    }

    return {diagnostics, recordDiagnostic};
}

/**
 * ConsoleMessage#text() already resolves a logged Error argument to real text (Puppeteer's CDP
 * layer reads it from the object's CDP `description`, in cdp/utils.js's
 * valueFromRemoteObjectReference()) - it does NOT print the "JSHandle@error" placeholder some
 * older Puppeteer versions were known for. But it deliberately keeps only the first line of that
 * description ("Error: boom"), discarding the stack trace - i.e. exactly the file/line of the
 * call site that threw. Every "caught, logged, and carried on" spot in the map JS (e.g.
 * MapObjectGroupManager's `catch (e) { console.error(e); }`) hits this, so a diagnostics-driven
 * investigation into a #finished_loading timeout (like #3920) can currently tell THAT a handler
 * threw and its message, but not WHICH of the dozens of `console.error(e)` call sites it was.
 * Resolving the argument in-page via evaluate() instead gets the full stack back out.
 *
 * Returns null (never throws) when an argument can't be resolved this way (e.g. a detached DOM
 * node, which can't cross evaluate()'s serialization boundary) so the caller can fall back to
 * text() for the whole message.
 */
async function resolveConsoleArg(arg) {
    try {
        const resolved = await arg.evaluate(value =>
            value instanceof Error ? (value.stack || `${value.name}: ${value.message}`) : value);

        if (typeof resolved === 'string') {
            return resolved;
        }

        // JSON.stringify() returns undefined (not a string) for undefined/functions/symbols rather
        // than throwing - treat that the same as an unresolvable arg (null) so the caller falls back
        // to text() instead of silently dropping this argument from the message.
        const json = JSON.stringify(resolved);

        return json === undefined ? null : json;
    } catch (e) {
        return null;
    }
}

async function formatConsoleMessage(message) {
    const type = message.type().substr(0, 3).toUpperCase();
    const args = message.args();

    if (args.length === 0) {
        return `CONSOLE ${type} ${message.text()}`;
    }

    const resolvedParts = await Promise.all(args.map(resolveConsoleArg));

    // Any unresolved argument means we can't reconstruct the message faithfully - fall back to
    // Puppeteer's own (lossy, but always available) text() for the whole thing.
    return resolvedParts.includes(null)
        ? `CONSOLE ${type} ${message.text()}`
        : `CONSOLE ${type} ${resolvedParts.join(' ')}`;
}

/**
 * Where did page initialization actually get to? A timeout on #finished_loading means the map's
 * synchronous DOMContentLoaded bootstrap never completed - this reports which of its steps exist,
 * which distinguishes "the map context script never loaded" from "the inline code threw".
 */
async function collectPageState(page) {
    try {
        return await page.evaluate(() => {
            const mapInlineCode = typeof _inlineManager === 'undefined'
                ? null
                : _inlineManager.getInlineCode('common/maps/map');

            return {
                readyState: document.readyState,
                hasMapContextStatic: typeof mapContextStaticData !== 'undefined',
                hasMapContextDungeon: typeof mapContextDungeonData !== 'undefined',
                hasMapContextMappingVersion: typeof mapContextMappingVersionData !== 'undefined',
                hasInlineManager: typeof _inlineManager !== 'undefined',
                // getInlineCode() returns an empty array when the map's inline code was never registered
                mapInlineCodeActivated: typeof mapInlineCode?.isActivated === 'function' ? mapInlineCode.isActivated() : null,
                hasDungeonMap: typeof dungeonMap !== 'undefined' && dungeonMap !== null,
                mapElementSize: (() => {
                    const map = document.getElementById('map');

                    return map === null ? null : `${map.clientWidth}x${map.clientHeight}`;
                })(),
                finishedLoadingCount: document.querySelectorAll('#finished_loading').length,
            };
        });
    } catch (e) {
        return {error: `Could not read page state: ${e.message}`};
    }
}

/**
 * Wires up 'console'/'pageerror'/'response'/'requestfailed' capture on `page` into `diagnostics`
 * (recorded via `recordDiagnostic`).
 *
 * formatConsoleMessage() resolves each console argument over a CDP round-trip, so - unlike every
 * other handler here - it doesn't record its diagnostic synchronously. Two things that requires:
 * reserve the line's position with recordDiagnostic() as soon as the event fires (a message that
 * resolves slowly must not end up after a later message that resolved fast), and return the
 * in-flight promises so the caller can await them before reading `diagnostics` - a message logged
 * right before the #finished_loading timeout fires would otherwise lose that race and never make
 * it in.
 *
 * @returns {Promise[]} the pending console-diagnostic promises to await before reading `diagnostics`.
 */
function wireDiagnostics(page, {diagnostics, recordDiagnostic}) {
    const pendingConsoleDiagnostics = [];

    page
        .on('console', message => {
            const index = recordDiagnostic(null);

            if (index !== -1) {
                pendingConsoleDiagnostics.push(
                    formatConsoleMessage(message).then(text => diagnostics[index] = text));
            }
        })
        .on('pageerror', error =>
            recordDiagnostic(`PAGEERROR ${error.stack || error.message}`))
        .on('response', response => {
            if (response.status() >= 400) {
                recordDiagnostic(`RESPONSE ${response.status()} ${response.url()}`);
            }
        })
        .on('requestfailed', request =>
            recordDiagnostic(`REQUESTFAILED ${request.failure()?.errorText} ${request.url()}`));

    return pendingConsoleDiagnostics;
}

/**
 * Puppeteer's own "Could not find Chrome" error just names the missing revision, not why it's
 * missing here: docker-compose/app-worker/Dockerfile bakes a Chrome build into the image matching
 * whatever puppeteer version was pinned in package.json AT BUILD TIME, but node_modules is
 * bind-mounted from the repo and can drift to a newer puppeteer (e.g. a dependabot bump) without
 * the image being rebuilt - see #4012. Rethrows unchanged for every other failure so this stays a
 * pure diagnostic addition, not a behaviour change.
 */
async function launchBrowser(puppeteerModule = puppeteer) {
    try {
        return await puppeteerModule.launch({
            headless: true,
            args: ['--no-sandbox'],
        });
    } catch (e) {
        if (typeof e?.message === 'string' && e.message.includes('Could not find Chrome')) {
            const puppeteerVersion = require('puppeteer/package.json').version;

            console.error(
                `This looks like the puppeteer/Chrome drift from #4012: node_modules' puppeteer ` +
                `(${puppeteerVersion}) does not match the Chrome cached at ` +
                `${process.env.PUPPETEER_CACHE_DIR ?? '(PUPPETEER_CACHE_DIR is not set)'}. Rebuild the ` +
                'keystone.guru-worker image (docker compose build horizon) to re-bake a matching Chrome.',
            );
        }

        throw e;
    }
}

/**
 * Loads `url` in a fresh page until #finished_loading appears, at most `maxLoads` times. Every
 * failed load is closed and described in a report, so a transient failure costs a reload instead
 * of the whole render.
 *
 * @param {function(): Promise<{page: object, diagnostics: string[], pendingConsoleDiagnostics: Promise[]}>} openPage
 * @param {string} url
 * @param {{maxLoads: number, loadTimeoutMs: number, graceMs: number}} options
 * @returns {Promise<{page: object|null, failedLoadReports: string[]}>} `page` is null when every load failed.
 */
async function loadUntilFinished(openPage, url, {maxLoads, loadTimeoutMs, graceMs}) {
    const failedLoadReports = [];

    for (let load = 1; load <= maxLoads; load++) {
        const startedAt = new Date().getTime();
        const {page, diagnostics, pendingConsoleDiagnostics} = await openPage();

        try {
            await page.goto(url, {timeout: loadTimeoutMs});
            await page.waitForSelector('#finished_loading', {timeout: graceMs});

            return {page, failedLoadReports};
        } catch (e) {
            // Failure path only - see the note on `diagnostics` above before moving any of this out of the catch.
            await Promise.all(pendingConsoleDiagnostics);
            const pageState = await collectPageState(page);

            failedLoadReports.push([
                `Page load ${load} of ${maxLoads} failed after ${new Date().getTime() - startedAt}ms for ${url}: ${e.message}`,
                `Page state: ${JSON.stringify(pageState)}`,
                diagnostics.length === 0
                    ? 'Browser reported no console output, page errors or failed requests.'
                    : `Browser events:\n${diagnostics.join('\n')}`,
            ].join('\n'));

            await page.close().catch(() => {});
        }
    }

    return {page: null, failedLoadReports};
}

async function render() {
    const [, , url, targetFile, viewportWidth, viewportHeight] = process.argv;
    let startTime = new Date().getTime();
    console.log('Creating browser');
    const browser = await launchBrowser();

    try {
        const hostname = new URL(url).hostname;
        const openPage = async () => {
            const page = await browser.newPage();
            const collector = createDiagnosticsCollector();
            const pendingConsoleDiagnostics = wireDiagnostics(page, collector);

            // Force facade for thumbnails
            await page.setCookie({name: 'map_facade_style', value: 'facade', domain: hostname});

            // Force the default pull-connection weight; the page's own cookie-default bootstrap uses secure
            // cookies which are rejected on plain-http (internal) URLs, and a NaN weight draws invisible lines
            await page.setCookie({name: 'kill_zone_path_weight', value: '5', domain: hostname});

            await page.setViewport({width: Math.max(viewportWidth ?? 0, 768), height: Math.max(viewportHeight ?? 0, 512)});

            return {page, diagnostics: collector.diagnostics, pendingConsoleDiagnostics};
        };

        console.log(`Navigating to ${url}`);
        const {page, failedLoadReports} = await loadUntilFinished(openPage, url, {
            maxLoads: MAX_PAGE_LOADS,
            loadTimeoutMs: PAGE_LOAD_TIMEOUT_MS,
            graceMs: FINISHED_LOADING_GRACE_MS,
        });

        if (failedLoadReports.length > 0) {
            console.error(failedLoadReports.join('\n\n'));
        }

        if (page === null) {
            throw new Error(`#finished_loading did not appear in any of ${MAX_PAGE_LOADS} page loads`);
        }

        console.log('Waiting for animations to complete');
        await delay(500);

        console.log('Taking screenshot');
        await page.screenshot({path: targetFile});
    } finally {
        await browser.close();
        let time = new Date().getTime() - startTime;
        console.log(`Finished in ${time}ms!`);
    }
}

// Only run as a script (invoked by ThumbnailService via a `node route_thumbnail.js ...` subprocess) -
// not when required by a test, which needs the functions below without also launching a real browser.
if (require.main === module) {
    render().catch(e => {
        console.error(e.stack || e.message);
        process.exitCode = 1;
    });
}

module.exports = {resolveConsoleArg, formatConsoleMessage, createDiagnosticsCollector, wireDiagnostics, launchBrowser, loadUntilFinished};

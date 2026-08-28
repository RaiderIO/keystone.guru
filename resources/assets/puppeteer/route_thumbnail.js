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

// Browser-side events are collected here rather than printed as they happen, and are only emitted
// when the render fails. This is deliberate and load-bearing: ThumbnailService::doCreateThumbnail()
// treats ANY non-empty stderr as a failed render (it returns null even when the screenshot itself
// succeeded), so writing diagnostics to stderr unconditionally would fail every good render. See #3920.
const MAX_DIAGNOSTICS = 200;
const diagnostics = [];

function recordDiagnostic(line) {
    if (diagnostics.length < MAX_DIAGNOSTICS) {
        diagnostics.push(line);
    } else if (diagnostics.length === MAX_DIAGNOSTICS) {
        diagnostics.push(`... further browser events suppressed after ${MAX_DIAGNOSTICS} entries`);
    }
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

        return typeof resolved === 'string' ? resolved : JSON.stringify(resolved);
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

async function render() {
    let startTime = new Date().getTime();
    console.log('Creating browser');
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox'],
    });

    try {
        const page = await browser.newPage();
        // formatConsoleMessage() resolves each console argument over a CDP round-trip, so - unlike
        // every other handler here - it doesn't record its diagnostic synchronously. Track the
        // in-flight promises and await them below before reading `diagnostics`, or a message logged
        // right before the #finished_loading timeout fires can lose the race and never make it in.
        const pendingConsoleDiagnostics = [];
        page
            .on('console', message =>
                pendingConsoleDiagnostics.push(formatConsoleMessage(message).then(recordDiagnostic)))
            .on('pageerror', error =>
                recordDiagnostic(`PAGEERROR ${error.stack || error.message}`))
            .on('response', response => {
                if (response.status() >= 400) {
                    recordDiagnostic(`RESPONSE ${response.status()} ${response.url()}`);
                }
            })
            .on('requestfailed', request =>
                recordDiagnostic(`REQUESTFAILED ${request.failure()?.errorText} ${request.url()}`));

        // Force facade for thumbnails
        await page.setCookie({
            name: 'map_facade_style',
            value: 'facade',
            domain: new URL(process.argv[2]).hostname
        });

        // Force the default pull-connection weight; the page's own cookie-default bootstrap uses secure
        // cookies which are rejected on plain-http (internal) URLs, and a NaN weight draws invisible lines
        await page.setCookie({
            name: 'kill_zone_path_weight',
            value: '5',
            domain: new URL(process.argv[2]).hostname
        });

        await page.setViewport({width: Math.max(process.argv[4] ?? 0, 768), height: Math.max(process.argv[5] ?? 0, 512)});

        console.log(`Navigating to ${process.argv[2]}`);
        await page.goto(process.argv[2]);

        console.log('Waiting for page to load fully');
        try {
            await page.waitForSelector('#finished_loading', {timeout: 10000});
        } catch (e) {
            // Failure path only - see the note on `diagnostics` above before moving any of this out of the catch.
            await Promise.all(pendingConsoleDiagnostics);
            const pageState = await collectPageState(page);

            console.error(`Render failed after ${new Date().getTime() - startTime}ms for ${process.argv[2]}`);
            console.error(`Page state: ${JSON.stringify(pageState)}`);
            console.error(diagnostics.length === 0
                ? 'Browser reported no console output, page errors or failed requests.'
                : `Browser events:\n${diagnostics.join('\n')}`);

            throw e;
        }

        console.log('Waiting for animations to complete');
        await delay(500);

        console.log('Taking screenshot');
        await page.screenshot({path: process.argv[3]});
    } finally {
        await browser.close();
        let time = new Date().getTime() - startTime;
        console.log(`Finished in ${time}ms!`);
    }
}

// Only run as a script (invoked by ThumbnailService via a `node route_thumbnail.js ...` subprocess) -
// not when required by a test, which needs resolveConsoleArg()/formatConsoleMessage() without also
// launching a real browser.
if (require.main === module) {
    render();
}

module.exports = {resolveConsoleArg, formatConsoleMessage};

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

(async () => {
    let startTime = new Date().getTime();
    console.log('Creating browser');
    const browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox'],
    });

    try {
        const page = await browser.newPage();
        page
            .on('console', message =>
                recordDiagnostic(`CONSOLE ${message.type().substr(0, 3).toUpperCase()} ${message.text()}`))
            .on('pageerror', ({message}) =>
                recordDiagnostic(`PAGEERROR ${message}`))
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
})();

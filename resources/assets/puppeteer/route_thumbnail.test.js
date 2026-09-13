const {resolveConsoleArg, formatConsoleMessage, createDiagnosticsCollector, wireDiagnostics, launchBrowser, loadUntilFinished} = require('./route_thumbnail');

/**
 * Puppeteer's real JSHandle#evaluate() runs a function inside the browser page and returns its
 * result. These fakes stand in for that: `resolveValue` is what the in-page function would see as
 * its argument, so passing a real Error mirrors an in-page `console.error(someError)`.
 */
function fakeArg(resolveValue) {
    return {evaluate: async (fn) => fn(resolveValue)};
}

function fakeThrowingArg() {
    return {evaluate: async () => { throw new Error('detached handle'); }};
}

function fakeMessage(type, args, text) {
    return {type: () => type, args: () => args, text: () => text};
}

describe('resolveConsoleArg', () => {
    it('resolveConsoleArg_givenErrorValue_returnsStack', async () => {
        const error = new Error('boom');

        const result = await resolveConsoleArg(fakeArg(error));

        expect(result).toBe(error.stack);
    });

    it('resolveConsoleArg_givenErrorValueWithoutStack_fallsBackToNameAndMessage', async () => {
        const error = new Error('boom');
        error.stack = undefined;

        const result = await resolveConsoleArg(fakeArg(error));

        expect(result).toBe('Error: boom');
    });

    it('resolveConsoleArg_givenStringValue_returnsItUnchanged', async () => {
        const result = await resolveConsoleArg(fakeArg('plain string'));

        expect(result).toBe('plain string');
    });

    it('resolveConsoleArg_givenPlainObjectValue_returnsJsonEncodedValue', async () => {
        const result = await resolveConsoleArg(fakeArg({killZoneId: 5}));

        expect(result).toBe('{"killZoneId":5}');
    });

    it('resolveConsoleArg_givenUnresolvableHandle_returnsNull', async () => {
        const result = await resolveConsoleArg(fakeThrowingArg());

        expect(result).toBeNull();
    });

    it('resolveConsoleArg_givenUndefinedValue_returnsNullInsteadOfSilentlyDroppingIt', async () => {
        // JSON.stringify(undefined) === undefined (not a string) - the bug this guards against is
        // treating that as a resolved value, which formatConsoleMessage() would then join() into an
        // empty string instead of falling back to text() for the whole message.
        const result = await resolveConsoleArg(fakeArg(undefined));

        expect(result).toBeNull();
    });
});

describe('formatConsoleMessage', () => {
    it('formatConsoleMessage_givenNoArgs_usesText', async () => {
        const message = fakeMessage('log', [], 'hello');

        const result = await formatConsoleMessage(message);

        expect(result).toBe('CONSOLE LOG hello');
    });

    it('formatConsoleMessage_givenLoggedError_includesFullStackInsteadOfOnlyItsFirstLine', async () => {
        const error = new Error('killzone enemy group missing');
        // text() would only carry the first line of this ("Error: killzone enemy group missing"),
        // not the frame that identifies which console.error(e) call site actually threw.
        const message = fakeMessage('error', [fakeArg(error)], 'Error: killzone enemy group missing');

        const result = await formatConsoleMessage(message);

        expect(result).toBe(`CONSOLE ERR ${error.stack}`);
        expect(result).toContain('route_thumbnail.test.js');
    });

    it('formatConsoleMessage_givenPrefixStringAndError_resolvesBothArgs', async () => {
        const error = new Error('boom');
        const message = fakeMessage('error', [fakeArg('Unable to create offset for pack'), fakeArg(error)], 'irrelevant');

        const result = await formatConsoleMessage(message);

        expect(result).toBe(`CONSOLE ERR Unable to create offset for pack ${error.stack}`);
    });

    it('formatConsoleMessage_givenUnresolvableArg_fallsBackToText', async () => {
        const message = fakeMessage('error', [fakeThrowingArg()], 'fallback text');

        const result = await formatConsoleMessage(message);

        expect(result).toBe('CONSOLE ERR fallback text');
    });
});

describe('launchBrowser', () => {
    it('launchBrowser_givenSuccessfulLaunch_returnsTheBrowserWithoutLogging', async () => {
        const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        const browser = {};
        const fakePuppeteer = {launch: async () => browser};

        const result = await launchBrowser(fakePuppeteer);

        expect(result).toBe(browser);
        expect(consoleErrorSpy).not.toHaveBeenCalled();
    });

    it('launchBrowser_givenChromeVersionMismatch_logsTheDriftCauseAndRethrows', async () => {
        const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        const error = new Error('Could not find Chrome (ver. 151.0.7922.47).');
        const fakePuppeteer = {launch: async () => { throw error; }};

        await expect(launchBrowser(fakePuppeteer)).rejects.toBe(error);
        expect(consoleErrorSpy).toHaveBeenCalledTimes(1);
        expect(consoleErrorSpy.mock.calls[0][0]).toContain('#4012');
    });

    it('launchBrowser_givenUnrelatedLaunchFailure_rethrowsWithoutLogging', async () => {
        const consoleErrorSpy = vi.spyOn(console, 'error').mockImplementation(() => {});
        const error = new Error('spawn ENOMEM');
        const fakePuppeteer = {launch: async () => { throw error; }};

        await expect(launchBrowser(fakePuppeteer)).rejects.toBe(error);
        expect(consoleErrorSpy).not.toHaveBeenCalled();
    });
});

/**
 * A minimal stand-in for Puppeteer's Page: just enough .on()/.emit() to let a test fire the same
 * events wireDiagnostics() would see, without a real browser.
 */
function fakePage() {
    const handlers = {};
    const page = {
        on(event, handler) {
            handlers[event] = handler;

            return page;
        },
        emit(event, arg) {
            handlers[event](arg);
        },
    };

    return page;
}

/** A console argument whose resolution is deferred until `resolve()` is called. */
function fakeDeferredArg() {
    let resolveDeferred;
    const deferred = new Promise(resolve => {
        resolveDeferred = resolve;
    });

    return {
        arg: {evaluate: async (fn) => fn(await deferred)},
        resolve: resolveDeferred,
    };
}

describe('wireDiagnostics', () => {
    it('wireDiagnostics_givenConsoleMessagesThatResolveOutOfOrder_keepsThemInEmissionOrder', async () => {
        const page = fakePage();
        const collector = createDiagnosticsCollector();
        const pending = wireDiagnostics(page, collector);

        const first = fakeDeferredArg();
        const second = fakeDeferredArg();

        // Emitted in this order - "first" is logged before "second" - but "second" resolves first,
        // simulating a fast console.log racing ahead of a slower console.error(e) that was logged
        // earlier. Without the reserved-slot fix, "second" would land at diagnostics[0].
        page.emit('console', fakeMessage('log', [first.arg], 'irrelevant'));
        page.emit('console', fakeMessage('log', [second.arg], 'irrelevant'));

        second.resolve('second message');
        await Promise.resolve();
        first.resolve('first message');
        await Promise.all(pending);

        expect(collector.diagnostics).toEqual(['CONSOLE LOG first message', 'CONSOLE LOG second message']);
    });

    it('wireDiagnostics_givenPageerror_recordsItsStack', () => {
        const page = fakePage();
        const collector = createDiagnosticsCollector();
        wireDiagnostics(page, collector);

        const error = new Error('uncaught in page');
        page.emit('pageerror', error);

        expect(collector.diagnostics).toEqual([`PAGEERROR ${error.stack}`]);
    });
});

/**
 * A stand-in for a freshly opened preview page: `outcome` 'finished' resolves #finished_loading,
 * 'missing' times the selector wait out, 'gotoFails' makes the navigation itself throw.
 */
function fakeLoadedPage(outcome) {
    return {
        goto: async () => {
            if (outcome === 'gotoFails') {
                throw new Error('Navigation timeout of 15000 ms exceeded');
            }
        },
        waitForSelector: async () => {
            if (outcome === 'missing') {
                throw new Error('Waiting for selector `#finished_loading` failed');
            }
        },
        evaluate: async () => ({readyState: 'complete', hasMapContextStatic: false}),
        close: vi.fn(async () => {}),
    };
}

function fakeOpenPage(outcomes) {
    const pages = outcomes.map(fakeLoadedPage);
    let opened = 0;

    const openPage = vi.fn(async () => ({
        page: pages[opened++],
        diagnostics: ['REQUESTFAILED net::ERR_CONNECTION_RESET http://assets/mapcontext.js'],
        pendingConsoleDiagnostics: [],
    }));

    return {openPage, pages};
}

const LOAD_OPTIONS = {maxLoads: 3, loadTimeoutMs: 15000, graceMs: 2000};

describe('loadUntilFinished', () => {
    it('loadUntilFinished_givenFirstLoadFinishes_returnsItsPageWithoutReports', async () => {
        const {openPage, pages} = fakeOpenPage(['finished']);

        const result = await loadUntilFinished(openPage, 'http://nginx/preview/1', LOAD_OPTIONS);

        expect(result.page).toBe(pages[0]);
        expect(result.failedLoadReports).toEqual([]);
        expect(openPage).toHaveBeenCalledTimes(1);
    });

    it('loadUntilFinished_givenFirstLoadMissesMarker_reloadsAndReportsTheFailedLoad', async () => {
        const {openPage, pages} = fakeOpenPage(['missing', 'finished']);

        const result = await loadUntilFinished(openPage, 'http://nginx/preview/1', LOAD_OPTIONS);

        expect(result.page).toBe(pages[1]);
        expect(pages[0].close).toHaveBeenCalledTimes(1);
        expect(result.failedLoadReports).toHaveLength(1);
        expect(result.failedLoadReports[0]).toContain('Page load 1 of 3 failed');
        expect(result.failedLoadReports[0]).toContain('Page state: {"readyState":"complete","hasMapContextStatic":false}');
        expect(result.failedLoadReports[0]).toContain('REQUESTFAILED net::ERR_CONNECTION_RESET');
    });

    it('loadUntilFinished_givenNavigationThrows_treatsItAsAFailedLoadAndReloads', async () => {
        const {openPage, pages} = fakeOpenPage(['gotoFails', 'finished']);

        const result = await loadUntilFinished(openPage, 'http://nginx/preview/1', LOAD_OPTIONS);

        expect(result.page).toBe(pages[1]);
        expect(result.failedLoadReports[0]).toContain('Navigation timeout of 15000 ms exceeded');
    });

    it('loadUntilFinished_givenEveryLoadFails_returnsNullPageAndOneReportPerLoad', async () => {
        const {openPage, pages} = fakeOpenPage(['missing', 'gotoFails', 'missing']);

        const result = await loadUntilFinished(openPage, 'http://nginx/preview/1', LOAD_OPTIONS);

        expect(result.page).toBeNull();
        expect(result.failedLoadReports).toHaveLength(3);
        expect(result.failedLoadReports[2]).toContain('Page load 3 of 3 failed');
        expect(openPage).toHaveBeenCalledTimes(3);
        pages.forEach(page => expect(page.close).toHaveBeenCalledTimes(1));
    });
});

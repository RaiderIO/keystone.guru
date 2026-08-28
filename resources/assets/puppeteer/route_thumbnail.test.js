const {resolveConsoleArg, formatConsoleMessage} = require('./route_thumbnail');

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

// Global stubs ($, Cookies) are provided by the shared Vitest setup file
// (resources/assets/js/test/setup.js). Each test overrides `Cookies` and/or `$` on
// `globalThis` to drive the branch under test; csrf.js reads both globals at call time.
const {getCsrfToken} = require('./csrf');

/**
 * Build a jQuery-like stub whose `$('meta[name="csrf-token"]').attr('content')` returns the
 * given value, mirroring how csrf.js reads the meta tag fallback. Any other selector or attribute
 * reads as absent, so reading the wrong tag falls through to null.
 *
 * @param {string|undefined} metaContent
 * @returns {Function}
 */
function makeJqueryStub(metaContent) {
    return (selector) => ({
        attr: (name) => (selector === 'meta[name="csrf-token"]' && name === 'content' ? metaContent : undefined),
    });
}

/**
 * Build a js-cookie-like stub that only knows the `XSRF-TOKEN` cookie.
 *
 * @param {?string} xsrfToken
 * @returns {{get: Function}}
 */
function makeCookiesStub(xsrfToken) {
    return {get: (name) => (name === 'XSRF-TOKEN' ? xsrfToken : undefined)};
}

describe('getCsrfToken', () => {
    const originalCookies = globalThis.Cookies;
    const originalJquery = globalThis.$;

    afterEach(() => {
        globalThis.Cookies = originalCookies;
        globalThis.$ = originalJquery;
    });

    it('getCsrfToken_givenXsrfCookiePresent_returnsXsrfHeaderFromCookie', () => {
        globalThis.Cookies = makeCookiesStub('cookie-token');
        globalThis.$ = makeJqueryStub('meta-token');

        expect(getCsrfToken()).toEqual({header: 'X-XSRF-TOKEN', value: 'cookie-token'});
    });

    it('getCsrfToken_givenNoCookie_returnsCsrfHeaderFromMetaTag', () => {
        globalThis.Cookies = {get: () => null};
        globalThis.$ = makeJqueryStub('meta-token');

        expect(getCsrfToken()).toEqual({header: 'X-CSRF-TOKEN', value: 'meta-token'});
    });

    it('getCsrfToken_givenEmptyCookie_fallsBackToMetaTag', () => {
        globalThis.Cookies = makeCookiesStub('');
        globalThis.$ = makeJqueryStub('meta-token');

        expect(getCsrfToken()).toEqual({header: 'X-CSRF-TOKEN', value: 'meta-token'});
    });

    it('getCsrfToken_givenNeitherCookieNorMetaTag_returnsNull', () => {
        globalThis.Cookies = {get: () => null};
        globalThis.$ = makeJqueryStub(undefined);

        expect(getCsrfToken()).toBeNull();
    });

    it('getCsrfToken_givenCookiesUndefined_fallsBackToMetaTag', () => {
        globalThis.Cookies = undefined;
        globalThis.$ = makeJqueryStub('meta-token');

        expect(getCsrfToken()).toEqual({header: 'X-CSRF-TOKEN', value: 'meta-token'});
    });

    it('getCsrfToken_givenCookiesNull_fallsBackToMetaTag', () => {
        globalThis.Cookies = null;
        globalThis.$ = makeJqueryStub('meta-token');

        expect(getCsrfToken()).toEqual({header: 'X-CSRF-TOKEN', value: 'meta-token'});
    });

    it('getCsrfToken_givenNoCookieAndEmptyMetaTag_returnsNull', () => {
        globalThis.Cookies = makeCookiesStub(undefined);
        globalThis.$ = makeJqueryStub('');

        expect(getCsrfToken()).toBeNull();
    });
});

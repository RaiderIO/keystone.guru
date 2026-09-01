// #4409: StateManager caches the map display settings it reads out of document.cookie, so anything
// that writes one of those cookies without going through a StateManager setter has to drop that
// cache itself. _initDefaults() is exactly such a writer - it fills in every map cookie with
// Cookies.set directly - and it is also where the cross-tab escape hatch is wired up.
//
// Follows the global-script recipe from map.favorite.test.js: stub what the class body touches at
// load time, then require the source.

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.SettingsTabMap = class SettingsTabMap {
};
globalThis.SettingsTabPull = class SettingsTabPull {
};

const {CommonMapsMap} = require('./map');

globalThis.ENVIRONMENT_LOCAL = 'local';

describe('CommonMapsMap._initDefaults cookie cache handling', () => {
    let invalidateCookieCache;
    let cookieJar;

    beforeEach(() => {
        cookieJar = new Map();
        invalidateCookieCache = vi.fn();

        globalThis.cookieDefaultAttributes = undefined;
        globalThis.Cookies = {
            get: (key) => cookieJar.get(key),
            set: (key, value) => cookieJar.set(key, `${value}`),
            withAttributes: () => undefined,
        };

        globalThis.getState = () => ({
            getMapContext: () => ({getEnvironment: () => ENVIRONMENT_LOCAL}),
            invalidateCookieCache: invalidateCookieCache,
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    /**
     * _initDefaults() only needs `this` for nothing at all, but calling it off the prototype keeps
     * the constructor (and its sidebar collaborators) out of the way.
     */
    function initDefaults() {
        CommonMapsMap.prototype._initDefaults.call({});
    }

    test('_initDefaults_givenAFreshPage_invalidatesTheCookieCacheAfterWritingTheDefaults', () => {
        // Without this the very first read of a setting could be cached from before the defaults
        // were written, and would then be served for the rest of the session.
        initDefaults();

        expect(invalidateCookieCache).toHaveBeenCalled();
        expect(cookieJar.get('map_enemy_dangerous_border')).toBe('1');
    });

    test('_initDefaults_givenTheTabIsFocusedAgain_invalidatesTheCookieCache', () => {
        // Another tab may have changed a display setting in the shared cookie while this one was in
        // the background; cookies have no change event, so focus is the trigger.
        initDefaults();
        invalidateCookieCache.mockClear();

        window.dispatchEvent(new Event('focus'));

        expect(invalidateCookieCache).toHaveBeenCalledTimes(1);
    });
});

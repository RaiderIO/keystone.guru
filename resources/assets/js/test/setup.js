// Shared Vitest setup for the global-script style `custom/` source files.
//
// These files are not modules: they reference browser globals like `$` (jQuery),
// `L` (Leaflet), `lang`, `getState`, and the `c` constants object both at load
// time and inside their function bodies. This file attaches minimal stand-ins to
// `globalThis` so the source can be `require`d in tests without a real browser.
//
// This is the single place to add a new global stub. Keep stubs minimal; an
// individual test that needs real behaviour can override any of these with
// `vi.fn()` (or assign its own value) inside the test itself.

// jQuery: `util.js` registers plugins via `$.fn.*` at load time, and the model
// classes use `$.inArray` for membership checks.
globalThis.$ = globalThis.$ ?? {
    fn: {},
    inArray: (value, array) => (Array.isArray(array) ? array.indexOf(value) : -1),
};

// Leaflet.
globalThis.L = globalThis.L ?? {};

// Translation helper, mirroring the shape used by the source (see util.js).
globalThis.lang = globalThis.lang ?? {
    getLocale: () => 'en_US',
    get: (key) => key,
    messages: {},
};

// State manager accessor. The real implementation lives in statemanager.blade.php;
// returning `false` matches the no-op fallback defined in util.js.
globalThis.getState = globalThis.getState ?? (() => false);

// Cookie accessor (js-cookie). `constants.js` reads cookie values at load time.
globalThis.Cookies = globalThis.Cookies ?? {get: () => null};

// The `t` translation helper bootstrap.js registers on the bundle's Handlebars, for tests that
// compile the real .handlebars sources. Resolves through whichever `lang` is current at render time.
require('handlebars').registerHelper('t', function (key) {
    return lang.get('js.' + key);
});

// util.js's abbreviateNumber is a bundle global that the route picker and route table call bare.
globalThis.abbreviateNumber = require('../custom/util').abbreviateNumber;

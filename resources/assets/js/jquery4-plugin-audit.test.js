// ---------------------------------------------------------------------------
// Regression coverage for #3590, a follow-up audit to #3588/#3589. jQuery 4.0
// (#3560 bumped jQuery 3.6.1 -> 4.0.0) removed several long-deprecated global
// helpers, and one bundled plugin (`jquery-bar-rating`) still called one of
// them (`$.isNumeric`), breaking pull lists in production. That plugin has since
// been replaced by a self-owned widget and its `$.isNumeric` shim removed
// (#3593), and `jquery-visible` has since been replaced by a native
// `getBoundingClientRect()` check and dropped entirely (#3594). `password-strength-meter`
// was also replaced by a self-owned widget (#3597, unmaintained upstream) and dropped from
// here since it's no longer bundled. This audit remains to guard the OTHER bundled plugins
// below.
//
// A static grep audit for #3590 found no other bundled plugin calling a
// removed jQuery-4 API. To actually close the loop the way #3589's test did -
// by exercising the real code path instead of just grepping for names - this
// file requires each other actively-used bundled plugin against the real
// jQuery 4 package and initialises it the same way the app does, asserting it
// doesn't throw. This is what should catch a future plugin-version bump that
// reintroduces a removed-API call, before it reaches production.
//
// Each plugin is required via the exact same module specifier used in
// resources/assets/js/bootstrap.js, since some packages (e.g. bootstrap5-toggle)
// resolve to a different, non-jQuery build via their bare package name.
// ---------------------------------------------------------------------------

const $ = require('jquery');
global.$ = global.jQuery = $;

describe('lightslider (#3590)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('lightSlider_givenSlideList_initialisesWithoutThrowing', () => {
        require('lightslider');
        document.body.innerHTML = `<ul class="light-slider">
            <li><img src="1.jpg" /></li>
            <li><img src="2.jpg" /></li>
            <li><img src="3.jpg" /></li>
        </ul>`;

        expect(() => $('.light-slider').lightSlider({loop: false, controls: false, pager: false}))
            .not.toThrow();
    });
});

describe('bootstrap5-toggle (#3590)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('bootstrapToggle_givenCheckbox_initialisesWithoutThrowing', () => {
        // jsdom doesn't implement ResizeObserver, which the plugin uses to react to its
        // container becoming visible; stub it so this test exercises the jQuery/plugin
        // code path rather than failing on an unrelated jsdom environment gap.
        global.ResizeObserver = global.ResizeObserver ?? class {
            observe() {
            }

            unobserve() {
            }

            disconnect() {
            }
        };

        // Same deep import as bootstrap.js - the package's bare "main" resolves to a
        // different, non-jQuery build.
        require('bootstrap5-toggle/js/bootstrap5-toggle.jquery.min.js');
        document.body.innerHTML = '<input type="checkbox" data-toggle="toggle">';

        expect(() => $('input[type=checkbox]').bootstrapToggle()).not.toThrow();
    });
});

describe('ion-rangeslider (#3590)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('ionRangeSlider_givenInput_initialisesWithoutThrowing', () => {
        require('ion-rangeslider');
        document.body.innerHTML = '<input id="rating-slider" />';

        expect(() => $('#rating-slider').ionRangeSlider({grid: true, min: 1, max: 10}))
            .not.toThrow();
    });
});

describe('intro.js (#3590)', () => {
    test('introJs_givenStart_doesNotThrowSynchronously', () => {
        const introJs = require('intro.js');

        expect(() => introJs().start()).not.toThrow();
    });
});

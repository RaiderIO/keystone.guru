// ---------------------------------------------------------------------------
// Regression coverage for #3590, a follow-up audit to #3588/#3589. jQuery 4.0
// (#3560 bumped jQuery 3.6.1 -> 4.0.0) removed several long-deprecated global
// helpers, and one bundled plugin (`jquery-bar-rating`) still called one of
// them (`$.isNumeric`), breaking pull lists in production. That plugin has since
// been replaced by a self-owned widget and its `$.isNumeric` shim removed
// (#3593), and `jquery-visible` has since been replaced by a native
// `getBoundingClientRect()` check and dropped entirely (#3594). `password-strength-meter`
// was also replaced by a self-owned widget (#3597, unmaintained upstream) and dropped from
// here since it's no longer bundled, and so was `lightslider` (#3595, replaced by the
// self-owned $.fn.thumbnailCarousel - see thumbnail-carousel.test.js). This audit remains to
// guard the OTHER bundled plugins below.
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

describe('bootstrap5-toggle (#3590)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('bootstrapToggle_givenCheckbox_initialisesWithoutThrowing', async () => {
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

        // The plugin only builds its widget once the checkbox's parent has a size, and defers
        // to the ResizeObserver above otherwise; jsdom lays out nothing, so give the parent one
        // or the render path - most of the plugin - never runs.
        vi.spyOn(HTMLElement.prototype, 'offsetWidth', 'get').mockReturnValue(100);
        vi.spyOn(HTMLElement.prototype, 'offsetHeight', 'get').mockReturnValue(30);

        // Same deep import as bootstrap.js - the package's bare "main" resolves to a
        // different, non-jQuery build.
        require('bootstrap5-toggle/js/bootstrap5-toggle.jquery.min.js');
        document.body.innerHTML = '<input type="checkbox" data-toggle="toggle">';

        expect(() => $('input[type=checkbox]').bootstrapToggle()).not.toThrow();
        // ...and really initialised: the checkbox sits inside the plugin's toggle widget, which
        // the plugin builds asynchronously after bootstrapToggle() returns
        await vi.waitFor(() => {
            expect(document.querySelector('input[type=checkbox]').closest('.toggle')).not.toBeNull();
        });
    });
});

describe('nouislider (#3596)', () => {
    // ion-rangeslider (audited above under #3590) was replaced by nouislider + the
    // self-owned resources/assets/js/range-slider.js adapter (see range-slider.test.js
    // for adapter-level coverage). This block only exercises the raw vendor package,
    // mirroring the other blocks in this file.
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('noUiSlider_givenDiv_initialisesWithoutThrowing', () => {
        const noUiSlider = require('nouislider');
        document.body.innerHTML = '<div id="rating-slider"></div>';

        expect(() => noUiSlider.create(document.getElementById('rating-slider'), {
            start: [1],
            range: {min: 1, max: 10},
        })).not.toThrow();
        expect(document.getElementById('rating-slider').noUiSlider.get()).toBe('1.00');
    });
});

describe('intro.js (#3590)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('introJs_givenStart_doesNotThrowSynchronously', () => {
        const introJs = require('intro.js');

        expect(() => introJs().start()).not.toThrow();
    });

    test('introJs_givenADataIntroStep_startsTheTourOnIt', () => {
        // Arrange: the map's tutorial button marks its steps with data-intro attributes (map.js)
        const introJs = require('intro.js');
        document.body.innerHTML = '<div id="editsidebar" data-intro="The sidebar" data-step="1" data-position="right"></div>';

        // Act
        introJs().start();

        // Assert
        expect(document.querySelector('.introjs-overlay')).not.toBeNull();
        expect(document.querySelector('.introjs-tooltiptext').textContent).toBe('The sidebar');
    });
});

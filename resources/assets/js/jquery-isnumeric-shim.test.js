// ---------------------------------------------------------------------------
// Regression test for #3588. Dependabot bumped jQuery 3.6.1 -> 4.0.0 (#3560),
// and jQuery 4.0 removed the deprecated `$.isNumeric()`. The bundled
// `jquery-bar-rating` plugin still calls `$.isNumeric()` internally, so it threw
// "t.isNumeric is not a function" during map activation, aborting map init and
// leaving the pull lists unrendered on production (v15.6.0).
//
// The fix installs a `$.isNumeric` shim in resources/assets/js/bootstrap.js
// before any jQuery plugin loads. This test mirrors that shim and asserts, against
// the real jQuery 4 + jquery-bar-rating, that the plugin throws without it and
// initialises cleanly with it.
// ---------------------------------------------------------------------------

const $ = require('jquery');
require('jquery-bar-rating');

/**
 * Mirrors the `$.isNumeric` shim installed in resources/assets/js/bootstrap.js.
 *
 * @param {*} obj
 * @returns {boolean}
 */
function isNumericShim(obj) {
    const type = typeof obj;

    return (type === 'number' || type === 'string') && !isNaN(obj - parseFloat(obj));
}

/**
 * @param {string} id
 * @returns {HTMLSelectElement}
 */
function createRatingSelect(id) {
    document.body.innerHTML = `<select id="${id}">
        <option value="1">1</option>
        <option value="2" selected>2</option>
        <option value="3">3</option>
    </select>`;

    return document.querySelector(`#${id}`);
}

describe('jQuery $.isNumeric shim (#3588)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('isNumericShim_givenVariousInputs_matchesJQuerySemantics', () => {
        expect(isNumericShim(5)).toBe(true);
        expect(isNumericShim('5')).toBe(true);
        expect(isNumericShim(-1.5)).toBe(true);
        expect(isNumericShim('5px')).toBe(false);
        expect(isNumericShim('')).toBe(false);
        expect(isNumericShim(NaN)).toBe(false);
        expect(isNumericShim(Infinity)).toBe(false);
        expect(isNumericShim(null)).toBe(false);
        expect(isNumericShim(true)).toBe(false);
    });

    test('isNumeric_givenJQuery4_isRemoved', () => {
        // Documents the root cause: jQuery 4.0 no longer ships $.isNumeric.
        expect(typeof $.fn.jquery).toBe('string');
        expect($.fn.jquery.startsWith('4.')).toBe(true);
    });

    test('barrating_givenNoIsNumeric_throwsIsNotAFunction', () => {
        const original = $.isNumeric;
        delete $.isNumeric;
        createRatingSelect('rating_without_shim');

        try {
            expect(() => $('#rating_without_shim').barrating({theme: 'fontawesome-stars'}))
                .toThrow(/isNumeric is not a function/);
        } finally {
            if (original !== undefined) {
                $.isNumeric = original;
            }
        }
    });

    test('barrating_givenIsNumericShim_initialisesWithoutThrowing', () => {
        $.isNumeric = isNumericShim;
        const select = createRatingSelect('rating_with_shim');

        try {
            expect(() => $('#rating_with_shim').barrating({theme: 'fontawesome-stars'})).not.toThrow();
            // The plugin replaces the <select> with its own widget wrapper on success.
            expect(document.querySelector('.br-widget')).not.toBeNull();
        } finally {
            $(select).barrating('destroy');
            delete $.isNumeric;
        }
    });
});

// ---------------------------------------------------------------------------
// Covers $.fn.rangeSlider (#3596), the self-owned noUiSlider-backed
// replacement for the unmaintained `ion-rangeslider` plugin. Real user
// interaction is exercised via a keyboard arrow-key press on the handle
// (noUiSlider fires the same slide/update/change/set sequence for keyboard
// interaction as it does for a pointer drag release - see
// eventKeydown() in node_modules/nouislider/dist/nouislider.js), which lets
// these tests assert the real `change` event without simulating a pointer
// drag.
// ---------------------------------------------------------------------------

const $ = require('jquery');
require('./range-slider');

/**
 * @param {string} [value]
 * @returns {HTMLInputElement}
 */
function createInput(value = '') {
    document.body.innerHTML = `<input id="range_input" type="text" value="${value}">`;
    return document.querySelector('#range_input');
}

/**
 * @param {HTMLInputElement} input
 * @returns {HTMLElement}
 */
function getHandle(input) {
    return $(input).next('.range-slider').find('.noUi-handle')[0];
}

/**
 * Simulates real user interaction (not a programmatic .update()/.set()) by
 * pressing the right-arrow key on a slider handle.
 *
 * @param {HTMLElement} handle
 */
function pressArrowRight(handle) {
    handle.dispatchEvent(new KeyboardEvent('keydown', {key: 'ArrowRight', bubbles: true}));
}

describe('$.fn.rangeSlider (#3596)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('rangeSlider_givenSingleType_hidesInputAndCreatesWrapperDiv', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 10});

        expect(input.style.display).toBe('none');
        expect(document.querySelectorAll('.range-slider').length).toBe(1);
    });

    test('rangeSlider_givenSingleTypeWithFrom_setsInputValueToFrom', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 10, from: 7});

        expect(input.value).toBe('7');
    });

    test('rangeSlider_givenDoubleTypeWithFromTo_setsInputValueToFromSemicolonTo', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'double', min: 0, max: 100, from: 20, to: 80});

        expect(input.value).toBe('20;80');
    });

    test('rangeSlider_givenNoFromToButExistingInputValue_usesInputValueAsStart', () => {
        // Mirrors admin/npchealth/edit.js and simulate.js: the input is pre-filled
        // from a cookie, then initialised with no `from`.
        const input = createInput('15');

        $(input).rangeSlider({type: 'single', min: 1, max: 40});

        expect(input.value).toBe('15');
    });

    test('rangeSlider_givenNonIntegerStep_formatsValueToStepPrecision', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 1, step: 0.01, from: 0.25});

        expect(input.value).toBe('0.25');
    });

    test('rangeSlider_givenInit_rendersTooltip', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 10, from: 5});

        expect(document.querySelectorAll('.noUi-tooltip').length).toBe(1);
        expect(document.querySelector('.noUi-tooltip').textContent).toBe('5');
    });

    test('rangeSlider_givenGridTrue_rendersPipsAndReservesLayoutSpace', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 10, grid: true});

        expect(document.querySelectorAll('.noUi-pips').length).toBe(1);
        // The pip labels are positioned absolutely below the track, so the wrapper needs a
        // dedicated class (range-slider.css) reserving space, or normal-flow content right
        // after the slider paints over them - see range-slider.css.
        expect($(input).next('.range-slider').hasClass('range-slider--has-pips')).toBe(true);
    });

    test('rangeSlider_givenGridOmitted_rendersNoPips', () => {
        // Mirrors HeatOptionMinOpacityHandler, the one handler that omits grid.
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 1, step: 0.01});

        expect(document.querySelectorAll('.noUi-pips').length).toBe(0);
    });

    test('rangeSlider_givenExtraClasses_appliedToWrapper', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 1, max: 10, extra_classes: 'inverse'});

        expect($(input).next('.range-slider').hasClass('inverse')).toBe(true);
    });

    test('rangeSlider_givenRealUserInteraction_firesOnFinishAndNativeChange', () => {
        const input = createInput();
        const onFinish = vi.fn();
        const onChange = vi.fn();
        $(input).on('change', onChange);

        $(input).rangeSlider({type: 'single', min: 0, max: 10, from: 5, onFinish});
        pressArrowRight(getHandle(input));

        expect(onFinish).toHaveBeenCalledTimes(1);
        // Numeric, not the formatted string $input.val() gets - matches ion-rangeslider's
        // own onFinish contract, even though no current caller reads the payload.
        expect(onFinish).toHaveBeenCalledWith({from: 6});
        expect(onChange).toHaveBeenCalledTimes(1);
        expect(input.value).toBe('6');
    });

    test('rangeSlider_givenProgrammaticUpdate_doesNotFireOnFinishOrNativeChange', () => {
        // The key invariant: ion-rangeslider's own .update() suppresses onFinish
        // (its is_update guard) - only real user interaction should fire it.
        // noUiSlider's `set` event fires on programmatic set()/updateOptions() too,
        // so onFinish must be bound to `change`, not `set`.
        const input = createInput();
        const onFinish = vi.fn();
        const onChange = vi.fn();
        $(input).on('change', onChange);

        $(input).rangeSlider({type: 'single', min: 0, max: 10, from: 5, onFinish});
        $(input).data('rangeSlider').update({min: 0, max: 20, from: 12});

        expect(onFinish).not.toHaveBeenCalled();
        expect(onChange).not.toHaveBeenCalled();
        expect(input.value).toBe('12');
    });

    test('rangeSlider_givenUpdateOnDoubleType_setsRangeAndFromTo', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'double', min: 0, max: 100, from: 20, to: 80});
        $(input).data('rangeSlider').update({min: 0, max: 200, from: 40, to: 160});

        expect(input.value).toBe('40;160');
    });

    test('rangeSlider_givenUpdateOnSingleTypeWithStrayTo_ignoresTo', () => {
        // Mirrors MinSamplesRequiredHandler/filterinputrating.js: both pass a `to`
        // into a single-type slider's update(), where it's a pre-existing no-op.
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 100, from: 10});
        $(input).data('rangeSlider').update({min: 0, max: 100, from: 30, to: 90});

        expect(input.value).toBe('30');
    });

    test('rangeSlider_givenRepeatInit_doesNotDuplicateWidget', () => {
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 10, from: 5});
        $(input).rangeSlider({type: 'single', min: 0, max: 10, from: 5});

        expect(document.querySelectorAll('.range-slider').length).toBe(1);
    });

    test('rangeSlider_givenGridTrue_rendersOnlyTheFiveLabelledPips', () => {
        // noUiSlider auto-fills unlabelled sub-pips between explicit positions (its `density`
        // option) unless filtered out - regression coverage for reintroducing the tick clutter
        // ion-rangeslider's deleted `.irs-grid-pol.small { height: 0; }` rule used to suppress.
        const input = createInput();

        $(input).rangeSlider({type: 'single', min: 0, max: 40, grid: true});

        expect(document.querySelectorAll('.noUi-pips .noUi-value').length).toBe(5);
    });

    test('rangeSlider_givenNonNumericExistingInputValue_fallsBackToMinInsteadOfNaN', () => {
        // Guards the fallback-to-.val() path (admin/npchealth/edit.js, simulate.js): malformed/
        // stale input content must not flow a NaN start into noUiSlider.
        const input = createInput('not-a-number');

        $(input).rangeSlider({type: 'single', min: 3, max: 40});

        expect(input.value).toBe('3');
    });

    test('rangeSlider_givenUpdateWithEmptyStringTo_treatsAsAbsentInsteadOfZero', () => {
        // Regression coverage: SearchFilter*.setValue(value) passes URL query params straight
        // through as e.g. value.split(';')[1], unsanitised (searchinlinebase.js). A malformed
        // URL like ?item_level=20; yields to: '', which must not snap the upper handle to 0.
        const input = createInput();

        $(input).rangeSlider({type: 'double', min: 0, max: 100, from: 20, to: 80});
        $(input).data('rangeSlider').update({from: '30', to: ''});

        expect(input.value).toBe('30;80');
    });

    test('rangeSlider_givenUpdateWithNoMinMax_skipsUpdateOptionsCall', () => {
        // Every SearchFilter*.setValue() call passes only from/to, never min/max - confirm the
        // range isn't redundantly reprocessed (and, concretely, that it's left untouched).
        const input = createInput();

        $(input).rangeSlider({type: 'double', min: 0, max: 100, from: 20, to: 80});
        $(input).data('rangeSlider').update({from: 30, to: 90});

        expect($(input).data('rangeSlider').noUiSlider.options.range).toEqual({min: 0, max: 100});
        expect(input.value).toBe('30;90');
    });
});

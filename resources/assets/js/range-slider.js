// ---------------------------------------------------------------------------
// $.fn.rangeSlider (#3596)
//
// Self-owned adapter around the actively-maintained `nouislider` package,
// replacing the unmaintained `ion-rangeslider` npm plugin (last release 2019).
// It preserves the exact call shape every existing handler class/filter class
// already used:
//
//     this.rangeSlider = $(selector).rangeSlider($.extend({...}, options)).data('rangeSlider');
//     ...
//     this.rangeSlider.update({min, max, from, to});
//
// noUiSlider mounts onto a plain DOM node it owns (it manages that node's
// innerHTML), unlike ion-rangeslider which could attach directly to an
// <input>. So this adapter hides the original <input> and injects a sibling
// <div class="range-slider"> for noUiSlider to own, and keeps the <input>'s
// value in sync as the exact "from;to" / "from" string every consumer
// already reads via .val() (form serialization, SearchFilterInput.getValue(),
// simulate.js._getData(), cookie persistence, etc.) - no blade changes needed.
//
// Two behavioural details that mirror ion-rangeslider exactly, not
// incidentally:
//   - `onFinish` is wired to noUiSlider's `change` event, never `set`/`update`.
//     ion's own `.update()` explicitly suppresses onChange/onFinish (its
//     `is_update` guard) - onFinish only ever fires from real user
//     interaction. noUiSlider's `set` event fires on programmatic
//     set()/updateOptions() too, `change` does not - so `change` is the only
//     matching event. Binding to `set` would spuriously re-fire onFinish
//     (e.g. a search re-fetch) every time a filter's setValue() restores a
//     value from a URL param/cookie.
//   - the `change` handler also triggers a native DOM `change` event on the
//     input, not just `.val()`: simulate.js persists slider state purely via
//     `$('#simulate_x').on('change', ...)`, with no `onFinish` at all, so a
//     `.val()`-only sync would silently break that persistence.
// ---------------------------------------------------------------------------

const $ = require('jquery');
const noUiSlider = require('nouislider');

/**
 * Derives a noUiSlider formatter (used for the input value, tooltips, and pip
 * labels alike) from a slider's `step`, so e.g. step 0.01/0.1/0.5/1 all round
 * the same way ion-rangeslider did. noUiSlider's own default formatter always
 * emits 2-decimal strings regardless of step, which would leak into
 * `$input.val()`, tooltip text, and visible filter-header text.
 *
 * @param {number} step
 * @returns {{to: function(number): string, from: function(string): number}}
 */
function formatterForStep(step) {
    const decimals = (String(step).split('.')[1] || '').length;
    return {
        to: (value) => decimals > 0 ? Number(value).toFixed(decimals) : String(Math.round(value)),
        from: (value) => Number(value),
    };
}

/**
 * @param value
 * @returns {Array}
 */
function toArray(value) {
    return Array.isArray(value) ? value : [value];
}

/**
 * Parses the "from;to" / "from" format every consumer already writes/reads
 * via $input.val() into numeric start values.
 *
 * @param {*} raw
 * @param {boolean} isDouble
 * @returns {[number|undefined, number|undefined]}
 */
function parseInputValue(raw, isDouble) {
    if (raw === undefined || raw === null || raw === '') {
        return [undefined, undefined];
    }

    const parts = String(raw).split(';');
    const from = toNumberOrUndefined(parts[0]);
    const to = isDouble ? toNumberOrUndefined(parts[1]) : undefined;

    return [from, to];
}

/**
 * `undefined`/`''`/non-numeric text all mean "no value" here, never `NaN` - an unguarded
 * `Number(x)` would otherwise flow a `NaN` start/target straight into noUiSlider (`??` only
 * substitutes `null`/`undefined`, not `NaN`), silently leaving a handle unpositioned.
 *
 * @param {*} value
 * @returns {number|undefined}
 */
function toNumberOrUndefined(value) {
    if (value === undefined || value === null || value === '') {
        return undefined;
    }

    const parsed = Number(value);

    return Number.isNaN(parsed) ? undefined : parsed;
}

/**
 * @param {jQuery} $input
 * @param {boolean} isDouble
 * @param {Array} values
 */
function syncInputValue($input, isDouble, values) {
    $input.val(isDouble ? `${values[0]};${values[1]}` : `${values[0]}`);
}

/**
 * Turns each matched hidden `<input>` into a noUiSlider-backed range slider,
 * mirroring ion-rangeslider's `type`/`min`/`max`/`from`/`to`/`step`/`grid`/
 * `grid_snap`/`extra_classes`/`onFinish` options.
 *
 * @param {{type?: 'single'|'double', min: number, max: number, from?: number,
 *          to?: number, step?: number, grid?: boolean, grid_snap?: boolean,
 *          extra_classes?: string, onFinish?: function}} options
 * @returns {jQuery}
 */
$.fn.rangeSlider = function (options) {
    const settings = $.extend({type: 'single', step: 1}, options);

    return this.each(function () {
        const $input = $(this);

        // Never initialise the same input twice - a second call would otherwise
        // inject a second slider div next to the already-hidden original.
        if ($input.data('rangeSlider')) {
            return;
        }

        const isDouble = settings.type === 'double';
        const isInverse = (settings.extra_classes || '').split(/\s+/).includes('inverse');
        const formatter = formatterForStep(settings.step);

        // When the caller doesn't pass from/to at all, fall back to the input's
        // existing value as the start position (mirrors ion-rangeslider): e.g.
        // admin/npchealth/edit.js and simulate.js pre-fill the input from a
        // cookie, then init with no `from`, relying on that value being read.
        let [initialFrom, initialTo] = [settings.from, settings.to];
        if (initialFrom === undefined && initialTo === undefined) {
            [initialFrom, initialTo] = parseInputValue($input.val(), isDouble);
        }

        const $wrapper = $('<div>', {'class': 'range-slider'});
        if (settings.extra_classes) {
            $wrapper.addClass(settings.extra_classes);
        }
        if (settings.grid) {
            // noUiSlider's pip labels are positioned absolutely below the track, so unlike
            // ion-rangeslider's grid (which sized its own wrapper to include it), they don't
            // contribute to this element's height - without reserved space, normal-flow content
            // right after the slider (e.g. the next filter card) paints over them. See
            // range-slider.css.
            $wrapper.addClass('range-slider--has-pips');
        }
        $input.hide().after($wrapper);

        const sliderApi = noUiSlider.create($wrapper[0], {
            start: isDouble
                ? [initialFrom ?? settings.min, initialTo ?? settings.max]
                : [initialFrom ?? settings.min],
            connect: isDouble ? true : (isInverse ? 'upper' : 'lower'),
            range: {min: settings.min, max: settings.max},
            step: settings.step,
            format: formatter,
            tooltips: isDouble ? [formatter, formatter] : [formatter],
            // Ion's `grid` shows a handful of evenly-spaced auto ticks, not one per
            // `step` - a fixed 5-position pip set avoids absurdly dense pips on
            // sliders with a tiny step (e.g. opacity's step: 0.01). noUiSlider
            // otherwise auto-fills unlabelled sub-pips between them (its `density`
            // option); the filter keeps only the 5 explicit, labelled positions
            // (pip type 1 = LargeValue), reproducing the deleted
            // `.irs-grid-pol.small { height: 0; }` rule's effect of suppressing
            // that sub-grid clutter.
            pips: settings.grid ? {
                mode: 'positions',
                values: [0, 25, 50, 75, 100],
                format: formatter,
                filter: (value, type) => type === 1 ? 1 : -1,
            } : undefined,
        });

        syncInputValue($input, isDouble, toArray(sliderApi.get()));

        sliderApi.on('update', (values) => {
            syncInputValue($input, isDouble, values);
        });
        sliderApi.on('change', (values) => {
            syncInputValue($input, isDouble, values);
            $input.trigger('change');

            if (typeof settings.onFinish === 'function') {
                // values are formatter.to() strings (matching $input.val()'s own format);
                // cast back to numbers so onFinish's payload keeps ion-rangeslider's numeric
                // contract, even though no current caller reads it.
                settings.onFinish(isDouble
                    ? {from: Number(values[0]), to: Number(values[1])}
                    : {from: Number(values[0])});
            }
        });

        $input.data('rangeSlider', {
            noUiSlider: sliderApi,
            update(opts) {
                // Callers pass raw strings straight from $(selector).val()/URL query params
                // (e.g. SearchFilter*.setValue()); normalise '' and non-numeric text to "no
                // value" the same way parseInputValue() does, rather than letting them reach
                // noUiSlider as NaN or an empty string (which its formatter reads as 0).
                const from = toNumberOrUndefined(opts.from);
                const to = toNumberOrUndefined(opts.to);
                const min = toNumberOrUndefined(opts.min);
                const max = toNumberOrUndefined(opts.max);

                if (min !== undefined || max !== undefined) {
                    const currentRange = sliderApi.options.range;
                    sliderApi.updateOptions({
                        range: {
                            min: min !== undefined ? min : currentRange.min,
                            max: max !== undefined ? max : currentRange.max,
                        },
                    }, false);
                }

                if (isDouble) {
                    if (from !== undefined || to !== undefined) {
                        const current = toArray(sliderApi.get(true));
                        sliderApi.set([
                            from !== undefined ? from : current[0],
                            to !== undefined ? to : current[1],
                        ]);
                    }
                } else if (from !== undefined) {
                    // A stray `to` on a single-type slider is silently ignored, matching
                    // ion-rangeslider's own behaviour there (filterinputrating.js's
                    // setValue() always passes both, relying on this no-op for `to`).
                    sliderApi.set([from]);
                }
            },
        });
    });
};

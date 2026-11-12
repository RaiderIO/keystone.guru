// ---------------------------------------------------------------------------
// Pickr undefined default colour guard
//
// @simonwep/pickr 1.10.3 initialises a picker in its setup frame with
//
//     that.setColor(opt.default);
//
// and merges options with Object.assign, so an explicit `default: undefined`
// overrides the built-in default and reaches the colour parser, which throws
// ("Cannot read properties of undefined (reading 'match')"). The setup frame
// then dies before 'init' fires and the picker never finishes initialising.
//
// Drop an undefined `default` so Pickr falls back to its built-in default.
// Remove this guard on the upgrade to a release that tolerates it -
// pickr-default-color-fix.test.js then goes red.
// ---------------------------------------------------------------------------

/**
 * Returns a subclass of the given Pickr class that ignores an undefined `default` option.
 *
 * @param {Function} Pickr The Pickr class (`import Pickr from '@simonwep/pickr'`).
 * @returns {Function}
 */
function withPickrDefaultColorFix(Pickr) {
    return class DefaultColorPickr extends Pickr {
        static create = options => new DefaultColorPickr(options);

        constructor(options) {
            const {default: defaultColor, ...optionsWithoutDefault} = options;

            super(typeof defaultColor === 'undefined' ? optionsWithoutDefault : options);
        }
    };
}

module.exports = {withPickrDefaultColorFix};

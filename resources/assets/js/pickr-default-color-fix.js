// ---------------------------------------------------------------------------
// Pickr default colour fix
//
// Upstream bug in @simonwep/pickr 1.10.2: the setup frame initialises a picker with
//
//     that.setColor(that._color?.toHSLA().toString() ?? opt.default);
//
// but `_color` is a class field that starts out as black, never null, so the
// `default` option is never applied. Every picker then opens on black, and its
// button shows black instead of the colour of the object it edits.
//
// Re-apply `default` once the picker has initialised, the way 1.10.1 did. Remove
// this shim on the upgrade to a release that applies `default` itself -
// pickr-default-color-fix.test.js then goes red.
// ---------------------------------------------------------------------------

/**
 * Returns a subclass of the given Pickr class that applies its `default` option on initialisation.
 *
 * @param {Function} Pickr The Pickr class (`import Pickr from '@simonwep/pickr'`).
 * @returns {Function}
 */
function withPickrDefaultColorFix(Pickr) {
    return class DefaultColorPickr extends Pickr {
        static create = options => new DefaultColorPickr(options);

        constructor(options) {
            super(options);

            this.on('init', () => {
                const defaultColor = this.options.default;
                // Pickr's colour parser throws on undefined
                if (typeof defaultColor === 'undefined') {
                    return;
                }

                // Silent, like the setup frame: initialising a picker must not fire 'save'
                if (this.setColor(defaultColor, true) && defaultColor !== null) {
                    this.applyColor(true);
                }
            });
        }
    };
}

module.exports = {withPickrDefaultColorFix};

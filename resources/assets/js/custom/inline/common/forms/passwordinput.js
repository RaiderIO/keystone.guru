/**
 @typedef {Object} CommonFormsPasswordinputOptions
 @property {string} inputSelector  The password input whose contents are revealed.
 @property {string} toggleSelector The button that toggles between hidden and revealed.
 */

/**
 * Lets the user reveal what they typed into a password field, so a typo on a phone keyboard can be
 * caught before submitting. The button keeps one fixed accessible name ("Show password") and
 * reports its state through aria-pressed, the pattern screen readers announce as a toggle.
 *
 * @property {CommonFormsPasswordinputOptions} options
 */
class CommonFormsPasswordinput extends InlineCode {

    activate() {
        super.activate();

        $(this.options.toggleSelector).unbind('click').bind('click', this._toggle.bind(this));
    }

    /**
     * @private
     */
    _toggle() {
        this._setRevealed($(this.options.inputSelector).attr('type') === 'password');
    }

    /**
     * @param {boolean} revealed
     * @private
     */
    _setRevealed(revealed) {
        $(this.options.inputSelector).attr('type', revealed ? 'text' : 'password');

        $(this.options.toggleSelector)
            .attr('aria-pressed', revealed ? 'true' : 'false')
            .find('i')
            .toggleClass('fa-eye', !revealed)
            .toggleClass('fa-eye-slash', revealed);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonFormsPasswordinput};
}

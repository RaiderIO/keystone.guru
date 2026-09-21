/**
 @typedef {Object} DrawerDialogOptions
 @property {string} drawerSelector             The .offcanvas element of the drawer.
 @property {string|null} openButtonSelector    Clicking any matching element opens the drawer.
 @property {string} confirmButtonSelector
 @property {string} statusSelector             Polite live region.
 */

/**
 * A side drawer used as a dialog: it opens, the user does something in its body, and confirms or dismisses it. The
 * drawer's markup and what happens in its body belong to whoever creates one; this only runs the dialog around it.
 */
class DrawerDialog {

    /**
     * @param {DrawerDialogOptions} options
     */
    constructor(options) {
        this.options = options;

        this._shownBefore = false;
        this._onFirstShowCallbacks = [];
        this._onShowCallbacks = [];
        this._onConfirmCallbacks = [];
    }

    activate() {
        let self = this;

        if (this.options.openButtonSelector) {
            $(document).on('click', this.options.openButtonSelector, function (event) {
                event.preventDefault();
                self.open();
            });
        }

        $(this.options.drawerSelector).on('show.bs.offcanvas', function () {
            if (!self._shownBefore) {
                self._shownBefore = true;
                self._onFirstShowCallbacks.forEach(callback => callback());

                return;
            }

            self._onShowCallbacks.forEach(callback => callback());
        });

        $(this.options.confirmButtonSelector).on('click', function () {
            self._onConfirmCallbacks.forEach(callback => callback());
        });
    }

    open() {
        bootstrap.Offcanvas.getOrCreateInstance($(this.options.drawerSelector)[0]).show();
    }

    close() {
        bootstrap.Offcanvas.getOrCreateInstance($(this.options.drawerSelector)[0]).hide();
    }

    /**
     * @returns {boolean}
     */
    hasBeenShown() {
        return this._shownBefore;
    }

    /**
     * @param {Function} callback Called once, when the drawer opens for the first time.
     */
    onFirstShow(callback) {
        this._onFirstShowCallbacks.push(callback);
    }

    /**
     * @param {Function} callback Called every time the drawer opens after the first time.
     */
    onShow(callback) {
        this._onShowCallbacks.push(callback);
    }

    /**
     * @param {Function} callback Called when the user confirms the dialog; closing it is left to the callback.
     */
    onConfirm(callback) {
        this._onConfirmCallbacks.push(callback);
    }

    /**
     * @param {string} text
     * @param {boolean} enabled
     */
    setConfirmButton(text, enabled) {
        $(this.options.confirmButtonSelector).text(text).prop('disabled', !enabled);
    }

    /**
     * @param {string} text Announced to screen readers.
     */
    setStatus(text) {
        $(this.options.statusSelector).text(text);
    }

    /**
     * Fires an event on the drawer element, for a host page that only knows the drawer's selector.
     * @param {string} name
     * @param {Array} parameters
     */
    trigger(name, parameters) {
        $(this.options.drawerSelector).trigger(name, parameters);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {DrawerDialog};
}

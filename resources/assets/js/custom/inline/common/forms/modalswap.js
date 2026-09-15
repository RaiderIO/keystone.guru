/**
 * @typedef {Object} CommonFormsModalswapOptions
 * @property {string} linkSelector The cross-link that should swap modals instead of navigating.
 * @property {string} fromModal    Selector of the modal the link lives in.
 * @property {string} toModal      Selector of the modal to open in its place.
 */

/**
 * Swaps the login modal for the register modal (and back) when the user follows the cross-link
 * between them, instead of letting the link navigate to the standalone page - navigating would
 * throw away the page state (route being edited, live session presence) that the modal exists to
 * preserve in the first place.
 *
 * @property {CommonFormsModalswapOptions} options
 */
class CommonFormsModalswap extends InlineCode {

    activate() {
        super.activate();

        $(this.options.linkSelector).unbind('click').bind('click', this._swap.bind(this));
    }

    /**
     * @param {Event} event
     * @private
     */
    _swap(event) {
        // The cross-link carries a real href to the standalone page, so a modified click (new tab,
        // new window, middle click) must be left to the browser rather than swapped in place
        if (event.ctrlKey || event.metaKey || event.shiftKey || event.altKey || event.which === 2) {
            return;
        }

        event.preventDefault();

        let $fromModal = $(this.options.fromModal);

        // Bootstrap stacks a second backdrop (and never removes the first) when a modal is shown
        // while another is still visible, so wait for the first to finish hiding before showing
        // the second. `one` rather than `on` - a repeated swap must not queue up handlers.
        $fromModal.one('hidden.bs.modal', function () {
            bootstrap.Modal.getOrCreateInstance(document.querySelector(this.options.toModal)).show();
        }.bind(this));

        bootstrap.Modal.getOrCreateInstance($fromModal[0]).hide();
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonFormsModalswap};
}

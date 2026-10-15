/**
 * @typedef {Object} ModalLazyOptions
 * @property {string} id
 * @property {string} ajax_url
 */

/**
 * @property {ModalLazyOptions} options
 */
class ModalLazy extends InlineCode {

    activate() {
        super.activate();

        let self = this;

        this.loaded = false;

        // When the modal is shown
        $(this.options.id).on('shown.bs.modal', function () {
            if (self.loaded) {
                return;
            }

            // request the html from the server using ajax
            $.ajax({
                type: 'GET',
                url: self.options.ajax_url,
                dataType: 'html',
                success: function (data) {
                    $(self.options.id).find('.probootstrap-modal-content').html(data);
                    self.loaded = true;

                    refreshSelectPickers();
                    refreshTooltips();

                    $(self.options.id).trigger('modallazy:loaded');
                }
            })
        });
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {ModalLazy};
}

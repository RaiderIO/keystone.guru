/**
 @typedef {Object} CommonCollectionPublishroutesconfirmOptions
 @property {string} modalSelector
 @property {string} yesButtonSelector
 @property {string} publishUrl
 @property {string} publishedState PublishedState name, e.g. "world". Used to look up its own title translation.
 */

/**
 * The "make them visible too" confirmation shown after a collection's published state was raised. "Yes" raises
 * every route of the collection that is still less visible than it, computed and authorized server-side - this
 * script never posts a list of routes.
 *
 * @property {CommonCollectionPublishroutesconfirmOptions} options
 */
class CommonCollectionPublishroutesconfirm extends InlineCode {

    activate() {
        super.activate();

        $(this.options.yesButtonSelector).on('click', this._onYesClicked.bind(this));
    }

    /**
     * @private
     */
    _onYesClicked() {
        let self = this;
        let $button = $(this.options.yesButtonSelector);

        $button.prop('disabled', true);

        $.ajax({
            type: 'POST',
            url: this.options.publishUrl,
            dataType: 'json',
            success: function (json) {
                bootstrap.Modal.getOrCreateInstance($(self.options.modalSelector)[0]).hide();

                if (json.raised_count > 0) {
                    let stateLabel = lang.get(`js.publish_state_title_${self.options.publishedState}`);
                    let key = json.raised_count === 1
                        ? 'js.collection_publish_routes_confirm_success_one'
                        : 'js.collection_publish_routes_confirm_success_many';

                    showSuccessNotification(lang.get(key, {count: json.raised_count, state: stateLabel}));
                } else {
                    let stateLabel = lang.get(`js.publish_state_title_${self.options.publishedState}`);

                    showInfoNotification(lang.get('js.collection_publish_routes_confirm_none_raised', {state: stateLabel}));
                }
            },
            error: function () {
                $button.prop('disabled', false);

                let stateLabel = lang.get(`js.publish_state_title_${self.options.publishedState}`);

                showErrorNotification(lang.get('js.collection_publish_routes_confirm_save_failed', {state: stateLabel}));
            },
        });
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonCollectionPublishroutesconfirm};
}

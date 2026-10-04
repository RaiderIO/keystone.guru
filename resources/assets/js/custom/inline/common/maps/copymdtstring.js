/**
 * @typedef {Object} CommonMapsCopymdtstringOptions
 * @property {string} buttonSelector
 * @property {string} shareModalSelector
 * @property {boolean} edit
 */

/**
 * Copies the route's MDT string straight from the map header, without going through the share modal.
 *
 * @property {CommonMapsCopymdtstringOptions} options
 */
class CommonMapsCopymdtstring extends InlineCode {
    constructor(id, bladePath, options = {}) {
        super(id, bladePath, options);

        /** @type {Promise<{mdt_string: string, warnings: Array}>|null} */
        this._exportPromise = null;
        this._busy = false;
    }

    activate() {
        super.activate();

        let self = this;
        let $buttons = $(this.options.buttonSelector);

        $buttons.unbind('click').bind('click', function (event) {
            event.preventDefault();
            self.copy($(this));
        });

        // The view page exports from the server cache, so fetching on intent makes the click itself instant
        if (!this.options.edit) {
            $buttons.on('pointerenter focus', function () {
                self._fetchExport().catch(function () {
                });
            });
        }
    }

    /**
     * @param {jQuery} $button
     * @returns {Promise<boolean>} Whether the string ended up on the clipboard.
     */
    async copy($button) {
        if (this._busy) {
            return false;
        }

        this._busy = true;
        this._setButtonState($button, 'busy');

        let exportPromise = this._fetchExport();

        let copied = false;
        try {
            copied = await this._writeToClipboard(exportPromise);
        } catch (exception) {
            // The export request failed; mdtExportAjaxErrorFn has already told the user why
            this._busy = false;
            this._setButtonState($button, 'idle');
            return false;
        }

        this._busy = false;

        if (!copied) {
            this._setButtonState($button, 'idle');
            showWarningNotification(lang.get('js.mdt_string_copy_blocked'));
            let shareModal = document.querySelector(this.options.shareModalSelector);
            if (shareModal !== null) {
                bootstrap.Modal.getOrCreateInstance(shareModal).show();
            }
            return false;
        }

        let json = await exportPromise;
        this._setButtonState($button, 'copied');

        if (json.warnings.length > 0) {
            showWarningNotification(lang.get('js.mdt_string_copied_with_warnings'));
        } else {
            showSuccessNotification(lang.get('js.mdt_string_copied'));
        }

        getState().sendMetricForDungeonRoute(METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, METRIC_TAG_MDT_COPY_VIEW);

        let self = this;
        setTimeout(function () {
            if (!self._busy) {
                self._setButtonState($button, 'idle');
            }
        }, 2000);

        return true;
    }

    /**
     * In edit mode the route changes under the user, so every copy exports afresh.
     *
     * @returns {Promise<{mdt_string: string, warnings: Array}>}
     * @private
     */
    _fetchExport() {
        if (this._exportPromise !== null) {
            return this._exportPromise;
        }

        let self = this;
        let mapContext = getState().getMapContext();
        let promise = new Promise(function (resolve, reject) {
            $.ajax({
                type: 'GET',
                url: self.options.edit ? mapContext.getMdtExportUrlUncached() : mapContext.getMdtExportUrl(),
                dataType: 'json',
                success: resolve,
                error: function (xhr, textStatus, errorThrown) {
                    mdtExportAjaxErrorFn(xhr, textStatus, errorThrown);
                    reject(xhr);
                },
            });
        });

        if (!this.options.edit) {
            this._exportPromise = promise;
            // A failed export may succeed on the next attempt
            promise.catch(function () {
                self._exportPromise = null;
            });
        }

        return promise;
    }

    /**
     * Safari only allows a clipboard write inside the click itself, before any await. Handing ClipboardItem the
     * pending export keeps the write inside the click; browsers without it copy once the export arrived.
     *
     * @param {Promise<{mdt_string: string}>} exportPromise
     * @returns {Promise<boolean>} Rejects when the export itself failed.
     * @private
     */
    async _writeToClipboard(exportPromise) {
        let textPromise = exportPromise.then(function (json) {
            return json.mdt_string;
        });

        if (typeof ClipboardItem !== 'undefined' && navigator.clipboard?.write) {
            let item = new ClipboardItem({
                'text/plain': textPromise.then(function (text) {
                    return new Blob([text], {type: 'text/plain'});
                }),
            });

            try {
                await navigator.clipboard.write([item]);
                return true;
            } catch (exception) {
                // Surface a failed export rather than mistaking it for a blocked clipboard
                await textPromise;
            }
        }

        let text = await textPromise;

        if (navigator.clipboard?.writeText) {
            try {
                await navigator.clipboard.writeText(text);
                return true;
            } catch (exception) {
                // Falls through to execCommand below
            }
        }

        return this._writeWithExecCommand(text);
    }

    /**
     * @param {string} text
     * @returns {boolean}
     * @private
     */
    _writeWithExecCommand(text) {
        let $textarea = $('<textarea readonly class="visually-hidden"></textarea>').val(text);
        $('body').append($textarea);
        $textarea.trigger('select');

        let copied = false;
        try {
            copied = document.execCommand('copy');
        } catch (exception) {
            copied = false;
        }

        $textarea.remove();

        return copied;
    }

    /**
     * @param {jQuery} $button
     * @param {'idle'|'busy'|'copied'} state
     * @private
     */
    _setButtonState($button, state) {
        let $icon = $button.find('.copy_mdt_string_icon');
        let $label = $button.find('.copy_mdt_string_label');

        if ($label.data('idleLabel') === undefined) {
            $label.data('idleLabel', $label.text());
        }

        $button.toggleClass('disabled', state === 'busy')
            .attr('aria-busy', state === 'busy' ? 'true' : 'false');

        $icon.removeClass('far fa-copy fas fa-check fa-circle-notch fa-spin');

        if (state === 'busy') {
            $icon.addClass('fas fa-circle-notch fa-spin');
            $label.text(lang.get('js.copy_mdt_string_copying'));
        } else if (state === 'copied') {
            $icon.addClass('fas fa-check');
            $label.text(lang.get('js.copy_mdt_string_copied'));
        } else {
            $icon.addClass('far fa-copy');
            $label.text($label.data('idleLabel'));
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonMapsCopymdtstring};
}

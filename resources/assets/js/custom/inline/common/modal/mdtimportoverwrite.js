/**
 * @typedef {Object} CommonModalMdtimportoverwriteOptions
 * @property {string} modalSelector
 * @property {string} importStringTextAreaSelector
 * @property {string} loaderSelector
 * @property {string} detailsSelector
 * @property {string} warningsSelector
 * @property {string} errorsSelector
 * @property {string} resetSelector
 * @property {string} discardExistingDraftSelector
 * @property {string} submitSelector
 * @property {boolean} hasPendingDraft
 * @property {string} detailsUrl
 * @property {string} importUrl
 */

/**
 * Pastes an MDT string, previews it through the MDT details endpoint, and imports it as a draft of the route
 * being edited. The server answers with the draft's edit page, which is where the author reviews and applies it.
 *
 * @property {CommonModalMdtimportoverwriteOptions} options
 */
class CommonModalMdtimportoverwrite extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);

        /** @type {string|null} The pasted string, once its preview came back without errors */
        this._previewedImportString = null;
        this._submitting = false;
    }

    /**
     *
     */
    activate() {
        super.activate();

        let self = this;

        $(this.options.modalSelector).on('hidden.bs.modal', this._reset.bind(this));
        $(this.options.importStringTextAreaSelector).unbind('paste').bind('paste', function (pasteEvent) {
            self._importStringPasted(pasteEvent.originalEvent.clipboardData.getData('text'));
        });
        $(this.options.resetSelector).unbind('click').bind('click', this._reset.bind(this));
        $(this.options.discardExistingDraftSelector).unbind('change').bind('change', this._refreshSubmit.bind(this));
        $(this.options.submitSelector).unbind('click').bind('click', this._submit.bind(this));
    }

    /**
     * @param {string} importString
     * @private
     */
    _importStringPasted(importString) {
        let self = this;

        // Deferred so the pasted value lands before the field locks
        setTimeout(function () {
            $(self.options.importStringTextAreaSelector).prop('disabled', true);
        }, 10);

        $.ajax({
            type: 'POST',
            url: this.options.detailsUrl,
            dataType: 'json',
            data: {
                'import_string': importString
            },
            beforeSend: function () {
                $(self.options.loaderSelector).show();
            },
            complete: function () {
                $(self.options.loaderSelector).hide();
            },
            success: function (responseData) {
                self._renderDetails(responseData);

                self._previewedImportString = responseData.errors.length === 0 ? importString : null;
                $(self.options.resetSelector).show();
                self._refreshSubmit();
            },
            error: function (xhr, textStatus, errorThrown) {
                self._reset();

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    /**
     * @param {Object} responseData The MDT details endpoint's answer
     * @private
     */
    _renderDetails(responseData) {
        let detailsTemplate = Handlebars.templates['import_string_details_template'];

        let details = [];
        details.push({key: lang.get('js.mdt_dungeon'), value: responseData.dungeon});
        details.push({key: lang.get('js.mdt_pulls'), value: responseData.pulls});
        details.push({key: lang.get('js.mdt_paths'), value: responseData.paths});
        details.push({key: lang.get('js.mdt_drawn_lines'), value: responseData.lines});
        details.push({key: lang.get('js.mdt_arrows'), value: responseData.arrows});
        details.push({key: lang.get('js.mdt_notes'), value: responseData.notes});
        details.push({
            key: lang.get('js.mdt_enemy_forces'),
            value: `${responseData.enemy_forces}/${responseData.enemy_forces_max}`
        });

        $(this.options.detailsSelector).html(detailsTemplate($.extend({}, getHandlebarsDefaultVariables(), {
            details: details
        })));

        if (responseData.warnings.length > 0) {
            (new MdtStringNoticesWarnings(responseData.warnings)).render($(this.options.warningsSelector));
        }
        if (responseData.errors.length > 0) {
            (new MdtStringNoticesErrors(responseData.errors)).render($(this.options.errorsSelector));
        }

        refreshTooltips();
    }

    /**
     * @returns {boolean} Whether the import may be submitted: a previewed, error free string, and - when the route
     *                    already has a draft - the author's confirmation that it may be discarded.
     */
    canSubmit() {
        if (this._previewedImportString === null || this._submitting) {
            return false;
        }

        return !this.options.hasPendingDraft || $(this.options.discardExistingDraftSelector).is(':checked');
    }

    /**
     * @returns {{import_string: string, discard_existing_draft: number}}
     */
    buildImportPayload() {
        return {
            'import_string': this._previewedImportString,
            'discard_existing_draft': this.options.hasPendingDraft && $(this.options.discardExistingDraftSelector).is(':checked') ? 1 : 0
        };
    }

    /**
     * @private
     */
    _refreshSubmit() {
        $(this.options.submitSelector).prop('disabled', !this.canSubmit());
    }

    /**
     * @private
     */
    _submit() {
        if (!this.canSubmit()) {
            return;
        }

        let self = this;
        this._submitting = true;
        this._refreshSubmit();

        $.ajax({
            type: 'POST',
            url: this.options.importUrl,
            dataType: 'json',
            data: this.buildImportPayload(),
            beforeSend: function () {
                $(self.options.loaderSelector).show();
            },
            complete: function () {
                $(self.options.loaderSelector).hide();
            },
            success: function (json) {
                window.location.href = json.redirect_url;
            },
            error: function (xhr, textStatus, errorThrown) {
                self._submitting = false;
                self._refreshSubmit();

                defaultAjaxErrorFn(xhr, textStatus, errorThrown);
            }
        });
    }

    /**
     * Makes the modal accept a new paste.
     * @private
     */
    _reset() {
        this._previewedImportString = null;
        this._submitting = false;

        $(this.options.importStringTextAreaSelector).removeAttr('disabled').val('');
        $(this.options.detailsSelector).html('');
        $(this.options.warningsSelector).html('');
        $(this.options.errorsSelector).html('');
        $(this.options.resetSelector).hide();

        this._refreshSubmit();
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonModalMdtimportoverwrite};
}

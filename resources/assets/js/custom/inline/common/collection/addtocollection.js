/**
 @typedef {Object} CommonCollectionAddtocollectionOptions
 @property {string} triggerSelector    Elements carrying a data-publickey that open the dialog for that route.
 @property {string} modalSelector
 @property {string} statusSelector
 @property {string} listSelector
 @property {string} newSelector
 @property {string} forDungeonRouteUrl
 */

/**
 * The "Add to collection…" dialog: the current user's collections as checkboxes for one of their own routes. A checkbox
 * saves at once and shows a toast from which the change can be undone. Collections the route may not join are shown
 * disabled with the reason, and the dialog ends with "New collection with this route…".
 *
 * @property {CommonCollectionAddtocollectionOptions} options
 */
class CommonCollectionAddtocollection extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);

        /** @type {string|null} */
        this._publicKey = null;
        /** @type {AddToCollectionRow[]} */
        this._rows = [];
    }

    activate() {
        super.activate();

        let self = this;

        // Delegated, so rows a table renders later open the dialog as well
        $(document).on('click', this.options.triggerSelector, function (event) {
            event.preventDefault();

            self.open(`${$(this).data('publickey')}`);
        });
    }

    /**
     * @param {string} publicKey
     */
    open(publicKey) {
        let self = this;

        this._publicKey = publicKey;
        this._rows = [];

        $(this.options.statusSelector).text(lang.get('js.add_to_collection_loading'));
        $(this.options.listSelector).empty().prop('hidden', true);
        $(this.options.newSelector).empty();

        bootstrap.Modal.getOrCreateInstance($(this.options.modalSelector)[0]).show();

        $.ajax({
            type: 'GET',
            url: this.options.forDungeonRouteUrl,
            dataType: 'json',
            data: {dungeon_route: publicKey},
            success: function (json) {
                // Another route may have been opened in the meantime
                if (self._publicKey !== publicKey) {
                    return;
                }

                self._rows = json.collections.map(collection => new AddToCollectionRow(collection));
                self._render(json);
            },
            error: function () {
                $(self.options.statusSelector).text(lang.get('js.add_to_collection_load_failed'));
            },
        });
    }

    /**
     * @param {Object} json As returned by the collections endpoint.
     * @private
     */
    _render(json) {
        let self = this;
        let $list = $(this.options.listSelector);

        $(this.options.statusSelector).text(this._rows.length === 0 ? lang.get('js.add_to_collection_no_collections') : '');

        $list.empty().prop('hidden', this._rows.length === 0);
        this._rows.forEach(function (row) {
            $list.append(self._renderRow(row));
        });

        this._renderNewCollection(json);
    }

    /**
     * @param {AddToCollectionRow} row
     * @returns {jQuery}
     * @private
     */
    _renderRow(row) {
        let self = this;
        let $row = $(Handlebars.templates['add_to_collection_row'](row.toTemplateData()));

        $row.find('.add_to_collection_checkbox').on('change', function () {
            self._onToggled(row, $(this).is(':checked'));
        });

        return $row;
    }

    /**
     * @param {Object} json
     * @private
     */
    _renderNewCollection(json) {
        let $new = $(this.options.newSelector).empty();
        let $icon = $('<i>', {'class': 'fas fa-plus'});
        let text = ` ${lang.get('js.add_to_collection_new_collection')}`;

        if (json.may_create) {
            $new.append($('<a>', {'class': 'btn btn-outline-success w-100', href: json.create_url})
                .append($icon, document.createTextNode(text)));

            return;
        }

        $new.append($('<button>', {type: 'button', 'class': 'btn btn-outline-secondary w-100', disabled: true})
            .append($icon, document.createTextNode(text)));
        $new.append($('<small>', {
            'class': 'd-block text-warning mt-1',
            text: lang.get('js.add_to_collection_max_collections', {max: json.max_collections}),
        }));
    }

    /**
     * @param {AddToCollectionRow} row
     * @param {boolean} isChecked
     * @private
     */
    _onToggled(row, isChecked) {
        let self = this;
        let publicKey = this._publicKey;

        this._setContainsDungeonRoute(row, isChecked, publicKey, function () {
            let text = lang.get(isChecked ? 'js.add_to_collection_added' : 'js.add_to_collection_removed', {name: self._escape(row.name)});

            self._showUndoableToast(text, function () {
                self._setContainsDungeonRoute(row, !isChecked, publicKey, function () {
                    showInfoNotification(lang.get('js.add_to_collection_undone'));
                });
            });
        });
    }

    /**
     * Adds the route to, or removes it from, the collection and updates its row once saved; a failed save restores
     * the row.
     *
     * @param {AddToCollectionRow} row
     * @param {boolean} containsDungeonRoute
     * @param {string} publicKey
     * @param {Function} onSaved
     * @private
     */
    _setContainsDungeonRoute(row, containsDungeonRoute, publicKey, onSaved) {
        let self = this;

        // One change at a time per collection, so an add and a remove can never race each other
        if (row.isSaving) {
            return;
        }

        row.isSaving = true;
        this._replaceRow(row, publicKey);

        $.ajax({
            type: containsDungeonRoute ? 'POST' : 'DELETE',
            url: containsDungeonRoute ? row.storeUrl : row.deleteUrl,
            dataType: 'json',
            data: {dungeon_routes: [publicKey]},
            success: function () {
                row.isSaving = false;
                row.setContainsDungeonRoute(containsDungeonRoute);

                self._replaceRow(row, publicKey);
                onSaved();
            },
            error: function (xhr) {
                row.isSaving = false;
                showErrorNotification(self._escape(self._getErrorMessage(xhr)));
                self._replaceRow(row, publicKey);
            },
        });
    }

    /**
     * @param {AddToCollectionRow} row
     * @param {string} publicKey The route the change was made for; the dialog may show another route by now.
     * @private
     */
    _replaceRow(row, publicKey) {
        if (this._publicKey !== publicKey) {
            return;
        }

        $(this.options.listSelector)
            .children()
            .filter((index, element) => $(element).attr('data-public-key') === row.publicKey)
            .replaceWith(this._renderRow(row));
    }

    /**
     * @param {Object} xhr
     * @returns {string}
     * @private
     */
    _getErrorMessage(xhr) {
        let errors = xhr?.responseJSON?.errors;
        if (errors) {
            for (let key in errors) {
                if (errors.hasOwnProperty(key) && errors[key].length > 0) {
                    return errors[key][0];
                }
            }
        }

        return lang.get('js.add_to_collection_save_failed');
    }

    /**
     * @param {string} text
     * @param {Function} undo
     * @private
     */
    _showUndoableToast(text, undo) {
        showSuccessNotification(text, {
            timeout: 8000,
            buttons: [
                Noty.button(lang.get('js.add_to_collection_undo'), 'btn btn-sm btn-light add_to_collection_undo', function (noty) {
                    noty.close();
                    undo();
                }),
            ],
        });
    }

    /**
     * Notifications render their text as HTML, and collection names are user input.
     * @param {string} text
     * @returns {string}
     * @private
     */
    _escape(text) {
        return $('<div>').text(text).html();
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonCollectionAddtocollection};
}

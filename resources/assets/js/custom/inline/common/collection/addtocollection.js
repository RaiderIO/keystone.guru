/**
 @typedef {Object} CommonCollectionAddtocollectionOptions
 @property {string} triggerSelector    Elements carrying a data-publickey that open the dialog for that route.
 @property {string} modalSelector
 @property {string} statusSelector
 @property {string} listSelector
 @property {string} newSelector
 @property {string} forDungeonRouteUrl
 @property {string} loadingText
 @property {string} loadFailedText
 @property {string} countText          Contains :count and :max.
 @property {string} newCollectionText
 @property {string} noCollectionsText
 @property {string} fullText
 @property {string} addedText          Contains :name.
 @property {string} removedText        Contains :name.
 @property {string} undoText
 @property {string} undoneText
 @property {string} saveFailedText
 */

/**
 @typedef {Object} CommonCollectionAddtocollectionCollection
 @property {string} public_key
 @property {string} name
 @property {string} kind_label
 @property {Number} route_count
 @property {Number} max_routes
 @property {boolean} is_member
 @property {string|null} blocked_reason   'game_version', 'season', 'full' or null.
 @property {string|null} blocked_text
 @property {string} store_url
 @property {string} delete_url
 */

/**
 * The "Add to collection…" dialog: the current user's collections as switches for one of their own routes. A switch
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
        /** @type {CommonCollectionAddtocollectionCollection[]} */
        this._collections = [];
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
        this._collections = [];

        $(this.options.statusSelector).text(this.options.loadingText);
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

                self._collections = json.collections;
                self._render(json);
            },
            error: function () {
                $(self.options.statusSelector).text(self.options.loadFailedText);
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

        $(this.options.statusSelector).text(json.collections.length === 0 ? this.options.noCollectionsText : '');

        $list.empty().prop('hidden', json.collections.length === 0);
        json.collections.forEach(function (collection) {
            $list.append(self._renderCollection(collection));
        });

        this._renderNewCollection(json);
    }

    /**
     * @param {CommonCollectionAddtocollectionCollection} collection
     * @returns {jQuery}
     * @private
     */
    _renderCollection(collection) {
        let self = this;
        let inputId = `add_to_collection_${collection.public_key}`;
        let isDisabled = !collection.is_member && collection.blocked_reason !== null;

        let $input = $('<input>', {
            type: 'checkbox',
            role: 'switch',
            'class': 'form-check-input',
            id: inputId,
        }).prop('checked', collection.is_member).prop('disabled', isDisabled);

        let $label = $('<label>', {'class': 'form-check-label', 'for': inputId})
            .append($('<span>', {'class': 'd-block', text: collection.name}))
            .append($('<small>', {'class': 'd-block text-body-secondary', text: collection.kind_label}));

        if (isDisabled) {
            $label.append($('<small>', {'class': 'd-block text-warning add_to_collection_reason', text: collection.blocked_text}));
        }

        $input.on('change', function () {
            self._onToggled(collection, $(this).is(':checked'));
        });

        return $('<li>', {
            'class': `list-group-item d-flex align-items-start gap-2${isDisabled ? ' text-body-secondary' : ''}`,
            'data-public-key': collection.public_key,
        })
            .append($('<div>', {'class': 'form-check form-switch mb-0 flex-grow-1 text-break'}).append($input, $label))
            .append($('<span>', {
                'class': 'badge text-bg-secondary add_to_collection_count',
                text: this.options.countText.replace(':count', collection.route_count).replace(':max', collection.max_routes),
            }));
    }

    /**
     * @param {Object} json
     * @private
     */
    _renderNewCollection(json) {
        let $new = $(this.options.newSelector).empty();
        let $icon = $('<i>', {'class': 'fas fa-plus'});

        if (json.may_create) {
            $new.append($('<a>', {'class': 'btn btn-outline-success w-100', href: json.create_url})
                .append($icon, document.createTextNode(` ${this.options.newCollectionText}`)));

            return;
        }

        $new.append($('<button>', {type: 'button', 'class': 'btn btn-outline-secondary w-100', disabled: true})
            .append($icon, document.createTextNode(` ${this.options.newCollectionText}`)));
        $new.append($('<small>', {'class': 'd-block text-warning mt-1', text: json.create_blocked_text}));
    }

    /**
     * @param {CommonCollectionAddtocollectionCollection} collection
     * @param {boolean} isChecked
     * @private
     */
    _onToggled(collection, isChecked) {
        let self = this;
        let publicKey = this._publicKey;

        this._setMember(collection, isChecked, publicKey, function () {
            let text = (isChecked ? self.options.addedText : self.options.removedText).replace(':name', self._escape(collection.name));

            self._showUndoableToast(text, function () {
                self._setMember(collection, !isChecked, publicKey, function () {
                    showInfoNotification(self.options.undoneText);
                });
            });
        });
    }

    /**
     * Adds the route to, or removes it from, the collection and updates its row once saved; a failed save restores
     * the row.
     *
     * @param {CommonCollectionAddtocollectionCollection} collection
     * @param {boolean} isMember
     * @param {string} publicKey
     * @param {Function} onSaved
     * @private
     */
    _setMember(collection, isMember, publicKey, onSaved) {
        let self = this;

        $.ajax({
            type: isMember ? 'POST' : 'DELETE',
            url: isMember ? collection.store_url : collection.delete_url,
            dataType: 'json',
            data: {dungeon_routes: [publicKey]},
            success: function () {
                collection.is_member = isMember;
                collection.route_count += isMember ? 1 : -1;
                if (!isMember && collection.blocked_reason === null && collection.route_count >= collection.max_routes) {
                    collection.blocked_reason = 'full';
                    collection.blocked_text = self.options.fullText;
                } else if (collection.blocked_reason === 'full' && collection.route_count < collection.max_routes) {
                    collection.blocked_reason = null;
                    collection.blocked_text = null;
                }

                self._replaceRow(collection, publicKey);
                onSaved();
            },
            error: function (xhr) {
                showErrorNotification(self._escape(self._getErrorMessage(xhr)));
                self._replaceRow(collection, publicKey);
            },
        });
    }

    /**
     * @param {CommonCollectionAddtocollectionCollection} collection
     * @param {string} publicKey The route the change was made for; the dialog may show another route by now.
     * @private
     */
    _replaceRow(collection, publicKey) {
        if (this._publicKey !== publicKey) {
            return;
        }

        $(this.options.listSelector)
            .children()
            .filter((index, element) => $(element).attr('data-public-key') === collection.public_key)
            .replaceWith(this._renderCollection(collection));
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

        return this.options.saveFailedText;
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
                Noty.button(this.options.undoText, 'btn btn-sm btn-light add_to_collection_undo', function (noty) {
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

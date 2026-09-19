/**
 @typedef {Object} CommonCollectionRoutesSection
 @property {string} inlineId            Inline code id of the section's ordered list.
 @property {string} rootSelector        Root of the section's ordered list.
 @property {string} addButtonSelector
 @property {Number|null} dungeonId      The dungeon of this section; null for the flat list of a free-form collection.
 @property {boolean} canAdd             Whether the picker can put routes in this section.
 @property {boolean} withDungeonName    Whether items are labelled with their dungeon as well.
 */

/**
 @typedef {Object} CommonCollectionRoutesOptions
 @property {string|null} pickerInlineId   Inline code id of the route picker; null when routes cannot be added.
 @property {string} pickerSelector
 @property {string} countSelector
 @property {CommonCollectionRoutesSection[]} sections
 @property {Object<string, string>} dungeonRoutes  Route id => public key of every route in the collection.
 @property {string} storeUrl
 @property {string} deleteUrl
 @property {string} orderUrl
 @property {Number} max
 @property {string} countText           Contains :count and :max.
 @property {string} addedOneText
 @property {string} addedManyText       Contains :count.
 @property {string} removedText         Contains :name.
 @property {string} undoText
 @property {string} undoneText
 @property {string} saveFailedText
 */

/**
 * The route lists of a collection's edit page. Every change saves at once: routes picked in the route picker are
 * added, a removed route is removed and a moved route stores the new order of the whole collection, each followed
 * by a toast; adding and removing can be undone from it.
 *
 * @property {CommonCollectionRoutesOptions} options
 */
class CommonCollectionRoutes extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);

        /** @type {Object<string, string>} */
        this._publicKeys = Object.assign({}, this.options.dungeonRoutes);
        /** @type {string[]} Route ids in the order the server last stored */
        this._savedOrder = [];
        this._orderTimeout = null;
    }

    activate() {
        super.activate();

        let self = this;

        this.options.sections.forEach(function (section) {
            $(section.addButtonSelector).on('click', self._onAddClicked.bind(self, section));
            $(section.rootSelector)
                .on('orderedselect:moved', self._onMoved.bind(self))
                .on('orderedselect:removed', function (event, removed) {
                    self._onRemoved(section, removed);
                });
        });

        $(this.options.pickerSelector).on('routepicker:added', function (event, result) {
            self._onAdded(result.response.dungeon_routes);
        });

        this._savedOrder = this._getOrder();
    }

    /**
     * @param {CommonCollectionRoutesSection} section
     * @param {Event} event
     * @private
     */
    _onAddClicked(section, event) {
        event.preventDefault();

        this._getPicker().open({dungeonId: section.dungeonId});
    }

    /**
     * @param {Object[]} dungeonRoutes As returned by the store endpoint.
     * @private
     */
    _onAdded(dungeonRoutes) {
        let self = this;

        this._insert(dungeonRoutes);

        let publicKeys = dungeonRoutes.map(dungeonRoute => dungeonRoute.public_key);
        let text = dungeonRoutes.length === 1 ? this.options.addedOneText : this.options.addedManyText;

        this._showUndoableToast(text.replace(':count', dungeonRoutes.length), function () {
            self._request('DELETE', self.options.deleteUrl, publicKeys, function () {
                dungeonRoutes.forEach(dungeonRoute => self._removeFromLists(dungeonRoute.id));
                showInfoNotification(self.options.undoneText);
            });
        });
    }

    /**
     * @param {CommonCollectionRoutesSection} section
     * @param {{id: string, name: string, position: Number}} removed
     * @private
     */
    _onRemoved(section, removed) {
        let self = this;
        let publicKey = this._publicKeys[removed.id];
        let previousOrder = this._savedOrder.slice();
        let orderedSelect = this._getOrderedSelect(section);
        let previousSectionOrder = previousOrder.filter(id => id === removed.id || orderedSelect.getIds().includes(id));

        this._refreshCount();

        this._request('DELETE', this.options.deleteUrl, [publicKey], function () {
            // What the server now stores; moves still waiting for their debounced save must stay unsaved
            self._savedOrder = self._savedOrder.filter(id => id !== removed.id);
            delete self._publicKeys[removed.id];
            self._refreshPicker();

            // The picker cannot offer a route back into a section it does not fill, so there is nothing to undo
            let undo = section.canAdd ? function () {
                self._request('POST', self.options.storeUrl, [publicKey], function (response) {
                    self._insert(response.dungeon_routes);
                    orderedSelect.setIds(previousSectionOrder);
                    self._saveOrder(function () {
                        showInfoNotification(self.options.undoneText);
                    });
                });
            } : null;

            self._showUndoableToast(self.options.removedText.replace(':name', self._escape(removed.name)), undo);
        }, function () {
            orderedSelect.addItem(removed.id, removed.name);
            orderedSelect.setIds(previousSectionOrder);
            self._refreshCount();
        });
    }

    /**
     * Moves can come in quick succession (arrow buttons), so the order is stored once they stop.
     * @private
     */
    _onMoved() {
        clearTimeout(this._orderTimeout);
        this._orderTimeout = setTimeout(this._saveOrder.bind(this, null), 400);
    }

    /**
     * @param {Function|null} onSaved
     * @private
     */
    _saveOrder(onSaved) {
        let self = this;
        let order = this._getOrder();

        if (order.join(',') === this._savedOrder.join(',')) {
            if (typeof onSaved === 'function') {
                onSaved();
            }

            return;
        }

        this._request('PUT', this.options.orderUrl, order.map(id => this._publicKeys[id]), function () {
            self._savedOrder = order;
            if (typeof onSaved === 'function') {
                onSaved();
            }
        }, function () {
            self._restoreOrder(self._savedOrder);
        });
    }

    /**
     * Puts newly added routes in the list they belong in: their dungeon's slot, or the one flat list.
     * @param {Object[]} dungeonRoutes As returned by the store endpoint.
     * @private
     */
    _insert(dungeonRoutes) {
        let self = this;

        dungeonRoutes.forEach(function (dungeonRoute) {
            let section = self._findSectionFor(dungeonRoute);
            if (section === null) {
                // Only a route outside the season's pool ends up here; the page groups it correctly on reload
                window.location.reload();

                return;
            }

            let name = section.withDungeonName ? `${dungeonRoute.title} — ${dungeonRoute.dungeon}` : dungeonRoute.title;

            self._publicKeys[dungeonRoute.id] = dungeonRoute.public_key;
            self._getOrderedSelect(section).addItem(dungeonRoute.id, name);
            // The server appends added routes to the end of the stored order
            self._savedOrder.push(String(dungeonRoute.id));
        });

        this._refreshCount();
        this._refreshPicker();
    }

    /**
     * @param {Object} dungeonRoute
     * @returns {CommonCollectionRoutesSection|null}
     * @private
     */
    _findSectionFor(dungeonRoute) {
        let candidates = this.options.sections.filter(section => section.canAdd);

        return candidates.find(section => section.dungeonId === dungeonRoute.dungeon_id) ||
            candidates.find(section => section.dungeonId === null) ||
            null;
    }

    /**
     * @param {string|Number} id
     * @private
     */
    _removeFromLists(id) {
        let self = this;

        this.options.sections.forEach(section => self._getOrderedSelect(section).removeItem(id));
        delete this._publicKeys[id];
        this._savedOrder = this._savedOrder.filter(savedId => savedId !== String(id));

        this._refreshCount();
        this._refreshPicker();
    }

    /**
     * @param {string[]} order Route ids.
     * @private
     */
    _restoreOrder(order) {
        let self = this;

        this.options.sections.forEach(section => self._getOrderedSelect(section).setIds(order));
    }

    /**
     * Read from the page rather than from the lists' inline code, which may not be initialised yet on activate().
     * @returns {string[]} Every route id, in page order.
     * @private
     */
    _getOrder() {
        let order = [];

        this.options.sections.forEach(function (section) {
            $(section.rootSelector).find('.ordered_select_list > .ordered_select_item')
                .not('.draggable-mirror, .draggable--original')
                .each(function () {
                    order.push($(this).attr('data-id'));
                });
        });

        return order;
    }

    /**
     * @private
     */
    _refreshCount() {
        let self = this;
        let count = this._getOrder().length;

        $(this.options.countSelector).text(this.options.countText.replace(':count', count).replace(':max', this.options.max));
        this.options.sections.forEach(section => self._getOrderedSelect(section).setFullCount(count));
    }

    /**
     * @private
     */
    _refreshPicker() {
        let picker = this._getPicker();
        if (picker !== null) {
            picker.setExistingPublicKeys(Object.values(this._publicKeys));
        }
    }

    /**
     * @param {string} type
     * @param {string} url
     * @param {string[]} publicKeys
     * @param {Function} onSuccess
     * @param {Function|null} onError
     * @private
     */
    _request(type, url, publicKeys, onSuccess, onError = null) {
        let self = this;

        $.ajax({
            type: type,
            url: url,
            dataType: 'json',
            data: {dungeon_routes: publicKeys},
            success: onSuccess,
            error: function () {
                showErrorNotification(self.options.saveFailedText);

                if (typeof onError === 'function') {
                    onError();
                }
            },
        });
    }

    /**
     * @param {string} text
     * @param {Function|null} undo
     * @private
     */
    _showUndoableToast(text, undo) {
        let opts = {};

        if (typeof undo === 'function') {
            opts = {
                timeout: 8000,
                buttons: [
                    Noty.button(this.options.undoText, 'btn btn-sm btn-light collection_routes_undo', function (noty) {
                        noty.close();
                        undo();
                    }),
                ],
            };
        }

        showSuccessNotification(text, opts);
    }

    /**
     * Notifications render their text as HTML, and route titles are user input.
     * @param {string} text
     * @returns {string}
     * @private
     */
    _escape(text) {
        return $('<div>').text(text).html();
    }

    /**
     * @param {CommonCollectionRoutesSection} section
     * @returns {CommonFormsOrderedselect}
     * @private
     */
    _getOrderedSelect(section) {
        return _inlineManager.getInlineCodeById(section.inlineId);
    }

    /**
     * @returns {CommonDungeonroutePicker|null}
     * @private
     */
    _getPicker() {
        return this.options.pickerInlineId === null ? null : _inlineManager.getInlineCodeById(this.options.pickerInlineId);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonCollectionRoutes};
}

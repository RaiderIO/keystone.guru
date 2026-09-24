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
 @property {string|null} dungeonRoutePickerInlineId   Inline code id of the route picker; null when routes cannot be added.
 @property {string} dungeonRoutePickerSelector
 @property {string} countSelector
 @property {CommonCollectionRoutesSection[]} sections
 @property {string|null} storeUrl       Null on a collection that does not exist yet, which posts its routes with its form.
 @property {string|null} deleteUrl
 @property {string|null} orderUrl
 @property {Number} max
 @property {Object<string, Number>} dungeonIds The dungeon of every route the page arrived with, by public key.
 @property {Number} maxPerDungeon
 */

/**
 * The route lists of a collection, on the page that creates one as well as on the page that edits one. An
 * existing collection saves every change at once - routes picked in the route picker are added, a removed
 * route is removed and a moved route stores the new order of the whole collection, each followed by a toast
 * that adding and removing can be undone from. A collection that does not exist yet only fills its lists;
 * the form that creates it posts them.
 *
 * @property {CommonCollectionRoutesOptions} options
 */
class CommonCollectionRoutes extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);

        /** Whether the routes are only collected in the form, to be saved with it */
        this._isLocal = this.options.storeUrl === null || typeof this.options.storeUrl === 'undefined';
        /** @type {string[]} Route public keys in the order the server last stored */
        this._savedOrder = [];
        this._orderTimeout = null;
        this._orderSaving  = false;
        /** @type {Function[]} Callbacks of the saves that came in while one was in flight */
        this._orderQueue = null;
        /** @type {Object<string, Number>} The dungeon of every route that has been in the lists, by public key */
        this._dungeonIds = Object.assign({}, this.options.dungeonIds || {});
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

        $(this.options.dungeonRoutePickerSelector).on('dungeonroutepicker:added', function (event, result) {
            self._onAdded(result);
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
     * @param {CommonDungeonroutePickerResult} result
     * @private
     */
    _onAdded(result) {
        let self = this;
        let dungeonRoutes = this._toDungeonRoutes(result);

        this._insert(dungeonRoutes);

        // Every posted route was already a member (a race, or a double submit) - nothing was inserted, so there
        // is nothing to undo and nothing to tell the user beyond what the picker already shows
        if (dungeonRoutes.length === 0) {
            return;
        }

        let text = dungeonRoutes.length === 1
            ? lang.get('js.collection_dungeonroutes_added_one')
            : lang.get('js.collection_dungeonroutes_added_many', {count: dungeonRoutes.length});

        // Nothing was stored, so there is nothing to undo - removing a route from the list is one click
        if (this._isLocal) {
            showSuccessNotification(text);

            return;
        }

        let publicKeys = dungeonRoutes.map(dungeonRoute => dungeonRoute.publicKey);

        this._showUndoableToast(text, function () {
            $.ajax({
                type: 'DELETE',
                url: self.options.deleteUrl,
                dataType: 'json',
                data: {dungeon_routes: publicKeys},
                success: function () {
                    publicKeys.forEach(publicKey => self._removeFromLists(publicKey));
                    showInfoNotification(lang.get('js.collection_dungeonroutes_undone'));
                },
                error: self._onSaveFailed.bind(self),
            });
        });
    }

    /**
     * The picker reports the routes it picked from; a collection that saves at once reports what it stored
     * instead, which is what the lists are filled from then.
     *
     * @param {CommonDungeonroutePickerResult} result
     * @returns {{publicKey: string, title: string, dungeonId: Number|null, dungeonName: string, detail: Object|null}[]}
     * @private
     */
    _toDungeonRoutes(result) {
        let stored = result.response === null || typeof result.response === 'undefined'
            ? null
            : result.response.dungeon_routes;

        if (stored) {
            return stored.map(dungeonRoute => ({
                publicKey:   dungeonRoute.public_key,
                title:       dungeonRoute.title,
                dungeonId:   dungeonRoute.dungeon_id,
                dungeonName: dungeonRoute.dungeon,
                detail:      this._enemyForcesDetail(dungeonRoute.enemy_forces, dungeonRoute.enemy_forces_required),
            }));
        }

        return (result.dungeonRoutes || []).map(dungeonRoute => ({
            publicKey:   dungeonRoute.publicKey,
            title:       dungeonRoute.title,
            dungeonId:   dungeonRoute.dungeonId,
            dungeonName: dungeonRoute.getDungeonName(),
            detail:      this._enemyForcesDetail(dungeonRoute.getEnemyForces(), dungeonRoute.getEnemyForcesRequired()),
        }));
    }

    /**
     * What a route's row says next to its title: its enemy forces against what its mapping version needs,
     * flagged when it falls short. Mirrors common/collection/routes.blade.php, which renders the same
     * detail for the routes the page arrived with - a freshly added row must read no differently.
     *
     * @param {Number|string} enemyForces
     * @param {Number|string} required
     * @returns {{text: string, isWarning: boolean}|null} Null for a dungeon that requires no enemy forces.
     * @private
     */
    _enemyForcesDetail(enemyForces, required) {
        let forces = parseInt(enemyForces) || 0;
        let needed = parseInt(required) || 0;

        if (needed <= 0) {
            return null;
        }

        return {text: `${forces} / ${needed}`, isWarning: forces < needed};
    }

    /**
     * @param {CommonCollectionRoutesSection} section
     * @param {{id: string, name: string, detail: Object|null, position: Number}} removed
     * @private
     */
    _onRemoved(section, removed) {
        let self = this;
        let publicKey = removed.id;

        this._refreshCount();

        if (this._isLocal) {
            this._refreshPicker();

            return;
        }

        let previousOrder = this._savedOrder.slice();
        let orderedSelect = this._getOrderedSelect(section);
        let previousSectionOrder = previousOrder.filter(id => id === publicKey || orderedSelect.getIds().includes(id));

        $.ajax({
            type: 'DELETE',
            url: this.options.deleteUrl,
            dataType: 'json',
            data: {dungeon_routes: [publicKey]},
            success: function () {
                // What the server now stores; moves still waiting for their debounced save must stay unsaved
                self._savedOrder = self._savedOrder.filter(id => id !== publicKey);
                self._refreshPicker();

                // The picker cannot offer a route back into a section it does not fill, and the server takes no route
                // back into a dungeon that is still at its limit, so there is nothing to undo then
                let undo = section.canAdd && self._hasRoomInDungeonOf(publicKey)
                    ? self._undoRemoval.bind(self, publicKey, orderedSelect, previousSectionOrder)
                    : null;

                self._showUndoableToast(
                    lang.get('js.collection_dungeonroutes_removed', {name: self._escape(removed.name)}),
                    undo,
                );
            },
            error: function () {
                self._onSaveFailed();

                orderedSelect.addItem(publicKey, removed.name, removed.detail);
                orderedSelect.setIds(previousSectionOrder);
                self._refreshCount();
            },
        });
    }

    /**
     * Undoes taking a route out: the server appends the route, so the order it had before is stored afterwards.
     *
     * @param {string} publicKey
     * @param {CommonFormsOrderedselect} orderedSelect
     * @param {string[]} previousSectionOrder
     * @private
     */
    _undoRemoval(publicKey, orderedSelect, previousSectionOrder) {
        let self = this;

        $.ajax({
            type: 'POST',
            url: this.options.storeUrl,
            dataType: 'json',
            data: {dungeon_routes: [publicKey]},
            success: function (response) {
                self._insert(self._toDungeonRoutes({publicKeys: [publicKey], dungeonRoutes: [], response: response}));
                orderedSelect.setIds(previousSectionOrder);
                self._saveOrder(function () {
                    showInfoNotification(lang.get('js.collection_dungeonroutes_undone'));
                });
            },
            error: this._onSaveFailed.bind(this),
        });
    }

    /**
     * Moves can come in quick succession (arrow buttons), so the order is stored once they stop.
     * @private
     */
    _onMoved() {
        // The form posts the lists in their own order, so there is nothing to store on a move
        if (this._isLocal) {
            return;
        }

        clearTimeout(this._orderTimeout);
        this._orderTimeout = setTimeout(this._saveOrder.bind(this, null), 400);
    }

    /**
     * @param {Function|null} onSaved
     * @private
     */
    _saveOrder(onSaved) {
        let self = this;

        // Only the in-flight request decides what is stored, so a save on top of one waits for it - otherwise a list
        // put back into the stored order looks unchanged here while the request still writes the superseded one
        if (this._orderSaving) {
            this._orderQueue ??= [];
            if (typeof onSaved === 'function') {
                this._orderQueue.push(onSaved);
            }

            return;
        }

        let order = this._getOrder();

        if (order.join(',') === this._savedOrder.join(',')) {
            if (typeof onSaved === 'function') {
                onSaved();
            }

            return;
        }

        this._orderSaving = true;

        $.ajax({
            type: 'PUT',
            url: this.options.orderUrl,
            dataType: 'json',
            data: {dungeon_routes: order},
            success: function () {
                self._savedOrder  = order;
                self._orderSaving = false;
                if (typeof onSaved === 'function') {
                    onSaved();
                }

                self._saveQueuedOrder();
            },
            error: function () {
                self._onSaveFailed();

                self._orderSaving = false;
                self._restoreOrder(self._savedOrder);
                self._saveQueuedOrder();
            },
        });
    }

    /**
     * Stores whatever the lists hold now, for the saves that arrived while a request was in flight.
     * @private
     */
    _saveQueuedOrder() {
        let queue = this._orderQueue;
        if (queue === null) {
            return;
        }

        this._orderQueue = null;
        this._saveOrder(function () {
            queue.forEach(onSaved => onSaved());
        });
    }

    /**
     * Puts newly added routes in the list they belong in: their dungeon's slot, or the one flat list.
     * @param {{publicKey: string, title: string, dungeonId: Number|null, dungeonName: string}[]} dungeonRoutes
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

            let name = section.withDungeonName ? `${dungeonRoute.title} — ${dungeonRoute.dungeonName}` : dungeonRoute.title;

            self._getOrderedSelect(section).addItem(dungeonRoute.publicKey, name, dungeonRoute.detail);
            self._dungeonIds[dungeonRoute.publicKey] = dungeonRoute.dungeonId;
            // The server appends added routes to the end of the stored order
            self._savedOrder.push(dungeonRoute.publicKey);
        });

        this._refreshCount();
        this._refreshPicker();
    }

    /**
     * @param {{dungeonId: Number|null}} dungeonRoute
     * @returns {CommonCollectionRoutesSection|null}
     * @private
     */
    _findSectionFor(dungeonRoute) {
        let candidates = this.options.sections.filter(section => section.canAdd);

        return candidates.find(section => section.dungeonId === dungeonRoute.dungeonId) ||
            candidates.find(section => section.dungeonId === null) ||
            null;
    }

    /**
     * @param {string} publicKey
     * @private
     */
    _removeFromLists(publicKey) {
        let self = this;

        this.options.sections.forEach(section => self._getOrderedSelect(section).removeItem(publicKey));
        this._savedOrder = this._savedOrder.filter(savedPublicKey => savedPublicKey !== publicKey);

        this._refreshCount();
        this._refreshPicker();
    }

    /**
     * @param {string[]} order Route public keys.
     * @private
     */
    _restoreOrder(order) {
        let self = this;

        this.options.sections.forEach(section => self._getOrderedSelect(section).setIds(order));
    }

    /**
     * Read from the page rather than from the lists' inline code, which may not be initialised yet on activate().
     * @returns {string[]} Every route public key, in page order.
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
     * @param {string} publicKey
     * @returns {boolean} Whether the route's dungeon has room for it next to the routes in the lists.
     * @private
     */
    _hasRoomInDungeonOf(publicKey) {
        let self = this;
        let dungeonId = this._dungeonIds[publicKey];

        if (typeof this.options.maxPerDungeon !== 'number' || typeof dungeonId === 'undefined') {
            return true;
        }

        let sameDungeonCount = this._getOrder()
            .filter(orderPublicKey => orderPublicKey !== publicKey && self._dungeonIds[orderPublicKey] === dungeonId)
            .length;

        return sameDungeonCount < this.options.maxPerDungeon;
    }

    /**
     * @private
     */
    _refreshCount() {
        let self = this;
        let count = this._getOrder().length;

        $(this.options.countSelector).text(lang.get('js.collection_dungeonroutes_count', {count: count, max: this.options.max}));
        this.options.sections.forEach(section => self._getOrderedSelect(section).setFullCount(count));
    }

    /**
     * @private
     */
    _refreshPicker() {
        let picker = this._getPicker();
        if (picker !== null) {
            picker.setExistingPublicKeys(this._getOrder(), this._dungeonIds);
        }
    }

    /**
     * @private
     */
    _onSaveFailed() {
        showErrorNotification(lang.get('js.collection_dungeonroutes_save_failed'));
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
                    Noty.button(lang.get('js.collection_dungeonroutes_undo'), 'btn btn-sm btn-light collection_routes_undo', function (noty) {
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
        return this.options.dungeonRoutePickerInlineId === null
            ? null
            : _inlineManager.getInlineCodeById(this.options.dungeonRoutePickerInlineId);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonCollectionRoutes};
}

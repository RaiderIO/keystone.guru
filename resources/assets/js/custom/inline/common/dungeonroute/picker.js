/**
 @typedef {Object} CommonDungeonroutePickerOptions
 @property {string} drawerSelector            The .offcanvas element of the drawer.
 @property {string|null} openButtonSelector   Clicking any matching element opens the drawer.
 @property {string} titleSearchSelector
 @property {string} dungeonSelectSelector
 @property {string} affixSelectSelector
 @property {string} attributesSelectSelector
 @property {string} requirementsSelectSelector
 @property {string} tagsSelectSelector
 @property {string} listSelector              The <ul> the route rows are rendered into.
 @property {string} loadingSelector
 @property {string} emptySelector
 @property {string} errorSelector
 @property {string} previousSelector
 @property {string} nextSelector
 @property {string} rangeSelector
 @property {string} selectionSelector         Says how many routes are ticked.
 @property {string} fullSelector              Says why no more routes can be ticked.
 @property {string} confirmButtonSelector
 @property {string|null} selectPageSelector   Tick box ticking every route of the listed page; null when the drawer has none.
 @property {string} statusSelector            Polite live region.
 @property {string} listUrl                   Server-side paged route list (DataTables protocol).
 @property {Number} pageSize
 @property {Object} sourceParameters          Sent with every list request, selects where the routes come from.
 @property {Object} lockedParameters          Sent with every list request, the constraints the user cannot change.
 @property {string[]} existingPublicKeys      Routes already in the target.
 @property {Number|null} max                  Most routes the target may hold, null for no limit.
 @property {Number|null} maxPerDungeon        Most routes of one dungeon the target may hold, null for no limit.
 @property {Object<string, Number>} existingDungeonIds The dungeon of each route already in the target, by public key.
 @property {string} actionKeyPrefix          Lang key prefix of the confirm button's wording, e.g. dungeonroute_picker_add.
 @property {string|null} actionUrl           Where the ticked routes are sent; null leaves acting on them to the host.
 @property {string} actionFieldName
 @property {string} actionMethod             HTTP method the ticked routes are sent with.
 @property {boolean} confirmsAction          Whether the user confirms once more before the routes are sent.
 @property {boolean} removesActedRoutes      Whether the action removes the routes from the source, so they are gone
                                             rather than "already in the target" afterwards.
 @property {string|null} actedFieldName      Field of the response holding the public keys actually acted on; null
                                             when every sent route is acted on.
 @property {string} fallbackImageBaseUrl
 @property {Object<string, {class: string, name: string}[]>} affixGroups Affixes per affix group id, for the filter's icons.
 */

/**
 @typedef {Object} CommonDungeonroutePickerResult
 @property {string[]} publicKeys
 @property {PickerDungeonRoute[]} dungeonRoutes  The listed routes the public keys were ticked on.
 @property {*} response                          What the action url answered; null when the drawer sent nothing.
 */

/**
 * Side drawer listing the source's routes, page by page, to tick and act on in one go. It knows nothing about
 * the target beyond the options: it fires `dungeonroutepicker:confirmed` on the drawer element (and calls every
 * onConfirmed() callback) with a CommonDungeonroutePickerResult, so the host can store the routes and show its
 * toast.
 *
 * @property {CommonDungeonroutePickerOptions} options
 */
class CommonDungeonroutePicker extends SearchInlineBase {

    constructor(id, bladePath, options) {
        super(new SearchHandlerDungeonRoutePicker(options), id, bladePath, options);

        this.dialog = new DrawerDialog({
            drawerSelector: options.drawerSelector,
            openButtonSelector: options.openButtonSelector,
            confirmButtonSelector: options.confirmButtonSelector,
            statusSelector: options.statusSelector,
        });

        let onFilterChanged = this._onFilterChanged.bind(this);
        this.filters = {
            'title': new SearchFilterTitle(options.titleSearchSelector, onFilterChanged),
            'dungeon': new SearchFilterInputChange(options.dungeonSelectSelector, onFilterChanged),
            'affixes': new SearchFilterInputChange(options.affixSelectSelector, onFilterChanged),
            'attributes': new SearchFilterInputChange(options.attributesSelectSelector, onFilterChanged),
            'requirements': new SearchFilterInputChange(options.requirementsSelectSelector, onFilterChanged),
            'tags': new SearchFilterInputChange(options.tagsSelectSelector, onFilterChanged),
        };

        this._listIsStale = false;
        /** @type {string} loading|loaded|empty|error */
        this._state = 'empty';
        this._page = 0;
        this._total = 0;
        this._saving = false;
        this._failed = false;
        /** @type {string|null} The filter values of the last search, without its paging */
        this._previousFilterParams = null;
        /** @type {Set<string>} */
        this._existing = new Set(this.options.existingPublicKeys || []);
        /** @type {Object<string, Number>} */
        this._existingDungeonIds = Object.assign({}, this.options.existingDungeonIds || {});
        /** @type {string[]} Ticked public keys, in the order they were ticked */
        this._selected = [];
        /** @type {Object<string, PickerDungeonRoute>} Every listed or ticked route, by public key */
        this._dungeonRoutes = {};
        this._onConfirmedCallbacks = [];
    }

    activate() {
        super.activate();

        this._decorateAffixOptions();

        this.dialog.activate();
        this.dialog.onFirstShow(this.reload.bind(this));
        this.dialog.onShow(this._reloadWhenOutOfDate.bind(this));
        this.dialog.onConfirm(this._confirmDungeonRoutes.bind(this));

        $(this.options.previousSelector).on('click', this._goToPage.bind(this, -1));
        $(this.options.nextSelector).on('click', this._goToPage.bind(this, 1));
        $(this.options.listSelector).on('change', '.route_picker_checkbox', this._onCheckboxChanged.bind(this));
        if (this.options.selectPageSelector) {
            $(this.options.selectPageSelector).on('change', this._onSelectPageChanged.bind(this));
        }

        this._refreshSelection();
    }

    /**
     * Opens the drawer; the first open loads the first page.
     * @param {Object} filters
     * @param {Number|string|null} [filters.dungeonId] Puts the dungeon filter on this value first.
     */
    open(filters = {}) {
        if (filters.dungeonId !== null && typeof filters.dungeonId !== 'undefined' &&
            this._setDungeonFilter(filters.dungeonId) && this.dialog.hasBeenShown()) {
            this.reload();
        }

        this.dialog.open();
    }

    close() {
        this.dialog.close();
    }

    /**
     * Registers a callback called with a CommonDungeonroutePickerResult after every successful action.
     * @param {Function} callback
     */
    onConfirmed(callback) {
        this._onConfirmedCallbacks.push(callback);
    }

    /**
     * Replaces the routes already in the target, e.g. after the host undid an action or removed a route.
     * @param {string[]} publicKeys
     * @param {Object<string, Number>|null} [dungeonIds] The dungeon of each of those routes, by public key.
     */
    setExistingPublicKeys(publicKeys, dungeonIds = null) {
        this._existing = new Set(publicKeys);
        if (dungeonIds !== null) {
            this._existingDungeonIds = Object.assign({}, dungeonIds);
        }
        this._selected = this._selected.filter(publicKey => !this._existing.has(publicKey));

        this._refreshRows();
        this._refreshSelection();
    }

    /**
     * @returns {string[]}
     */
    getSelectedPublicKeys() {
        return this._selected.slice();
    }

    /**
     * Loads the first page again with the current filters.
     */
    reload() {
        this._page = 0;
        // The same filters may list other routes by now
        this._previousSearchParams = null;
        this._search();
    }

    /**
     * @returns {Number|null} How many more routes may be ticked, null when there is no limit.
     */
    getRemaining() {
        if (this.options.max === null || typeof this.options.max === 'undefined') {
            return null;
        }

        return Math.max(0, this.options.max - this._existing.size - this._selected.length);
    }

    /**
     * @param {Number} dungeonId
     * @returns {Number|null} How many more routes of the dungeon may be ticked, null when there is no limit.
     */
    getRemainingForDungeon(dungeonId) {
        if (this.options.maxPerDungeon === null || typeof this.options.maxPerDungeon === 'undefined') {
            return null;
        }

        let self = this;
        let existingCount = [...this._existing].filter(publicKey => self._existingDungeonIds[publicKey] === dungeonId).length;
        let selectedCount = this._selected.filter(publicKey => self._dungeonRoutes[publicKey]?.dungeonId === dungeonId).length;

        return Math.max(0, this.options.maxPerDungeon - existingCount - selectedCount);
    }

    /**
     * @param {string} publicKey
     * @returns {boolean} Whether the listed route's dungeon has no room left.
     * @private
     */
    _isDungeonFull(publicKey) {
        let dungeonRoute = this._dungeonRoutes[publicKey];

        return typeof dungeonRoute !== 'undefined' && this.getRemainingForDungeon(dungeonRoute.dungeonId) === 0;
    }

    /**
     * @returns {boolean}
     * @protected
     */
    _syncsWithUrl() {
        return false;
    }

    /**
     * @param {Number|string} dungeonId
     * @returns {boolean} Whether the filter changed.
     * @private
     */
    _setDungeonFilter(dungeonId) {
        let select = $(this.options.dungeonSelectSelector)[0];
        let value = String(dungeonId);

        if (typeof select === 'undefined' || select.value === value) {
            return false;
        }

        if (select.tomselect) {
            // Silent, so the change handler does not load a page of its own on top of ours
            select.tomselect.setValue(value, true);
        } else {
            this.filters.dungeon.setValue(value);
        }

        return select.value === value;
    }

    /**
     * A filter also reports when it merely lost focus; an unchanged search must not redraw the list under the user.
     * @private
     */
    _onFilterChanged() {
        if (!this.dialog.hasBeenShown() ||
            (!this._failed && this._previousFilterParams === this._getFilterParams())) {
            return;
        }

        this.reload();
    }

    /**
     * Opening the drawer again is the obvious way to try a list that failed to load once more; a list the
     * last action removed routes from is refetched for the same reason.
     * @private
     */
    _reloadWhenOutOfDate() {
        if (this._failed || this._listIsStale) {
            this.reload();
        }
    }

    /**
     * @returns {string}
     * @private
     */
    _getFilterParams() {
        return JSON.stringify(new SearchParams(this.filters).params);
    }

    /**
     * @param {Number} direction
     * @private
     */
    _goToPage(direction) {
        let lastPage = Math.max(0, Math.ceil(this._total / this.options.pageSize) - 1);
        this._page = Math.min(lastPage, Math.max(0, this._page + direction));
        this._search();
    }

    /**
     * @private
     */
    _search() {
        let self = this;

        this._failed = false;
        this._listIsStale = false;
        this._previousFilterParams = this._getFilterParams();

        super._search({
            beforeSend: function () {
                self._setState('loading');
            },
            success: function (json) {
                self._total = json.recordsFiltered;
                self._renderRows(json.data.map(row => new PickerDungeonRoute(row)));
                self._setState(json.data.length === 0 ? 'empty' : 'loaded');
            },
            error: function () {
                self._failed = true;
                self._total = 0;
                self._renderRows([]);
                self._setState('error');
            },
        }, {
            offset: this._page * this.options.pageSize,
            limit: this.options.pageSize,
        });
    }

    /**
     * @param {string} state loading|loaded|empty|error
     * @private
     */
    _setState(state) {
        this._state = state;
        this._refreshSelectPage();

        $(this.options.loadingSelector).prop('hidden', state !== 'loading');
        $(this.options.emptySelector).prop('hidden', state !== 'empty');
        $(this.options.errorSelector).prop('hidden', state !== 'error');
        $(this.options.listSelector).closest('[aria-busy]').attr('aria-busy', state === 'loading' ? 'true' : 'false');

        let from = this._total === 0 ? 0 : this._page * this.options.pageSize + 1;
        let to = Math.min(this._total, (this._page + 1) * this.options.pageSize);
        $(this.options.rangeSelector).text(state === 'loaded' ? lang.get('js.dungeonroute_picker_range', {
            from: from,
            to: to,
            total: this._total,
        }) : '');

        $(this.options.previousSelector).prop('disabled', state !== 'loaded' || this._page === 0);
        $(this.options.nextSelector).prop('disabled', state !== 'loaded' || to >= this._total);
    }

    /**
     * @param {PickerDungeonRoute[]} dungeonRoutes
     * @private
     */
    _renderRows(dungeonRoutes) {
        let self = this;
        let template = Handlebars.templates['dungeonroute_picker_row'];

        // A ticked route is kept after it leaves the listing: the host page is handed every route it adds,
        // and a selection may span pages and filters
        let kept = {};
        this._selected.forEach(function (publicKey) {
            if (typeof self._dungeonRoutes[publicKey] !== 'undefined') {
                kept[publicKey] = self._dungeonRoutes[publicKey];
            }
        });
        this._dungeonRoutes = kept;

        $(this.options.listSelector).html(dungeonRoutes.map(function (dungeonRoute) {
            self._dungeonRoutes[dungeonRoute.publicKey] = dungeonRoute;

            return template($.extend({}, getHandlebarsDefaultVariables(),
                dungeonRoute.toTemplateData(self.options.fallbackImageBaseUrl)));
        }).join(''));

        this._refreshRows();
    }

    /**
     * The affix filter lists its affix icons, the same way the route table's filter does.
     * @private
     */
    _decorateAffixOptions() {
        let affixGroups = this.options.affixGroups || {};
        let template = Handlebars.templates['affixgroup_select_option_template'];

        for (let affixGroupId in affixGroups) {
            if (!affixGroups.hasOwnProperty(affixGroupId)) {
                continue;
            }

            $(`${this.options.affixSelectSelector} option[value='${affixGroupId}']`)
                .attr('data-content', template({affixes: affixGroups[affixGroupId]}));
        }

        refreshSelectPickers();
    }

    /**
     * Syncs every rendered row's checkbox with the existing and ticked routes and the max.
     * @private
     */
    _refreshRows() {
        let self = this;
        let isFull = this.getRemaining() === 0;

        $(this.options.listSelector).find('.route_picker_row').each(function () {
            let $row = $(this);
            let publicKey = $row.attr('data-public-key');
            let isExisting = self._existing.has(publicKey);
            let isSelected = self._selected.includes(publicKey);

            $row.toggleClass('route_picker_row_existing', isExisting);
            $row.toggleClass('route_picker_row_selected', isSelected);
            $row.find('.route_picker_already_in').prop('hidden', !isExisting);
            $row.find('.route_picker_checkbox')
                .prop('checked', isExisting || isSelected)
                .prop('disabled', isExisting || ((isFull || self._isDungeonFull(publicKey)) && !isSelected));
        });

        this._refreshSelectPage();
    }

    /**
     * @returns {string[]} The listed page's routes that are not in the target yet, in listed order.
     * @private
     */
    _getPageSelectablePublicKeys() {
        let self = this;

        return $(this.options.listSelector).find('.route_picker_row').map(function () {
            return $(this).attr('data-public-key');
        }).get().filter(publicKey => !self._existing.has(publicKey));
    }

    /**
     * @param {string} publicKey
     * @returns {boolean} Whether ticking the route now would be accepted.
     * @private
     */
    _canTick(publicKey) {
        return !this._selected.includes(publicKey) && !this._existing.has(publicKey) && this.getRemaining() !== 0 &&
            !this._isDungeonFull(publicKey);
    }

    /**
     * Checked when every selectable route of the page is ticked, mixed when some are.
     * @private
     */
    _refreshSelectPage() {
        if (!this.options.selectPageSelector) {
            return;
        }

        let self = this;
        let selectable = this._getPageSelectablePublicKeys();
        let selectedCount = selectable.filter(publicKey => self._selected.includes(publicKey)).length;
        let canTickMore = selectable.some(publicKey => self._canTick(publicKey));
        let $checkbox = $(this.options.selectPageSelector);

        $checkbox.closest('.route_picker_select_page').prop('hidden', this._state !== 'loaded' || selectable.length === 0);
        $checkbox
            .prop('checked', selectable.length > 0 && selectedCount === selectable.length)
            .prop('indeterminate', selectedCount > 0 && selectedCount < selectable.length)
            .prop('disabled', selectedCount === 0 && !canTickMore);
    }

    /**
     * Ticks the page's routes in listed order while they fit; with nothing more to tick, a click unticks the page.
     * @private
     */
    _onSelectPageChanged() {
        let self = this;
        let selectable = this._getPageSelectablePublicKeys();

        if (selectable.some(publicKey => self._canTick(publicKey))) {
            selectable.forEach(function (publicKey) {
                if (self._canTick(publicKey)) {
                    self._selected.push(publicKey);
                }
            });
        } else {
            this._selected = this._selected.filter(publicKey => !selectable.includes(publicKey));
        }

        this._refreshRows();
        this._refreshSelection();

        let count = this._selected.length;
        let plural = count === 0 ? 'none' : (count === 1 ? 'one' : 'many');
        this.dialog.setStatus(lang.get(`js.dungeonroute_picker_selected_${plural}`, {count: count}));
    }

    /**
     * @param {Event} event
     * @private
     */
    _onCheckboxChanged(event) {
        let $checkbox = $(event.currentTarget);
        let publicKey = $checkbox.val();

        if ($checkbox.prop('checked')) {
            if (this._canTick(publicKey)) {
                this._selected.push(publicKey);
            }
        } else {
            this._selected = this._selected.filter(selectedPublicKey => selectedPublicKey !== publicKey);
        }

        this._refreshRows();
        this._refreshSelection();
    }

    /**
     * @private
     */
    _refreshSelection() {
        let count = this._selected.length;
        let plural = count === 0 ? 'none' : (count === 1 ? 'one' : 'many');

        $(this.options.selectionSelector).text(lang.get(`js.dungeonroute_picker_selected_${plural}`, {count: count}));
        let isFull = this.getRemaining() === 0;
        let hasFullDungeon = !isFull && Object.keys(this._dungeonRoutes).some(publicKey =>
            !this._existing.has(publicKey) && !this._selected.includes(publicKey) && this._isDungeonFull(publicKey));

        $(this.options.fullSelector)
            .text(isFull
                ? lang.get(this._getFullKey(), {max: this.options.max})
                : lang.get('js.dungeonroute_picker_dungeon_full', {max: this.options.maxPerDungeon}))
            .prop('hidden', !isFull && !hasFullDungeon);
        this.dialog.setConfirmButton(
            lang.get(`js.${this.options.actionKeyPrefix}_${plural}`, {count: count}),
            count > 0 && !this._saving,
        );
    }

    /**
     * @returns {string} The action's own wording of the max, or the drawer's general one when it has none.
     * @private
     */
    _getFullKey() {
        let actionKey = `js.${this.options.actionKeyPrefix}_full`;

        return lang.has(actionKey) ? actionKey : 'js.dungeonroute_picker_full';
    }

    /**
     * @private
     */
    _confirmDungeonRoutes() {
        let publicKeys = this.getSelectedPublicKeys();

        if (publicKeys.length === 0 || this._saving) {
            return;
        }

        if (!this.options.confirmsAction) {
            this._sendDungeonRoutes(publicKeys);

            return;
        }

        let plural = publicKeys.length === 1 ? 'one' : 'many';
        showConfirmYesCancel(
            lang.get(`js.${this.options.actionKeyPrefix}_confirm_${plural}`, {count: publicKeys.length}),
            this._sendDungeonRoutes.bind(this, publicKeys)
        );
    }

    /**
     * @param {string[]} publicKeys
     * @private
     */
    _sendDungeonRoutes(publicKeys) {
        let self = this;

        // Without an endpoint of its own the drawer only hands the routes over; the host page acts on them
        if (this.options.actionUrl === null || typeof this.options.actionUrl === 'undefined') {
            this._reportConfirmed(publicKeys, null);
            this.close();

            return;
        }

        this._saving = true;
        this._refreshSelection();

        let data = {};
        data[this.options.actionFieldName] = publicKeys;

        $.ajax({
            type: this.options.actionMethod,
            url: this.options.actionUrl,
            dataType: 'json',
            data: data,
            success: function (response) {
                let actedPublicKeys = self._getActedPublicKeys(publicKeys, response);

                if (actedPublicKeys.length > 0) {
                    self._reportConfirmed(actedPublicKeys, response);
                }

                // A server that acted on fewer routes than it was sent keeps the rest ticked, so the user
                // retries only those instead of a selection the endpoint would now reject
                if (actedPublicKeys.length < publicKeys.length) {
                    self.dialog.setStatus(lang.get(`js.${self.options.actionKeyPrefix}_failed`));

                    if (self._listIsStale) {
                        self.reload();
                    }

                    return;
                }

                self.close();
            },
            error: function () {
                self.dialog.setStatus(lang.get(`js.${self.options.actionKeyPrefix}_failed`));
            },
            complete: function () {
                self._saving = false;
                self._refreshSelection();
            },
        });
    }

    /**
     * The routes the server reports it acted on; every sent route when it reports nothing of its own.
     * @param {string[]} publicKeys
     * @param {*} response
     * @returns {string[]}
     * @private
     */
    _getActedPublicKeys(publicKeys, response) {
        let field = this.options.actedFieldName;

        if (!field || response === null || typeof response !== 'object' || !Array.isArray(response[field])) {
            return publicKeys;
        }

        return publicKeys.filter(publicKey => response[field].includes(publicKey));
    }

    /**
     * @param {string[]} publicKeys
     * @param {*} response
     * @private
     */
    _reportConfirmed(publicKeys, response) {
        let self = this;
        let dungeonRoutes = publicKeys
            .map(publicKey => self._dungeonRoutes[publicKey])
            .filter(dungeonRoute => typeof dungeonRoute !== 'undefined');

        if (this.options.removesActedRoutes) {
            // The routes are gone from the source, so they are not "already in the target" and must not keep
            // occupying the max either - the next batch starts with the whole allowance again
            publicKeys.forEach(publicKey => delete self._dungeonRoutes[publicKey]);
            this._listIsStale = true;
        } else {
            publicKeys.forEach(publicKey => self._existing.add(publicKey));
            dungeonRoutes.forEach(dungeonRoute => self._existingDungeonIds[dungeonRoute.publicKey] = dungeonRoute.dungeonId);
        }

        this._selected = this._selected.filter(publicKey => !publicKeys.includes(publicKey));
        this._refreshRows();
        this._refreshSelection();

        /** @type {CommonDungeonroutePickerResult} */
        let result = {publicKeys: publicKeys, dungeonRoutes: dungeonRoutes, response: response};
        this.dialog.trigger('dungeonroutepicker:confirmed', [result]);
        this._onConfirmedCallbacks.forEach(callback => callback(result));
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonDungeonroutePicker};
}

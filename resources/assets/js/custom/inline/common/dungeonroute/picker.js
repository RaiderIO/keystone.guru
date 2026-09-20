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
 @property {string} addButtonSelector
 @property {string} statusSelector            Polite live region.
 @property {string} listUrl                   Server-side paged route list (DataTables protocol).
 @property {Number} pageSize
 @property {Object} sourceParameters          Sent with every list request, selects where the routes come from.
 @property {Object} lockedParameters          Sent with every list request, the constraints the user cannot change.
 @property {string[]} existingPublicKeys      Routes already in the target.
 @property {Number|null} max                  Most routes the target may hold, null for no limit.
 @property {string|null} addUrl               Where the ticked routes are POSTed; null leaves saving to the host.
 @property {string} addFieldName
 @property {string} fallbackImageBaseUrl
 @property {Object<string, {class: string, name: string}[]>} affixGroups Affixes per affix group id, for the filter's icons.
 */

/**
 @typedef {Object} CommonDungeonroutePickerResult
 @property {string[]} publicKeys
 @property {PickerDungeonRoute[]} dungeonRoutes  The listed routes the public keys were ticked on.
 @property {*} response                          What the add url answered; null when the drawer posted nothing.
 */

/**
 * Side drawer listing the source's routes, page by page, to tick and add to a target in one go. It knows
 * nothing about the target beyond the options: it fires `dungeonroutepicker:added` on the drawer element (and
 * calls every onAdded() callback) with a CommonDungeonroutePickerResult, so the host can store the routes and
 * show its toast.
 *
 * @property {CommonDungeonroutePickerOptions} options
 */
class CommonDungeonroutePicker extends SearchInlineBase {

    constructor(id, bladePath, options) {
        super(new SearchHandlerDungeonRoutePicker(options), id, bladePath, options);

        this.dialog = new DrawerDialog({
            drawerSelector: options.drawerSelector,
            openButtonSelector: options.openButtonSelector,
            confirmButtonSelector: options.addButtonSelector,
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

        this._page = 0;
        this._total = 0;
        this._saving = false;
        this._failed = false;
        /** @type {string|null} The filter values of the last search, without its paging */
        this._previousFilterParams = null;
        /** @type {Set<string>} */
        this._existing = new Set(this.options.existingPublicKeys || []);
        /** @type {string[]} Ticked public keys, in the order they were ticked */
        this._selected = [];
        /** @type {Object<string, PickerDungeonRoute>} Every listed or ticked route, by public key */
        this._dungeonRoutes = {};
        this._onAddedCallbacks = [];
    }

    activate() {
        super.activate();

        this._decorateAffixOptions();

        this.dialog.activate();
        this.dialog.onFirstShow(this.reload.bind(this));
        this.dialog.onShow(this._retryAfterFailure.bind(this));
        this.dialog.onConfirm(this._addDungeonRoutes.bind(this));

        $(this.options.previousSelector).on('click', this._goToPage.bind(this, -1));
        $(this.options.nextSelector).on('click', this._goToPage.bind(this, 1));
        $(this.options.listSelector).on('change', '.route_picker_checkbox', this._onCheckboxChanged.bind(this));

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
     * Registers a callback called with a CommonDungeonroutePickerResult after every successful add.
     * @param {Function} callback
     */
    onAdded(callback) {
        this._onAddedCallbacks.push(callback);
    }

    /**
     * Replaces the routes already in the target, e.g. after the host undid an add or removed a route.
     * @param {string[]} publicKeys
     */
    setExistingPublicKeys(publicKeys) {
        this._existing = new Set(publicKeys);
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
     * Opening the drawer again is the obvious way to try a list that failed to load once more.
     * @private
     */
    _retryAfterFailure() {
        if (this._failed) {
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
                .prop('disabled', isExisting || (isFull && !isSelected));
        });
    }

    /**
     * @param {Event} event
     * @private
     */
    _onCheckboxChanged(event) {
        let $checkbox = $(event.currentTarget);
        let publicKey = $checkbox.val();

        if ($checkbox.prop('checked')) {
            if (!this._selected.includes(publicKey) && !this._existing.has(publicKey) && this.getRemaining() !== 0) {
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
        $(this.options.fullSelector)
            .text(lang.get('js.dungeonroute_picker_full', {max: this.options.max}))
            .prop('hidden', this.getRemaining() !== 0);
        this.dialog.setConfirmButton(
            lang.get(`js.dungeonroute_picker_add_${plural}`, {count: count}),
            count > 0 && !this._saving,
        );
    }

    /**
     * @private
     */
    _addDungeonRoutes() {
        let self = this;
        let publicKeys = this.getSelectedPublicKeys();

        if (publicKeys.length === 0 || this._saving) {
            return;
        }

        // Without an endpoint of its own the drawer only hands the routes over; the host page saves them
        if (this.options.addUrl === null || typeof this.options.addUrl === 'undefined') {
            this._reportAdded(publicKeys, null);
            this.close();

            return;
        }

        this._saving = true;
        this._refreshSelection();

        let data = {};
        data[this.options.addFieldName] = publicKeys;

        $.ajax({
            type: 'POST',
            url: this.options.addUrl,
            dataType: 'json',
            data: data,
            success: function (response) {
                self._reportAdded(publicKeys, response);
                self.close();
            },
            error: function () {
                self.dialog.setStatus(lang.get('js.dungeonroute_picker_add_failed'));
            },
            complete: function () {
                self._saving = false;
                self._refreshSelection();
            },
        });
    }

    /**
     * @param {string[]} publicKeys
     * @param {*} response
     * @private
     */
    _reportAdded(publicKeys, response) {
        let self = this;
        let dungeonRoutes = publicKeys
            .map(publicKey => self._dungeonRoutes[publicKey])
            .filter(dungeonRoute => typeof dungeonRoute !== 'undefined');

        publicKeys.forEach(publicKey => self._existing.add(publicKey));
        this._selected = [];
        this._refreshRows();
        this._refreshSelection();

        /** @type {CommonDungeonroutePickerResult} */
        let result = {publicKeys: publicKeys, dungeonRoutes: dungeonRoutes, response: response};
        this.dialog.trigger('dungeonroutepicker:added', [result]);
        this._onAddedCallbacks.forEach(callback => callback(result));
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonDungeonroutePicker};
}

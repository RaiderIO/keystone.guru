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
 @property {string} rowTemplateSelector       <template> holding one blank route row.
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
 @property {string} addUrl
 @property {string} addFieldName
 @property {string} fallbackImageBaseUrl
 @property {string} rangeText                 Contains :from, :to and :total.
 @property {string} keyLevelText              Contains :level.
 @property {string} keyRangeText              Contains :min and :max.
 @property {string} enemyForcesText           Contains :count and :required.
 @property {string} selectedNoneText
 @property {string} selectedOneText
 @property {string} selectedManyText          Contains :count.
 @property {string} fullText                  Contains :max.
 @property {string} addNoneText
 @property {string} addOneText
 @property {string} addManyText               Contains :count.
 @property {string} addFailedText
 */

/**
 * Side drawer listing the source's routes, page by page, to tick and add to a target in one POST. It
 * knows nothing about the target beyond the options: after a successful add it fires
 * `routepicker:added` on the drawer element (and calls every onAdded() callback) with the added public
 * keys and the endpoint's response, so the host can show its toast and Undo.
 *
 * @property {CommonDungeonroutePickerOptions} options
 */
class CommonDungeonroutePicker extends InlineCode {

    constructor(id, bladePath, options) {
        super(id, bladePath, options);

        this._page = 0;
        this._total = 0;
        this._draw = 0;
        this._loaded = false;
        this._saving = false;
        this._request = null;
        this._titleSearchTimeout = null;
        /** @type {Set<string>} */
        this._existing = new Set(this.options.existingPublicKeys || []);
        /** @type {string[]} Ticked public keys, in the order they were ticked */
        this._selected = [];
        this._onAddedCallbacks = [];
    }

    activate() {
        super.activate();

        let $drawer = $(this.options.drawerSelector);

        if (this.options.openButtonSelector) {
            $(document).on('click', this.options.openButtonSelector, this._onOpenClicked.bind(this));
        }

        $drawer.on('show.bs.offcanvas', this._onShow.bind(this));

        $(this.options.titleSearchSelector).on('input', this._onTitleSearchInput.bind(this));
        $([
            this.options.dungeonSelectSelector,
            this.options.affixSelectSelector,
            this.options.attributesSelectSelector,
            this.options.requirementsSelectSelector,
            this.options.tagsSelectSelector,
        ].join(',')).on('change', this.reload.bind(this));

        $(this.options.previousSelector).on('click', this._goToPage.bind(this, -1));
        $(this.options.nextSelector).on('click', this._goToPage.bind(this, 1));
        $(this.options.listSelector).on('change', '.route_picker_checkbox', this._onCheckboxChanged.bind(this));
        $(this.options.addButtonSelector).on('click', this._add.bind(this));

        this._refreshSelection();
    }

    /**
     * Opens the drawer; the first open loads the first page.
     */
    open() {
        bootstrap.Offcanvas.getOrCreateInstance($(this.options.drawerSelector)[0]).show();
    }

    close() {
        bootstrap.Offcanvas.getOrCreateInstance($(this.options.drawerSelector)[0]).hide();
    }

    /**
     * Registers a callback called with ({publicKeys: string[], response: *}) after every successful add.
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
        this._load();
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
     * @param {Event} event
     * @private
     */
    _onOpenClicked(event) {
        event.preventDefault();
        this.open();
    }

    /**
     * @private
     */
    _onShow() {
        if (!this._loaded) {
            this.reload();
        }
    }

    /**
     * @private
     */
    _onTitleSearchInput() {
        clearTimeout(this._titleSearchTimeout);
        this._titleSearchTimeout = setTimeout(this.reload.bind(this), 300);
    }

    /**
     * @param {Number} direction
     * @private
     */
    _goToPage(direction) {
        let lastPage = Math.max(0, Math.ceil(this._total / this.options.pageSize) - 1);
        this._page = Math.min(lastPage, Math.max(0, this._page + direction));
        this._load();
    }

    /**
     * Builds a request in the DataTables server-side protocol that /ajax/routes speaks.
     * @returns {Object}
     * @private
     */
    _getRequestData() {
        let column = function (data, name, searchValue, orderable) {
            return {
                data: data,
                name: name,
                searchable: 'true',
                orderable: orderable ? 'true' : 'false',
                search: {value: searchValue, regex: 'false'},
            };
        };

        return $.extend({
            draw: this._draw,
            start: this._page * this.options.pageSize,
            length: this.options.pageSize,
            columns: [
                column('title', 'title', ($(this.options.titleSearchSelector).val() || '').trim(), true),
                column('dungeon', 'dungeon_id', $(this.options.dungeonSelectSelector).val() || '', false),
                column('affixes', 'affixes.id', $(this.options.affixSelectSelector).val() || [], false),
                column('routeattributes', 'routeattributes.name', $(this.options.attributesSelectSelector).val() || [], false),
            ],
            order: [{column: 0, dir: 'asc'}],
            search: {value: '', regex: 'false'},
            requirements: $(this.options.requirementsSelectSelector).val() || [],
            tags: $(this.options.tagsSelectSelector).val() || [],
        }, this.options.sourceParameters, this.options.lockedParameters);
    }

    /**
     * @private
     */
    _load() {
        let self = this;

        if (this._request !== null) {
            this._request.abort();
        }

        this._draw++;
        this._loaded = true;
        this._setState('loading');

        let draw = this._draw;
        this._request = $.ajax({
            type: 'GET',
            url: this.options.listUrl,
            dataType: 'json',
            data: this._getRequestData(),
            cache: false,
            success: function (json) {
                // A slower, older response must not overwrite the newest one
                if (parseInt(json.draw) !== self._draw) {
                    return;
                }

                self._total = json.recordsFiltered;
                self._renderRows(json.data);
                self._setState(json.data.length === 0 ? 'empty' : 'loaded');
            },
            error: function (xhr, textStatus) {
                if (textStatus !== 'abort') {
                    self._total = 0;
                    self._renderRows([]);
                    self._setState('error');
                }
            },
            complete: function () {
                if (draw === self._draw) {
                    self._request = null;
                }
            },
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
        $(this.options.rangeSelector).text(state === 'loaded' ? this._format(this.options.rangeText, {
            from: from,
            to: to,
            total: this._total,
        }) : '');

        $(this.options.previousSelector).prop('disabled', state !== 'loaded' || this._page === 0);
        $(this.options.nextSelector).prop('disabled', state !== 'loaded' || to >= this._total);
    }

    /**
     * @param {Object[]} rows
     * @private
     */
    _renderRows(rows) {
        let $list = $(this.options.listSelector).empty();
        let template = $(this.options.rowTemplateSelector).prop('content').firstElementChild;

        for (let index in rows) {
            if (!rows.hasOwnProperty(index)) {
                continue;
            }

            let row = rows[index];
            let $row = $(template.cloneNode(true));

            $row.attr('data-public-key', row.public_key);
            $row.find('.route_picker_checkbox').val(row.public_key);
            $row.find('.route_picker_thumbnail').attr('src', this._getThumbnailUrl(row));
            $row.find('.route_picker_title').text(row.title);
            $row.find('.route_picker_dungeon').text(this._translate(row.dungeon.name));
            $row.find('.route_picker_key_range').text(this._getKeyRange(row));
            $row.find('.route_picker_enemy_forces').text(this._getEnemyForces(row));
            $row.find('.route_picker_unpublished').prop('hidden', row.published !== 'unpublished');

            $list.append($row);
        }

        this._refreshRows();
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
        let remaining = this.getRemaining();

        let selectionText = count === 0 ? this.options.selectedNoneText :
            (count === 1 ? this.options.selectedOneText : this.options.selectedManyText);
        let addText = count === 0 ? this.options.addNoneText :
            (count === 1 ? this.options.addOneText : this.options.addManyText);

        $(this.options.selectionSelector).text(this._format(selectionText, {count: count}));
        $(this.options.fullSelector)
            .text(this._format(this.options.fullText, {max: this.options.max}))
            .prop('hidden', remaining !== 0);
        $(this.options.addButtonSelector)
            .text(this._format(addText, {count: count}))
            .prop('disabled', count === 0 || this._saving);
    }

    /**
     * @private
     */
    _add() {
        let self = this;
        let publicKeys = this.getSelectedPublicKeys();

        if (publicKeys.length === 0 || this._saving) {
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
                publicKeys.forEach(publicKey => self._existing.add(publicKey));
                self._selected = [];
                self._refreshRows();

                let result = {publicKeys: publicKeys, response: response};
                $(self.options.drawerSelector).trigger('routepicker:added', [result]);
                self._onAddedCallbacks.forEach(callback => callback(result));

                self.close();
            },
            error: function () {
                $(self.options.statusSelector).text(self.options.addFailedText);
            },
            complete: function () {
                self._saving = false;
                self._refreshSelection();
            },
        });
    }

    /**
     * @param {Object} row
     * @returns {string}
     * @private
     */
    _getThumbnailUrl(row) {
        if (row.has_thumbnail && row.thumbnails && row.thumbnails.length > 0) {
            return row.thumbnails[0].url;
        }

        return `${this.options.fallbackImageBaseUrl}/dungeons/${row.dungeon.expansion.shortname}/${row.dungeon.key}_3-2.jpg`;
    }

    /**
     * @param {Object} row
     * @returns {string}
     * @private
     */
    _getKeyRange(row) {
        if (row.level_min === null || typeof row.level_min === 'undefined') {
            return '';
        }

        if (row.level_max === null || typeof row.level_max === 'undefined' || row.level_min === row.level_max) {
            return this._format(this.options.keyLevelText, {level: row.level_min});
        }

        return this._format(this.options.keyRangeText, {min: row.level_min, max: row.level_max});
    }

    /**
     * @param {Object} row
     * @returns {string}
     * @private
     */
    _getEnemyForces(row) {
        let required = row.teeming === 1 ? row.enemy_forces_required_teeming : row.enemy_forces_required;

        return this._format(this.options.enemyForcesText, {count: row.enemy_forces, required: required});
    }

    /**
     * @param {string} key
     * @returns {string}
     * @private
     */
    _translate(key) {
        return typeof lang !== 'undefined' ? lang.get(key) : key;
    }

    /**
     * @param {string} text
     * @param {Object} replacements
     * @returns {string}
     * @private
     */
    _format(text, replacements) {
        let result = text;
        for (let key in replacements) {
            if (replacements.hasOwnProperty(key)) {
                // The lookahead keeps :to from matching inside :total
                result = result.replace(new RegExp(`:${key}(?![a-z_])`, 'g'), replacements[key]);
            }
        }

        return result;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonDungeonroutePicker};
}

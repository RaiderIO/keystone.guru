/**
 * @typedef {Object} CommonMapsCombatlogrouteenemyresolutionsOptions
 * @property {Number}   dungeonId
 * @property {Number}   mappingVersionId
 * @property {String}   pageUrl
 * @property {String}   getEnemyResolutionsUrl
 * @property {String}   linesUrl
 * @property {String}   showLinesSelector
 * @property {Object}   linePopupTexts     npc, distance, weighted, route, importedRoute, noRoute
 * @property {String}   groupsUrl
 * @property {String}   showGroupsSelector
 * @property {Object}   verdictColors      verdict key => colour
 * @property {String}   deleteUrl
 * @property {String}   filterMappingVersionIdSelector
 * @property {String}   filterNpcIdSelector
 * @property {String}   filterMetricSelector
 * @property {String}   filterMinDistanceSelector
 * @property {String}   clearButtonSelector
 * @property {String}   summarySelector
 * @property {String}   routesContainerSelector
 * @property {String}   routesListSelector
 * @property {String}   summaryText        :drawn, :total and :max placeholders
 * @property {String[]} dependencies
 */

/**
 * @property {CommonMapsCombatlogrouteenemyresolutionsOptions} options
 */
class CommonMapsCombatlogrouteenemyresolutions extends SearchInlineBase {
    constructor(id, bladePath, options) {
        super(new SearchHandlerCombatLogRouteEnemyResolutions(options), id, bladePath, options);

        this.filters = {
            'dungeon_id': new SearchFilterPassThrough(),
            'mapping_version_id': new SearchFilterPassThrough(),
            // Registered rather than read in _search() so that SearchInlineBase restores them from the URL - a
            // shared link to a hot spot must come back with the same weighting and cut-off
            'metric': new SearchFilterInputChange(options.filterMetricSelector, this._search.bind(this)),
            'min_distance': new SearchFilterInputChange(options.filterMinDistanceSelector, this._search.bind(this)),
        };
        this.filters['dungeon_id'].setValue(options.dungeonId);
        this.filters['mapping_version_id'].setValue(options.mappingVersionId);
    }

    activate() {
        super.activate();

        // A cell's weight here is a distance, not a count of records, so the renderer must not add up the cells it
        // folds together
        getState().getDungeonMap().pluginHeat.setCombineMode(HEAT_COMBINE_MODE_MAX);
        getState().getDungeonMap().pluginHeat.toggle(true);

        // The map context (enemies, floor unions) is built server-side for the selected mapping version, and so are
        // the per-npc counts in the npc filter - switching is a navigation, not a re-fetch.
        $(this.options.filterMappingVersionIdSelector).on('change', (event) => {
            this._navigateTo(`${this.options.pageUrl}?dungeon_id=${this.options.dungeonId}&mapping_version_id=${$(event.target).val()}`);
        });

        $(this.options.filterNpcIdSelector).on('change', () => this._search());

        // The npc selection goes into the URL from _search(), but it is not a registered filter - nothing restores
        // it the way SearchInlineBase restores the others, so a shared link would come back showing every npc
        this._restoreNpcSelectionFromQueryParams();

        $(this.options.clearButtonSelector).on('click', () => {
            $.ajax({type: 'DELETE', url: this.options.deleteUrl, data: {dungeon_id: this.options.dungeonId}})
                .done(() => {
                    // The counts in both filters are rendered server-side - reload to reset them
                    this._reload();
                });
        });

        // Lines: redrawn whenever the floor changes (the layer group survives a floor switch, so it has to be
        // cleared by hand) and refetched with every search
        this._lines = [];
        $(this.options.showLinesSelector).on('change', () => this._redrawLines());
        getState().register('floorid:changed', this, () => this._redrawLines());

        // Pack groups: same lifecycle as the lines
        this._groups = [];
        $(this.options.showGroupsSelector).on('change', () => this._redrawGroups());
        getState().register('floorid:changed', this, () => this._redrawGroups());

        this._search();
    }

    /**
     * @param {Object}   options
     * @param {Object}   queryParameters
     * @param {string[]} queryParametersUrlBlacklist
     * @protected
     */
    _search(options = {}, queryParameters = {}, queryParametersUrlBlacklist = []) {
        let npcIds = this._getSelectedNpcIds();

        if (npcIds.length > 0) {
            queryParameters = $.extend({}, queryParameters, {'npc_id': npcIds});
        }

        super._search(options, queryParameters, queryParametersUrlBlacklist);

        this._fetchLines(npcIds);
        this._fetchGroups(npcIds);
    }

    /**
     * @param {Number[]} npcIds
     * @protected
     */
    _fetchLines(npcIds) {
        if (!this.options.linesUrl) {
            return;
        }

        let data = {dungeon_id: this.options.dungeonId, mapping_version_id: this.options.mappingVersionId};
        if (npcIds.length > 0) {
            data.npc_id = npcIds;
        }

        // Read from the filter rather than the input, so the lines and the heatmap under them can never end up
        // filtering on different values
        let minDistance = this.filters['min_distance'].getValue();
        if (minDistance !== '' && minDistance !== null && typeof minDistance !== 'undefined') {
            data.min_distance = minDistance;
        }

        // Rapid filter changes fire overlapping requests - only the latest one may paint, so a slower earlier
        // response is discarded when it returns
        let requestId = this._lineRequestId = (this._lineRequestId || 0) + 1;

        $.ajax({type: 'GET', url: this.options.linesUrl, data: data, dataType: 'json'})
            .done((json) => {
                if (requestId !== this._lineRequestId) {
                    return;
                }

                this._lines = (json && json.data) || [];
                this._redrawLines();
            });
    }

    /**
     * @param {Number[]} npcIds
     * @protected
     */
    _fetchGroups(npcIds) {
        if (!this.options.groupsUrl) {
            return;
        }

        let data = {dungeon_id: this.options.dungeonId, mapping_version_id: this.options.mappingVersionId};
        if (npcIds.length > 0) {
            data.npc_id = npcIds;
        }

        let minDistance = this.filters['min_distance'].getValue();
        if (minDistance !== '' && minDistance !== null && typeof minDistance !== 'undefined') {
            data.min_distance = minDistance;
        }

        let requestId = this._groupRequestId = (this._groupRequestId || 0) + 1;

        $.ajax({type: 'GET', url: this.options.groupsUrl, data: data, dataType: 'json'})
            .done((json) => {
                if (requestId !== this._groupRequestId) {
                    return;
                }

                this._groups = (json && json.data) || [];
                this._redrawGroups();
            });
    }

    /**
     * @returns {L.LayerGroup}
     * @private
     */
    _getGroupLayerGroup() {
        if (!this._groupLayerGroup) {
            this._groupLayerGroup = L.layerGroup().addTo(getState().getDungeonMap().leafletMap);
        }

        return this._groupLayerGroup;
    }

    /**
     * Draws a dashed arrow per pack group on the current floor, from its mapped centroid to where it is engaged, in its
     * verdict's colour - faded when it was seen in few routes.
     * @protected
     */
    _redrawGroups() {
        let layerGroup = this._getGroupLayerGroup();
        layerGroup.clearLayers();

        let $toggle = $(this.options.showGroupsSelector);
        if ($toggle.length > 0 && !$toggle.is(':checked')) {
            return;
        }

        let currentFloor = getState().getCurrentFloor();
        if (!currentFloor) {
            return;
        }

        for (let group of this._groups) {
            if (group.floor_id !== currentFloor.id) {
                continue;
            }

            let color   = this.options.verdictColors[group.verdict] ?? '#ffffff';
            let opacity = group.low_volume ? 0.3 : 0.9;
            let mapped  = [group.mapped_centroid.lat, group.mapped_centroid.lng];
            let engaged = [group.engaged_centroid.lat, group.engaged_centroid.lng];
            let popup   = this._getGroupPopupHtml(group);

            L.polyline([mapped, engaged], {color: color, weight: 3, opacity: opacity, dashArray: '6 4'})
                .bindPopup(popup, {maxWidth: 360}).addTo(layerGroup);
            L.circleMarker(mapped, {radius: 4, color: color, weight: 2, opacity: opacity, fillOpacity: 0})
                .bindPopup(popup, {maxWidth: 360}).addTo(layerGroup);
            L.circleMarker(engaged, {
                radius: 5 + 2 * Math.log2(Math.max(1, group.route_count)),
                color: color, weight: 2, opacity: opacity, fillColor: color, fillOpacity: group.low_volume ? 0.15 : 0.6,
            }).bindPopup(popup, {maxWidth: 360}).addTo(layerGroup);
        }
    }

    /**
     * @param {Object} group
     * @returns {String}
     * @private
     */
    _getGroupPopupHtml(group) {
        let escape = (text) => $('<span>').text(text ?? '').html();
        let color  = this.options.verdictColors[group.verdict] ?? '#ffffff';
        let title  = group.enemy_pack_id === null
            ? lang.get('js.enemy_resolution_group_enemy', {id: group.enemy_ids[0]})
            : lang.get('js.enemy_resolution_group_pack', {group: group.enemy_pack_group ?? '-', id: group.enemy_pack_id});

        let rows = [
            `<div><strong>${escape(title)}</strong> ` +
            `<span class="badge" style="background-color: ${color}">${escape(lang.get(`js.enemy_resolution_group_verdict_${group.verdict}`))}</span></div>`,
            `<div>${escape(group.npc_names)}</div>`,
            `<div>${escape(lang.get('js.enemy_resolution_group_resolutions', {count: group.count, routes: group.route_count, share: Math.round(group.route_share * 100)}))}</div>`,
            `<div>${escape(lang.get('js.enemy_resolution_group_displacement', {distance: Math.round(group.displacement)}))}</div>`,
            `<div class="text-muted small">${escape(lang.get('js.enemy_resolution_group_consistency', {consistency: group.direction_consistency, ratio: group.shape_ratio ?? '-'}))}</div>`,
            `<div class="text-muted small">${escape(lang.get('js.enemy_resolution_group_seen', {first: (group.first_seen ?? '').substring(0, 10), last: (group.last_seen ?? '').substring(0, 10)}))}</div>`,
        ];

        if (group.low_volume) {
            rows.push(`<div class="text-warning small">${escape(lang.get('js.enemy_resolution_group_low_volume'))}</div>`);
        }

        rows.push(`<div class="mt-1"><em>${escape(group.suggestion)}</em></div>`);

        return rows.join('');
    }

    /**
     * @returns {L.LayerGroup}
     * @private
     */
    _getLineLayerGroup() {
        if (!this._lineLayerGroup) {
            this._lineLayerGroup = L.layerGroup().addTo(getState().getDungeonMap().leafletMap);
        }

        return this._lineLayerGroup;
    }

    /**
     * Draws a line per recorded match on the current floor, from where the engagement happened to the enemy it
     * resolved to, redder the further off it was.
     * @protected
     */
    _redrawLines() {
        let layerGroup = this._getLineLayerGroup();
        layerGroup.clearLayers();

        let $toggle = $(this.options.showLinesSelector);
        if ($toggle.length > 0 && !$toggle.is(':checked')) {
            return;
        }

        let currentFloor = getState().getCurrentFloor();
        if (!currentFloor) {
            return;
        }

        let worst = this._lines.reduce((result, line) => Math.max(result, line.weighted_distance), 1);

        for (let line of this._lines) {
            if (line.floor_id !== currentFloor.id) {
                continue;
            }

            L.polyline([[line.lat, line.lng], [line.enemy_lat, line.enemy_lng]], {
                color: this._getLineColor(line.weighted_distance / worst),
                weight: 2,
                opacity: 0.8,
            }).bindPopup(this._getLinePopupHtml(line), {maxWidth: 360}).addTo(layerGroup);
        }
    }

    /**
     * @param {Number} severity 0..1
     * @returns {String}
     * @private
     */
    _getLineColor(severity) {
        // Same reading as the heatmap underneath: yellow is bad, red is worse
        return severity >= 0.8 ? '#e74c3c' : (severity >= 0.5 ? '#e67e22' : '#f1c40f');
    }

    /**
     * @param {Object} line
     * @returns {String}
     * @private
     */
    _getLinePopupHtml(line) {
        let texts   = this.options.linePopupTexts;
        let escape  = (text) => $('<span>').text(text ?? '').html();
        let replace = (text, replacements) => Object.entries(replacements)
            .reduce((result, [key, value]) => result.replaceAll(`:${key}`, escape(value ?? '-')), text);

        let rows = [
            `<div><strong>${replace(texts.npc, {name: line.npc_name ?? line.npc_id, id: line.npc_id})}</strong></div>`,
            `<div>${replace(texts.distance, {distance: line.distance, enemy: line.enemy_id})}</div>`,
        ];

        // The two recorded distances say whether kill priority moved this match, and they say it about the match as
        // it happened - the enemy's priority as it is mapped today may be a different number entirely
        if (Math.abs(line.weighted_distance - line.distance) >= 0.1) {
            rows.push(`<div>${replace(texts.weighted, {weighted: line.weighted_distance})}</div>`);
        }

        if (line.dungeon_route_url) {
            rows.push(`<div><a href="${escape(line.dungeon_route_url)}" target="_blank" rel="noopener noreferrer">` +
                `<i class="fas fa-external-link-alt me-1"></i>${replace(texts.route, {key: line.dungeon_route_public_key})}</a></div>`);
        } else if (line.source) {
            // An imported row has no local route to link: its id is the one the deployment it came from uses
            rows.push(`<div>${replace(texts.importedRoute, {source: line.source, id: line.dungeon_route_id})}</div>`);
        } else {
            rows.push(`<div>${escape(texts.noRoute)}</div>`);
        }

        return rows.join('');
    }

    /**
     * @param {String} url
     * @protected
     */
    _navigateTo(url) {
        window.location.href = url;
    }

    /**
     * @protected
     */
    _reload() {
        window.location.reload();
    }

    /**
     * @private
     */
    _restoreNpcSelectionFromQueryParams() {
        let npcIds = (getQueryParams()['npc_id'] ?? '').toString().split(',').filter((npcId) => npcId.length > 0);
        if (npcIds.length === 0) {
            return;
        }

        let select = $(this.options.filterNpcIdSelector)[0];
        if (!select) {
            return;
        }

        if (select.tomselect) {
            // Silent: activate() searches once on its own, and a change here would have it search twice
            select.tomselect.setValue(npcIds, true);
        } else {
            $(select).val(npcIds);
        }
    }

    /**
     * @returns {Number[]}
     * @private
     */
    _getSelectedNpcIds() {
        let selectedVals = $(this.options.filterNpcIdSelector).val();

        return selectedVals
            ? (Array.isArray(selectedVals) ? selectedVals : [selectedVals]).map(Number).filter(Number.isFinite)
            : [];
    }

}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonMapsCombatlogrouteenemyresolutions};
}

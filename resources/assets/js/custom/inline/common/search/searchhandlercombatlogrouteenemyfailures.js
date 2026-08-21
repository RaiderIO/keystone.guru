/**
 * @typedef {Object} CommonMapsCombatlogrouteenemyfailuresOptions
 * @property {Number}   dungeonId
 * @property {Number}   mappingVersionId
 * @property {String}   pageUrl
 * @property {String}   getEnemyFailuresUrl
 * @property {String}   clustersUrl
 * @property {String}   showClustersSelector
 * @property {Object}   verdicts           verdict key => {label, color}
 * @property {Object}   clusterPopupTexts  failures, nearestEnemy, nearestNone, inRange, seen, filterNpc
 * @property {String}   deleteUrl
 * @property {String}   filterMappingVersionIdSelector
 * @property {String}   filterNpcIdSelector
 * @property {String}   clearButtonSelector
 * @property {String}   routesContainerSelector
 * @property {String}   routesListSelector
 * @property {String}   noMatchingRoutesText
 * @property {String[]} dependencies
 */

class SearchHandlerCombatLogRouteEnemyFailures extends SearchHandler {
    constructor(options) {
        super({
            loaderFn: function (isLoading, responseText) {
                if (isLoading || responseText === null) {
                    return;
                }
                try {
                    let json = JSON.parse(responseText);
                    if (json && json.data) {
                        getState().getDungeonMap().pluginHeat.setRawLatLngsPerFloor(
                            json.data, null, null, json.weight_max, json.grid_size_x, json.grid_size_y
                        );
                    }

                    if (json) {
                        const $container = $(options.routesContainerSelector);
                        const $list      = $(options.routesListSelector);
                        const routes     = json.dungeon_routes ?? [];

                        $list.empty();

                        if (routes.length > 0) {
                            routes.forEach(function (route) {
                                $list.append(
                                    $('<a>')
                                        .addClass('d-block text-truncate mb-1')
                                        .attr('href', route.url)
                                        .attr('target', '_blank')
                                        .attr('rel', 'noopener noreferrer')
                                        .html('<i class="fas fa-external-link-alt me-1"></i>' + $('<span>').text(route.title).html())
                                );
                            });
                            $container.show();
                        } else {
                            $container.hide();
                        }
                    }
                } catch (e) {
                    console.error('CombatLogRouteEnemyFailures: failed to parse response', e);
                }
            },
        });
    }

    getSearchUrl() {
        console.assert(this instanceof SearchHandlerCombatLogRouteEnemyFailures, 'this is not a SearchHandlerCombatLogRouteEnemyFailures', this);
        return `/ajax/admin/combatlogroute/enemy-failures`;
    }

    getAjaxOptions() {
        return {
            type: 'GET',
            dataType: 'json',
        };
    }
}

/**
 * @property {CommonMapsCombatlogrouteenemyfailuresOptions} options
 */
class CommonMapsCombatlogrouteenemyfailures extends SearchInlineBase {
    constructor(id, bladePath, options) {
        super(new SearchHandlerCombatLogRouteEnemyFailures(options), id, bladePath, options);

        this.filters = {
            'dungeon_id': new SearchFilterPassThrough(),
            'mapping_version_id': new SearchFilterPassThrough(),
        };
        this.filters['dungeon_id'].setValue(options.dungeonId);
        this.filters['mapping_version_id'].setValue(options.mappingVersionId);
    }

    activate() {
        super.activate();

        getState().getDungeonMap().pluginHeat.toggle(true);

        // The map context (enemies, floor unions) is built server-side for the selected mapping version, and so are
        // the per-npc failure counts in the npc filter - switching is a navigation, not a re-fetch.
        $(this.options.filterMappingVersionIdSelector).on('change', (event) => {
            this._navigateTo(`${this.options.pageUrl}?dungeon_id=${this.options.dungeonId}&mapping_version_id=${$(event.target).val()}`);
        });

        $(this.options.filterNpcIdSelector).on('change', () => this._search());

        $(this.options.clearButtonSelector).on('click', () => {
            $.ajax({type: 'DELETE', url: this.options.deleteUrl, data: {dungeon_id: this.options.dungeonId}})
                .done(() => {
                    // The failure counts in both filters are rendered server-side - reload to reset them
                    this._reload();
                });
        });

        // Cluster layer: redrawn whenever the floor changes (the layer group survives a floor switch, so it has to
        // be cleared by hand), toggled by the checkbox, and refetched together with every search
        this._clusters = [];
        $(this.options.showClustersSelector).on('change', () => this._redrawClusters());
        getState().register('floorid:changed', this, () => this._redrawClusters());
        $(document).on('click', '.js-enemy-failure-cluster-select-npc', (event) => {
            this._selectNpc(Number($(event.currentTarget).data('npc-id')));
        });

        this._search();
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
     * @returns {Number[]}
     * @private
     */
    _getSelectedNpcIds() {
        let selectedVals = $(this.options.filterNpcIdSelector).val();

        return selectedVals
            ? (Array.isArray(selectedVals) ? selectedVals : [selectedVals]).map(Number).filter(Number.isFinite)
            : [];
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

        this._fetchClusters(npcIds);
    }

    /**
     * @param {Number[]} npcIds
     * @protected
     */
    _fetchClusters(npcIds) {
        if (!this.options.clustersUrl) {
            return;
        }

        let data = {dungeon_id: this.options.dungeonId, mapping_version_id: this.options.mappingVersionId};
        if (npcIds.length > 0) {
            data.npc_id = npcIds;
        }

        $.ajax({type: 'GET', url: this.options.clustersUrl, data: data, dataType: 'json'})
            .done((json) => {
                this._clusters = (json && json.data) || [];
                this._redrawClusters();
            });
    }

    /**
     * @returns {L.LayerGroup}
     * @private
     */
    _getClusterLayerGroup() {
        if (!this._clusterLayerGroup) {
            this._clusterLayerGroup = L.layerGroup().addTo(getState().getDungeonMap().leafletMap);
        }

        return this._clusterLayerGroup;
    }

    /**
     * Draws the clusters of the current floor: a circle marker at the centroid (size by failure count, colour by
     * verdict, faded when low-volume) and the convex hull of the failures around it.
     * @protected
     */
    _redrawClusters() {
        let layerGroup = this._getClusterLayerGroup();
        layerGroup.clearLayers();

        let $toggle = $(this.options.showClustersSelector);
        if ($toggle.length > 0 && !$toggle.is(':checked')) {
            return;
        }

        let currentFloor = getState().getCurrentFloor();
        if (!currentFloor) {
            return;
        }

        for (let cluster of this._clusters) {
            if (cluster.floor_id !== currentFloor.id) {
                continue;
            }

            let color   = (this.options.verdicts[cluster.verdict] ?? {}).color ?? '#ffffff';
            let opacity = cluster.low_volume ? 0.3 : 0.8;

            if (cluster.hull.length >= 3) {
                L.polygon(cluster.hull, {
                    color: color, weight: 1, opacity: opacity, fillColor: color, fillOpacity: cluster.low_volume ? 0.08 : 0.2, interactive: false,
                }).addTo(layerGroup);
            }

            L.circleMarker([cluster.centroid.lat, cluster.centroid.lng], {
                radius: 6 + 3 * Math.log2(Math.max(1, cluster.count)),
                color: color, weight: 2, opacity: opacity, fillColor: color, fillOpacity: cluster.low_volume ? 0.2 : 0.6,
            }).bindPopup(this._getClusterPopupHtml(cluster), {maxWidth: 360}).addTo(layerGroup);
        }
    }

    /**
     * @param {Object} cluster
     * @returns {String}
     * @private
     */
    _getClusterPopupHtml(cluster) {
        let texts   = this.options.clusterPopupTexts;
        let verdict = this.options.verdicts[cluster.verdict] ?? {label: cluster.verdict, color: '#ffffff'};
        let replace = (text, replacements) => Object.entries(replacements)
            .reduce((result, [key, value]) => result.replaceAll(`:${key}`, String(value ?? '-')), text);
        let escape  = (text) => $('<span>').text(text ?? '').html();

        let nearest = cluster.nearest_enemy_id === null
            ? texts.nearestNone
            : replace(texts.nearestEnemy, {
                id: cluster.nearest_enemy_id, distance: cluster.nearest_enemy_distance,
                floor: cluster.nearest_enemy_floor_id, pack: cluster.nearest_enemy_pack_group,
            });

        return `
            <div class="enemy-failure-cluster-popup">
                <strong>${escape(cluster.npc_name)} (${cluster.npc_id})</strong>
                <span class="badge" style="background-color: ${verdict.color}">${escape(verdict.label)}${cluster.low_volume ? ' *' : ''}</span>
                <div>${escape(replace(texts.failures, {count: cluster.count, routes: cluster.route_count, avg: cluster.avg_failures_per_route}))}</div>
                <div>${escape(nearest)}</div>
                <div>${escape(replace(texts.inRange, {count: cluster.enemies_within_range}))}</div>
                <div class="text-muted small">${escape(replace(texts.seen, {first: (cluster.first_seen ?? '').substring(0, 10), last: (cluster.last_seen ?? '').substring(0, 10)}))}</div>
                <div class="mt-1"><em>${escape(cluster.suggestion)}</em></div>
                <button class="btn btn-sm btn-primary mt-2 js-enemy-failure-cluster-select-npc" data-npc-id="${cluster.npc_id}">${escape(texts.filterNpc)}</button>
            </div>`;
    }

    /**
     * Selects just this npc in the npc filter - the change event then re-searches (heatmap + clusters).
     * @param {Number} npcId
     * @protected
     */
    _selectNpc(npcId) {
        let select = $(this.options.filterNpcIdSelector)[0];
        if (select && select.tomselect) {
            select.tomselect.setValue([String(npcId)]);
        } else {
            $(select).val([String(npcId)]).trigger('change');
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchHandlerCombatLogRouteEnemyFailures, CommonMapsCombatlogrouteenemyfailures};
}

/**
 * @typedef {Object} CommonMapsCombatlogrouteenemyresolutionsOptions
 * @property {Number}   dungeonId
 * @property {Number}   mappingVersionId
 * @property {String}   pageUrl
 * @property {String}   getEnemyResolutionsUrl
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

class SearchHandlerCombatLogRouteEnemyResolutions extends SearchHandler {
    constructor(options) {
        super({
            loaderFn: function (isLoading, responseText) {
                if (isLoading || responseText === null) {
                    return;
                }
                try {
                    let json = JSON.parse(responseText);
                    if (!json) {
                        return;
                    }

                    if (json.data) {
                        getState().getDungeonMap().pluginHeat.setRawLatLngsPerFloor(
                            json.data, null, null, json.weight_max, json.grid_size_x, json.grid_size_y
                        );
                    }

                    $(options.summarySelector).text(
                        options.summaryText
                            .replace(':drawn', (json.drawn_count ?? 0).toLocaleString())
                            .replace(':total', (json.resolution_count ?? 0).toLocaleString())
                            .replace(':max', (json.weight_max ?? 0).toLocaleString())
                    );

                    const $container = $(options.routesContainerSelector);
                    const $list = $(options.routesListSelector);
                    const routes = json.dungeon_routes ?? [];

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
                } catch (e) {
                    console.error('CombatLogRouteEnemyResolutions: failed to parse response', e);
                }
            },
        });
    }

    getSearchUrl() {
        console.assert(this instanceof SearchHandlerCombatLogRouteEnemyResolutions, 'this is not a SearchHandlerCombatLogRouteEnemyResolutions', this);
        return `/ajax/admin/combatlogroute/enemy-resolutions`;
    }

    getAjaxOptions() {
        return {
            type: 'GET',
            dataType: 'json',
        };
    }
}

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
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchHandlerCombatLogRouteEnemyResolutions, CommonMapsCombatlogrouteenemyresolutions};
}

/**
 * @typedef {Object} CommonMapsCombatlogrouteenemyfailuresOptions
 * @property {Number}   dungeonId
 * @property {Number}   mappingVersionId
 * @property {String}   pageUrl
 * @property {String}   getEnemyFailuresUrl
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
     * @param {Object}   options
     * @param {Object}   queryParameters
     * @param {string[]} queryParametersUrlBlacklist
     * @protected
     */
    _search(options = {}, queryParameters = {}, queryParametersUrlBlacklist = []) {
        let selectedVals = $(this.options.filterNpcIdSelector).val();
        let npcIds       = selectedVals
            ? (Array.isArray(selectedVals) ? selectedVals : [selectedVals]).map(Number).filter(Number.isFinite)
            : [];

        if (npcIds.length > 0) {
            queryParameters = $.extend({}, queryParameters, {'npc_id': npcIds});
        }

        super._search(options, queryParameters, queryParametersUrlBlacklist);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchHandlerCombatLogRouteEnemyFailures, CommonMapsCombatlogrouteenemyfailures};
}

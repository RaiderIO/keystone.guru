/**
 * @typedef {Object} CommonMapsCombatlogrouteenemyfailuresOptions
 * @property {Number}   dungeonId
 * @property {String}   getEnemyFailuresUrl
 * @property {String}   deleteUrl
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
        };
        this.filters['dungeon_id'].setValue(options.dungeonId);
    }

    activate() {
        super.activate();

        getState().getDungeonMap().pluginHeat.toggle(true);

        $(this.options.filterNpcIdSelector).on('change', () => this._search());

        $(this.options.clearButtonSelector).on('click', () => {
            $.ajax({type: 'DELETE', url: this.options.deleteUrl})
                .done(() => {
                    $(this.options.routesContainerSelector).hide();
                    this._search();
                });
        });

        this._search();
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

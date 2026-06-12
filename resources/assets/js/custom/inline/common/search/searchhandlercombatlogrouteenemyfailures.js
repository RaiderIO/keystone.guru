/**
 * @typedef {Object} CommonMapsCombatlogrouteenemyfailuresOptions
 * @property {Number}   dungeonId
 * @property {String}   getEnemyFailuresUrl
 * @property {String}   deleteUrl
 * @property {String}   filterNpcIdSelector
 * @property {String}   clearButtonSelector
 * @property {String[]} dependencies
 */

class SearchHandlerCombatLogRouteEnemyFailures extends SearchHandler {
    constructor() {
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
        super(new SearchHandlerCombatLogRouteEnemyFailures(), id, bladePath, options);

        this.filters = {
            'dungeon_id': new SearchFilterPassThrough(),
        };
        this.filters['dungeon_id'].setValue(options.dungeonId);
    }

    activate() {
        super.activate();

        getState().getDungeonMap().pluginHeat.toggle(true);

        $(this.options.filterNpcIdSelector)
            .on('keydown', (e) => {
                if (e.keyCode === 13) {
                    this._search();
                }
            })
            .on('focusout', () => this._search());

        $(this.options.clearButtonSelector).on('click', () => {
            $.ajax({type: 'DELETE', url: this.options.deleteUrl})
                .done(() => this._search());
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
        let inputVal = $(this.options.filterNpcIdSelector).val() || '';
        let npcIds   = inputVal
            .split(',')
            .map((s) => s.trim())
            .filter((s) => s.length > 0)
            .map((s) => parseInt(s, 10))
            .filter((n) => !isNaN(n));

        if (npcIds.length > 0) {
            queryParameters = $.extend({}, queryParameters, {'npc_id': npcIds});
        }

        super._search(options, queryParameters, queryParametersUrlBlacklist);
    }
}

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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchHandlerCombatLogRouteEnemyFailures};
}

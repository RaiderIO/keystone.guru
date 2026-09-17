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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchHandlerCombatLogRouteEnemyResolutions};
}

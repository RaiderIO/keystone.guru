class CommonMapsMap extends InlineCode {

    constructor(options) {
        super(options);
        this._dungeonMap = null;
        this._introTexts = [];

        // Add all intro texts that exist
        let count = 1;
        let text;
        while ((text = lang.get('messages.intro_' + count)) !== 'messages.intro_' + count) {
            this._introTexts.push(text);

            count++;
        }
    }

    /**
     *
     * @returns {null}
     */
    getDungeonMap() {
        return this._dungeonMap;
    }

    /**
     *
     */
    activate() {
        this.initDungeonMap();
    }

    /**
     * Initializes the dungeon map. Cannot put this in activate() because that leads to the draw controls being messed up.
     */
    initDungeonMap() {
        let self = this;

        if (isMapAdmin) {
            this._dungeonMap = new AdminDungeonMap('map', dungeonData, this.options);
        } else {
            this._dungeonMap = new DungeonMap('map', dungeonData, this.options);
        }

        $('#start_virtual_tour').bind('click', function () {
            introjs().start();
        });

        // Bind leaflet virtual tour classes
        let selectors = [
            ['#sidebar', 'right'],
            ['#sidebarToggle', 'right'],

            ['.visibility_tools', 'right'],
            ['#map_enemy_visuals', 'right'],
            ['.floor_selection', 'right'],

            ['.route_actions', 'right'],

            ['.route_manipulation_tools', 'left'],
            ['#map_enemy_forces_numbers', 'left'],
            ['.leaflet-draw-draw-path', 'left'],
            ['.leaflet-draw-draw-killzone', 'left'],
            ['.leaflet-draw-draw-icon', 'left'],
            ['.leaflet-draw-draw-brushline', 'left'],

            ['.leaflet-draw-edit-edit', 'left'],
            ['.leaflet-draw-edit-remove', 'left'],

            ['#edit_route_freedraw_options_color', 'left'],
            ['.draw_element .bootstrap-select', 'left'],

            ['#map_controls .leaflet-draw-toolbar', 'left'],
        ];

        this._dungeonMap.register('map:refresh', null, function () {
            // Upon map refresh, re-init the tutorial selectors
            for (let i = 0; i < selectors.length; i++) {
                let $selector = $(selectors[i][0]);
                // Floor selection may not exist
                if ($selector.length > 0) {
                    $selector.attr('data-intro', self._introTexts[i]);
                    $selector.attr('data-position', selectors[i][1]);
                    $selector.attr('data-step', i + 1);
                }
            }

            // If the map is opened on mobile hide the sidebar
            if (isMobile()) {
                let fn = function () {
                    if (typeof _hideSidebar === 'function') {
                        // @TODO This introduces a dependency on sidebar, but sidebar loads before dungeonMap is instantiated
                        _hideSidebar();
                    }
                };
                self._dungeonMap.leafletMap.off('move', fn);
                self._dungeonMap.leafletMap.on('move', fn);
            }
        });

        // Refresh the map; draw the layers on it
        this._dungeonMap.refreshLeafletMap();
    }
}
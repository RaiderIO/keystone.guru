class CommonMapsMap extends InlineCode {

    constructor() {
        super();

        this._options = {};
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
     * Sets the options of the map.
     * @param options
     */
    setOptions(options) {
        this._options = options;
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
        // Empty, we have to wait for options to be set prior to loading
    }

    /**
     * Initializes the dungeon map.
     */
    initDungeonMap() {
        let self = this;

        if (isAdmin) {
            this._dungeonMap = new AdminDungeonMap('map', dungeonData, this._options);
        } else {
            this._dungeonMap = new DungeonMap('map', dungeonData, this._options);
        }

        // Support not having a sidebar (preview map)
        if (typeof (_switchDungeonFloorSelect) !== 'undefined') {
            $(_switchDungeonFloorSelect).change(function () {
                // Pass the new floor ID to the map
                this._dungeonMap.currentFloorId = $(_switchDungeonFloorSelect).val();
                this._dungeonMap.refreshLeafletMap();
            });
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

            ['.route_manipulation_tools', 'top'],
            ['#map_enemy_forces_numbers', 'top'],
            ['.leaflet-draw-draw-path', 'top'],
            ['.leaflet-draw-draw-killzone', 'top'],
            ['.leaflet-draw-draw-mapcomment', 'top'],
            ['.leaflet-draw-draw-brushline', 'top'],

            ['.leaflet-draw-edit-edit', 'top'],
            ['.leaflet-draw-edit-remove', 'top'],

            ['#edit_route_freedraw_options_container', 'right'],

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
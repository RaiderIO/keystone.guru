class AdminDrawControls extends DrawControls {
    constructor(map, drawnItemsLayer) {
        super(map, drawnItemsLayer);

        this.map.hotkeys.attach('e', 'leaflet-draw-draw-enemy');
        this.map.hotkeys.attach('a', 'leaflet-draw-draw-enemypack');
        this.map.hotkeys.attach('p', 'leaflet-draw-draw-enemypatrol');
    }

    /**
     *
     * @returns
     * @protected
     */
    _getDrawControlOptions() {
        let options = super._getDrawControlOptions();

        options = $.extend(true, options, {
            // This now shows/hides the brushline icon
            brushline: false,
            draw: {
                killzone: false,
                brushline: false,
                path: false,
                enemypack: {
                    allowIntersection: false, // Restricts shapes to simple polygons
                    drawError: {
                        color: '#e1e100', // Color the shape will turn when intersects
                        message: '<strong>Oh snap!<strong> you can\'t draw that!' // Message that will show when intersect
                    },
                    faClass: 'fa-draw-polygon',
                    title: 'Draw an enemy pack'
                },
                enemy: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-user',
                    title: 'Draw an enemy'
                },
                enemypatrol: {
                    shapeOptions: {
                        color: 'red',
                        weight: 3
                    },
                    zIndexOffset: 1000,
                    faClass: 'fa-exchange-alt',
                    title: 'Draw a patrol route for an enemy'
                },
                dungeonstartmarker: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-flag',
                    title: 'Draw a dungeon start marker'
                },
                dungeonfloorswitchmarker: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-door-open',
                    title: 'Draw a dungeon floor switch marker'
                }
            }
        });

        return options;
    }
}
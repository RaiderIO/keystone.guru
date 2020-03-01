class AdminDrawControls extends DrawControls {

    _attachHotkeys() {
        this.map.hotkeys.attach('1', 'leaflet-draw-draw-mapicon');
        this.map.hotkeys.attach('2', 'leaflet-draw-draw-enemypack');
        this.map.hotkeys.attach('3', 'leaflet-draw-draw-enemy');
        this.map.hotkeys.attach('4', 'leaflet-draw-draw-enemypatrol');
        this.map.hotkeys.attach('5', 'leaflet-draw-draw-dungeonstartmarker');
        this.map.hotkeys.attach('6', 'leaflet-draw-draw-dungeonfloorswitchmarker');
        this.map.hotkeys.attach('7', 'leaflet-draw-edit-edit');
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
class AdminDrawControls extends DrawControls {
    _getHotkeys(){
        return [{
            hotkey: '1',
            cssClass: 'leaflet-draw-draw-mapicon',
        }, {
            hotkey: '2',
            cssClass: 'leaflet-draw-draw-enemypack',
        }, {
            hotkey: '3',
            cssClass: 'leaflet-draw-draw-enemy',
        }, {
            hotkey: '4',
            cssClass: 'leaflet-draw-draw-enemypatrol',
        }, {
            hotkey: '5',
            cssClass: 'leaflet-draw-draw-dungeonfloorswitchmarker',
        }, {
            hotkey: '6',
            cssClass: 'leaflet-draw-edit-edit',
        }];
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
                    title: 'Draw an enemy pack',
                    hotkey: this._findHotkeyByCssClass('enemypack')
                },
                enemy: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-user',
                    title: 'Draw an enemy',
                    hotkey: this._findHotkeyByCssClass('enemy')
                },
                enemypatrol: {
                    shapeOptions: {
                        color: 'red',
                        weight: 3
                    },
                    zIndexOffset: 1000,
                    faClass: 'fa-exchange-alt',
                    title: 'Draw a patrol route for an enemy',
                    hotkey: this._findHotkeyByCssClass('enemypatrol')
                },
                dungeonfloorswitchmarker: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-door-open',
                    title: 'Draw a dungeon floor switch marker',
                    hotkey: this._findHotkeyByCssClass('dungeonfloorswitchmarker')
                }
            }
        });

        return options;
    }
}
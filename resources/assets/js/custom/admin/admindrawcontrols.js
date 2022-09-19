class AdminDrawControls extends DrawControls {
    _getHotkeys() {
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
            cssClass: 'leaflet-draw-draw-mountablearea',
        }, {
            hotkey: '7',
            cssClass: 'leaflet-draw-edit-edit',
        }, {
            hotkey: '8',
            cssClass: 'leaflet-draw-edit-remove',
        }];
    }

    /**
     *
     * @returns
     * @protected
     */
    _getDrawControlOptions() {
        let options = super._getDrawControlOptions();

        let hotkeys = {
            enemypack: this._findHotkeyByCssClass('enemypack'),
            enemy: this._findHotkeyByCssClass('enemy'),
            enemypatrol: this._findHotkeyByCssClass('enemypatrol'),
            dungeonfloorswitchmarker: this._findHotkeyByCssClass('dungeonfloorswitchmarker'),
            mountablearea: this._findHotkeyByCssClass('mountablearea')
        }

        options = $.extend(true, options, {
            // This now shows/hides the brushline icon
            brushline: false,
            draw: {
                killzone: false,
                brushline: false,
                path: false,
                pridefulenemy: false,
                enemypack: {
                    allowIntersection: false, // Restricts shapes to simple polygons
                    drawError: {
                        color: '#e1e100', // Color the shape will turn when intersects
                        message: '<strong>Oh snap!<strong> you can\'t draw that!' // Message that will show when intersect
                    },
                    faClass: 'fa-draw-polygon',
                    title: lang.get('messages.enemypack_title', {hotkey: hotkeys.enemypack}),
                    hotkey: hotkeys.enemypack
                },
                enemy: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-user',
                    title: lang.get('messages.enemy_title', {hotkey: hotkeys.enemy}),
                    hotkey: hotkeys.enemy
                },
                enemypatrol: {
                    shapeOptions: {
                        color: 'red',
                        weight: 3
                    },
                    zIndexOffset: 1000,
                    faClass: 'fa-exchange-alt',
                    title: lang.get('messages.enemypatrol_title', {hotkey: hotkeys.enemypatrol}),
                    hotkey: hotkeys.enemypatrol
                },
                dungeonfloorswitchmarker: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-door-open',
                    title: lang.get('messages.dungeonfloorswitchmarker_title', {hotkey: hotkeys.dungeonfloorswitchmarker}),
                    hotkey: hotkeys.dungeonfloorswitchmarker
                },
                mountablearea: {
                    shapeOptions: {
                        color: c.map.mountablearea.color
                    },
                    allowIntersection: false, // Restricts shapes to simple polygons
                    drawError: {
                        color: '#e1e100', // Color the shape will turn when intersects
                        message: '<strong>Oh snap!<strong> you can\'t draw that!' // Message that will show when intersect
                    },
                    faClass: 'fa-horse-head',
                    title: lang.get('messages.mountablearea_title', {hotkey: hotkeys.mountablearea}),
                    hotkey: hotkeys.mountablearea
                },
            }
        });

        return options;
    }
}

$(function () {
    L.DrawToolbar.prototype.getModeHandlers = function (map) {
        return [
            {
                enabled: this.options.route,
                handler: new L.Draw.Route(map, this.options.route),
                title: this.options.route.title
            }, {
                enabled: this.options.killzone,
                handler: new L.Draw.KillZone(map, this.options.killzone),
                title: this.options.killzone.title
            }, {
                enabled: this.options.mapcomment,
                handler: new L.Draw.MapComment(map, this.options.mapcomment),
                title: this.options.mapcomment.title
            }, {
                enabled: this.options.enemypack,
                handler: new L.Draw.EnemyPack(map, this.options.enemypack),
                title: this.options.enemypack.title
            }, {
                enabled: this.options.enemy,
                handler: new L.Draw.Enemy(map, this.options.enemy),
                title: this.options.enemy.title
            }, {
                enabled: this.options.enemypatrol,
                handler: new L.Draw.EnemyPatrol(map, this.options.enemypatrol),
                title: this.options.enemypatrol.title
            }, {
                enabled: this.options.dungeonstartmarker,
                handler: new L.Draw.DungeonStartMarker(map, this.options.dungeonstartmarker),
                title: this.options.dungeonstartmarker.title
            }, {
                enabled: this.options.dungeonfloorswitchmarker,
                handler: new L.Draw.DungeonFloorSwitchMarker(map, this.options.dungeonfloorswitchmarker),
                title: this.options.dungeonfloorswitchmarker.title
            }
        ];
    };

    // Add some new strings to the draw controls
    $.extend(L.drawLocal.draw.handlers, {
        route: {
            tooltip: {
                start: 'Click to start drawing route',
                cont: 'Click to continue drawing route',
                end: 'Click the \'Finish\' button on the toolbar to complete your route'
            }
        }
    });
});

class DrawControls extends MapControl {
    constructor(map, drawnItemsLayer) {
        super(map);
        console.assert(this instanceof DrawControls, this, 'this is not DrawControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.drawnItems = drawnItemsLayer;

        this.drawControlOptions = {
            position: 'topleft',
            draw: {
                route: {
                    shapeOptions: {
                        color: 'green',
                        weight: 3
                    },
                    zIndexOffset: 1000,
                    faClass: 'fa-route',
                    title: 'Draw a route',
                    tooltip: {
                        start: 'Click to start drawing line zz1',
                        cont: 'Click to continue drawing line zz1',
                        end: 'Click last point to finish line zz1'
                    }
                },
                killzone: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-bullseye',
                    title: 'Draw a killzone'
                },
                mapcomment: {
                    repeatMode: false,
                    zIndexOffset: 1000,
                    faClass: 'fa-comment',
                    title: 'Create a map comment'
                },
                enemypack: false,
                enemypatrol: false,
                enemy: false,
                dungeonstartmarker: false,
                dungeonfloorswitchmarker: false,

                // handlers: {
                //     polyline: {},
                //     route: {
                //         tooltip: {
                //             start: 'Click to start drawing line zz2',
                //             cont: 'Click to continue drawing line zz2',
                //             end: 'Click last point to finish line zz2'
                //         }
                //     }
                // }
            },
            edit: {
                featureGroup: drawnItemsLayer, //REQUIRED!!
                remove: true
            }
        };

        // Add a created item to the list of drawn items
        this.map.leafletMap.on(L.Draw.Event.CREATED, function (event) {
            let layer = event.layer;
            self.drawnItems.addLayer(layer);
        });

        this.map.hotkeys.attach('r', 'leaflet-draw-draw-route');
        this.map.hotkeys.attach('c', 'leaflet-draw-edit-edit');
        this.map.hotkeys.attach('d', 'leaflet-draw-edit-remove');
    }

    /**
     * Adds the control to the map.
     */
    addControl() {
        // Add the control to the map
        this._mapControl = new L.Control.Draw(this.drawControlOptions);
        this.map.leafletMap.addControl(this._mapControl);

        // If the option wants, render it with a font-awesome icon instead.
        // Surely there must be a better way for this but whatever, this works..
        for (let optionName in this.drawControlOptions.draw) {
            if (this.drawControlOptions.draw.hasOwnProperty(optionName)) {
                let option = this.drawControlOptions.draw[optionName];
                if (option.hasOwnProperty('faClass')) {
                    // Set the FA icon and remove the background image that was initially there
                    $(".leaflet-draw-draw-" + optionName)
                        .html("<i class='fas " + option.faClass + "'></i>")
                        .css('background-image', 'none');
                }
            }
        }
    }

    cleanup() {
        super.cleanup();

        // this.map.leafletMap.off(L.Draw.Event.CREATED);
    }
}
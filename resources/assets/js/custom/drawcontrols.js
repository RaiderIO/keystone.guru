$(function () {
    L.DrawToolbar.prototype.getModeHandlers = function (map) {
        return [{
            enabled: this.options.polyline,
            handler: new L.Draw.Polyline(map, this.options.polyline),
            title: this.options.polyline.hasOwnProperty('title') ? this.options.polyline.title : L.drawLocal.draw.toolbar.buttons.polyline
        }, {
            enabled: this.options.polygon,
            handler: new L.Draw.Polygon(map, this.options.polygon),
            title: this.options.polygon.hasOwnProperty('title') ? this.options.polygon.title : L.drawLocal.draw.toolbar.buttons.polygon
        }, {
            enabled: this.options.rectangle,
            handler: new L.Draw.Rectangle(map, this.options.rectangle),
            title: this.options.rectangle.hasOwnProperty('title') ? this.options.rectangle.title : L.drawLocal.draw.toolbar.buttons.rectangle
        }, {
            enabled: this.options.circle,
            handler: new L.Draw.Circle(map, this.options.circle),
            title: this.options.circle.hasOwnProperty('title') ? this.options.circle.title : L.drawLocal.draw.toolbar.buttons.circle
        }, {
            enabled: this.options.marker,
            handler: new L.Draw.Marker(map, this.options.marker),
            title: this.options.marker.hasOwnProperty('title') ? this.options.marker.title : L.drawLocal.draw.toolbar.buttons.marker
        }, {
            enabled: this.options.circlemarker,
            handler: new L.Draw.CircleMarker(map, this.options.circlemarker),
            title: this.options.circlemarker.hasOwnProperty('title') ? this.options.circlemarker.title : L.drawLocal.draw.toolbar.buttons.circlemarker
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
        }]
    };
});

class DrawControls {
    constructor(map, drawnItemsLayer) {
        console.assert(this instanceof DrawControls, this, 'this is not DrawControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.map = map;
        this.drawnItems = drawnItemsLayer;

        this.drawControlOptions = {
            position: 'topleft',
            draw: {
                polyline: {
                    shapeOptions: {
                        color: '#f357a1',
                        weight: 10
                    }
                },
                polygon: false,
                rectangle: false,
                circle: false,
                marker: false,
                circlemarker: false,
                enemypack: false,
                enemypatrol: false,
                enemy: false,
                dungeonstartmarker: false,
                dungeonfloorswitchmarker: false
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

            console.log("DrawControls-> drawn item", event);
        });
    }

    /**
     * Removes the control from the map if it exists.
     */
    cleanup() {
        // Remove the control if it already existed
        if (typeof this._drawControl === 'object') {
            this.map.leafletMap.removeControl(this._drawControl);
        }
    }

    /**
     * Adds the control to the map.
     */
    addControl() {
        // Add the control to the map
        this._drawControl = new L.Control.Draw(this.drawControlOptions);
        this.map.leafletMap.addControl(this._drawControl);

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
}
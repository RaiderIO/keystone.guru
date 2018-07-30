class DrawControls {
    constructor(map, drawnItemsLayer) {
        console.assert(this instanceof DrawControls, this, 'this is not DrawControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.map = map;
        this.drawnItems = drawnItemsLayer;

        console.log(drawnItemsLayer);
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
                circlemarker: false
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
    addControl(){
        // Add the control to the map
        this._drawControl = new L.Control.Draw(this.drawControlOptions);
        this.map.leafletMap.addControl(this._drawControl);
    }
}
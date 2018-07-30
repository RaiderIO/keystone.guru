class DrawControls {
    constructor(map, drawnItemsLayer) {
        console.assert(this instanceof DrawControls, this, 'this is not DrawControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        let self = this;

        this.map = map;

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
    }

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
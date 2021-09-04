class MapControl {
    constructor(map) {
        console.assert(this instanceof MapControl, 'this is not MapControls', this);
        console.assert(map instanceof DungeonMap, 'map is not DungeonMap', map);

        this.map = map;
        this._mapControl = null;
    }

    /**
     * Cleans up the MapControl; removing it from the current LeafletMap.
     */
    cleanup() {
        console.assert(this instanceof MapControl, 'this is not MapControl', this);

        if (typeof this._mapControl === 'object') {
            this.map.leafletMap.removeControl(this._mapControl);
        }
    }

    addControl() {

    }
}

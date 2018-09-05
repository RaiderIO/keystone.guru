class MapControl {
    constructor(map){
        console.assert(this instanceof MapControl, this, 'this is not MapControls');
        console.assert(map instanceof DungeonMap, map, 'map is not DungeonMap');

        this.map = map;
        this._mapControl = null;
    }

    /**
     * Cleans up the MapControl; removing it from the current LeafletMap.
     */
    cleanup() {
        console.assert(this instanceof MapControl, this, 'this is not MapControl');

        if (typeof this._mapControl === 'object') {
            this.map.leafletMap.removeControl(this._mapControl);
        }
    }

    addControl(){

    }
}
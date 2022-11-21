class MapObjectMapState extends MapState {
    constructor(map, sourceMapObject) {
        super(map);

        console.assert(sourceMapObject instanceof MapObject, 'sourceMapObject is not a MapObject', sourceMapObject);
        this.sourceMapObject = sourceMapObject;
    }

    getName() {
        return 'MapObjectMapState';
    }

    /**
     * Get the map object that initiated this selection.
     * @returns {MapObject}
     */
    getMapObject() {
        return this.sourceMapObject;
    }
}

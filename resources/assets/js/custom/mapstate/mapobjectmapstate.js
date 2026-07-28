class MapObjectMapState extends MapState {
    constructor(map, sourceMapObject) {
        super(map);

        console.assert(
            sourceMapObject instanceof MapObject || sourceMapObject === null,
            'sourceMapObject is not a MapObject',
            sourceMapObject
        );
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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MapObjectMapState,
    };
}

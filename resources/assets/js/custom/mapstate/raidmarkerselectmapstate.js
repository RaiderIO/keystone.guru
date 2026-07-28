class RaidMarkerSelectMapState extends MapObjectMapState {
    constructor(map, sourceMapObject) {
        super(map, sourceMapObject);
    }

    getName() {
        return 'RaidMarkerSelectMapState';
    }

    /**
     * The raid marker circle menu is drawn on top of the enemy it belongs to, so any enemy tooltip -
     * the source enemy's own, or a neighbour's when the mouse passes over it on its way to a menu
     * item - covers the menu the user is aiming at (#3703).
     * @inheritDoc
     */
    disablesTooltips() {
        return true;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        RaidMarkerSelectMapState,
    };
}

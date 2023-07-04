class MapContextState extends Signalable {

    constructor(mapContext) {
        super();

        this.mapContext = mapContext;

        this._activeDungeonRoute = null;
        // MapContextDungeon does not show a route - so the active dungeon route is always null
        if (!(this.mapContext instanceof MapContextDungeon)) {
            this._activeDungeonRoute = Object.values(this.mapContext.getDungeonRoutes())[0].public_key;
        }
    }

    /**
     * Get the public key of the currently active dungeon route
     * @returns {String|null}
     */
    getActiveDungeonRoute() {
        return this._activeDungeonRoute;
    }

    /**
     *
     * @param publicKey {String}
     */
    setActiveDungeonRoute(publicKey) {
        this._activeDungeonRoute = publicKey;
    }
}

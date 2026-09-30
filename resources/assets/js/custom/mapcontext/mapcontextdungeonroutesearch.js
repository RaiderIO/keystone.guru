class MapContextDungeonRouteSearch extends MapContextDungeonExplore {

    setDungeonRoute(dungeonRoute) {
        if (this._options.dungeonRoute?.publicKey === dungeonRoute?.publicKey) {
            return;
        }

        this._options.dungeonRoute = dungeonRoute;

        for (let name of MAP_OBJECT_GROUP_NAMES_DUNGEON_ROUTE) {
            getMapObjectGroup(name).reset().load();
        }

        getState().getDungeonMap().redrawMapContents();
    }

    getDungeonRoute() {
        return this._options.dungeonRoute;
    }

    /**
     * @returns {[]}
     */
    getPaths() {
        return this._options.dungeonRoute?.paths ?? [];
    }

    /**
     * @returns {[]}
     */
    getBrushlines() {
        return this._options.dungeonRoute?.brushlines ?? [];
    }

    /**
     * @returns {[]}
     */
    getArrows() {
        return this._options.dungeonRoute?.arrows ?? [];
    }

    /**
     * @returns {[]}
     */
    getKillZones() {
        return this._options.dungeonRoute?.killZones ?? [];
    }

    /**
     * @returns {[]}
     */
    getMapIcons() {
        return super.getMapIcons().concat(this._options.dungeonRoute?.mapIcons ?? []);
    }

    /**
     * @returns {[]}
     */
    getKillZonePaths() {
        return this._options.dungeonRoute?.killZonePaths ?? [];
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MapContextDungeonRouteSearch,
    };
}

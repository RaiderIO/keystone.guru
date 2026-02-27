class MapContextDungeonRouteSearch extends MapContextDungeonExplore {

    setDungeonRoute(dungeonRoute) {
        if (this._options.dungeonRoute?.publicKey === dungeonRoute?.publicKey) {
            return;
        }

        this._options.dungeonRoute = dungeonRoute;

        let mapObjectGroupManager = getState().getDungeonMap().mapObjectGroupManager;
        let toReset = [
            MAP_OBJECT_GROUP_PATH,
            MAP_OBJECT_GROUP_BRUSHLINE,
            MAP_OBJECT_GROUP_KILLZONE,
            MAP_OBJECT_GROUP_KILLZONE_PATH,
            MAP_OBJECT_GROUP_MAPICON,
        ];

        for (let group of toReset) {
            mapObjectGroupManager.getByName(group).reset().load();
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
    getKillZones() {
        return this._options.dungeonRoute?.killZones ?? [];
    }

    /**
     * @returns {[]}
     */
    getMapIcons() {
        return super.getMapIcons().concat(this._options.dungeonRoute?.mapIcons ?? []);
    }
}

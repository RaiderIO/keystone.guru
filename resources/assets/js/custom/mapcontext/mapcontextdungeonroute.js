class MapContextDungeonRoute extends MapContext {
    constructor(options) {
        super(options);

        // The active route is always the first since we only have one in DungeonRoute map context
        let publicKey = this.getDungeonRoutes()[0].publicKey;
        this.setActiveDungeonRoute(publicKey);
    }

    /**
     *
     * @returns {*}
     */
    getDungeonRoutes() {
        return this._options.dungeonRoutes;
    }

    /**
     *
     * @param publicKey {String}
     * @returns {null}
     */
    getDungeonRouteByPublicKey(publicKey) {
        let result = null;
        for (let index in this._options.dungeonRoutes) {
            let dungeonRoute = this._options.dungeonRoutes[index];
            if (dungeonRoute.publicKey === publicKey) {
                result = dungeonRoute;
                break;
            }
        }

        console.assert(result !== null, `Unable to find route for public key ${publicKey}`);

        return result;
    }

    /**
     *
     * @returns {*}
     */
    getActiveDungeonRoute() {
        return this._activeDungeonRoute;
    }

    /**
     *
     * @param publicKey {String}
     */
    setActiveDungeonRoute(publicKey) {
        let previousDungeonRoute = this._activeDungeonRoute;

        this._activeDungeonRoute = this.getDungeonRouteByPublicKey(publicKey);

        if (previousDungeonRoute !== this._activeDungeonRoute) {
            console.log({
                previous: previousDungeonRoute,
                current: this._activeDungeonRoute
            });
            this.signal('activeDungeonRoute:changed', {
                previous: previousDungeonRoute,
                current: this._activeDungeonRoute
            });
        }
    }

    /**
     *
     * @param {String} affix
     * @returns {Boolean}
     */
    hasAffix(affix) {
        return this._activeDungeonRoute.uniqueAffixes.includes(affix);
    }

    /**
     *
     * @returns {Number}|null
     */
    getDungeonDifficulty() {
        return this._activeDungeonRoute.dungeonDifficulty;
    }

    /**
     *
     * @returns {String}
     */
    getPublicKey() {
        return this._activeDungeonRoute.publicKey;
    }

    /**
     *
     * @returns {Number}
     */
    getTeamId() {
        return this._activeDungeonRoute.teamId;
    }

    /**
     *
     * @returns {Number}
     */
    getSeasonalIndex() {
        return this._activeDungeonRoute.seasonalIndex;
    }

    /**
     *
     * @param seasonalIndex {Number}
     */
    setSeasonalIndex(seasonalIndex) {
        this._activeDungeonRoute.seasonalIndex = seasonalIndex;

        // Let everyone know it's changed
        this.signal('seasonalindex:changed', {seasonalIndex: this._options.seasonalIndex});
    }

    /**
     *
     * @returns {string}
     */
    getPullGradient() {
        return this._activeDungeonRoute.pullGradient;
    }

    /**
     *
     * @param pullGradient {string}
     */
    setPullGradient(pullGradient) {
        this._activeDungeonRoute.pullGradient = pullGradient;

        // Let everyone know it's changed
        this.signal('pullgradient:changed', {pullgradient: this._options.pullGradient});
    }

    /**
     *
     * @returns {Boolean}
     */
    getPullGradientApplyAlways() {
        return this._activeDungeonRoute.pullGradientApplyAlways;
    }

    /**
     *
     * @param pullGradientApplyAlways {Boolean}
     */
    setPullGradientApplyAlways(pullGradientApplyAlways) {
        this._activeDungeonRoute.pullGradientApplyAlways = pullGradientApplyAlways;

        // Let everyone know it's changed
        this.signal('pullgradientapplyalways:changed', {
            pullgradientapplyalways: this._activeDungeonRoute.pullGradientApplyAlways
        });
    }

    /**
     *
     * @returns {Number}
     */
    getEnemyForces() {
        return this._activeDungeonRoute.enemyForces;
    }

    /**
     *
     * @param enemyForces {Number}
     */
    setEnemyForces(enemyForces) {
        this._activeDungeonRoute.enemyForces = enemyForces;
    }

    /**
     * @returns {[]}
     */
    getKillZones() {
        return this._activeDungeonRoute.killZones;
    }

    /**
     * @returns {[]}
     */
    getMapIcons() {
        // https://stackoverflow.com/a/1584377/771270
        return _.union(this._activeDungeonRoute.mapIcons, this._options.dungeon.mapIcons);
    }

    /**
     * @returns {[]}
     */
    getPaths() {
        return this._activeDungeonRoute.paths;
    }

    /**
     * @returns {[]}
     */
    getBrushlines() {
        return this._activeDungeonRoute.brushlines;
    }

    /**
     * @returns {[]}
     */
    getPridefulEnemies() {
        return this._activeDungeonRoute.pridefulEnemies;
    }

    /**
     * "enemyRaidMarkers":[{"enemy_id":6891,"raid_marker_name":"skull"}]
     * @returns {[]}
     */
    getEnemyRaidMarkers() {
        return this._activeDungeonRoute.enemyRaidMarkers;
    }

    /**
     * @returns {[]}
     */
    getSetup() {
        return this._activeDungeonRoute.setup;
    }

    /**
     * @returns {Number}
     */
    getLevelMin() {
        return this._activeDungeonRoute.levelMin;
    }

    /**
     * @param levelMin {Number}
     */
    setLevelMin(levelMin) {
        this._activeDungeonRoute.levelMin = levelMin;
    }

    /**
     *
     * @returns {Number}
     */
    getLevelMax() {
        return this._activeDungeonRoute.levelMax;
    }

    /**
     * @param levelMax {Number}
     */
    setLevelMax(levelMax) {
        this._activeDungeonRoute.levelMax = levelMax;
    }

    /**
     *
     * @returns {String}
     */
    getMappingVersionUpgradeUrl() {
        return this._activeDungeonRoute.mappingVersionUpgradeUrl;
    }
}

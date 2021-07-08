class MapContextDungeonRoute extends MapContext {
    constructor(options) {
        super(options);
    }

    /**
     *
     * @param {String} affix
     * @returns {Boolean}
     */
    hasAffix(affix) {
        return this._options.uniqueAffixes.includes(affix);
    }

    /**
     *
     * @returns {String}
     */
    getPublicKey() {
        return this._options.publicKey;
    }

    /**
     *
     * @returns {Number}
     */
    getTeamId() {
        return this._options.teamId;
    }

    /**
     *
     * @returns {Number}
     */
    getSeasonalIndex() {
        return this._options.seasonalIndex;
    }

    /**
     *
     * @param seasonalIndex {Number}
     */
    setSeasonalIndex(seasonalIndex) {
        this._options.seasonalIndex = seasonalIndex;

        // Let everyone know it's changed
        this.signal('seasonalindex:changed', {seasonalIndex: this._options.seasonalIndex});
    }

    /**
     *
     * @returns {string}
     */
    getPullGradient() {
        return this._options.pullGradient;
    }

    /**
     *
     * @param pullGradient {string}
     */
    setPullGradient(pullGradient) {
        this._options.pullGradient = pullGradient;

        // Let everyone know it's changed
        this.signal('pullgradient:changed', {pullgradient: this._options.pullGradient});
    }

    /**
     *
     * @returns {Boolean}
     */
    getPullGradientApplyAlways() {
        return this._options.pullGradientApplyAlways;
    }

    /**
     *
     * @param pullGradientApplyAlways {Boolean}
     */
    setPullGradientApplyAlways(pullGradientApplyAlways) {
        this._options.pullGradientApplyAlways = pullGradientApplyAlways;

        // Let everyone know it's changed
        this.signal('pullgradientapplyalways:changed', {pullgradientapplyalways: this._options.pullGradientApplyAlways});
    }

    /**
     *
     * @returns {Number}
     */
    getEnemyForces() {
        return this._options.enemyForces;
    }

    /**
     *
     * @param enemyForces {Number}
     */
    setEnemyForces(enemyForces) {
        this._options.enemyForces = enemyForces;
    }

    /**
     * @returns {[]}
     */
    getKillZones() {
        return this._options.killZones;
    }

    /**
     * @returns {[]}
     */
    getMapIcons() {
        // https://stackoverflow.com/a/1584377/771270
        return _.union(this._options.mapIcons, this._options.dungeon.mapIcons);
    }

    /**
     * @returns {[]}
     */
    getPaths() {
        return this._options.paths;
    }

    /**
     * @returns {[]}
     */
    getBrushlines() {
        return this._options.brushlines;
    }

    /**
     * @returns {[]}
     */
    getPridefulEnemies() {
        return this._options.pridefulenemies;
    }
}
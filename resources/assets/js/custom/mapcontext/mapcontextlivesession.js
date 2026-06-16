class MapContextLiveSession extends MapContextDungeonRoute {

    constructor(options) {
        super(options);

        /** @type {Set<Number>} */
        this._killedEnemyIds = new Set(this._options.killedEnemies ?? []);
    }

    /**
     *
     * @returns {String}
     */
    getLiveSessionPublicKey() {
        return this._options.liveSessionPublicKey;
    }

    /**
     *
     * @returns {Number|null}
     */
    getExpiresInSeconds() {
        return this._options.expiresInSeconds;
    }

    /**
     *
     * @param seconds {Number}
     */
    setExpiresInSeconds(seconds) {
        this._options.expiresInSeconds = seconds;
    }

    /**
     * @returns {[]}
     */
    getOverpulledEnemies() {
        return this._options.overpulledEnemies;
    }

    /**
     * @returns {[]}
     */
    getObsoleteEnemies() {
        return this._options.obsoleteEnemies;
    }

    /**
     * @returns {Number[]}
     */
    getInCombatEnemies() {
        return this._options.inCombatEnemies ?? [];
    }

    /**
     * @returns {Number}
     */
    getEnemyForcesOverride() {
        return this._options.enemyForcesOverride;
    }

    /**
     * @returns {Number[]}
     */
    getKilledEnemies() {
        return Array.from(this._killedEnemyIds);
    }

    /**
     * @param enemyId {Number}
     */
    addKilledEnemy(enemyId) {
        this._killedEnemyIds.add(enemyId);
    }

    /**
     * @param enemyId {Number}
     * @returns {boolean}
     */
    isKilledEnemy(enemyId) {
        return this._killedEnemyIds.has(enemyId);
    }

    /**
     * @returns {Array<{player_guid: string, character_name: string, lat: Number, lng: Number, floor_id: Number}>}
     */
    getPlayerPositions() {
        return this._options.playerPositions ?? [];
    }
}
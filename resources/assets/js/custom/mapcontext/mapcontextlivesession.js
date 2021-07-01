class MapContextLiveSession extends MapContextDungeonRoute {

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
     * @returns {Number}
     */
    getEnemyForcesOverride() {
        return this._options.enemyForcesOverride;
    }
}
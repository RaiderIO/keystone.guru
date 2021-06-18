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
}
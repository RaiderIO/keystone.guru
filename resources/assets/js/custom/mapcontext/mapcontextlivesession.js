class MapContextLiveSession extends MapContextDungeonRoute {

    constructor(options) {
        super(options);

        /** @type {LiveSessionState} */
        this._liveSessionState = new LiveSessionState(this._options.liveSessionState);
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
     * @returns {LiveSessionState}
     */
    getLiveSessionState() {
        return this._liveSessionState;
    }

    /**
     * @param data {LiveSessionStateData}
     */
    setLiveSessionState(data) {
        this._options.liveSessionState = data;
        this._liveSessionState = new LiveSessionState(data);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MapContextLiveSession,
    };
}

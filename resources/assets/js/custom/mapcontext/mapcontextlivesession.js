class MapContextLiveSession extends MapContextDungeonRoute {

    /**
     *
     * @returns {String}
     */
    getLiveSessionPublicKey() {
        return this._options.liveSessionPublicKey;
    }
}
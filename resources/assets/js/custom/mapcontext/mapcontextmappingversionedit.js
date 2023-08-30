class MapContextMappingVersionEdit extends MapContextMappingVersion {
    constructor(options) {
        super(options);
    }

    /**
     *
     * @returns {[]}
     */
    getMdtEnemies() {
        return this._options.dungeon.enemiesMdt;
    }
}

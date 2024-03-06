class MapContextDungeonExplore extends MapContextMappingVersion {
    constructor(options) {
        super(options);
    }

    /**
     *
     * @returns {Number}|null
     */
    getDungeonDifficulty() {
        // @TODO Make this configurable?
        return DUNGEON_DIFFICULTY_10_MAN;
    }
}

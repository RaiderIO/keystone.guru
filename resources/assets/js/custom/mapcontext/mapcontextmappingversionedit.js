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

    /**
     * Every dungeon, by translated name.
     *
     * @param {Boolean} includeCurrentDungeon
     * @returns {{id: Number, name: String}[]}
     */
    getDungeonSelectValues(includeCurrentDungeon = true) {
        return (this._options.dungeonSelectValues ?? [])
            .filter((dungeon) => includeCurrentDungeon || dungeon.id !== this._options.dungeon.id)
            .map((dungeon) => ({id: dungeon.id, name: lang.get(dungeon.name)}))
            .sort((a, b) => a.name.localeCompare(b.name));
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MapContextMappingVersionEdit,
    };
}

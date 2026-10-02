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
     * Every other dungeon, by translated name.
     *
     * @returns {{id: Number, name: String}[]}
     */
    getDungeonSelectValues() {
        return (this._options.dungeonSelectValues ?? [])
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

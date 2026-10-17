class MapContextDungeonExplore extends MapContextMappingVersion {
    constructor(options) {
        super(options);

        this._dungeonDifficulty = DUNGEON_DIFFICULTY_10_MAN;
    }

    /**
     * When mapping a dungeon assume we have all affixes so things show up properly
     * @param affix {String}
     * @returns {boolean}
     */
    hasAffix(affix) {
        let featuredAffixes = this.getFeaturedAffixes();

        // Loop over featuredAffixes object
        for (let index in featuredAffixes) {
            if (featuredAffixes.hasOwnProperty(index)) {
                let featuredAffix = featuredAffixes[index];
                if (featuredAffix.key === affix) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     *
     * @returns {Number|null}
     */
    getDungeonDifficulty() {
        return this._dungeonDifficulty;
    }

    /**
     * Shows the enemies of another raid size, e.g. the ones that only exist on 25-man.
     * @param dungeonDifficulty {Number}
     */
    setDungeonDifficulty(dungeonDifficulty) {
        if (this._dungeonDifficulty === dungeonDifficulty) {
            return;
        }

        this._dungeonDifficulty = dungeonDifficulty;

        this.signal('dungeondifficulty:changed', {dungeonDifficulty: dungeonDifficulty});
    }

    getFeaturedAffixes() {
        return this._options.featuredAffixes;
    }

    /**
     * The most recent season's weeks, each paired with the keystone leaderboard period it falls in.
     * @returns {{week: Number, period: Number}[]}
     */
    getSeasonWeeks() {
        return this._options.seasonWeeks ?? [];
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {
        MapContextDungeonExplore,
    };
}

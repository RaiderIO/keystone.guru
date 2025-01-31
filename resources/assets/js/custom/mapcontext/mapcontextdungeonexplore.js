class MapContextDungeonExplore extends MapContextMappingVersion {
    constructor(options) {
        super(options);
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
     * @returns {Number}|null
     */
    getDungeonDifficulty() {
        // @TODO Make this configurable?
        return DUNGEON_DIFFICULTY_10_MAN;
    }

    getFeaturedAffixes() {
        return this._options.featuredAffixes;
    }

    getSeasonStartPeriod() {
        return this._options.seasonStartPeriod;
    }
}

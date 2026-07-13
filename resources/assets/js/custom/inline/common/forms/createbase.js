/**
 * @typedef {Object} CommonFormsCreatebaseOptions
 * @property {string} levelSelector
 * @property {string} dungeonSelector
 * @property {number} keyLevelMinDefault
 * @property {number} keyLevelMaxDefault
 * @property {Object|null} currentSeason
 * @property {Object|null} nextSeason
 */

/**
 * @property {CommonFormsCreatebaseOptions} options
 */
class CommonFormsCreatebase extends InlineCode {
    activate() {
        super.activate();

        this.levelSliderInitializer = $(this.options.levelSelector).length > 0 ?
            (new LevelSliderInitializer(this.options)) : null;

        $(`${this.options.dungeonSelector}`).bind('change', this._dungeonChanged.bind(this));

        // Init the slider properly
        this._dungeonChanged();
    }

    _dungeonChanged() {
        let dungeonId = parseInt($(this.options.dungeonSelector).val());

        let season = this._getSeasonForDungeon(dungeonId);

        // Can be null if no key levels exist for the currently selected game version
        if (this.levelSliderInitializer !== null) {
            if (season === null) {
                this.levelSliderInitializer.update(this.options.keyLevelMinDefault, this.options.keyLevelMaxDefault);
            } else {
                this.levelSliderInitializer.update(season.key_level_min, season.key_level_max);
            }
        }
    }

    _getSeasonForDungeon(dungeonId) {
        let result = null;
        if (this._seasonHasDungeon(this.options.nextSeason, dungeonId)) {
            result = this.options.nextSeason;
        } else if (this._seasonHasDungeon(this.options.currentSeason, dungeonId)) {
            result = this.options.currentSeason;
        }
        return result;
    }

    _seasonHasDungeon(season, dungeonId) {
        if (season === null) {
            return false;
        }

        let result = false;
        for (let index in season.season_dungeons) {
            let seasonDungeon = season.season_dungeons[index];
            if (seasonDungeon.dungeon_id === dungeonId) {
                result = true;
                break;
            }
        }

        return result;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonFormsCreatebase};
}

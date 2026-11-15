/**
 * @typedef {HeatmapSearchBaseOptions} HeatmapSearchOptions
 *
 * @property {String} keyLevelSelector
 * @property {Number} keyLevelMin
 * @property {Number} keyLevelMax
 * @property {Number} itemLevelMin
 * @property {Number} itemLevelMax
 * @property {Number} playerDeathsMin
 * @property {Number} playerDeathsMax
 * @property {Number} durationMin
 * @property {Number} durationMax
 *
 * @property {String} filterEventTypeContainerSelector
 * @property {String} filterEventTypeSelector
 * @property {String} filterDataTypeContainerSelector
 * @property {String} filterDataTypeSelector
 * @property {String} filterPlayerSpellsContainerSelector
 * @property {String} filterPlayerSpellsSelector
 * @property {String} filterRegionContainerSelector
 * @property {String} filterRegionSelector
 * @property {String} filterKeyLevelSelector
 * @property {String} filterItemLevelSelector
 * @property {String} filterPlayerDeathsSelector
 * @property {String} filterAffixesSelector
 * @property {String} filterWeeklyAffixGroupsSelector
 * @property {String} filterClassesSelector
 * @property {String} filterSpecializationsSelector
 * @property {String} filterClassesPlayerDeathsContainerSelector
 * @property {String} filterClassesPlayerDeathsSelector
 * @property {String} filterSpecializationsPlayerDeathsContainerSelector
 * @property {String} filterSpecializationsPlayerDeathsSelector
 * @property {String} filterDurationSelector
 */

/**
 * The heatmap sidebar of a dungeon, with the Mythic+ filter set.
 *
 * @property {HeatmapSearchOptions} options
 */
class CommonMapsHeatmapsearchsidebar extends SearchInlineHeatmapBase {

    /**
     * @returns {Object<string, SearchFilter>}
     * @protected
     */
    _createFilters() {
        let self = this;

        return {
            'type': new SearchFilterRadioEventType(this.options.filterEventTypeContainerSelector, this.options.filterEventTypeSelector, function () {
                self._applyEventTypeVisibility();

                self._search();
            }),
            'dataType': new SearchFilterRadioDataType(this.options.filterDataTypeContainerSelector, this.options.filterDataTypeSelector, this._search.bind(this)),
            'includePlayerSpellIds':new SearchFilterPlayerSpells(this.options.filterPlayerSpellsSelector, this._search.bind(this)),
            'region': new SearchFilterRadioRegion(this.options.filterRegionContainerSelector, this.options.filterRegionSelector, this._search.bind(this)),
            'keyLevel': new SearchFilterMythicLevel(this.options.filterKeyLevelSelector, this._search.bind(this), this.options.keyLevelMin, this.options.keyLevelMax),
            'itemLevel': new SearchFilterItemLevel(this.options.filterItemLevelSelector, this._search.bind(this), this.options.itemLevelMin, this.options.itemLevelMax),
            'playerDeaths': new SearchFilterPlayerDeaths(this.options.filterPlayerDeathsSelector, this._search.bind(this), this.options.playerDeathsMin, this.options.playerDeathsMax),
            'includeAffixIds': new SearchFilterAffixes(this.options.filterAffixesSelector, this._search.bind(this)),
            'weeklyAffixGroups': new SearchFilterWeeklyAffixGroups(this.options.filterWeeklyAffixGroupsSelector, function () {
                self._selectWeeksInBetween(self.options.filterWeeklyAffixGroupsSelector);

                self._search();
            }),
            'includeClassIds': new SearchFilterClasses(this.options.filterClassesSelector, this._search.bind(this)),
            'includeSpecIds': new SearchFilterSpecializations(this.options.filterSpecializationsSelector, this._search.bind(this)),
            'includePlayerDeathClassIds': new SearchFilterClassesPlayerDeaths(this.options.filterClassesPlayerDeathsSelector, this._search.bind(this)),
            'includePlayerDeathSpecIds': new SearchFilterSpecializationsPlayerDeaths(this.options.filterSpecializationsPlayerDeathsSelector, this._search.bind(this)),
            'duration': new SearchFilterDuration(this.options.filterDurationSelector, this._search.bind(this), this.options.durationMin, this.options.durationMax),

            'excludeSpecIds': new SearchFilterPassThrough(),
            'excludeClassIds': new SearchFilterPassThrough(),
            'excludeAffixIds': new SearchFilterPassThrough(),
            'excludePlayerDeathSpecIds': new SearchFilterPassThrough(),
            'excludePlayerDeathClassIds': new SearchFilterPassThrough(),
            // 'includePlayerSpellIds': new SearchFilterPassThrough(),
            'showSidebar': new SearchFilterPassThrough(),
            'token': new SearchFilterPassThrough(),
            'season': new SearchFilterPassThrough(),
        };
    }

    /**
     * @protected
     */
    _applyFilterVisibility() {
        this._applyEventTypeVisibility();
    }

    /**
     * Enables/disables the filters that only apply to a specific event type, and shows/hides their containers
     * (which the blade renders hidden by default).
     *
     * @protected
     */
    _applyEventTypeVisibility() {
        console.assert(this instanceof CommonMapsHeatmapsearchsidebar, 'this is not a CommonMapsHeatmapsearchsidebar', this);

        // Pass through mode means the sidebar is hidden entirely (see 'passThroughEverything' in
        // heatmapsearch.blade.php) and the filters are fully controlled by whoever embedded us - there's no UI
        // to keep in sync, so don't disable any of the filters they passed based on the event type.
        if (this.options.passThroughEverything) {
            return;
        }

        let eventType = this.filters['type'].getValue();

        let isNpcDeath = eventType === COMBAT_LOG_EVENT_EVENT_TYPE_NPC_DEATH;
        $(this.options.filterDataTypeContainerSelector).toggle(isNpcDeath);
        this.filters['dataType'].toggle(isNpcDeath);


        let isPlayerDeath = eventType === COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_DEATH;
        $(this.options.filterClassesPlayerDeathsContainerSelector).toggle(isPlayerDeath);
        $(this.options.filterSpecializationsPlayerDeathsContainerSelector).toggle(isPlayerDeath);
        this.filters['includePlayerDeathClassIds'].toggle(isPlayerDeath);
        this.filters['includePlayerDeathSpecIds'].toggle(isPlayerDeath);


        let isPlayerSpell = eventType === COMBAT_LOG_EVENT_EVENT_TYPE_PLAYER_SPELL;
        $(this.options.filterPlayerSpellsContainerSelector).toggle(isPlayerSpell);
        this.filters['includePlayerSpellIds'].toggle(isPlayerSpell);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonMapsHeatmapsearchsidebar};
}

/**
 * @typedef {HeatmapSearchBaseOptions} ContinentHeatmapSearchOptions
 *
 * @property {Number} itemLevelMin
 * @property {Number} itemLevelMax
 *
 * @property {String} filterEventTypeContainerSelector
 * @property {String} filterEventTypeSelector
 * @property {String} filterRegionContainerSelector
 * @property {String} filterRegionSelector
 * @property {String} filterItemLevelSelector
 * @property {String} filterWeeksSelector
 */

/**
 * The heatmap sidebar of a continent, with the open-world filter set: nothing Mythic+ specific, and no season.
 *
 * @property {ContinentHeatmapSearchOptions} options
 */
class CommonMapsContinentheatmapsearchsidebar extends SearchInlineHeatmapBase {

    /**
     * @returns {Object<string, SearchFilter>}
     * @protected
     */
    _createFilters() {
        let self = this;

        return {
            'type': new SearchFilterRadioDeathType(this.options.filterEventTypeContainerSelector, this.options.filterEventTypeSelector, this._search.bind(this)),
            'region': new SearchFilterRadioRegion(this.options.filterRegionContainerSelector, this.options.filterRegionSelector, this._search.bind(this)),
            // The open world spans every item level, well below the Mythic+ range the slider offers by default
            'itemLevel': new SearchFilterItemLevel(this.options.filterItemLevelSelector, this._search.bind(this), this.options.itemLevelMin, this.options.itemLevelMax, {
                min: this.options.itemLevelMin,
                max: this.options.itemLevelMax,
            }),
            'weeks': new SearchFilterWeeks(this.options.filterWeeksSelector, function () {
                self._selectWeeksInBetween(self.options.filterWeeksSelector);

                self._search();
            }),

            'showSidebar': new SearchFilterPassThrough(),
            'token': new SearchFilterPassThrough(),
        };
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonMapsContinentheatmapsearchsidebar};
}

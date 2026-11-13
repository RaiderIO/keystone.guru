/**
 * The week filter of a continent's heatmap. A continent has no season, so its weeks are the most recent ones
 * (see MapContextDungeonExplore::getSeasonWeeks()) and the header names the weeks by the date they start at.
 */
class SearchFilterWeeks extends SearchFilterWeeklyAffixGroups {
    getFilterHeaderText() {
        let value = this.getValue();

        if (value.length === 0) {
            return '';
        }

        let first = this._getWeekStart(parseInt(value[0]));
        let last = this._getWeekStart(parseInt(value[value.length - 1]));

        return lang.get('js.filter_input_select_weeks_header')
            .replace(':from', first)
            .replace(':to', last);
    }

    /**
     * @param week {Number}
     * @returns {String} The date the week starts at, or an empty string when there is no such week.
     * @private
     */
    _getWeekStart(week) {
        let seasonWeek = this._getSeasonWeeks().find(seasonWeek => seasonWeek.week === week);

        return seasonWeek === undefined ? '' : seasonWeek.start;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterWeeks};
}

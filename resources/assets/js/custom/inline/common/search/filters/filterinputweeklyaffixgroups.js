class SearchFilterWeeklyAffixGroups extends SearchFilterInput {
    activate() {
        super.activate();

        let self = this;

        // Grouped affixes
        if (!this.passThrough) {
            $(this.selector).off('change').on('change', function () {
                self.onChange();

                refreshSelectPickers();
            });
        }
    }

    getDefaultValue() {
        return [];
    }

    getFilterHeaderText() {
        let value = this.getValue();

        let displayValue = value.length > 0 ? `${value[0]} - ${value[value.length - 1]}` : '';

        return lang.get('js.filter_input_select_weekly_affix_groups_header')
            .replace(':week', '' + displayValue);
    }

    getParamsOverride() {
        let val = this.getValue();

        return {
            'minPeriod': val.length === 0 ? 0 : this._getPeriodForWeek(parseInt(val[0])),
            'maxPeriod': val.length === 0 ? 0 : this._getPeriodForWeek(parseInt(val[val.length - 1])),
        }
    }

    setValueOverride(name, value) {
        // Select nothing if one of the periods is 0
        if (value === 0) {
            this.setValue([]);
            return;
        }

        // New value must be brought down to week indices
        let week = this._getWeekForPeriod(parseInt(value));

        // A period no week of the season falls in - a link from a season that has since been replaced, say -
        // has nothing to select
        if (week === null) {
            this.setValue([]);
            return;
        }

        let val = this.getValue();
        let min = val.length === 0 ? 0 : (parseInt(val[0]));
        let max = val.length === 0 ? 0 : (parseInt(val[val.length - 1]));

        if (name === 'minPeriod') {
            min = week;
        } else if (name === 'maxPeriod') {
            max = week;
        } else {
            console.error(`Invalid name ${name} for weekly affix groups filter override`);
        }

        // Min can't be above max - at least be equal so we can set min, and max afterwards
        max = Math.max(min, max);

        // http://localhost:8008/explore/retail/the-necrotic-wake/4?type=enemy_killed&dataType=player_position&minMythicLevel=2&maxMythicLevel=13&minPeriod=977&maxPeriod=986&minTimerFraction=0.28125&maxTimerFraction=1.125
        let newVal = [];
        for (let i = min; i <= max; i++) {
            newVal.push(i);
        }

        this.setValue(newVal);
    }

    getDefaultValueOverride(name) {
        return 0;
    }

    /**
     * The keystone leaderboard period a week of the season falls in, looked up in the list the server computed
     * rather than derived from the season's start period. minPeriod/maxPeriod are passed straight through to
     * Raider.IO, so they must be true periods, and adding a week index to a start period is neither aligned to
     * the season's first reset nor safe across a DST change.
     * @param week {Number}
     * @returns {Number} 0 when the season has no such week, which reads as "no selection".
     * @private
     */
    _getPeriodForWeek(week) {
        let seasonWeek = this._getSeasonWeeks().find(seasonWeek => seasonWeek.week === week);

        return seasonWeek === undefined ? 0 : seasonWeek.period;
    }

    /**
     * The inverse of _getPeriodForWeek().
     * @param period {Number}
     * @returns {Number|null} null when no week of the season falls in this period.
     * @private
     */
    _getWeekForPeriod(period) {
        let seasonWeek = this._getSeasonWeeks().find(seasonWeek => seasonWeek.period === period);

        return seasonWeek === undefined ? null : seasonWeek.week;
    }

    /**
     * @returns {{week: Number, period: Number}[]}
     * @private
     */
    _getSeasonWeeks() {
        return getState().getMapContext().getSeasonWeeks();
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterWeeklyAffixGroups};
}

class SearchFilterWeeklyAffixGroups extends SearchFilterInput {
    activate() {
        super.activate();

        let self = this;

        // Grouped affixes
        $(this.selector).off('change').on('change', function() {
            self.onChange();

            refreshSelectPickers();
        });
    }

    getDefaultValue() {
        return [];
    }

    getFilterHeaderText() {
        let value = this.getValue();
        let option = $(`${this.selector} option`).eq(value);

        return lang.get('messages.filter_image_select_weekly_affix_groups_header')
            .replace(':week', '' + value)
            .replace(':date', option.data('date'));
    }

    getParamsOverride() {
        let val = this.getValue();

        let seasonStartPeriod = getState().getMapContext().getSeasonStartPeriod();

        return {
            'minPeriod': val.length === 0 ? 0 : (seasonStartPeriod + parseInt(val[0])),
            'maxPeriod': val.length === 0 ? 0 : (seasonStartPeriod + parseInt(val[val.length - 1])),
        }
    }

    setValueOverride(name, value) {
        // Select nothing if one of the periods is 0
        if (value === 0) {
            this.setValue([]);
            return;
        }

        let val = this.getValue();

        let seasonStartPeriod = getState().getMapContext().getSeasonStartPeriod();

        // New value must be brought down to week indices
        value -= seasonStartPeriod;

        let min = val.length === 0 ? seasonStartPeriod : (parseInt(val[0]) - seasonStartPeriod);
        let max = val.length === 0 ? seasonStartPeriod : (parseInt(val[val.length - 1]) - seasonStartPeriod);

        if (name === 'minPeriod') {
            min = parseInt(value);
        } else if (name === 'maxPeriod') {
            max = parseInt(value);
        } else {
            console.error(`Invalid name ${name} for weekly affix groups filter override`);
        }

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
}

class SearchFilterDuration extends SearchFilterInput {
    constructor(selector, onChange, min, max) {
        super(selector, onChange);

        this.min = min;
        this.max = max;
        this.durationHandler = null;
        this.passThroughValue = `${min};${max}`;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        if (!this.passThrough) {
            (this.durationHandler = new DurationHandler(this.min, this.max)).apply(this.selector, {
                onFinish: function () {
                    self.onChange();
                }
            });
        }
    }

    getDefaultValue() {
        return `${this.min};${this.max}`;
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_duration_header')
            .replace(':value', this.getValue().replace(';', ' - '));
    }

    /**
     *
     * @param value
     */
    setValue(value) {
        if (this.passThrough) {
            super.setValue(value);
        } else {
            $(this.selector).data('ionRangeSlider').update({
                from: value.split(';')[0],
                to: value.split(';')[1],
            });
        }
    }

    getParamsOverride() {
        let split = this.getValue().split(';');

        // By default, include runs that go up to twice over time
        let minTimerFraction = 0;
        let maxTimerFraction = 2;

        let timer = getState().getMapContext().getMappingVersion().timer_max_seconds;
        if (timer > 0) {
            minTimerFraction = (split[0] * 60) / timer;
            maxTimerFraction = (split[1] * 60) / timer;
        }

        return {
            'minTimerFraction': minTimerFraction,
            'maxTimerFraction': maxTimerFraction,
        }
    }

    setValueOverride(name, value) {
        let split = this.getValue().split(';');

        let timer = getState().getMapContext().getMappingVersion().timer_max_seconds;
        let minutesValue = timer > 0 ? Math.floor((value * timer) / 60) : 0;

        if (name === 'minTimerFraction') {
            this.setValue(`${minutesValue};${split[1]}`);
        } else if (name === 'maxTimerFraction') {
            this.setValue(`${split[0]};${minutesValue}`);
        } else {
            console.error(`Invalid name ${name} for Duration filter override`);
        }
    }

    getDefaultValueOverride(name) {
        let result = 0;

        let timer = getState().getMapContext().getMappingVersion().timer_max_seconds;
        if (name === 'minTimerFraction') {
            result = (this.min * 60) / timer;
        } else if (name === 'maxTimerFraction') {
            result = (this.max * 60) / timer;
        } else {
            console.error(`Invalid name ${name} for Duration filter override`);
        }

        return result;
    }
}

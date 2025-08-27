class SearchFilterPlayerDeaths extends SearchFilterInput {
    constructor(selector, onChange, playerDeathsMin, playerDeathsMax) {
        super(selector, onChange);

        this.playerDeathsMin = playerDeathsMin;
        this.playerDeathsMax = playerDeathsMax;
        this.levelHandler = null;
        this.passThroughValue = `${playerDeathsMin};${playerDeathsMax}`;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        if (!this.passThrough) {
            (this.levelHandler = new PlayerDeathsHandler(this.playerDeathsMin, this.playerDeathsMax)).apply(this.selector, {
                onFinish: function () {
                    self.onChange();
                }
            });
        }
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_player_deaths_header')
            .replace(':value', this.getValue().replace(';', ' - '));
    }

    getDefaultValue() {
        return `${this.playerDeathsMin};${this.playerDeathsMax}`;
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

    /**
     *
     * @param min {Number}
     * @param max {Number}
     */
    setPlayerDeaths(min, max) {
        this.playerDeathsMin = min;
        this.playerDeathsMax = max;

        if (this.levelHandler !== null) {
            this.levelHandler.update(min, max);
        }
    }

    getParamsOverride() {
        let split = this.getValue().split(';');
        return {
            'minPlayerDeaths': parseInt(split[0]),
            'maxPlayerDeaths': parseInt(split[1]),
        }
    }

    setValueOverride(name, value) {
        let split = this.getValue().split(';');

        if (name === 'minPlayerDeaths') {
            this.setValue(`${value};${split[1]}`);
        } else if (name === 'maxPlayerDeaths') {
            this.setValue(`${split[0]};${value}`);
        } else {
            console.error(`Invalid name ${name} for Player deaths filter override`);
        }
    }

    getDefaultValueOverride(name) {
        let result = 0;

        if (name === 'minPlayerDeaths') {
            result = this.playerDeathsMin;
        } else if (name === 'maxPlayerDeaths') {
            result = this.playerDeathsMax;
        } else {
            console.error(`Invalid name ${name} for Player deaths filter override`);
        }

        return result;
    }
}

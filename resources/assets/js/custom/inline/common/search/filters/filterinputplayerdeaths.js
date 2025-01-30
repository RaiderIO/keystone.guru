class SearchFilterPlayerDeaths extends SearchFilterInput {
    constructor(selector, onChange, playerDeathsMin, playerDeathsMax) {
        super(selector, onChange);

        this.playerDeathsMin = playerDeathsMin;
        this.playerDeathsMax = playerDeathsMax;
        this.levelHandler = null;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        (this.levelHandler = new PlayerDeathsHandler(this.playerDeathsMin, this.playerDeathsMax)).apply(this.selector, {
            onFinish: function () {
                self.onChange();
            }
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_player_deaths_header')
            .replace(':value', this.getValue().replace(';', ' - '));
    }

    /**
     *
     * @param value
     */
    setValue(value) {
        $(this.selector).data('ionRangeSlider').update({
            from: value.split(';')[0],
            to: value.split(';')[1],
        });
    }

    /**
     *
     * @param min {Number}
     * @param max {Number}
     */
    setPlayerDeaths(min, max) {
        this.playerDeathsMin = min;
        this.playerDeathsMax = max;

        this.levelHandler.update(min, max);
    }

    getParamsOverride() {
        let split = this.getValue().split(';');
        return {
            'minPlayerDeaths': split[0],
            'maxPlayerDeaths': split[1],
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
}

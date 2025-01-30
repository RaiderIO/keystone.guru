class SearchFilterKeyLevel extends SearchFilterInput {
    constructor(selector, onChange, levelMin, levelMax) {
        super(selector, onChange);

        this.levelMin = levelMin;
        this.levelMax = levelMax;
        this.levelHandler = null;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        (this.levelHandler = new KeyLevelHandler(this.levelMin, this.levelMax)).apply(this.selector, {
            onFinish: function () {
                self.onChange();
            }
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_key_level_header')
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
    setKeyLevel(min, max) {
        this.levelMin = min;
        this.levelMax = max;

        this.levelHandler.update(min, max);
    }

    getParamsOverride() {
        let split = this.getValue().split(';');
        return {
            'minMythicLevel': split[0],
            'maxMythicLevel': split[1],
        }
    }

    setValueOverride(name, value) {
        let split = this.getValue().split(';');

        if (name === 'minMythicLevel') {
            this.setValue(`${value};${split[1]}`);
        } else if (name === 'maxMythicLevel') {
            this.setValue(`${split[0]};${value}`);
        } else {
            console.error(`Invalid name ${name} for Key level filter override`);
        }
    }
}

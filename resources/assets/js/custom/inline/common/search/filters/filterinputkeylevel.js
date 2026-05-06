class SearchFilterKeyLevel extends SearchFilterInput {
    constructor(selector, onChange, levelMin, levelMax) {
        super(selector, onChange);

        this.levelMin = levelMin;
        this.levelMax = levelMax;
        this.keyLevelHandler = null;
        this.passThroughValue = `${levelMin};${levelMax}`;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        if (!this.passThrough) {
            (this.keyLevelHandler = new KeyLevelHandler(this.levelMin, this.levelMax)).apply(this.selector, {
                onFinish: function () {
                    self.onChange();
                }
            });
        }
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_key_level_header')
            .replace(':value', this.getValue().replace(';', ' - '));
    }

    getDefaultValue() {
        return `${this.levelMin};${this.levelMax}`;
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
    setKeyLevel(min, max) {
        this.levelMin = min;
        this.levelMax = max;

        if (this.levelHandler !== null) {
            this.keyLevelHandler.update(min, max);
        }
    }
}

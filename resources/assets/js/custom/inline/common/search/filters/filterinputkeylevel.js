class SearchFilterKeyLevel extends SearchFilterInput {
    constructor(selector, onChange, levelMin, levelMax) {
        super(selector, onChange);

        this.levelMin = levelMin;
        this.levelMax = levelMax;
        this.keyLevelHandler = null;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        (this.keyLevelHandler = new KeyLevelHandler(this.levelMin, this.levelMax)).apply(this.selector, {
            onFinish: function () {
                self.onChange();
            }
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_key_level_header')
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

        this.keyLevelHandler.update(min, max);
    }
}

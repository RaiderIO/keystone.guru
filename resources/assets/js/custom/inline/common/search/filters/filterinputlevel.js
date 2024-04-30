class SearchFilterLevel extends SearchFilterInput {
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
        (this.levelHandler = new LevelHandler(this.levelMin, this.levelMax)).apply(this.selector, {
            onFinish: function () {
                self.onChange();
            }
        });
    }

    getFilterHeaderText() {
        return lang.get('messages.filter_input_level_header')
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
}

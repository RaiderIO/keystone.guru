class SearchFilterDuration extends SearchFilterInput {
    constructor(selector, onChange, min, max) {
        super({
            selector: selector,
            onChange: onChange
        });

        this.min = min;
        this.max = max;
        this.durationHandler = null;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        (this.durationHandler = new DurationHandler(this.min, this.max)).apply(this.options.selector, {
            onFinish: function (data) {
                self.options.onChange();
            }
        });
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
        $(this.options.selector).data('ionRangeSlider').update({
            from: value.split(';')[0],
            to: value.split(';')[1],
        });
    }
}

class SearchFilterMinSamplesRequired extends SearchFilterInput {
    constructor(selector, onChange, minSamplesRequiredMin, minSamplesRequiredMax) {
        super(selector, onChange);

        this.minSamplesRequiredMin = minSamplesRequiredMin;
        this.minSamplesRequiredMax = minSamplesRequiredMax;
        this.minSamplesRequiredHandler = null;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        (this.minSamplesRequiredHandler = new MinSamplesRequiredHandler(this.minSamplesRequiredMin, this.minSamplesRequiredMax)).apply(this.selector, {
            onFinish: function () {
                self.onChange();
            }
        });
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_min_samples_required_header')
            .replace(':value', this.getValue());
    }

    /**
     *
     * @param value
     */
    setValue(value) {
        $(this.selector).data('ionRangeSlider').update({
            from: value
        });
    }
}

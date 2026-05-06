class SearchFilterRating extends SearchFilterInput {

    activate() {
        super.activate();

        let self = this;

        $(this.selector).ionRangeSlider({
            grid: true,
            grid_snap: true,
            min: 1,
            max: 10,
            extra_classes: 'inverse',
            onFinish: function () {
                self.onChange();
            }
        });
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_rating_header').replace(':value', this.getValue());
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

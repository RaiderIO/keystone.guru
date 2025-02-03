class MinSamplesRequiredHandler {
    constructor(min, max) {
        this.min = min;
        this.max = max;

        this.rangeSlider = null;
    }

    apply(selector, options) {
        this.rangeSlider = $(selector).ionRangeSlider($.extend({
            grid: true,
            grid_snap: true,
            type: 'single',
            min: this.min,
            max: this.max,
        }, options)).data('ionRangeSlider');
    }

    update(min, max) {
        this.min = min;
        this.max = max;

        this.rangeSlider.update({
            min: this.min,
            max: this.max,
            from: this.min,
            to: this.max,
        });
    }
}

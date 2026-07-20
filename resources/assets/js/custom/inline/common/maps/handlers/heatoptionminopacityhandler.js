class HeatOptionMinOpacityHandler {
    constructor(min, max) {
        this.min = min;
        this.max = max;

        this.rangeSlider = null;
    }

    apply(selector, options) {
        this.rangeSlider = $(selector).rangeSlider($.extend({
            type: 'single',
            min: this.min,
            max: this.max,
            step: 0.01,
        }, options)).data('rangeSlider');
    }

    update(min, max) {
        this.min = min;
        this.max = max;

        this.rangeSlider.update({
            min: this.min,
            max: this.max,
        });
    }
}

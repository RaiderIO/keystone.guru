class KeyLevelHandler {
    constructor(min, max) {
        this.min = min;
        this.max = max;

        this.options = null;
        this.rangeSlider = null;
    }

    apply(selector, options) {
        this.options = options;
        this.rangeSlider = $(selector).ionRangeSlider($.extend({
            grid: true,
            grid_snap: true,
            type: 'double',
            min: this.min,
            max: this.max,
            from: this.min,
            to: this.max,
        }, options)).data('ionRangeSlider');
    }

    update(min, max) {
        this.min = min;
        this.max = max;

        this.rangeSlider.update({
            min: this.min,
            max: this.max,
            from: this.options.from ?? this.min,
            to: this.options.to ?? this.max,
        });
    }
}

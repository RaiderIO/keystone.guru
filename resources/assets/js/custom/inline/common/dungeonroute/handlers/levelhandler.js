class LevelHandler {
    constructor(min, max) {
        this.min = min;
        this.max = max;
    }

    apply(selector, options) {
        $(selector).ionRangeSlider($.extend({
            grid: true,
            grid_snap: true,
            type: 'double',
            min: this.min,
            max: this.max,
            from: this.min,
            to: this.max,
        }, options));
    }
}
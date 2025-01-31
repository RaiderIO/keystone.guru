class ItemLevelHandler {
    constructor(from, to) {
        this.min = 400;
        this.max = 999;

        this.from = from;
        this.to = to;

        this.rangeSlider = null;
    }

    apply(selector, options) {
        this.rangeSlider = $(selector).ionRangeSlider($.extend({
            grid: true,
            grid_snap: true,
            type: 'double',
            min: this.min,
            max: this.max,
            from: this.from,
            to: this.to,
        }, options)).data('ionRangeSlider');
    }

    update(min, max) {
        this.min = min;
        this.max = max;

        this.rangeSlider.update({
            min: this.min,
            max: this.max,
            from: this.from,
            to: this.to,
        });
    }
}

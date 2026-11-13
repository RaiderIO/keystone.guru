class ItemLevelHandler {
    /**
     * @param from {Number}
     * @param to {Number}
     * @param min {Number} The lowest item level the slider offers.
     * @param max {Number} The highest item level the slider offers.
     */
    constructor(from, to, min = 240, max = 999) {
        this.min = min;
        this.max = max;

        this.from = from;
        this.to = to;

        this.rangeSlider = null;
    }

    apply(selector, options) {
        this.rangeSlider = $(selector).rangeSlider($.extend({
            grid: true,
            grid_snap: true,
            type: 'double',
            min: this.min,
            max: this.max,
            from: this.from,
            to: this.to,
        }, options)).data('rangeSlider');
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

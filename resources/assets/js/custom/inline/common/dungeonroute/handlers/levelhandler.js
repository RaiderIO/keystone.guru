class LevelHandler {
    /**
     * @param min {Number} The lowest character level the slider offers.
     * @param max {Number} The highest character level the slider offers.
     */
    constructor(min, max) {
        this.min = min;
        this.max = max;

        this.rangeSlider = null;
    }

    apply(selector, options) {
        this.rangeSlider = $(selector).rangeSlider($.extend({
            grid: true,
            grid_snap: true,
            type: 'double',
            min: this.min,
            max: this.max,
            from: this.min,
            to: this.max,
        }, options)).data('rangeSlider');
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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {LevelHandler};
}

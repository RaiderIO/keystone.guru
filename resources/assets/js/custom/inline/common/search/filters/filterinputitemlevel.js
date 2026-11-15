class SearchFilterItemLevel extends SearchFilterInput {
    /**
     * @param selector {String}
     * @param onChange {Function}
     * @param itemLevelMin {Number} The default lower bound of the selection.
     * @param itemLevelMax {Number} The default upper bound of the selection.
     * @param sliderBounds {{min: Number, max: Number}|null} The item levels the slider offers; null for the
     * ItemLevelHandler's own.
     */
    constructor(selector, onChange, itemLevelMin, itemLevelMax, sliderBounds = null) {
        super(selector, onChange);

        this.itemLevelMin = itemLevelMin;
        this.itemLevelMax = itemLevelMax;
        this.sliderBounds = sliderBounds;
        this.levelHandler = null;
        this.passThroughValue = `${itemLevelMin};${itemLevelMax}`;
    }

    activate() {
        super.activate();

        let self = this;

        // Level
        if (!this.passThrough) {
            (this.levelHandler = this.sliderBounds === null ?
                new ItemLevelHandler(this.itemLevelMin, this.itemLevelMax) :
                new ItemLevelHandler(this.itemLevelMin, this.itemLevelMax, this.sliderBounds.min, this.sliderBounds.max)
            ).apply(this.selector, {
                onFinish: function () {
                    self.onChange();
                }
            });
        }
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_item_level_header')
            .replace(':value', this.getValue().replace(';', ' - '));
    }

    getDefaultValue() {
        return `${this.itemLevelMin};${this.itemLevelMax}`;
    }

    /**
     *
     * @param value
     */
    setValue(value) {
        if (this.passThrough) {
            super.setValue(value);
        } else {
            $(this.selector).data('rangeSlider').update({
                from: value.split(';')[0],
                to: value.split(';')[1],
            });
        }
    }

    /**
     *
     * @param min {Number}
     * @param max {Number}
     */
    setItemLevel(min, max) {
        this.itemLevelMin = min;
        this.itemLevelMax = max;

        if (this.levelHandler !== null) {
            this.levelHandler.update(min, max);
        }
    }

    getParamsOverride() {
        let split = this.getValue().split(';');
        return {
            'minItemLevel': parseInt(split[0]),
            'maxItemLevel': parseInt(split[1]),
        }
    }

    setValueOverride(name, value) {
        let split = this.getValue().split(';');

        if (name === 'minItemLevel') {
            this.setValue(`${value};${split[1]}`);
        } else if (name === 'maxItemLevel') {
            this.setValue(`${split[0]};${value}`);
        } else {
            console.error(`Invalid name ${name} for Item level filter override`);
        }
    }

    getDefaultValueOverride(name) {
        let result = 0;

        if (name === 'minItemLevel') {
            result = this.itemLevelMin;
        } else if (name === 'maxItemLevel') {
            result = this.itemLevelMax;
        } else {
            console.error(`Invalid name ${name} for Item level filter override`);
        }

        return result;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterItemLevel};
}

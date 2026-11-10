/**
 * The character level band of an open-world heatmap.
 */
class SearchFilterLevel extends SearchFilterInput {
    /**
     * @param selector {String}
     * @param onChange {Function}
     * @param levelMin {Number} The lowest character level the slider offers, and the default lower bound.
     * @param levelMax {Number} The highest character level the slider offers, and the default upper bound.
     */
    constructor(selector, onChange, levelMin, levelMax) {
        super(selector, onChange);

        this.levelMin = levelMin;
        this.levelMax = levelMax;
        this.levelHandler = null;
        this.passThroughValue = `${levelMin};${levelMax}`;
    }

    activate() {
        super.activate();

        let self = this;

        if (!this.passThrough) {
            (this.levelHandler = new LevelHandler(this.levelMin, this.levelMax)).apply(this.selector, {
                onFinish: function () {
                    self.onChange();
                }
            });
        }
    }

    getFilterHeaderText() {
        return lang.get('js.filter_input_level_header')
            .replace(':value', this.getValue().replace(';', ' - '));
    }

    getDefaultValue() {
        return `${this.levelMin};${this.levelMax}`;
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

    getParamsOverride() {
        let split = this.getValue().split(';');
        return {
            'minLevel': parseInt(split[0]),
            'maxLevel': parseInt(split[1]),
        }
    }

    setValueOverride(name, value) {
        let split = this.getValue().split(';');

        if (name === 'minLevel') {
            this.setValue(`${value};${split[1]}`);
        } else if (name === 'maxLevel') {
            this.setValue(`${split[0]};${value}`);
        } else {
            console.error(`Invalid name ${name} for Level filter override`);
        }
    }

    getDefaultValueOverride(name) {
        let result = 0;

        if (name === 'minLevel') {
            result = this.levelMin;
        } else if (name === 'maxLevel') {
            result = this.levelMax;
        } else {
            console.error(`Invalid name ${name} for Level filter override`);
        }

        return result;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterLevel};
}

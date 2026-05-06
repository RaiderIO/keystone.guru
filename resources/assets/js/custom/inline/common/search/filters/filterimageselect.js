class SearchFilterImageSelect extends SearchFilter {

    constructor(selector, onChange, options = {}) {
        super(selector, onChange, options);

        // Passthrough disables reading/writing from the DOM element, and instead uses an internal variable
        this.passThrough = options.hasOwnProperty('passThrough') ? options.passThrough : false;
        this.passThroughValue = '';
    }

    activate() {
        super.activate();

        let self = this;

        if (!this.passThrough) {
            $(this.selector).unbind('click').bind('click', function () {
                $(this).toggleClass('selected');

                self.onChange();
            });
        }
    }

    /**
     *
     * @returns {Array}
     */
    getValue() {
        if (this.passThrough) {
            return this.passThroughValue.split(',').filter(item => item.length > 0).map(item => item.trim());
        } else {
            let ids = [];

            $(`${this.selector}.selected`).each(function (index, element) {
                ids.push($(element).data('id'));
            });

            return ids;
        }
    }

    /**
     *
     * @param value {string}
     */
    setValue(value) {
        if (this.passThrough) {
            this.passThroughValue = value;
        } else {
            $(`${this.selector}`).removeClass('selected');

            let split = value.split(',');

            for (let index in split) {
                if (split.hasOwnProperty(index)) {
                    $(`${this.selector}[data-id='${split[index]}']`).addClass('selected');
                }
            }
        }
    }

    setPassThrough(value) {
        this.passThrough = value;
    }
}

class SearchFilterRadio extends SearchFilterInput {
    constructor(containerSelector, selector, onChange, options = {}) {
        super(selector, onChange, options);

        this.containerSelector = containerSelector;
    }

    activate() {
        super.activate();

        let self = this;

        if (!this.passThrough) {
            $(this.selector).off('change').on('change', function () {
                self.onChange();
            });
        }
    }

    /**
     *
     * @returns {string}
     */
    getValue() {
        if (this.passThrough) {
            return super.getValue();
        } else {
            return $(`${this.selector}:checked`).val();
        }
    }

    /**
     *
     * @param value
     */
    setValue(value) {
        if (this.passThrough) {
            super.setValue(value);
        } else {
            // Check the new button; the btn-check CSS styles its label automatically
            let $radioButton = $(`${this.selector}.${value}`);
            // If radio button is not checked already
            if (!$radioButton.is(':checked')) {
                $radioButton.prop('checked', true);
            }
        }
    }
}

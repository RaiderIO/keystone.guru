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
            // Check the new button along with its button
            let $radioButton = $(`${this.selector}.${value}`);
            // If radio button is not checked already
            if (!$radioButton.is(':checked')) {
                // Deselect any previous buttons
                $(`${this.containerSelector} .btn.active input`).removeAttr('checked');
                $(`${this.containerSelector} .btn.active`).removeClass('active');

                $radioButton.attr('checked', 'checked');
                $($radioButton.closest('.btn')).button('toggle');
            }
        }
    }
}

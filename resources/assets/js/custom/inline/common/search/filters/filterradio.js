class SearchFilterRadio extends SearchFilter {
    constructor(containerSelector, selector, onChange) {
        super(selector, onChange);

        this.containerSelector = containerSelector;

        $(this.selector).change(this.onChange);
    }

    /**
     *
     * @returns {string}
     */
    getValue() {
        return $(`${this.selector}:checked`).val();
    }

    /**
     *
     * @param value
     */
    setValue(value) {
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

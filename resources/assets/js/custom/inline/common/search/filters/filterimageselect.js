class SearchFilterImageSelect extends SearchFilter {
    activate() {
        super.activate();

        let self = this;

        $(this.selector).unbind('click').bind('click', function () {
            $(this).toggleClass('selected');

            self.onChange();
        });
    }

    /**
     *
     * @returns {Array}
     */
    getValue() {
        let ids = [];

        $(`${this.selector}.selected`).each(function (index, element) {
            ids.push($(element).data('id'));
        });

        return ids;
    }

    /**
     *
     * @param value {string}
     */
    setValue(value) {
        $(`${this.selector}`).removeClass('selected');

        let split = value.split(',');

        for (let index in split) {
            if (split.hasOwnProperty(index)) {
                $(`${this.selector}[data-id='${split[index]}']`).addClass('selected');
            }
        }
    }
}

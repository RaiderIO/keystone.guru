class SearchFilterImageSelect extends SearchFilter {
    constructor(options) {
        super(options);
    }

    activate() {
        super.activate();

        let self = this;

        $(this.options.selector).bind('click', function () {
            $(this).toggleClass('selected');

            self.options.onChange();
        });
    }

    getValue() {
        let ids = [];

        $(`${this.options.selector}.selected`).each(function (index, element) {
            ids.push($(element).data('id'));
        });

        return ids;
    }

    setValue(value) {
        $(`${this.options.selector}`).removeClass('selected');

        let split = value.split(',');

        for (let index in split) {
            if (split.hasOwnProperty(index)) {
                $(`${this.options.selector}[data-id='${split[index]}']`).addClass('selected');
            }
        }
    }
}
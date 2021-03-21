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
}
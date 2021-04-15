class SearchFilterInputText extends SearchFilterInput {
    constructor(options) {
        super(options);
    }

    activate() {
        super.activate();

        let self = this;

        $(this.options.selector).on('keypress', function (keyEvent) {
            // Enter pressed
            if (keyEvent.keyCode === 13) {
                self.options.onChange();
            }
        }).on('focusout', function () {
            self.options.onChange();
        });
    }
}
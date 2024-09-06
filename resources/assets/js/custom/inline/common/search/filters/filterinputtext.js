class SearchFilterInputText extends SearchFilterInput {
    activate() {
        super.activate();

        let self = this;

        $(this.selector).on('keydown', function (keyEvent) {
            // Enter pressed
            if (keyEvent.keyCode === 13) {
                self.onChange();
            }
        }).on('focusout', function () {
            self.onChange();
        });
    }
}

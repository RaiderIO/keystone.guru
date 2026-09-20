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

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterInputText};
}

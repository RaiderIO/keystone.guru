/**
 * An input or select that searches again as soon as its value changes. SearchFilterInput itself binds nothing, so a
 * control registered as one is restored from the URL but sits inert until something else asks for a search.
 */
class SearchFilterInputChange extends SearchFilterInput {
    activate() {
        super.activate();

        let self = this;

        $(this.selector).on('change', function () {
            self.onChange();
        });
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {SearchFilterInputChange};
}

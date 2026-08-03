/**
 * @typedef {Object} ProfileEdittabsCreatorOptions
 * @property {string} filterInputSelector Text input used to filter the pinnable route list by title/dungeon.
 * @property {string} itemSelector        Selector for each row in the pinnable route list (the label
 *                                        wrapping the checkbox), each carrying a lowercased
 *                                        `data-search` attribute to filter against.
 * @property {number} maxPinnedRoutes     The maximum number of routes a user may pin - once reached,
 *                                        unchecked rows are disabled so the user can't submit more
 *                                        than the server will accept.
 */

/**
 * Compact, searchable replacement for a plain `<select multiple>` of a creator's own routes to pin
 * to their public profile. A plain select doesn't scale well once a user has more than a handful of
 * routes, so this filters the list client-side as the user types and caps how many can be checked.
 *
 * @property {ProfileEdittabsCreatorOptions} options
 */
class ProfileEdittabsCreator extends InlineCode {
    activate() {
        super.activate();

        let self = this;

        $(this.options.filterInputSelector).on('input', function () {
            self._applyFilter($(this).val());
        });

        $(this.options.itemSelector).find('input[type=checkbox]').on('change', function () {
            self._enforceMax();
        });

        this._enforceMax();
    }

    /**
     * Hides any route row whose `data-search` doesn't contain the (lowercased) search term.
     *
     * @param {string} searchTerm
     * @private
     */
    _applyFilter(searchTerm) {
        let needle = searchTerm.toLowerCase();

        $(this.options.itemSelector).each(function () {
            let $item  = $(this);
            let hidden = needle.length > 0 && $item.data('search').toString().indexOf(needle) === -1;

            $item.toggleClass('d-none', hidden);
        });
    }

    /**
     * Disables any not-yet-checked checkbox once the maximum number of pinned routes is checked, so
     * the user gets immediate feedback instead of a server-side validation error on submit.
     *
     * @private
     */
    _enforceMax() {
        let $checkboxes = $(this.options.itemSelector).find('input[type=checkbox]');
        let checkedCount = $checkboxes.filter(':checked').length;
        let atMax        = checkedCount >= this.options.maxPinnedRoutes;

        $checkboxes.not(':checked').prop('disabled', atMax);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {ProfileEdittabsCreator};
}

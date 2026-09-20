/**
 @typedef {Object} CommonCollectionDetailsOptions
 @property {string} routesSelector       The routes section, swapped as a whole when the season changes.
 @property {string} loadingSelector      Shown while the section is being rebuilt.
 @property {string} errorSelector        Shown when rebuilding the section failed.
 @property {string} seasonSelector       The season radios; absent on a game version without seasons.
 @property {string} formUrl              The new-collection form, which renders the section for the season_id it is given.
 @property {string} seasonNone           The season_id value that asks for a free-form collection.
 */

/**
 * The season a new collection is created for: the routes section covers the season's dungeons and offers only its
 * routes, so picking another season rebuilds the section from the server rather than the whole page - the details
 * filled in so far stay as they are.
 *
 * @property {CommonCollectionDetailsOptions} options
 */
class CommonCollectionDetails extends InlineCode {

    activate() {
        super.activate();

        this._requestCount = 0;

        $(this.options.seasonSelector).on('change', this._onSeasonChanged.bind(this));
    }

    /**
     * @private
     */
    _onSeasonChanged() {
        let seasonId = String($(`${this.options.seasonSelector}:checked`).val() ?? '');
        let requestNumber = ++this._requestCount;
        let $routes = $(this.options.routesSelector);

        $routes.attr('aria-busy', 'true').find('input, select, button').prop('disabled', true);
        $(this.options.loadingSelector).prop('hidden', false);
        $(this.options.errorSelector).prop('hidden', true);

        let url = new URL(this.options.formUrl, window.location.href);
        url.searchParams.set('season_id', seasonId === '' ? this.options.seasonNone : seasonId);

        fetch(url.toString(), {credentials: 'same-origin', headers: {'X-Requested-With': 'XMLHttpRequest'}})
            .then((response) => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                return response.text();
            })
            .then((html) => {
                // A later pick superseded this one
                if (requestNumber !== this._requestCount) {
                    return;
                }

                let $replacement = $(new DOMParser().parseFromString(html, 'text/html'))
                    .find(this.options.routesSelector);
                if ($replacement.length === 0) {
                    throw new Error('The response holds no routes section');
                }

                $(this.options.routesSelector).replaceWith($replacement);
                this._activateInlineCode($replacement);
                $(this.options.loadingSelector).prop('hidden', true);
            })
            .catch((error) => {
                if (requestNumber !== this._requestCount) {
                    return;
                }

                console.error(error);
                $(this.options.routesSelector).removeAttr('aria-busy');
                $(this.options.loadingSelector).prop('hidden', true);
                $(this.options.errorSelector).prop('hidden', false);
            });
    }

    /**
     * The swapped-in markup arrives without the scripts that activated it on page load. Every control is
     * rebuilt under the id it had before, so the references they hold to one another keep pointing at the
     * live instances.
     *
     * @param {jQuery} $section
     * @private
     */
    _activateInlineCode($section) {
        let selector = '[data-inline-id][data-inline-path][data-inline-options]';
        // The section's own controller comes last: it looks the others up as it activates. jQuery's add()
        // would sort it back to the front, so the two sets are concatenated by hand.
        let elements = $section.find(selector).toArray().concat($section.filter(selector).toArray());

        elements.forEach(function (element) {
            let $element = $(element);

            _inlineManager.init($element.data('inline-id'), $element.data('inline-path'), $element.data('inline-options'));
        });

        elements.forEach(element => _inlineManager.activate($(element).data('inline-id')));

        // Tom Select drives the drawer's filter selects, which arrive as plain <select> elements
        if (typeof refreshSelectPickers === 'function') {
            refreshSelectPickers();
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonCollectionDetails};
}

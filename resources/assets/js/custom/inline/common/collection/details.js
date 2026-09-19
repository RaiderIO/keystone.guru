/**
 @typedef {Object} CommonCollectionDetailsOptions
 @property {string}      dungeonRoutesSelector   The route picker section, swapped as a whole when the kind changes.
 @property {string}      totalSelector           The "n / max" counter over every slot.
 @property {string}      loadingSelector         Shown while the picker is being rebuilt.
 @property {string}      errorSelector           Shown when rebuilding the picker failed.
 @property {string}      gameVersionSelector     The game version radios.
 @property {string}      seasonContainerSelector One season fieldset per game version with seasons, carrying data-game-version-id.
 @property {Number}      max
 @property {string}      countText               Contains :count and :max.
 @property {string|null} formUrl                 The new-collection form, which renders the picker for the game_version_id and season_id it is given; null when the picker never changes.
 @property {string}      seasonNone              The season_id value that asks for a free-form collection.
 */

/**
 * The collection form's route picker: keeps the collection-wide route counter current and, on a new collection,
 * rebuilds the picker for the game version and season the user picks.
 *
 * @property {CommonCollectionDetailsOptions} options
 */
class CommonCollectionDetails extends InlineCode {

    activate() {
        super.activate();

        this._requestCount = 0;

        $(document).on('orderedselect:changed', this.options.dungeonRoutesSelector, this._refreshTotal.bind(this));
        this._refreshTotal();

        if (this.options.formUrl !== null) {
            $(this.options.gameVersionSelector).on('change', this._onKindChanged.bind(this));
            $(`${this.options.seasonContainerSelector} input`).on('change', this._onKindChanged.bind(this));
            this._applySeasonVisibility();
        }
    }

    /**
     * @private
     */
    _refreshTotal() {
        let count = $(this.options.dungeonRoutesSelector).find('.ordered_select_item input[type="hidden"]').length;

        $(this.options.totalSelector).text(
            this.options.countText.replace(':count', count).replace(':max', this.options.max)
        );
    }

    /**
     * Shows only the selected game version's season field, and keeps the hidden ones from being posted.
     *
     * @returns {{gameVersionId: string, seasonId: string|null}} seasonId is null for a game version without seasons.
     * @private
     */
    _applySeasonVisibility() {
        let gameVersionId = String($(`${this.options.gameVersionSelector}:checked`).val());
        let seasonId = null;

        $(this.options.seasonContainerSelector).each(function () {
            let $container = $(this);
            let isSelected = String($container.data('game-version-id')) === gameVersionId;

            $container.prop('hidden', !isSelected).find('input').prop('disabled', !isSelected);
            if (isSelected) {
                seasonId = String($container.find('input:checked').val() ?? '');
            }
        });

        return {gameVersionId: gameVersionId, seasonId: seasonId};
    }

    /**
     * @private
     */
    _onKindChanged() {
        let kind = this._applySeasonVisibility();
        let requestNumber = ++this._requestCount;
        let $dungeonRoutes = $(this.options.dungeonRoutesSelector);

        $dungeonRoutes.attr('aria-busy', 'true').find('input, select, button').prop('disabled', true);
        $(this.options.loadingSelector).prop('hidden', false);
        $(this.options.errorSelector).prop('hidden', true);

        let url = new URL(this.options.formUrl, window.location.href);
        url.searchParams.set('game_version_id', kind.gameVersionId);
        if (kind.seasonId !== null) {
            url.searchParams.set('season_id', kind.seasonId === '' ? this.options.seasonNone : kind.seasonId);
        }

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
                    .find(this.options.dungeonRoutesSelector);
                if ($replacement.length === 0) {
                    throw new Error('The response holds no route picker');
                }

                $(this.options.dungeonRoutesSelector).replaceWith($replacement);
                this._activateOrderedSelects($replacement);
                this._refreshTotal();
                $(this.options.loadingSelector).prop('hidden', true);
            })
            .catch((error) => {
                if (requestNumber !== this._requestCount) {
                    return;
                }

                console.error(error);
                $(this.options.dungeonRoutesSelector).removeAttr('aria-busy');
                $(this.options.loadingSelector).prop('hidden', true);
                $(this.options.errorSelector).prop('hidden', false);
            });
    }

    /**
     * The swapped-in controls arrive without the scripts that activated them on page load.
     *
     * @param {jQuery} $container
     * @private
     */
    _activateOrderedSelects($container) {
        let requestNumber = this._requestCount;

        $container.find('.ordered_select[data-inline-options]').each(function () {
            let id = `${this.id}_${requestNumber}`;

            _inlineManager.init(id, 'common/forms/orderedselect', $(this).data('inline-options'));
            _inlineManager.activate(id);
        });
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonCollectionDetails};
}

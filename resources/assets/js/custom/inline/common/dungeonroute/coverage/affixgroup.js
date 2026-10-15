/**
 * @typedef {Object} CommonDungeonrouteCoverageAffixgroupOptions
 * @property {string} coverageFilterButtonSelector
 * @property {string} coverageNewDungeonRouteButtonSelector
 * @property {string} createRouteModalSelector
 * @property {string} seasonIdSelector
 */

/**
 * @property {CommonDungeonrouteCoverageAffixgroupOptions} options
 */
class CommonDungeonrouteCoverageAffixgroup extends InlineCode {
    activate() {
        super.activate();

        let self = this;

        // Both inline codes are looked up on click: the route table's is initialized after this one, and the
        // affixes' only exists once the create route modal has lazily loaded its form
        $(this.options.coverageFilterButtonSelector).on('click', function () {
            _inlineManager.getInlineCode('dungeonroute/table')
                .overrideSelection($(this).data('dungeon-id'), [$(this).data('affix-group-id')]);

            refreshSelectPickers();
        });

        $(this.options.coverageNewDungeonRouteButtonSelector).on('click', function () {
            self._overrideCreateRouteSelection($(this).data('dungeon-id'), $(this).data('affix-group-id'));
        });

        $(this.options.seasonIdSelector).on('change', function () {
            let newVal = $(this).val();
            if (Cookies.get('dungeonroute_coverage_season_id') !== newVal) {
                Cookies.set('dungeonroute_coverage_season_id', newVal, cookieDefaultAttributes);
                // Refresh the page to show new season
                window.location.href = '/';
            }
        });
    }

    /**
     * Preselects the dungeon and affix group in the create route modal's form, or does so once the modal has loaded it.
     *
     * @param {number} dungeonId
     * @param {number} affixGroupId
     * @private
     */
    _overrideCreateRouteSelection(dungeonId, affixGroupId) {
        let groupAffixesInlineCode = _inlineManager.getInlineCode('common/group/affixes');

        if (typeof groupAffixesInlineCode.overrideSelection === 'function') {
            groupAffixesInlineCode.overrideSelection(dungeonId, [affixGroupId]);

            refreshSelectPickers();
        } else {
            $(this.options.createRouteModalSelector).off('modallazy:loaded.coverage').one('modallazy:loaded.coverage', () => {
                this._overrideCreateRouteSelection(dungeonId, affixGroupId);
            });
        }
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonDungeonrouteCoverageAffixgroup};
}

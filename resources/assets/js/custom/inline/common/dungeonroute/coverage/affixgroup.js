/**
 * @typedef {Object} CommonDungeonrouteCoverageAffixgroupOptions
 * @property {string} coverageFilterButtonSelector
 * @property {string} coverageNewDungeonRouteButtonSelector
 * @property {string} seasonIdSelector
 */

/**
 * @property {CommonDungeonrouteCoverageAffixgroupOptions} options
 */
class CommonDungeonrouteCoverageAffixgroup extends InlineCode {
    activate() {
        super.activate();

        let groupAffixesInlineCode = _inlineManager.getInlineCode('common/group/affixes');
        let dungeonRouteTableInlineCode = _inlineManager.getInlineCode('dungeonroute/table');

        $(this.options.coverageFilterButtonSelector).on('click', function () {
            dungeonRouteTableInlineCode.overrideSelection($(this).data('dungeon-id'), [$(this).data('affix-group-id')]);

            refreshSelectPickers();
        });

        $(this.options.coverageNewDungeonRouteButtonSelector).on('click', function () {
            groupAffixesInlineCode.overrideSelection($(this).data('dungeon-id'), [$(this).data('affix-group-id')]);

            refreshSelectPickers();
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
}

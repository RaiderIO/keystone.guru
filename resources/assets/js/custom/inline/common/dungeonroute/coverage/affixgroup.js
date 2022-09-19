class CommonDungeonrouteCoverageAffixgroup extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        let groupAffixesInlineCode = _inlineManager.getInlineCode('common/group/affixes');
        let dungeonRouteTableInlineCode = _inlineManager.getInlineCode('dungeonroute/table');

        $('.dungeonroute_coverage_filter_select .btn').on('click', function () {
            dungeonRouteTableInlineCode.overrideSelection($(this).data('dungeon-id'), [$(this).data('affix-group-id')]);

            refreshSelectPickers();
        });

        $('.dungeonroute_coverage_new_dungeon_route .btn').on('click', function () {
            groupAffixesInlineCode.overrideSelection($(this).data('dungeon-id'), [$(this).data('affix-group-id')]);

            refreshSelectPickers();
        });

        $('#dungeonroute_coverage_season_id').on('change', function () {
            let newVal = $(this).val();
            if (Cookies.get('dungeonroute_coverage_season_id') !== newVal) {
                Cookies.set('dungeonroute_coverage_season_id', newVal, cookieDefaultAttributes);
                // Refresh the page to show new season
                window.location.href = '/';
            }
        });
    }
}

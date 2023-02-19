class CommonDungeonrouteShare extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        // Copy to clipboard functionality
        $('#map_shareable_link_copy_to_clipboard').unbind('click').bind('click', function () {
            let $shareableLink = $('#map_shareable_link');
            copyToClipboard($shareableLink.val(), $shareableLink);
        });
        $('#map_shareable_short_link_copy_to_clipboard').unbind('click').bind('click', function () {
            let $shareableLink = $('#map_shareable_short_link');
            copyToClipboard($shareableLink.val(), $shareableLink);
        });
        $('#map_embedable_link_copy_to_clipboard').unbind('click').bind('click', function () {
            let $embedableLink = $('#map_embedable_link');
            copyToClipboard($embedableLink.val(), $embedableLink);
        });
        $('.copy_mdt_string_to_clipboard').unbind('click').bind('click', function () {
            let $exportResult = $('#mdt_export_result');
            copyToClipboard($exportResult.val(), $exportResult);

            getState().sendMetricForDungeonRoute(METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, METRIC_TAG_MDT_COPY_VIEW);
        });
    }
}

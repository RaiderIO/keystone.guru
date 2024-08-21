/**
 * @typedef DungeonRouteShareOptions
 * @property {String} mapShareableLinkSelector
 *
 * @property {String} shareLink
 * @property {String} mapShareableLinkCopyToClipboardSelector
 * @property {String} mapShareableShortLinkSelector
 *
 * @property {String} shareShortLink
 * @property {String} mapShareableShortLinkCopyToClipboardSelector
 * @property {String} mapIncludeLocationCheckboxSelector
 *
 * @property {String} mapEmbeddableLinkSelector
 * @property {String} mapEmbeddableLinkCopyToClipboardSelector
 *
 * @property {String} mdtExportResultSelector
 * @property {String} copyMdtStringToClipboardSelector
 *
 * @property {String|null} modalSelector
 */

/**
 * @property {DungeonRouteShareOptions} options
 */
class CommonDungeonrouteShare extends InlineCode {
    /**
     *
     */
    activate() {
        super.activate();

        let self = this;

        // Copy to clipboard functionality
        $(this.options.mapShareableLinkCopyToClipboardSelector).unbind('click').bind('click', function () {
            let $shareableLink = $(self.options.mapShareableLinkSelector);
            copyToClipboard($shareableLink.val(), $shareableLink);
        });

        $(this.options.mapShareableShortLinkCopyToClipboardSelector).unbind('click').bind('click', function () {
            let $shareableLink = $(self.options.mapShareableShortLinkSelector);
            copyToClipboard($shareableLink.val(), $shareableLink);
        });

        $(this.options.mapEmbeddableLinkCopyToClipboardSelector).unbind('click').bind('click', function () {
            let $embeddableLink = $(self.options.mapEmbeddableLinkSelector);
            copyToClipboard($embeddableLink.val(), $embeddableLink);
        });

        $(this.options.copyMdtStringToClipboardSelector).unbind('click').bind('click', function () {
            let $exportResult = $(self.options.mdtExportResultSelector);
            copyToClipboard($exportResult.val(), $exportResult);

            getState().sendMetricForDungeonRoute(METRIC_CATEGORY_DUNGEON_ROUTE_MDT_COPY, METRIC_TAG_MDT_COPY_VIEW);
        });


        let updateLinks = function () {
            let includeLocation = $('#map_include_location_checkbox').is(':checked');

            let state = getState();
            let currentZoomLevel = state.getMapZoomLevel();
            /** @type {LatLng} */
            let center = getState().getDungeonMap().leafletMap.getCenter();

            // https://stackoverflow.com/a/12830454/771270
            let locationStr = includeLocation ? `?lat=${+center.lat.toFixed(2)}&lng=${+center.lng.toFixed(2)}&z=${+currentZoomLevel.toFixed(2)}` : '';

            $(self.options.mapShareableLinkSelector).val(self.options.shareLink + locationStr);
            $(self.options.mapShareableShortLinkSelector).val(self.options.shareShortLink + locationStr);
        };

        $(this.options.mapIncludeLocationCheckboxSelector).on('change', updateLinks);

        if (this.options.hasOwnProperty('modalSelector') && this.options.modalSelector !== null) {
            $(this.options.modalSelector).on('show.bs.modal', updateLinks);
        }
    }
}

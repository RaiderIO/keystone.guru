/**
 * @typedef {Object} CommonMapsHeatmaphotspotsOptions
 * @property {String} toggleSelector
 * @property {String} enabledStateCookie
 */

/**
 * The toggle that shows or hides the deadly-NPC hotspots of an open-world heatmap.
 *
 * @property {CommonMapsHeatmaphotspotsOptions} options
 */
class CommonMapsHeatmaphotspots extends InlineCode {
    activate() {
        super.activate();

        let self = this;
        let $toggle = $(this.options.toggleSelector);

        $toggle.on('change', function () {
            self._toggleHotspots($(this).is(':checked'));
        });

        this._toggleHotspots($toggle.is(':checked'));
    }

    /**
     * @param enabled {Boolean}
     * @private
     */
    _toggleHotspots(enabled) {
        getState().getDungeonMap().pluginHeatmapHotspot.toggle(enabled);

        Cookies.set(this.options.enabledStateCookie, (enabled ? 1 : 0) + '', cookieDefaultAttributes);
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {CommonMapsHeatmaphotspots};
}

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

const {CommonMapsHeatmaphotspots} = require('./heatmaphotspots');

describe('CommonMapsHeatmaphotspots', () => {
    let toggle;

    beforeEach(() => {
        toggle = vi.fn();
        globalThis.getState = () => ({getDungeonMap: () => ({pluginHeatmapHotspot: {toggle}})});
        globalThis.Cookies = {get: () => undefined, set: vi.fn()};
        globalThis.cookieDefaultAttributes = {};
    });

    /**
     * @param checked {Boolean}
     * @returns {CommonMapsHeatmaphotspots}
     */
    function activate(checked) {
        document.body.innerHTML = `<input id="heatmap_hotspots_toggle" type="checkbox" ${checked ? 'checked' : ''}>`;
        const code = new CommonMapsHeatmaphotspots('id', 'common/maps/heatmaphotspots', {
            toggleSelector: '#heatmap_hotspots_toggle',
            enabledStateCookie: 'heatmap_hotspots_enabled',
        });
        code.activate();

        return code;
    }

    it('activate_givenToggleUnchecked_hidesTheHotspots', () => {
        activate(false);

        expect(toggle).toHaveBeenCalledWith(false);
        expect(Cookies.set).toHaveBeenCalledWith('heatmap_hotspots_enabled', '0', {});
    });

    it('activate_givenToggleChecked_showsTheHotspots', () => {
        activate(true);

        expect(toggle).toHaveBeenCalledWith(true);
        expect(Cookies.set).toHaveBeenCalledWith('heatmap_hotspots_enabled', '1', {});
    });

    it('change_givenToggleSwitchedOff_hidesTheHotspotsAndRemembersIt', () => {
        activate(true);
        toggle.mockClear();
        Cookies.set.mockClear();

        $('#heatmap_hotspots_toggle').prop('checked', false).trigger('change');

        expect(toggle).toHaveBeenCalledWith(false);
        expect(Cookies.set).toHaveBeenCalledWith('heatmap_hotspots_enabled', '0', {});
    });
});

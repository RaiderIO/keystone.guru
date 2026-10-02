// The map's left rail is a scroll container that stops at the header and above a bottom ad; its dropdown menus
// open beside it and Popper has to keep them within that same extent.
//
// Follows the global-script recipe from map.favorite.test.js: stub what the class body touches at load time,
// then require the source.

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.SettingsTabMap = class SettingsTabMap {
};
globalThis.SettingsTabPull = class SettingsTabPull {
};

const {CommonMapsMap} = require('./map');

describe('CommonMapsMap rail dropdowns', () => {
    /** Bootstrap's own Popper config for a dropend dropdown with data-bs-boundary="viewport" */
    const bootstrapDefaultConfig = {
        placement: 'right-start',
        modifiers: [
            {name: 'preventOverflow', options: {boundary: 'viewport'}},
            {name: 'offset', options: {offset: [0, 2]}},
        ],
    };

    let getOrCreateInstance;

    beforeEach(() => {
        getOrCreateInstance = vi.fn();
        globalThis.bootstrap = {Dropdown: {getOrCreateInstance: getOrCreateInstance}};

        document.body.innerHTML = `
            <nav id="rail" class="route_manipulation_tools left">
                <button id="rail_toggle" data-bs-toggle="dropdown"></button>
            </nav>
            <nav class="route_manipulation_tools left top presenter">
                <button id="presenter_toggle" data-bs-toggle="dropdown"></button>
            </nav>`;
        document.getElementById('rail').getBoundingClientRect = () => ({
            top: 111, bottom: window.innerHeight - 90, left: 0, right: 69, width: 69, height: window.innerHeight - 201,
        });
    });

    afterEach(() => {
        vi.clearAllMocks();
        delete globalThis.bootstrap;
        document.body.innerHTML = '';
    });

    /**
     * @returns {CommonMapsMap}
     */
    function buildMap() {
        return new CommonMapsMap('map', 'common/maps/map', {});
    }

    it('_getRailDropdownPopperConfig_givenRailAboveAnAdReserve_padsTheMenuToTheRail', () => {
        // Arrange
        const rail = document.getElementById('rail');

        // Act
        const config = buildMap()._getRailDropdownPopperConfig(rail, bootstrapDefaultConfig);

        // Assert
        expect(config.strategy).toBe('fixed');
        expect(config.placement).toBe('right-start');
        expect(config.modifiers).toEqual([
            {name: 'preventOverflow', options: {boundary: 'viewport', padding: {top: 111, bottom: 90}}},
            {name: 'offset', options: {offset: [0, 2]}},
        ]);
    });

    it('_setupRailDropdowns_givenRailAndPresenterDropdowns_configuresOnlyTheRailOnes', () => {
        // Act
        buildMap()._setupRailDropdowns();

        // Assert
        expect(getOrCreateInstance).toHaveBeenCalledTimes(1);
        const [toggle, options] = getOrCreateInstance.mock.calls[0];
        expect(toggle.id).toBe('rail_toggle');
        expect(options.popperConfig(bootstrapDefaultConfig).modifiers[0].options.padding).toEqual({top: 111, bottom: 90});
    });
});

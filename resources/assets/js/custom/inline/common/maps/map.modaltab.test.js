// Follows the global-script recipe from map.favorite.test.js: stub the collaborators the class body
// touches at load time, then require the source.

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.SettingsTabMap = class SettingsTabMap {
};
globalThis.SettingsTabPull = class SettingsTabPull {
};

const {CommonMapsMap} = require('./map');

describe('CommonMapsMap._selectModalTab', () => {
    let showSpy;
    let getOrCreateInstanceSpy;

    beforeEach(() => {
        document.body.innerHTML = `
            <a id="dungeon_route_info_tab" href="#route-info"></a>
            <a id="combatlog_info_tab" href="#combatlog-info"></a>
            <a id="with_tab" data-modal-tab="#combatlog_info_tab"></a>
            <a id="with_missing_tab" data-modal-tab="#does_not_exist"></a>
            <a id="without_tab"></a>
        `;

        showSpy = vi.fn();
        getOrCreateInstanceSpy = vi.fn(() => ({show: showSpy}));
        globalThis.bootstrap = {Tab: {getOrCreateInstance: getOrCreateInstanceSpy}};
    });

    afterEach(() => {
        vi.clearAllMocks();
        document.body.innerHTML = '';
    });

    /**
     * @returns {CommonMapsMap}
     */
    function buildMap() {
        return new CommonMapsMap('map', 'common/maps/map', {});
    }

    it('_selectModalTab_givenTriggerWithModalTab_showsThatTab', () => {
        // Arrange
        let relatedTarget = document.getElementById('with_tab');

        // Act
        buildMap()._selectModalTab({relatedTarget: relatedTarget});

        // Assert
        expect(getOrCreateInstanceSpy).toHaveBeenCalledWith(document.getElementById('combatlog_info_tab'));
        expect(showSpy).toHaveBeenCalledTimes(1);
    });

    it('_selectModalTab_givenTriggerWithoutModalTab_showsNoTab', () => {
        // Arrange
        let relatedTarget = document.getElementById('without_tab');

        // Act
        buildMap()._selectModalTab({relatedTarget: relatedTarget});

        // Assert
        expect(getOrCreateInstanceSpy).not.toHaveBeenCalled();
    });

    it('_selectModalTab_givenNoTrigger_showsNoTab', () => {
        // Act
        buildMap()._selectModalTab({relatedTarget: undefined});

        // Assert
        expect(getOrCreateInstanceSpy).not.toHaveBeenCalled();
    });

    it('_selectModalTab_givenModalTabThatDoesNotExist_showsNoTab', () => {
        // Arrange
        let relatedTarget = document.getElementById('with_missing_tab');

        // Act
        buildMap()._selectModalTab({relatedTarget: relatedTarget});

        // Assert
        expect(getOrCreateInstanceSpy).not.toHaveBeenCalled();
    });
});

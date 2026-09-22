// Right-clicking an enemy opens its NPC Compendium page in a new tab; the `#enemy_details_modal`
// bootstrap modal is only the fallback for when the popup is blocked.
//
// Follows the global-script recipe from map.favorite.test.js: stub the collaborators the class
// body touches at load time, then require the source.

globalThis.$ = globalThis.jQuery = require('jquery');

const {InlineCode} = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

globalThis.SettingsTabMap = class SettingsTabMap {
};
globalThis.SettingsTabPull = class SettingsTabPull {
};

const {CommonMapsMap} = require('./map');

describe('CommonMapsMap._onEnemyContextMenu', () => {
    let visualData;
    let enemy;
    let windowOpenSpy;
    let modalShowSpy;

    beforeEach(() => {
        document.body.innerHTML = '';

        visualData = {};
        enemy = {
            npc: {id: 123, name: 'Some Npc'},
            getVisualData: () => visualData,
        };

        globalThis.getState = vi.fn(() => ({
            isMapAdmin: () => false,
        }));
        globalThis.lang = {get: (key) => key};

        // Returns a fake Window-like object by default, i.e. the popup was not blocked
        windowOpenSpy = vi.spyOn(window, 'open').mockImplementation(() => ({}));

        modalShowSpy = vi.fn();
        globalThis.Handlebars = {templates: {map_sidebar_enemy_info_template: () => ''}};
        globalThis.refreshTooltips = vi.fn();
        globalThis.bootstrap = {
            Collapse: {getOrCreateInstance: () => ({hide: vi.fn()})},
            Modal: {getOrCreateInstance: () => ({show: modalShowSpy})},
        };
    });

    afterEach(() => {
        vi.clearAllMocks();
    });

    /**
     * @param {Object} options
     * @returns {CommonMapsMap}
     */
    function buildMap(options = {}) {
        return new CommonMapsMap('map', 'common/maps/map', options);
    }

    it('_onEnemyContextMenu_givenNoEnemyDetailsModalInDom_doesNothing', () => {
        // Arrange
        let map = buildMap({npcCompendiumBaseUrl: '/compendium/npc'});

        // Act
        map._onEnemyContextMenu({context: enemy});

        // Assert
        expect(windowOpenSpy).not.toHaveBeenCalled();
    });

    it('_onEnemyContextMenu_givenEnemyDetailsModalInDom_opensCompendiumPageInNewTab', () => {
        // Arrange
        document.body.innerHTML = '<div id="enemy_details_modal"></div>';
        let map = buildMap({npcCompendiumBaseUrl: '/compendium/npc'});

        // Act
        map._onEnemyContextMenu({context: enemy});

        // Assert
        expect(windowOpenSpy).toHaveBeenCalledWith('/compendium/npc/123');
    });

    it('_onEnemyContextMenu_givenPopupBlocked_fallsBackToModal', () => {
        // Arrange - a sandboxed embed iframe without allow-popups returns null from window.open()
        document.body.innerHTML = '<div id="enemy_details_modal"></div><div id="enemy_details_modal_title_text"></div><div id="enemy_details_modal_body"></div>';
        windowOpenSpy.mockImplementation(() => null);
        let map = buildMap({npcCompendiumBaseUrl: '/compendium/npc'});

        // Act
        map._onEnemyContextMenu({context: enemy});

        // Assert
        expect(windowOpenSpy).toHaveBeenCalledWith('/compendium/npc/123');
        expect(modalShowSpy).toHaveBeenCalled();
    });
});

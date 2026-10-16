// The raid size switch in the map's left rail: picking an entry hands its difficulty to the map context and
// moves the active marker to it.
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

describe('CommonMapsMap dungeon difficulty', () => {
    let mapContext;

    beforeEach(() => {
        mapContext = {setDungeonDifficulty: vi.fn()};
        globalThis.getState = () => ({getMapContext: () => mapContext});

        document.body.innerHTML = `
            <div id="map_dungeon_difficulty_dropdown" class="dropdown-menu">
                <a class="dropdown-item disabled">Raid size</a>
                <a class="dropdown-item active" data-value="1">10-man</a>
                <a class="dropdown-item" data-value="2">25-man</a>
            </div>`;

        new CommonMapsMap('map', 'common/maps/map', {})._setupDungeonDifficulty();
    });

    afterEach(() => {
        delete globalThis.getState;
        document.body.innerHTML = '';
    });

    /**
     * @param {string} value
     * @returns {jQuery}
     */
    function item(value) {
        return $(`#map_dungeon_difficulty_dropdown a[data-value="${value}"]`);
    }

    it('_setupDungeonDifficulty_given25ManClicked_setsItOnTheMapContextAndMarksItActive', () => {
        // Act
        item('2').trigger('click');

        // Assert
        expect(mapContext.setDungeonDifficulty).toHaveBeenCalledTimes(1);
        expect(mapContext.setDungeonDifficulty).toHaveBeenCalledWith(2);
        expect(item('2').hasClass('active')).toBe(true);
        expect(item('1').hasClass('active')).toBe(false);
    });

    it('_setupDungeonDifficulty_givenTheHeaderClicked_leavesTheDifficultyAlone', () => {
        // Act
        $('#map_dungeon_difficulty_dropdown a.disabled').trigger('click');
        item('1').trigger('click');

        // Assert - only the real entry got through
        expect(mapContext.setDungeonDifficulty).toHaveBeenCalledTimes(1);
        expect(mapContext.setDungeonDifficulty).toHaveBeenCalledWith(1);
        expect(item('1').hasClass('active')).toBe(true);
    });
});

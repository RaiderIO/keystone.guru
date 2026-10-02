// ---------------------------------------------------------------------------
// See the recipe at the top of ../models/killzone.test.js: fake the base class instead of constructing the real
// (heavy) MapContext.
// ---------------------------------------------------------------------------

global.MapContextMappingVersion = class MapContextMappingVersion {
    constructor(options) {
        this._options = options;
    }
};

global.lang = {
    get: (key) => ({
        'dungeons.classic.deadmines': 'The Deadmines',
        'dungeons.classic.blackfathom_deeps': 'Blackfathom Deeps',
        'dungeons.classic.wailing_caverns': 'Wailing Caverns',
    })[key] ?? key,
};

const {MapContextMappingVersionEdit} = require('./mapcontextmappingversionedit');

describe('MapContextMappingVersionEdit', () => {
    test('getDungeonSelectValues_givenDungeons_returnsThemTranslatedAndSortedByName', () => {
        // Arrange
        const mapContext = new MapContextMappingVersionEdit({
            dungeonSelectValues: [
                {id: 3, name: 'dungeons.classic.wailing_caverns'},
                {id: 1, name: 'dungeons.classic.deadmines'},
                {id: 2, name: 'dungeons.classic.blackfathom_deeps'},
            ],
        });

        // Act
        const selectValues = mapContext.getDungeonSelectValues();

        // Assert
        expect(selectValues).toEqual([
            {id: 2, name: 'Blackfathom Deeps'},
            {id: 1, name: 'The Deadmines'},
            {id: 3, name: 'Wailing Caverns'},
        ]);
    });
});

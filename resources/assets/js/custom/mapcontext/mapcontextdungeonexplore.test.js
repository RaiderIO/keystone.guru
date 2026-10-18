const {Signalable} = require('../signalable');

globalThis.DUNGEON_DIFFICULTY_10_MAN = 1;
globalThis.DUNGEON_DIFFICULTY_25_MAN = 2;
globalThis.MapContextMappingVersion = class MapContextMappingVersion extends Signalable {
    constructor(options) {
        super();
        this._options = options;
    }
};

const {MapContextDungeonExplore} = require('./mapcontextdungeonexplore');

describe('MapContextDungeonExplore', () => {
    test('getDungeonDifficulty_givenANewMapContext_returns10Man', () => {
        // Arrange
        const mapContext = new MapContextDungeonExplore({});

        // Act
        const dungeonDifficulty = mapContext.getDungeonDifficulty();

        // Assert
        expect(dungeonDifficulty).toBe(DUNGEON_DIFFICULTY_10_MAN);
    });

    test('setDungeonDifficulty_given25Man_returnsItAndSignalsTheChange', () => {
        // Arrange
        const mapContext = new MapContextDungeonExplore({});
        const changed = vi.fn();
        mapContext.register('dungeondifficulty:changed', this, changed);

        // Act
        mapContext.setDungeonDifficulty(DUNGEON_DIFFICULTY_25_MAN);

        // Assert
        expect(mapContext.getDungeonDifficulty()).toBe(DUNGEON_DIFFICULTY_25_MAN);
        expect(changed).toHaveBeenCalledTimes(1);
        expect(changed.mock.calls[0][0].data).toEqual({dungeonDifficulty: DUNGEON_DIFFICULTY_25_MAN});
    });

    test('setDungeonDifficulty_givenTheCurrentDifficulty_doesNotSignal', () => {
        // Arrange
        const mapContext = new MapContextDungeonExplore({});
        const changed = vi.fn();
        mapContext.register('dungeondifficulty:changed', this, changed);

        // Act
        mapContext.setDungeonDifficulty(DUNGEON_DIFFICULTY_10_MAN);

        // Assert
        expect(changed).not.toHaveBeenCalled();
        expect(mapContext.getDungeonDifficulty()).toBe(DUNGEON_DIFFICULTY_10_MAN);
    });
});

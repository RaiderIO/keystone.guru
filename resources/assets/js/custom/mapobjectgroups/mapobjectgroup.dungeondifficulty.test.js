// Switching the raid size on Explore re-decides which map objects show: enemies of the other raid size
// hide, enemies of the picked one show.

const {Signalable} = require('../signalable');
globalThis.Signalable = Signalable;
globalThis.MapObjectGroupManager = class MapObjectGroupManager {
};
globalThis.AdminDungeonMap = class AdminDungeonMap {
};

const {MapObjectGroup} = require('./mapobjectgroup');

describe('MapObjectGroup dungeon difficulty', () => {
    let originalGetState;
    let mapContext;
    let group;

    beforeEach(() => {
        originalGetState = globalThis.getState;
        mapContext = new Signalable();
        globalThis.getState = () => ({getMapContext: () => mapContext});

        vi.spyOn(MapObjectGroup.prototype, 'reset').mockReturnThis();
        const manager = Object.assign(new MapObjectGroupManager(), {map: new AdminDungeonMap()});
        manager.map.register = vi.fn();
        group = new MapObjectGroup(manager, 'enemy');
        group.setMapObjectVisibility = vi.fn();
    });

    afterEach(() => {
        vi.restoreAllMocks();
        globalThis.getState = originalGetState;
    });

    it('dungeonDifficultyChanged_givenObjectsOfBothRaidSizes_letsEachObjectDecideItsVisibility', () => {
        // Arrange
        const onlyOn10Man = {shouldBeVisible: () => false};
        const onlyOn25Man = {shouldBeVisible: () => true};
        group.objects = {1: onlyOn10Man, 2: onlyOn25Man};

        // Act
        mapContext.signal('dungeondifficulty:changed', {dungeonDifficulty: 2});

        // Assert
        expect(group.setMapObjectVisibility.mock.calls).toEqual([
            [onlyOn10Man, false],
            [onlyOn25Man, true],
        ]);
    });
});

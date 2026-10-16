// Toggling teeming re-decides which map objects show: enemies that only exist with (or without)
// teeming hide or show, every other object keeps its own verdict.

const {Signalable} = require('../signalable');
globalThis.Signalable = Signalable;
globalThis.MapObjectGroupManager = class MapObjectGroupManager {
};
globalThis.AdminDungeonMap = class AdminDungeonMap {
};

const {MapObjectGroup} = require('./mapobjectgroup');

describe('MapObjectGroup teeming', () => {
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

    it('teemingChanged_givenObjectsWithAndWithoutTeeming_letsEachObjectDecideItsVisibility', () => {
        // Arrange
        const onlyWithTeeming = {shouldBeVisible: () => false};
        const alwaysThere = {shouldBeVisible: () => true};
        group.objects = {1: onlyWithTeeming, 2: alwaysThere};

        // Act
        mapContext.signal('teeming:changed', {teeming: false});

        // Assert
        expect(group.setMapObjectVisibility.mock.calls).toEqual([
            [onlyWithTeeming, false],
            [alwaysThere, true],
        ]);
    });
});

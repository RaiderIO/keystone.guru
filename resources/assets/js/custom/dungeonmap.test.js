// DungeonMap is a global-script class in the concatenated bundle; the only thing it needs at
// module-load time is its base class `Signalable` (for `extends`). Stubbing that lets us require the
// source. We exercise `_whenMapSized` on a bare prototype instance (Object.create) so none of the
// heavy constructor (Leaflet map creation, event wiring) has to run.

global.Signalable = class Signalable {
};
global.MAP_OBJECT_GROUP_KILLZONE = 'killzone';
global.MapContextLiveSession = class MapContextLiveSession {
};
global.EditKillZoneEnemySelection = {isEnemySelectable: vi.fn(() => true)};
global.getState = () => ({getMapContext: () => ({})});

const DungeonMap = require('./dungeonmap');

/**
 * Builds a minimal object that satisfies `_whenMapSized`: the `_mapSizedRafId` field, a fake
 * leafletMap, and a controllable requestAnimationFrame queue.
 * @param options {{mapSizeX?: Number, containerWidth?: Number}}
 */
function createGate({mapSizeX = 0, containerWidth = 0} = {}) {
    const size = {x: mapSizeX, y: 100};
    const container = {clientWidth: containerWidth};

    const map = Object.create(DungeonMap.prototype);
    map._mapSizedRafId = null;
    map.leafletMap = {
        getSize: () => size,
        getContainer: () => container,
        invalidateSize: vi.fn(),
    };

    const rafCallbacks = [];
    window.requestAnimationFrame = vi.fn((callback) => {
        rafCallbacks.push(callback);

        return rafCallbacks.length;
    });
    window.cancelAnimationFrame = vi.fn();

    return {map, size, container, rafCallbacks};
}

describe('DungeonMap._whenMapSized', () => {
    it('whenMapSized_givenSizedMap_runsCallbackSynchronously', () => {
        const {map} = createGate({mapSizeX: 500});
        const callback = vi.fn();

        map._whenMapSized(callback);

        expect(callback).toHaveBeenCalledTimes(1);
        expect(window.requestAnimationFrame).not.toHaveBeenCalled();
        expect(map.leafletMap.invalidateSize).not.toHaveBeenCalled();
    });

    it('whenMapSized_givenZeroSizeUntilLaidOut_invalidatesSizeThenRunsCallback', () => {
        const {map, container, rafCallbacks} = createGate({mapSizeX: 0, containerWidth: 0});
        const callback = vi.fn();

        map._whenMapSized(callback);

        // Still zero width: the first frame reschedules instead of running the callback.
        rafCallbacks.shift()();
        expect(callback).not.toHaveBeenCalled();

        // The container is laid out; the next frame invalidates the size and runs the callback.
        container.clientWidth = 500;
        rafCallbacks.shift()();

        expect(map.leafletMap.invalidateSize).toHaveBeenCalledTimes(1);
        expect(callback).toHaveBeenCalledTimes(1);
    });

    it('whenMapSized_givenPendingGate_cancelsPreviousFrame', () => {
        const {map} = createGate({mapSizeX: 0, containerWidth: 0});

        map._whenMapSized(vi.fn());
        map._whenMapSized(vi.fn());

        expect(window.cancelAnimationFrame).toHaveBeenCalledTimes(1);
    });
});

describe('DungeonMap._enemyClicked', () => {
    // Regression test (#4431): clicking an enemy on a page with no killzone map object group
    // (Explore mode, the heatmap, some admin tools - anywhere `hiddenMapObjectGroups` hides
    // 'killzone', so there is no such thing as a pull) used to still try to build a
    // KillZone/EditKillZoneEnemySelection pair and crashed. None of those pages can have pulls,
    // so the click should just do nothing.
    it('does nothing when the current page has no killzone map object group', () => {
        const map = Object.create(DungeonMap.prototype);
        map.mapObjectGroupManager = {getByName: () => false};
        map.getMapState = () => null;
        EditKillZoneEnemySelection.isEnemySelectable.mockClear();

        expect(() => map._enemyClicked({context: {}, data: {}})).not.toThrow();
        expect(EditKillZoneEnemySelection.isEnemySelectable).not.toHaveBeenCalled();
    });
});

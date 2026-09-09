// ---------------------------------------------------------------------------
// KillZonePathMapObjectGroup.refresh() rebuilds every pull-to-pull polyline. It runs on every
// killzone save:success, and saving one pull returns the paths of the whole route - so a route
// with 24 pulls tore down and re-added 23 polylines (each with fresh Leaflet handlers) for an
// identical result (#4590). These tests pin the equality short-circuit and the paths that must
// still rebuild unconditionally (floor change, path weight change, clearing all pulls).
// ---------------------------------------------------------------------------

globalThis.PolylineMapObjectGroup = class PolylineMapObjectGroup {
};
globalThis.MAP_OBJECT_GROUP_KILLZONE_PATH = 'killzonepath';
globalThis.LEAFLET_PANE_TOOLTIP = 'tooltipPane';
globalThis.LEAFLET_PANE_OVERLAY = 'overlayPane';
globalThis.pickHexFromHandlers = () => '#ffffff';
globalThis.c = {map: {killZonePath: {defaultHandlers: []}}};

const {KillZonePathMapObjectGroup} = require('./killzonepathmapobjectgroup');

const point = (floorId, lat, lng) => ({floor_id: floorId, lat: lat, lng: lng});
const segment = (floorId, offset) => [point(floorId, offset, offset), point(floorId, offset + 1, offset + 1)];

/**
 * A group wired up just enough to run refresh(): Object.create bypasses the MapObjectGroup
 * constructor chain, which wants a real map manager, and the layer-building collaborators are
 * replaced by spies so the test can count rebuilds.
 *
 * @param {Number} [currentFloorId]
 * @returns {KillZonePathMapObjectGroup}
 */
function buildGroup(currentFloorId = 1) {
    const group = Object.create(KillZonePathMapObjectGroup.prototype);

    group._killZonePaths = [];
    group._rendered = false;
    group.objects = [];
    group.currentId = 1;
    group.clear = vi.fn();
    group.createNewPath = vi.fn(() => ({}));
    group.setMapObjectVisibility = vi.fn();

    globalThis.getState = () => ({getCurrentFloor: () => ({id: currentFloorId})});

    return group;
}

describe('KillZonePathMapObjectGroup.refresh', () => {
    it('builds a polyline per segment on the first run', () => {
        const group = buildGroup();

        group.refresh([segment(1, 0), segment(1, 10)]);

        expect(group.createNewPath).toHaveBeenCalledTimes(2);
    });

    it('skips the rebuild when the incoming paths are identical to the rendered ones', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0), segment(1, 10)]);
        group.clear.mockClear();
        group.createNewPath.mockClear();

        group.refresh([segment(1, 0), segment(1, 10)]);

        expect(group.clear).not.toHaveBeenCalled();
        expect(group.createNewPath).not.toHaveBeenCalled();
    });

    it('rebuilds when a single point moved', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0)]);
        group.createNewPath.mockClear();

        group.refresh([[point(1, 0, 0), point(1, 5, 5)]]);

        expect(group.createNewPath).toHaveBeenCalledOnce();
        expect(group._killZonePaths).toEqual([[point(1, 0, 0), point(1, 5, 5)]]);
    });

    it('rebuilds when a segment was added', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0)]);
        group.createNewPath.mockClear();

        group.refresh([segment(1, 0), segment(1, 10)]);

        expect(group.createNewPath).toHaveBeenCalledTimes(2);
    });

    it('rebuilds when a segment gained a point', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0)]);
        group.clear.mockClear();

        group.refresh([[point(1, 0, 0), point(1, 1, 1), point(1, 2, 2)]]);

        expect(group.clear).toHaveBeenCalledOnce();
    });

    // Deleting all pulls calls refresh([]); the short-circuit must not swallow it.
    it('rebuilds when the paths are emptied', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0)]);
        group.clear.mockClear();
        group.createNewPath.mockClear();

        group.refresh([]);

        expect(group.clear).toHaveBeenCalledOnce();
        expect(group.createNewPath).not.toHaveBeenCalled();
    });

    // A floor change (update()) and a path weight change both call refresh() without paths.
    it('always rebuilds when called without paths', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0)]);
        group.createNewPath.mockClear();

        group.refresh();

        expect(group.createNewPath).toHaveBeenCalledOnce();
    });

    it('drops segments that have fewer than two points on the current floor', () => {
        const group = buildGroup(1);

        group.refresh([[point(1, 0, 0), point(2, 1, 1)], segment(1, 10)]);

        expect(group.createNewPath).toHaveBeenCalledOnce();
    });
});

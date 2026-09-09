// ---------------------------------------------------------------------------
// KillZonePathMapObjectGroup.refresh() draws the pull-to-pull polylines. It runs on every killzone
// save:success, and saving one pull returns the paths of the whole route - so a route with 24 pulls
// tore down and re-added every polyline (each with a fresh set of Leaflet handlers) when at most two
// segments had actually moved (#4590). These tests pin the per-segment diff and the paths that must
// still rebuild everything (floor change, path weight change, clearing all pulls).
// ---------------------------------------------------------------------------

globalThis.PolylineMapObjectGroup = class PolylineMapObjectGroup {
};
globalThis.MAP_OBJECT_GROUP_KILLZONE_PATH = 'killzonepath';
globalThis.LEAFLET_PANE_TOOLTIP = 'tooltipPane';
globalThis.LEAFLET_PANE_OVERLAY = 'overlayPane';
// The colour is a pure function of the segment's position in the route.
globalThis.pickHexFromHandlers = (handlers, progress) => `#${Math.round(progress)}`;
globalThis.c = {map: {killZonePath: {defaultHandlers: []}}};

const {KillZonePathMapObjectGroup} = require('./killzonepathmapobjectgroup');

const point = (floorId, lat, lng) => ({floor_id: floorId, lat: lat, lng: lng});
const segment = (floorId, offset) => [point(floorId, offset, offset), point(floorId, offset + 1, offset + 1)];

/**
 * A group wired up just enough to run refresh(): Object.create bypasses the MapObjectGroup
 * constructor chain, which wants a real map manager, and the layer-building collaborators are
 * replaced by spies so the test can count what was created and destroyed.
 *
 * @param {Number} [currentFloorId]
 * @returns {KillZonePathMapObjectGroup}
 */
function buildGroup(currentFloorId = 1) {
    const group = Object.create(KillZonePathMapObjectGroup.prototype);

    group._killZonePaths = [];
    group._renderedSegments = null;
    group.objects = [];
    group.currentId = 1;
    group.clear = vi.fn();
    group.createNewPath = vi.fn((points, options) => ({points: points, color: options.polyline.color, cleanup: vi.fn(), localDelete: vi.fn()}));
    group.setMapObjectVisibility = vi.fn();
    group.setLayerToMapObject = vi.fn();

    globalThis.getState = () => ({getCurrentFloor: () => ({id: currentFloorId})});

    return group;
}

/**
 * @param {KillZonePathMapObjectGroup} group
 */
function forgetCalls(group) {
    group.clear.mockClear();
    group.createNewPath.mockClear();
    group.setLayerToMapObject.mockClear();
}

describe('KillZonePathMapObjectGroup.refresh', () => {
    it('builds a polyline per drawable segment on the first run', () => {
        const group = buildGroup();

        group.refresh([segment(1, 0), segment(1, 10)]);

        expect(group.createNewPath).toHaveBeenCalledTimes(2);
        expect(group.setMapObjectVisibility).toHaveBeenCalledTimes(2);
    });

    it('drops segments that have fewer than two points on the current floor', () => {
        const group = buildGroup(1);

        group.refresh([[point(1, 0, 0), point(2, 1, 1)], segment(1, 10)]);

        expect(group.createNewPath).toHaveBeenCalledOnce();
    });

    // The regression this pins (#4590): every save rebuilt all 23 polylines of a 24-pull route.
    it('recreates nothing when the incoming paths are identical to the drawn ones', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0), segment(1, 10), segment(1, 20)]);
        forgetCalls(group);

        group.refresh([segment(1, 0), segment(1, 10), segment(1, 20)]);

        expect(group.clear).not.toHaveBeenCalled();
        expect(group.createNewPath).not.toHaveBeenCalled();
        expect(group.setLayerToMapObject).not.toHaveBeenCalled();
    });

    it('recreates only the segment whose points moved', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0), segment(1, 10), segment(1, 20)]);
        const untouched = group._renderedSegments[0].path;
        forgetCalls(group);

        group.refresh([segment(1, 0), [point(1, 10, 10), point(1, 99, 99)], segment(1, 20)]);

        expect(group.clear).not.toHaveBeenCalled();
        expect(group.createNewPath).toHaveBeenCalledOnce();
        expect(group.createNewPath.mock.calls[0][0]).toEqual([point(1, 10, 10), point(1, 99, 99)]);
        expect(group._renderedSegments[0].path).toBe(untouched);
    });

    it('destroys the path it replaces so it leaves no layer behind', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0)]);
        const replaced = group._renderedSegments[0].path;
        replaced.cleanup = vi.fn();
        replaced.localDelete = vi.fn();
        forgetCalls(group);

        group.refresh([[point(1, 0, 0), point(1, 99, 99)]]);

        expect(group.setLayerToMapObject).toHaveBeenCalledWith(null, replaced);
        expect(replaced.cleanup).toHaveBeenCalledOnce();
        expect(replaced.localDelete).toHaveBeenCalledOnce();
    });

    it('rebuilds everything when a segment was added, so the colour gradient is redistributed', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0), segment(1, 10)]);
        forgetCalls(group);

        group.refresh([segment(1, 0), segment(1, 10), segment(1, 20)]);

        expect(group.clear).toHaveBeenCalledOnce();
        expect(group.createNewPath).toHaveBeenCalledTimes(3);
    });

    // Deleting all pulls calls refresh([]); the diff must not swallow it.
    it('rebuilds when the paths are emptied', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0)]);
        forgetCalls(group);

        group.refresh([]);

        expect(group.clear).toHaveBeenCalledOnce();
        expect(group.createNewPath).not.toHaveBeenCalled();
        expect(group._renderedSegments).toEqual([]);
    });

    // A floor change (update()) and a path weight change both call refresh() without paths, and the
    // weight is read inside createNewPath() - so those must not take the diff path.
    it('always rebuilds every polyline when called without paths', () => {
        const group = buildGroup();
        group.refresh([segment(1, 0), segment(1, 10)]);
        forgetCalls(group);

        group.refresh();

        expect(group.clear).toHaveBeenCalledOnce();
        expect(group.createNewPath).toHaveBeenCalledTimes(2);
    });
});

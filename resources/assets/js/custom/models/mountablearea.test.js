// Follows the global-script recipe from killzone.test.js: stub the collaborators the class body
// touches at LOAD time, then require the source.

global.VersionableMapObject = class VersionableMapObject {
    rebindTooltip() {}

    loadRemoteMapObject() {}
};
global.Polyline = require('./polyline').Polyline;
global.HullPolyline = require('./hullpolyline').HullPolyline;
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};
global.MAP_OBJECT_GROUP_MOUNTABLE_AREA = 'mountablearea';

global.L = {
    Draw: {
        Polygon: {extend: (o) => o},
        Feature: {prototype: {initialize: () => {}}},
    },
};

global.c = {
    map: {
        mountablearea: {
            color: '#eb4934',
            margin: 2,
            arcSegments: () => 5,
            polygonOptions: {color: '#eb4934', weight: 1},
        },
    },
};

const {MountableArea} = require('./mountablearea');
const {fakeMapObjectGroupManager} = require('#test/fixtures/mapObjectGroupManager');

/**
 * Builds a mountable area on a bare prototype (Object.create), so none of the constructor's signal
 * wiring has to run.
 *
 * @param options {{inMappingEditor?: Boolean}}
 */
function createMountableArea({inMappingEditor = false} = {}) {
    const mountableAreaMapObjectGroup = {
        setLayerToMapObject: vi.fn(),
    };

    const mountableArea = Object.create(MountableArea.prototype);
    mountableArea.id = 7;
    mountableArea.layer = {
        toGeoJSON: () => ({geometry: {type: 'Polygon', coordinates: [[[10, -10], [20, -10], [20, -20], [10, -10]]]}}),
    };
    mountableArea.map = {
        mapObjectGroupManager: fakeMapObjectGroupManager((name) => (name === MAP_OBJECT_GROUP_MOUNTABLE_AREA ? mountableAreaMapObjectGroup : null)),
    };

    global.getState = () => ({
        getMapContext: () => (inMappingEditor ? new MapContextMappingVersionEdit() : {}),
    });

    return {mountableArea, mountableAreaMapObjectGroup};
}

describe('MountableArea hull', () => {
    it('getHullPoints_givenItsPolygon_returnsItsOwnVertices', () => {
        // Arrange
        const {mountableArea} = createMountableArea();

        // Act
        const points = mountableArea._getHullPoints();

        // Assert
        expect(points).toEqual([[-10, 10], [-10, 20], [-20, 20]]);
    });

    it('getHullSettings_givenMountableArea_returnsTheMountableAreaConstants', () => {
        // Arrange
        const {mountableArea, mountableAreaMapObjectGroup} = createMountableArea();

        // Act
        const margin = mountableArea._getHullMargin();
        const arcSegments = mountableArea._getHullArcSegments();
        const polygonOptions = mountableArea._getHullPolygonOptions();
        const group = mountableArea._getHullMapObjectGroup();

        // Assert
        expect(margin).toBe(c.map.mountablearea.margin);
        expect(arcSegments).toBe(c.map.mountablearea.arcSegments);
        expect(polygonOptions).toBe(c.map.mountablearea.polygonOptions);
        expect(group).toBe(mountableAreaMapObjectGroup);
    });
});

describe('MountableArea polyline seams', () => {
    it('isColorEditable_givenMountableArea_returnsFalse', () => {
        // Arrange
        const {mountableArea} = createMountableArea();

        // Act
        const isColorEditable = mountableArea._isColorEditable();

        // Assert
        expect(isColorEditable).toBe(false);
    });

    it('getPolylineColorDefault_givenMountableArea_returnsTheMountableAreaColor', () => {
        // Arrange
        const {mountableArea} = createMountableArea();

        // Act
        const color = mountableArea._getPolylineColorDefault();

        // Assert
        expect(color).toBe(c.map.mountablearea.color);
    });

    it('getPolylineWeightDefault_givenMountableArea_returnsTheHullPolygonWeight', () => {
        // Arrange
        const {mountableArea} = createMountableArea();

        // Act
        const weight = mountableArea._getPolylineWeightDefault();

        // Assert
        expect(weight).toBe(c.map.mountablearea.polygonOptions.weight);
    });
});

describe('MountableArea.loadRemoteMapObject', () => {
    it('loadRemoteMapObject_givenTheArea_buildsItsHull', () => {
        // Arrange
        const {mountableArea} = createMountableArea();
        mountableArea._updateHullLayer = vi.fn();

        // Act
        mountableArea.loadRemoteMapObject({id: 7, speed: null, polyline: {}});

        // Assert
        expect(mountableArea._updateHullLayer).toHaveBeenCalledTimes(1);
    });

    it('loadRemoteMapObject_givenTheNestedPolyline_leavesTheHullAlone', () => {
        // Arrange
        const {mountableArea} = createMountableArea();
        mountableArea._updateHullLayer = vi.fn();

        // Act
        mountableArea.loadRemoteMapObject({color: '#eb4934', vertices_json: '[]'}, {name: 'polyline', attributes: []});

        // Assert
        expect(mountableArea._updateHullLayer).not.toHaveBeenCalled();
    });

    it('loadRemoteMapObject_givenTheMappingEditor_keepsTheEditablePolygon', () => {
        // Arrange
        const {mountableArea} = createMountableArea({inMappingEditor: true});
        mountableArea._updateHullLayer = vi.fn();

        // Act
        mountableArea.loadRemoteMapObject({id: 7, speed: null, polyline: {}});

        // Assert
        expect(mountableArea._updateHullLayer).not.toHaveBeenCalled();
    });
});

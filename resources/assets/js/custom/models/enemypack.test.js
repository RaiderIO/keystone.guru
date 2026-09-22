// Follows the global-script recipe from killzone.test.js: stub the collaborators the class body
// touches at LOAD time, then require the source.

global.Polyline = class Polyline {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
    }

    rebindTooltip() {}

    loadRemoteMapObject() {}
};
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};
global.MAP_OBJECT_GROUP_ENEMY = 'enemy';
global.MAP_OBJECT_GROUP_ENEMY_PACK = 'enemypack';

global.L = {
    Draw: {
        Polygon: {extend: (o) => o},
        Feature: {prototype: {initialize: () => {}}},
    },
    polygon: () => ({on: () => {}}),
};

let lastOffsetPolygonMargin = null;
let lastOffsetPolygonPoints = null;
global.createOffsetHullPolygon = (points, margin) => {
    lastOffsetPolygonMargin = margin;
    lastOffsetPolygonPoints = points;

    return {on: () => {}};
};

global.c = {
    map: {
        enemypack: {
            margin: 2,
            arcSegments: () => 5,
            polygonOptions: {weight: 1},
        },
    },
};

const {EnemyPack} = require('./enemypack');
const {fakeMapObjectGroupManager} = require('#test/fixtures/mapObjectGroupManager');

/**
 * Builds an enemy pack on a bare prototype (Object.create), so none of the constructor's signal
 * wiring has to run, with just the collaborators `_updateHullLayer` reaches for.
 *
 * @param options {{floorId?: Number, floorEnemyPackMargin?: Number|null|undefined}}
 */
function createEnemyPack({floorId = 1, floorEnemyPackMargin = null} = {}) {
    const enemyMapObjectGroup = {
        findMapObjectById: (id) => ({
            id,
            layer: {getLatLng: () => ({lat: id, lng: id})},
            shouldBeVisible: () => true,
        }),
    };

    const enemyPackMapObjectGroup = {
        setLayerToMapObject: () => {},
    };

    const enemyPack = Object.create(EnemyPack.prototype);
    enemyPack.id = 99;
    enemyPack.floor_id = floorId;
    enemyPack.rawEnemies = [{id: 1}, {id: 2}];
    enemyPack.map = {
        mapObjectGroupManager: fakeMapObjectGroupManager((name) => (name === MAP_OBJECT_GROUP_ENEMY ? enemyMapObjectGroup : enemyPackMapObjectGroup)),
    };

    const floor = floorEnemyPackMargin === undefined ? false : {id: floorId, enemy_pack_margin: floorEnemyPackMargin};

    global.getState = () => ({
        getMapContext: () => ({
            getFloorById: () => floor,
        }),
    });

    return enemyPack;
}

describe('EnemyPack._updateHullLayer', () => {
    it('updateHullLayer_givenFloorWithMarginOverride_usesTheFloorMargin', () => {
        // Arrange
        const enemyPack = createEnemyPack({floorEnemyPackMargin: 1.5});

        // Act
        enemyPack._updateHullLayer();

        // Assert
        expect(lastOffsetPolygonMargin).toBe(1.5);
    });

    it('updateHullLayer_givenFloorWithoutMarginOverride_fallsBackToTheGlobalDefault', () => {
        // Arrange
        const enemyPack = createEnemyPack({floorEnemyPackMargin: null});

        // Act
        enemyPack._updateHullLayer();

        // Assert
        expect(lastOffsetPolygonMargin).toBe(c.map.enemypack.margin);
    });

    it('updateHullLayer_givenNoFloorFound_fallsBackToTheGlobalDefault', () => {
        // Arrange
        const enemyPack = createEnemyPack({floorEnemyPackMargin: undefined});

        // Act
        enemyPack._updateHullLayer();

        // Assert
        expect(lastOffsetPolygonMargin).toBe(c.map.enemypack.margin);
    });
});

describe('EnemyPack polyline seams', () => {
    it('updateHullLayer_givenVisibleEnemies_buildsTheHullAroundTheirPositions', () => {
        // Arrange
        const enemyPack = createEnemyPack();

        // Act
        enemyPack._updateHullLayer();

        // Assert
        expect(lastOffsetPolygonPoints).toEqual([[1, 1], [2, 2]]);
    });

    it('isWeightEditable_givenEnemyPack_returnsFalse', () => {
        // Arrange
        const enemyPack = createEnemyPack();

        // Act
        const isWeightEditable = enemyPack._isWeightEditable();

        // Assert
        expect(isWeightEditable).toBe(false);
    });

    it('isAnimatable_givenEnemyPack_returnsFalse', () => {
        // Arrange
        const enemyPack = createEnemyPack();

        // Act
        const isAnimatable = enemyPack._isAnimatable();

        // Assert
        expect(isAnimatable).toBe(false);
    });

    it('getPolylineWeightDefault_givenEnemyPack_returnsTheHullPolygonWeight', () => {
        // Arrange
        const enemyPack = createEnemyPack();

        // Act
        const weight = enemyPack._getPolylineWeightDefault();

        // Assert
        expect(weight).toBe(c.map.enemypack.polygonOptions.weight);
    });
});

describe('EnemyPack.loadRemoteMapObject', () => {
    it('loadRemoteMapObject_givenThePack_buildsTheHullFromItsEnemies', () => {
        // Arrange
        const enemyPack = createEnemyPack();
        enemyPack.setRawEnemies = vi.fn();
        enemyPack._updateHullLayer = vi.fn();
        const enemies = [{id: 1}];

        // Act
        enemyPack.loadRemoteMapObject({id: 99, enemies: enemies, polyline: {}});

        // Assert
        expect(enemyPack.setRawEnemies).toHaveBeenCalledWith(enemies);
        expect(enemyPack._updateHullLayer).toHaveBeenCalledTimes(1);
    });

    it('loadRemoteMapObject_givenTheNestedPolyline_leavesTheEnemiesAndHullAlone', () => {
        // Arrange
        const enemyPack = createEnemyPack();
        enemyPack.setRawEnemies = vi.fn();
        enemyPack._updateHullLayer = vi.fn();

        // Act
        enemyPack.loadRemoteMapObject({color: '#5993D2', vertices_json: '[]'}, {name: 'polyline', attributes: []});

        // Assert
        expect(enemyPack.setRawEnemies).not.toHaveBeenCalled();
        expect(enemyPack._updateHullLayer).not.toHaveBeenCalled();
    });
});

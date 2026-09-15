// Follows the global-script recipe from killzone.test.js: stub the collaborators the class body
// touches at LOAD time, then require the source.

global.VersionableMapObject = class VersionableMapObject {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
    }

    rebindTooltip() {}
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

global.hull = () => [[0, 0], [1, 1], [2, 2]];

let lastOffsetPolygonMargin = null;
global.createOffsetPolygon = (vertices, margin) => {
    lastOffsetPolygonMargin = margin;

    return [{lat: 0, lng: 0}];
};

global.c = {
    map: {
        enemypack: {
            margin: 2,
            arcSegments: () => 5,
            polygonOptions: {},
        },
    },
};

const {EnemyPack} = require('./enemypack');

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
        mapObjectGroupManager: {
            getByName: (name) => (name === MAP_OBJECT_GROUP_ENEMY ? enemyMapObjectGroup : enemyPackMapObjectGroup),
        },
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

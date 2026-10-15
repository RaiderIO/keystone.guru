// Follows the global-script recipe from killzone.test.js: stub the collaborators the class body
// touches at LOAD time, then require the source.

global.Polyline = class Polyline {
    constructor(map, layer) {
        this.map = map;
        this.layer = layer;
    }

    rebindTooltip() {}

    bindTooltip() {}

    loadRemoteMapObject() {}
};
global.HullPolyline = require('./hullpolyline').HullPolyline;
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
global.hull = (points) => {
    lastOffsetPolygonPoints = points;

    return points;
};
global.createOffsetPolygon = (vertices, margin) => {
    lastOffsetPolygonMargin = margin;

    return vertices.map(vertex => [vertex.lat, vertex.lng]);
};

global.getFormattedPercentage = require('../util').getFormattedPercentage;

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
function createEnemyPack({floorId = 1, floorEnemyPackMargin = null, enemiesById = null, inMappingEditor = false} = {}) {
    const enemyMapObjectGroup = {
        findMapObjectById: (id) => (enemiesById === null ? makeFakeEnemy(id) : enemiesById[id] ?? null),
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

    const mapContext = inMappingEditor ? new MapContextMappingVersionEdit() : {};
    mapContext.getFloorById = () => floor;

    global.getState = () => ({
        getMapContext: () => mapContext,
    });

    return enemyPack;
}

/**
 * An enemy of the pack, positioned at [id, id].
 *
 * @param {Number} id
 * @param {Object} overrides
 * @returns {Object}
 */
function makeFakeEnemy(id, overrides = {}) {
    return {
        id,
        layer: {getLatLng: () => ({lat: id, lng: id})},
        shouldBeVisible: () => true,
        getEnemyForces: () => 10,
        register: vi.fn(),
        bindTooltip: vi.fn(),
        ...overrides,
    };
}

beforeEach(() => {
    lastOffsetPolygonMargin = null;
    lastOffsetPolygonPoints = null;
});

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

    it('getHullPoints_givenHiddenMissingAndLayerlessEnemies_returnsOnlyTheVisibleOnes', () => {
        // Arrange
        const enemyPack = createEnemyPack({
            enemiesById: {
                1: makeFakeEnemy(1),
                2: makeFakeEnemy(2, {shouldBeVisible: () => false}),
                4: makeFakeEnemy(4, {layer: null}),
                5: makeFakeEnemy(5),
            },
        });
        enemyPack.rawEnemies = [{id: 1}, {id: 2}, {id: 3}, {id: 4}, {id: 5}];

        let points = null;

        // Act + Assert
        expect(() => (points = enemyPack._getHullPoints())).not.toThrow();
        expect(points).toEqual([[1, 1], [5, 5]]);
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

    it('loadRemoteMapObject_givenTheMappingEditor_keepsTheEditablePolygon', () => {
        // Arrange
        const enemyPack = createEnemyPack({inMappingEditor: true});
        enemyPack.setRawEnemies = vi.fn();
        enemyPack._updateHullLayer = vi.fn();

        // Act
        enemyPack.loadRemoteMapObject({id: 99, enemies: [{id: 1}], polyline: {}});

        // Assert
        expect(enemyPack.setRawEnemies).not.toHaveBeenCalled();
        expect(enemyPack._updateHullLayer).not.toHaveBeenCalled();
    });
});

describe('EnemyPack.setRawEnemies', () => {
    it('setRawEnemies_givenEnemiesOfThePack_listensToTheirVisibilityAndRebindsTheirTooltips', () => {
        // Arrange
        const enemy = makeFakeEnemy(1);
        const enemyPack = createEnemyPack({enemiesById: {1: enemy}});
        enemyPack._updateHullLayer = vi.fn();
        const consoleWarn = vi.spyOn(console, 'warn').mockImplementation(() => {});

        // Act
        enemyPack.setRawEnemies([{id: 1}, {id: 2}]);

        // Assert
        expect(enemyPack.rawEnemies).toEqual([{id: 1}, {id: 2}]);
        expect(enemy.register).toHaveBeenCalledWith(['shown', 'hidden'], enemyPack, expect.any(Function));
        expect(enemy.bindTooltip).toHaveBeenCalledTimes(1);
        expect(consoleWarn).toHaveBeenCalledWith('Unable to find enemy with id 2 for enemy pack 99');

        enemy.register.mock.calls[0][2]({});
        expect(enemyPack._updateHullLayer).toHaveBeenCalledTimes(1);
    });
});

describe('EnemyPack._createHullLayer', () => {
    it('createHullLayer_givenAHull_signalsEnemyPackClickedWhenItIsClicked', () => {
        // Arrange
        const enemyPack = createEnemyPack();
        enemyPack.signal = vi.fn();
        const handlers = {};
        vi.spyOn(global.L, 'polygon').mockImplementation(() => ({on: (event, handler) => (handlers[event] = handler)}));
        const clickEvent = {latlng: {lat: 1, lng: 1}};

        // Act
        const result = enemyPack._createHullLayer([[1, 1], [2, 2]]);
        handlers.click(clickEvent);

        // Assert
        expect(result).not.toBeNull();
        expect(enemyPack.signal).toHaveBeenCalledWith('enemypack:clicked', {clickEvent: clickEvent});
    });

    it('createHullLayer_givenTooFewPoints_returnsNull', () => {
        // Arrange
        const enemyPack = createEnemyPack();

        // Act + Assert
        expect(() => enemyPack._createHullLayer([[1, 1]])).not.toThrow();
        expect(enemyPack._createHullLayer([[1, 1]])).toBeNull();
    });
});

describe('EnemyPack.bindTooltip', () => {
    /**
     * @param {Number|null} group
     * @returns {EnemyPack}
     */
    function createTooltipEnemyPack(group) {
        const enemyPack = createEnemyPack();
        enemyPack.group = group;
        enemyPack.layer = {bindTooltip: vi.fn()};
        enemyPack.map.enemyForcesManager = {getEnemyForcesRequired: () => 200};

        return enemyPack;
    }

    it('bindTooltip_givenAGroup_prefixesTheGroupToTheEnemyForces', () => {
        // Arrange
        const enemyPack = createTooltipEnemyPack(3);

        // Act
        enemyPack.bindTooltip();

        // Assert
        expect(enemyPack.layer.bindTooltip).toHaveBeenCalledWith('G3: +20 / +10%', {sticky: true, direction: 'top'});
    });

    it('bindTooltip_givenNoGroup_showsOnlyTheEnemyForces', () => {
        // Arrange
        const enemyPack = createTooltipEnemyPack(null);

        // Act
        enemyPack.bindTooltip();

        // Assert
        expect(enemyPack.layer.bindTooltip).toHaveBeenCalledWith('+20 / +10%', {sticky: true, direction: 'top'});
    });
});

describe('EnemyPack.getEnemyForces', () => {
    it('getEnemyForces_givenAPackEnemyThatCannotBeFound_skipsIt', () => {
        // Arrange
        const enemyPack = createEnemyPack();
        const enemiesById = {1: {id: 1, getEnemyForces: () => 5}};
        enemyPack.map.mapObjectGroupManager = fakeMapObjectGroupManager((name) => (name === MAP_OBJECT_GROUP_ENEMY ? {
            findMapObjectById: (id) => enemiesById[id] ?? null,
        } : null));

        // Act
        const getEnemyForces = () => enemyPack.getEnemyForces();

        // Assert
        expect(getEnemyForces).not.toThrow();
        expect(getEnemyForces()).toBe(5);
    });
});

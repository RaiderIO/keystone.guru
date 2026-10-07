/**
 * Leaflet is not loaded in the test runner: these stand-ins carry just enough of L.LayerGroup for
 * EnemyCanvasLayerGroup's own bookkeeping, and a map that records which layers are on it.
 */
function makeExtendable(base = function () {
}) {
    base.extend = (properties) => {
        const Extended = function (...args) {
            if (typeof this.initialize === 'function') {
                this.initialize(...args);
            }
        };
        Extended.prototype = Object.assign(Object.create(base.prototype), properties);

        return Extended;
    };

    return base;
}

const LayerGroup = makeExtendable();
LayerGroup.prototype.initialize = function () {
    this._layers = {};
};
LayerGroup.prototype.getLayerId = (layer) => layer._leaflet_id;
LayerGroup.prototype.hasLayer = function (layer) {
    return this.getLayerId(layer) in this._layers;
};

class Marker {
    constructor(id) {
        this._leaflet_id = id;
    }
}

global.L = {
    CircleMarker: makeExtendable(),
    Canvas: makeExtendable(),
    LayerGroup: LayerGroup,
    Marker: Marker,
    point: (x, y) => ({x, y}),
};
global.EnemyCanvasSpriteCache = require('./enemycanvasspritecache').EnemyCanvasSpriteCache;

const {EnemyCanvasLayerGroup} = require('./enemypath');

function makeMap() {
    const layers = new Set();

    return {
        layers,
        addLayer: (layer) => layers.add(layer),
        removeLayer: (layer) => layers.delete(layer),
        hasLayer: (layer) => layers.has(layer),
    };
}

/**
 * A group on a map holding one marker, drawn by its path.
 */
function makeGroupWithMarker() {
    const marker = new Marker(1);
    const path = {name: 'path'};
    const map = makeMap();
    const group = new EnemyCanvasLayerGroup([], {resolvePath: () => path});
    group._map = map;
    group.addLayer(marker);

    return {group, marker, path, map};
}

describe('EnemyCanvasLayerGroup DOM promotion', () => {
    test('promote_givenMarkerDrawnByItsPath_putsTheMarkerOnTheMapInsteadOfThePath', () => {
        // Arrange
        const {group, marker, path, map} = makeGroupWithMarker();

        // Act
        const promoted = group.promote(marker);

        // Assert
        expect(promoted).toBe(true);
        expect(map.hasLayer(marker)).toBe(true);
        expect(map.hasLayer(path)).toBe(false);
        expect(group.isPromoted(marker)).toBe(true);
    });

    test('promote_givenMarkerNotInTheGroup_returnsFalse', () => {
        // Arrange
        const {group, map} = makeGroupWithMarker();
        const hiddenMarker = new Marker(2);

        // Act
        const promoted = group.promote(hiddenMarker);

        // Assert
        expect(promoted).toBe(false);
        expect(map.hasLayer(hiddenMarker)).toBe(false);
    });

    test('demote_givenPromotedMarker_putsThePathBackOnTheMap', () => {
        // Arrange
        const {group, marker, path, map} = makeGroupWithMarker();
        group.promote(marker);

        // Act
        const demoted = group.demote(marker);

        // Assert
        expect(demoted).toBe(true);
        expect(map.hasLayer(marker)).toBe(false);
        expect(map.hasLayer(path)).toBe(true);
        expect(group.isPromoted(marker)).toBe(false);
    });

    test('removeLayer_givenPromotedMarker_removesTheMarkerAndForgetsThePromotion', () => {
        // Arrange
        const {group, marker, path, map} = makeGroupWithMarker();
        group.promote(marker);

        // Act
        group.removeLayer(marker);
        group.addLayer(marker);

        // Assert
        expect(map.hasLayer(marker)).toBe(false);
        expect(map.hasLayer(path)).toBe(true);
        expect(group.demote(marker)).toBe(false);
    });
});

/**
 * Leaflet is not loaded in the test runner; EnemyPath only needs extend() to build its class, and
 * its drawing is tested by calling _updatePath() on an instance wired to a recording context.
 */
function makeExtendable() {
    const Base = function () {
    };
    Base.extend = (properties) => {
        const Extended = function () {
        };
        Extended.prototype = Object.assign(Object.create(Base.prototype), properties);

        return Extended;
    };

    return Base;
}

global.L = {CircleMarker: makeExtendable(), LayerGroup: makeExtendable(), Marker: class Marker {
}};
global.EnemyCanvasSpriteCache = require('./enemycanvasspritecache').EnemyCanvasSpriteCache;

const {EnemyPath, EnemyCanvasLayerGroup} = require('./enemypath');

function makeRecordingContext() {
    const calls = [];
    const ctx = {
        calls,
        beginPath: () => calls.push(['beginPath']),
        arc: (...args) => calls.push(['arc', ...args]),
        fill: () => calls.push(['fill', ctx.fillStyle, ctx.globalAlpha]),
        stroke: () => calls.push(['stroke', ctx.strokeStyle, ctx.lineWidth]),
        drawImage: (image, ...args) => calls.push(['drawImage', image.name, ...args]),
        moveTo: () => {
        },
        arcTo: () => {
        },
        closePath: () => {
        },
        setLineDash: (pattern) => calls.push(['setLineDash', pattern]),
        fillStyle: null,
        strokeStyle: null,
        lineWidth: 1,
        globalAlpha: 1,
    };

    return ctx;
}

function makeAppearance(overrides = {}) {
    return {
        outerDiameter: 40,
        borderWidth: 1,
        borderColor: 'black',
        innerMargin: 5,
        outerBackgroundColor: 'rgb(220, 60, 60)',
        opacity: 0.5,
        sprite: {backgroundColors: ['white'], imageUrl: null, imageFit: 'cover', blendMode: 'source-over'},
        ...overrides,
    };
}

function makePath(appearance, badgeCanvas = {name: 'badge'}) {
    const ctx = makeRecordingContext();
    const path = new EnemyPath();
    path.options = {
        spriteCache: {
            get: (sprite, size) => ({name: `sprite ${size}`}),
            getBadge: () => badgeCanvas,
        },
    };
    path._renderer = {_drawing: true, _ctx: ctx};
    path._empty = () => false;
    path._point = {x: 100, y: 200};
    path._appearance = appearance;

    return {path, ctx};
}

test('_updatePath_givenAggressivenessRing_fillsRingAroundSpriteOnlyAndDrawsSpriteOnce', () => {
    // Arrange
    const {path, ctx} = makePath(makeAppearance());

    // Act
    path._updatePath();

    // Assert: 40px outer, 1px border and a 5px ring leave a 28px sprite
    expect(ctx.calls).toContainEqual(['arc', 100, 200, 19, 0, Math.PI * 2]);
    expect(ctx.calls).toContainEqual(['arc', 100, 200, 14, 0, Math.PI * 2, true]);
    expect(ctx.calls).toContainEqual(['fill', 'rgb(220, 60, 60)', 0.5]);
    expect(ctx.calls).toContainEqual(['drawImage', 'sprite 28', 86, 186, 28, 28]);
    expect(ctx.calls.filter(call => call[0] === 'drawImage')).toHaveLength(1);
    expect(ctx.calls).toContainEqual(['arc', 100, 200, 19.5, 0, Math.PI * 2]);
    expect(ctx.calls).toContainEqual(['stroke', 'black', 1]);
    expect(ctx.globalAlpha).toBe(1);
});

test('_updatePath_givenNoOuterBackground_drawsNoRing', () => {
    // Arrange
    const {path, ctx} = makePath(makeAppearance({outerBackgroundColor: null}));

    // Act
    path._updatePath();

    // Assert
    expect(ctx.calls.filter(call => call[0] === 'fill')).toEqual([]);
    expect(ctx.calls).toContainEqual(['drawImage', 'sprite 28', 86, 186, 28, 28]);
});

test('_updatePath_givenNoInnerMargin_drawsNoRingBehindTheSprite', () => {
    // Arrange
    const {path, ctx} = makePath(makeAppearance({innerMargin: 0}));

    // Act
    path._updatePath();

    // Assert: the sprite fills everything inside the border
    expect(ctx.calls.filter(call => call[0] === 'fill')).toEqual([]);
    expect(ctx.calls).toContainEqual(['drawImage', 'sprite 38', 81, 181, 38, 38]);
});

test('_updatePath_givenSpriteStillLoading_fillsItsFirstBackgroundColourInstead', () => {
    // Arrange
    const {path, ctx} = makePath(makeAppearance({outerBackgroundColor: null}));
    path.options.spriteCache.get = () => null;

    // Act
    path._updatePath();

    // Assert
    expect(ctx.calls).toContainEqual(['arc', 100, 200, 14, 0, Math.PI * 2]);
    expect(ctx.calls).toContainEqual(['fill', 'white', 0.5]);
    expect(ctx.calls.filter(call => call[0] === 'drawImage')).toEqual([]);
});

test('_updatePath_givenSpriteStillLoadingWithoutBackgroundColour_drawsNoSprite', () => {
    // Arrange
    const sprite = {backgroundColors: [null], imageUrl: null, imageFit: 'cover', blendMode: 'source-over'};
    const {path, ctx} = makePath(makeAppearance({outerBackgroundColor: null, sprite}));
    path.options.spriteCache.get = () => null;

    // Act
    path._updatePath();

    // Assert: only the border
    expect(ctx.calls.filter(call => call[0] === 'fill')).toEqual([]);
    expect(ctx.calls).toContainEqual(['stroke', 'black', 1]);
});

test('_updatePath_givenNoBorder_strokesNothing', () => {
    // Arrange
    const {path, ctx} = makePath(makeAppearance({borderWidth: 0}));

    // Act
    path._updatePath();

    // Assert
    expect(ctx.calls.filter(call => call[0] === 'stroke')).toEqual([]);
    expect(ctx.calls).toContainEqual(['drawImage', 'sprite 30', 85, 185, 30, 30]);
});

test('_updatePath_givenRendererNotDrawing_drawsNothing', () => {
    // Arrange
    const {path, ctx} = makePath(makeAppearance());
    path._renderer._drawing = false;

    // Act
    path._updatePath();

    // Assert
    expect(ctx.calls).toHaveLength(0);
});

test('_updatePath_givenBadges_drawsThemFromOuterCircleCornerAfterBorder', () => {
    // Arrange
    const box = {width: 16, height: 16};
    const {path, ctx} = makePath(makeAppearance({badges: [{box, left: -8, top: 24}]}));

    // Act
    path._updatePath();

    // Assert
    const names = ctx.calls.map(call => call[0] === 'drawImage' ? call[1] : call[0]);
    expect(ctx.calls).toContainEqual(['drawImage', 'badge', 72, 204, 16, 16]);
    expect(names.indexOf('badge')).toBeGreaterThan(names.lastIndexOf('stroke'));
});

test('_updatePath_givenBadgeStillLoading_skipsIt', () => {
    // Arrange
    const {path, ctx} = makePath(makeAppearance({badges: [{box: {width: 16, height: 16}, left: 0, top: 0}]}), null);

    // Act
    path._updatePath();

    // Assert
    expect(ctx.calls.filter(call => call[0] === 'drawImage')).toHaveLength(1);
});

test('_updatePath_givenDashedSelection_strokesDashedHaloAndResetsDash', () => {
    // Arrange
    const selection = {width: 48, height: 48, borderWidth: 4, borderColor: 'yellow', borderDashed: true, borderRadius: 4};
    const {path, ctx} = makePath(makeAppearance({selection}));

    // Act
    path._updatePath();

    // Assert
    expect(ctx.calls).toContainEqual(['stroke', 'yellow', 4]);
    const dashes = ctx.calls.filter(call => call[0] === 'setLineDash');
    expect(dashes[0][1]).toHaveLength(2);
    expect(dashes[dashes.length - 1][1]).toEqual([]);
});

test('_updatePath_givenNoAppearance_drawsNothing', () => {
    // Arrange
    const {path, ctx} = makePath(null);

    // Act
    path._updatePath();

    // Assert
    expect(ctx.calls).toHaveLength(0);
});

test('getDrawnRadius_givenNothingOutside_returnsOuterRadius', () => {
    expect(EnemyPath.getDrawnRadius(makeAppearance())).toBe(20);
});

test('getDrawnRadius_givenBadgeHangingOverEdge_coversBadgeCorners', () => {
    // Arrange
    const appearance = makeAppearance({badges: [{box: {width: 16, height: 16}, left: -8, top: 32}]});

    // Act
    const radius = EnemyPath.getDrawnRadius(appearance);

    // Assert: the badge's far corner is at (-8, 48) from the top left, (-28, 28) from the centre
    expect(radius).toBeCloseTo(Math.hypot(28, 28), 6);
});

test('getDrawnRadius_givenSelection_coversHaloCorners', () => {
    // Arrange
    const appearance = makeAppearance({selection: {width: 48, height: 48}});

    // Act
    const radius = EnemyPath.getDrawnRadius(appearance);

    // Assert
    expect(radius).toBeCloseTo(Math.hypot(24, 24), 6);
});

describe('EnemyPath.setAppearance', () => {
    function makeSizedPath(radius) {
        const path = new EnemyPath();
        path._radius = radius;
        path.setRadius = vi.fn();
        path.redraw = vi.fn();

        return path;
    }

    test('setAppearance_givenAppearanceReachingFurther_growsTheRadius', () => {
        // Arrange
        const path = makeSizedPath(20);
        const appearance = makeAppearance({selection: {width: 48, height: 48}});

        // Act
        path.setAppearance(appearance);

        // Assert
        expect(path._appearance).toBe(appearance);
        expect(path.setRadius).toHaveBeenCalledWith(Math.hypot(24, 24));
        expect(path.redraw).not.toHaveBeenCalled();
    });

    test('setAppearance_givenAppearanceOfTheSameReach_onlyRedraws', () => {
        // Arrange
        const path = makeSizedPath(20);
        const appearance = makeAppearance();

        // Act
        path.setAppearance(appearance);

        // Assert
        expect(path._appearance).toBe(appearance);
        expect(path.redraw).toHaveBeenCalledTimes(1);
        expect(path.setRadius).not.toHaveBeenCalled();
    });
});

describe('EnemyPath._project', () => {
    beforeEach(() => {
        L.CircleMarker.prototype._project = function () {
            this._point = {x: 100, y: 200};
        };
    });

    afterEach(() => {
        delete L.CircleMarker.prototype._project;
    });

    test('_project_givenProjectedCallbackAndAppearance_reportsPointAndOuterRadius', () => {
        // Arrange
        const path = new EnemyPath();
        path._radius = 34;
        path._appearance = makeAppearance();
        const projected = [];
        path.setProjectedCallback((point, radius) => projected.push([point, radius]));

        // Act
        path._project();

        // Assert: the enemy's own radius, not the larger one its badges and halo are drawn with
        expect(projected).toEqual([[{x: 100, y: 200}, 20]]);
    });

    test('_project_givenProjectedCallbackWithoutAppearance_reportsPathRadius', () => {
        // Arrange
        const path = new EnemyPath();
        path._radius = 34;
        path._appearance = null;
        const projected = [];
        path.setProjectedCallback((point, radius) => projected.push([point, radius]));

        // Act
        path._project();

        // Assert
        expect(projected).toEqual([[{x: 100, y: 200}, 34]]);
    });
});

describe('EnemyCanvasLayerGroup', () => {
    // Stand-ins for the bookkeeping EnemyCanvasLayerGroup inherits from L.LayerGroup.
    beforeEach(() => {
        L.LayerGroup.prototype.initialize = function (layers) {
            this._layers = {};
            for (const layer of layers) {
                this.addLayer(layer);
            }
        };
        L.LayerGroup.prototype.getLayerId = (layer) => layer.id;
        L.LayerGroup.prototype.eachLayer = function (callback, context) {
            for (const id in this._layers) {
                callback.call(context, this._layers[id]);
            }
        };
    });

    afterEach(() => {
        delete L.LayerGroup.prototype.initialize;
        delete L.LayerGroup.prototype.getLayerId;
        delete L.LayerGroup.prototype.eachLayer;
    });

    function makeMarker(id) {
        return Object.assign(new L.Marker(), {id});
    }

    function makeGroup(layers = []) {
        const group = new EnemyCanvasLayerGroup();
        group.initialize(layers, {resolvePath: (marker) => ({path: marker.id})});
        const map = {added: [], removed: [], addLayer: (layer) => map.added.push(layer), removeLayer: (layer) => map.removed.push(layer)};

        return {group, map};
    }

    test('addLayer_givenMarkerWhileOnTheMap_putsItsPathOnTheMapInstead', () => {
        // Arrange
        const marker = makeMarker(1);
        const {group, map} = makeGroup();
        group._map = map;

        // Act
        group.addLayer(marker);

        // Assert
        expect(group._layers[1]).toBe(marker);
        expect(map.added).toEqual([{path: 1}]);
    });

    test('addLayer_givenOtherLayerWhileOnTheMap_putsTheLayerItselfOnTheMap', () => {
        // Arrange
        const polyline = {id: 2};
        const {group, map} = makeGroup();
        group._map = map;

        // Act
        group.addLayer(polyline);

        // Assert
        expect(map.added).toEqual([polyline]);
    });

    test('removeLayer_givenMarkerWhileOnTheMap_takesItsPathOffTheMap', () => {
        // Arrange
        const marker = makeMarker(1);
        const {group, map} = makeGroup([marker]);
        group._map = map;

        // Act
        group.removeLayer(marker);

        // Assert
        expect(group._layers[1]).toBeUndefined();
        expect(map.removed).toEqual([{path: 1}]);
    });

    test('removeLayer_givenLayerId_takesThatMarkersPathOffTheMap', () => {
        // Arrange
        const marker = makeMarker(1);
        const {group, map} = makeGroup([marker]);
        group._map = map;

        // Act
        group.removeLayer(1);

        // Assert
        expect(group._layers[1]).toBeUndefined();
        expect(map.removed).toEqual([{path: 1}]);
    });

    test('onAddAndOnRemove_givenMarkers_moveEveryPathOnAndOffTheMap', () => {
        // Arrange
        const first = makeMarker(1);
        const second = makeMarker(2);
        const {group, map} = makeGroup([first, second]);

        // Act
        group.onAdd(map);
        group.onRemove(map);

        // Assert
        expect(map.added).toEqual([{path: 1}, {path: 2}]);
        expect(map.removed).toEqual([{path: 1}, {path: 2}]);
    });
});

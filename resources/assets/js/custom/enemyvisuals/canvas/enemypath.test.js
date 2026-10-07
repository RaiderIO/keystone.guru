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

global.L = {
    CircleMarker: makeExtendable(),
    LayerGroup: makeExtendable(),
    Canvas: makeExtendable(),
    point: (x, y) => ({x, y}),
};
global.EnemyCanvasSpriteCache = require('./enemycanvasspritecache').EnemyCanvasSpriteCache;

const {EnemyPath, EnemyCanvasRenderer} = require('./enemypath');

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
    expect(ctx.globalAlpha).toBe(1);
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

describe('EnemyPath#_containsPoint', () => {
    test('_containsPoint_givenAPointInsideTheOuterCircle_returnsTrue', () => {
        // Arrange: centre (100, 200), outer radius 20
        const {path} = makePath(makeAppearance());

        // Act
        const result = path._containsPoint({x: 114, y: 214});

        // Assert
        expect(result).toBe(true);
    });

    test('_containsPoint_givenAPointOnTheBorder_returnsTrue', () => {
        // Arrange
        const {path} = makePath(makeAppearance());

        // Act
        const result = path._containsPoint({x: 120, y: 200});

        // Assert
        expect(result).toBe(true);
    });

    test('_containsPoint_givenAPointInTheIconBoxCornerOutsideTheCircle_returnsFalse', () => {
        // Arrange: (115, 215) is inside the 40px box but 21.2px from the centre
        const {path} = makePath(makeAppearance());

        // Act
        const result = path._containsPoint({x: 115, y: 215});

        // Assert
        expect(result).toBe(false);
    });

    test('_containsPoint_givenAPointOnABadgeOutsideTheCircle_returnsTrue', () => {
        // Arrange: a 16px badge from (-8, 32) off the top left corner covers x 72-88, y 212-228
        const {path} = makePath(makeAppearance({badges: [{box: {width: 16, height: 16}, left: -8, top: 32}]}));

        // Act
        const result = path._containsPoint({x: 74, y: 226});

        // Assert
        expect(result).toBe(true);
    });

    test('_containsPoint_givenAPointJustPastABadge_returnsFalse', () => {
        // Arrange
        const {path} = makePath(makeAppearance({badges: [{box: {width: 16, height: 16}, left: -8, top: 32}]}));

        // Act
        const result = path._containsPoint({x: 71, y: 226});

        // Assert
        expect(result).toBe(false);
    });

    test('_containsPoint_givenAPointInsideTheSelectionHaloOnly_returnsFalse', () => {
        // Arrange: the halo reaches 24px out, the enemy 20px
        const {path} = makePath(makeAppearance({selection: {width: 48, height: 48, borderWidth: 2, borderColor: 'red'}}));

        // Act
        const result = path._containsPoint({x: 122, y: 200});

        // Assert
        expect(result).toBe(false);
    });

    test('_containsPoint_givenNoAppearanceYet_returnsFalse', () => {
        // Arrange
        const {path} = makePath(null);

        // Act
        const result = path._containsPoint({x: 100, y: 200});

        // Assert
        expect(result).toBe(false);
    });
});

describe('EnemyPath overlay anchors', () => {
    test('_getTooltipAnchor_givenAnAppearance_returnsTheTopOfTheOuterCircle', () => {
        // Arrange: EnemyVisual anchors the DOM tooltip at [0, -(height / 2) - margin]
        const {path} = makePath(makeAppearance({outerDiameter: 46}));

        // Act
        const anchor = path._getTooltipAnchor();

        // Assert
        expect(anchor).toEqual({x: 0, y: -23});
    });

    test('_getPopupAnchor_givenAnAppearance_returnsTheTopOfTheOuterCircle', () => {
        // Arrange
        const {path} = makePath(makeAppearance({outerDiameter: 46}));

        // Act
        const anchor = path._getPopupAnchor();

        // Assert
        expect(anchor).toEqual({x: 0, y: -23});
    });

    test('_getTooltipAnchor_givenNoAppearanceYet_returnsTheCentre', () => {
        // Arrange
        const {path} = makePath(null);

        // Act
        const anchor = path._getTooltipAnchor();

        // Assert
        expect(anchor).toEqual({x: 0, y: 0});
    });
});

describe('EnemyPath#fire', () => {
    function makeFiringPath() {
        const {path} = makePath(makeAppearance());
        path.getLatLng = () => ({lat: -100, lng: 150});
        path._map = {
            latLngToLayerPoint: () => ({x: 100, y: 200}),
            layerPointToContainerPoint: () => ({x: 300, y: 400}),
        };
        const fired = [];
        L.CircleMarker.prototype.fire = function (type, data) {
            fired.push([type, data]);

            return this;
        };

        return {path, fired};
    }

    test('fire_givenAMouseEventAtTheMousePosition_movesItToTheEnemyLikeAMarker', () => {
        // Arrange
        const {path, fired} = makeFiringPath();
        const data = {originalEvent: {}, latlng: {lat: -90, lng: 160}, layerPoint: {x: 110, y: 190}, containerPoint: {x: 310, y: 390}};

        // Act
        path.fire('click', data, true);

        // Assert
        expect(fired).toEqual([['click', {
            originalEvent: {},
            latlng: {lat: -100, lng: 150},
            layerPoint: {x: 100, y: 200},
            containerPoint: {x: 300, y: 400},
        }]]);
    });

    test('fire_givenANonMouseEvent_passesItOnUntouched', () => {
        // Arrange
        const {path, fired} = makeFiringPath();
        const data = {tooltip: 'tooltip'};

        // Act
        path.fire('tooltipopen', data, true);

        // Assert
        expect(fired).toEqual([['tooltipopen', {tooltip: 'tooltip'}]]);
    });

    test('options_givenANewPath_isInteractiveAndKeepsMouseEventsFromTheMap', () => {
        expect(EnemyPath.prototype.options.interactive).toBe(true);
        expect(EnemyPath.prototype.options.bubblingMouseEvents).toBe(false);
    });
});

describe('EnemyCanvasRenderer', () => {
    function makeHitPath(hit) {
        return {options: {interactive: true}, _containsPoint: () => hit};
    }

    function makeRenderer(paths) {
        const renderer = new EnemyCanvasRenderer();
        const canvas = {style: {}};
        renderer._container = canvas;
        renderer._capturesPointer = null;
        renderer.layoutReads = 0;
        renderer._map = {
            getContainer: () => ({
                getBoundingClientRect: () => {
                    renderer.layoutReads++;

                    return {left: 100, top: 50, width: 800, height: 600};
                },
                offsetWidth: 800,
                offsetHeight: 600,
                clientLeft: 0,
                clientTop: 0,
            }),
            containerPointToLayerPoint: (point) => ({x: point.x - 10, y: point.y - 20}),
            mouseEventToLayerPoint: () => {
                renderer.layoutReads++;

                return {x: 0, y: 0};
            },
        };
        renderer._containerRect = null;
        renderer.mouseOuts = 0;
        renderer._handleMouseOut = () => renderer.mouseOuts++;
        let order = null;
        for (let i = paths.length - 1; i >= 0; i--) {
            order = {layer: paths[i], next: order};
        }
        renderer._drawFirst = order;

        return {renderer, canvas};
    }

    test('mouseEventToLayerPoint_givenAMouseEvent_returnsTheLayerPointUnderIt', () => {
        // Arrange
        const {renderer} = makeRenderer([]);

        // Act
        const point = renderer.mouseEventToLayerPoint({clientX: 150, clientY: 80});

        // Assert: 50, 30 into the container, which the map pane has moved by 10, 20
        expect(point).toEqual({x: 40, y: 10});
    });

    test('mouseEventToLayerPoint_givenRepeatedMouseMoves_readsTheLayoutOnce', () => {
        // Arrange
        const {renderer} = makeRenderer([]);

        // Act
        renderer.mouseEventToLayerPoint({clientX: 150, clientY: 80});
        renderer.mouseEventToLayerPoint({clientX: 160, clientY: 90});
        renderer.mouseEventToLayerPoint({clientX: 170, clientY: 100});

        // Assert
        expect(renderer.layoutReads).toBe(1);
    });

    test('mouseEventToLayerPoint_givenTheContainerRectWasInvalidated_readsTheLayoutAgain', () => {
        // Arrange
        const {renderer} = makeRenderer([]);
        renderer.mouseEventToLayerPoint({clientX: 150, clientY: 80});

        // Act
        renderer._invalidateContainerRect();
        renderer.mouseEventToLayerPoint({clientX: 150, clientY: 80});

        // Assert
        expect(renderer.layoutReads).toBe(2);
    });

    test('_onMapContainerMouseMove_givenRepeatedMouseMoves_readsTheLayoutOnce', () => {
        // Arrange
        const {renderer} = makeRenderer([makeHitPath(false)]);

        // Act
        renderer._onMapContainerMouseMove({target: {}, clientX: 150, clientY: 80});
        renderer._onMapContainerMouseMove({target: {}, clientX: 160, clientY: 90});

        // Assert
        expect(renderer.layoutReads).toBe(1);
    });

    test('getLayerAt_givenTwoOverlappingPaths_returnsTheTopmost', () => {
        // Arrange
        const bottom = makeHitPath(true);
        const top = makeHitPath(true);
        const {renderer} = makeRenderer([bottom, makeHitPath(false), top]);

        // Act
        const result = renderer.getLayerAt({x: 0, y: 0});

        // Assert
        expect(result).toBe(top);
    });

    test('getLayerAt_givenANonInteractivePathUnderTheMouse_returnsNull', () => {
        // Arrange
        const path = makeHitPath(true);
        path.options.interactive = false;
        const {renderer} = makeRenderer([path]);

        // Act
        const result = renderer.getLayerAt({x: 0, y: 0});

        // Assert
        expect(result).toBeNull();
    });

    test('_onMapContainerMouseMove_givenTheMouseOverAnEnemy_letsTheCanvasTakePointerEvents', () => {
        // Arrange
        const {renderer, canvas} = makeRenderer([makeHitPath(true)]);
        renderer._setCapturesPointer(false);

        // Act
        renderer._onMapContainerMouseMove({target: {}});

        // Assert
        expect(canvas.style.pointerEvents).toBe('auto');
    });

    test('_onMapContainerMouseMove_givenTheMouseLeftTheEnemyOnTheCanvas_dropsPointerEventsAndFiresMouseOut', () => {
        // Arrange
        const path = makeHitPath(true);
        const {renderer, canvas} = makeRenderer([path]);
        renderer._onMapContainerMouseMove({target: {}});
        path._containsPoint = () => false;

        // Act
        renderer._onMapContainerMouseMove({target: canvas});

        // Assert
        expect(canvas.style.pointerEvents).toBe('none');
        expect(renderer.mouseOuts).toBe(1);
    });

    test('_onMapContainerMouseMove_givenTheMouseStaysOffEnemies_firesNoMouseOut', () => {
        // Arrange
        const {renderer, canvas} = makeRenderer([makeHitPath(false)]);
        renderer._setCapturesPointer(false);

        // Act
        renderer._onMapContainerMouseMove({target: canvas});

        // Assert
        expect(canvas.style.pointerEvents).toBe('none');
        expect(renderer.mouseOuts).toBe(0);
    });
});

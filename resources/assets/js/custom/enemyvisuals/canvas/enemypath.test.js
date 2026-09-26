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

global.L = {CircleMarker: makeExtendable(), LayerGroup: makeExtendable()};
global.EnemyCanvasSpriteCache = require('./enemycanvasspritecache').EnemyCanvasSpriteCache;

const {EnemyPath} = require('./enemypath');

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

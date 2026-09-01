// ---------------------------------------------------------------------------
// Coverage for EnemyMarkerCuller#update (#4412, extracted from EnemyVisualManager per Wotuu's
// PR #4420 review comment).
//
// On a facade floor the large majority of enemies sit outside the viewport at any time, and the
// browser was laying out and painting all of them on every zoom. The cull hides that DOM with a
// class rather than removing the layer from the map, so nothing is destroyed and every reference
// the rest of the app holds into an enemy's DOM stays valid.
//
// The class is constructed directly with a plain array and a hovered-enemy getter - it needs
// nothing else, so no DungeonMap/Leaflet stand-in is required for these tests.
// ---------------------------------------------------------------------------

const {
    EnemyMarkerCuller,
    ENEMY_CULL_CLASS,
    ENEMY_CULL_BOUNDS_PADDING,
    ENEMY_CULL_MIN_ENEMIES
} = require('./enemymarkerculler');

/**
 * A stand-in for L.LatLngBounds covering only the latitude axis, which is all the cull reads.
 * @param min {Number}
 * @param max {Number}
 */
function makeBounds(min, max) {
    return {
        pad(fraction) {
            const margin = (max - min) * fraction;
            return makeBounds(min - margin, max + margin);
        },
        contains(latLng) {
            return latLng.lat >= min && latLng.lat <= max;
        }
    };
}

/** The viewport used by every test: latitude 0..100, so the pad margin is 40 on each side. */
const BOUNDS = makeBounds(0, 100);
const INSIDE = 50;
const IN_PAD_MARGIN = 120;
const FAR_OUTSIDE = 500;

function makeEnemy(id, lat, visualOverrides = {}) {
    return {
        id: id,
        setVisible: vi.fn(),
        layer: {
            _icon: document.createElement('div'),
            getLatLng: () => ({lat: lat})
        },
        visual: visualOverrides === null ? null : Object.assign({
            _circleMenu: null,
            isHighlighted: () => false
        }, visualOverrides)
    };
}

/**
 * @param enemies {Array} Padded out to ENEMY_CULL_MIN_ENEMIES so the cull is not gated off.
 * @param hoveredEnemy {Object|null}
 */
function makeCuller(enemies, hoveredEnemy = null) {
    const padding = [];
    for (let i = enemies.length; i < ENEMY_CULL_MIN_ENEMIES; i++) {
        padding.push(makeEnemy(1000 + i, INSIDE));
    }

    return new EnemyMarkerCuller(null, enemies.concat(padding), () => hoveredEnemy);
}

function cull(culler) {
    culler.update(BOUNDS);
}

function isCulled(enemy) {
    return enemy.layer._icon.classList.contains(ENEMY_CULL_CLASS);
}

test('update_givenAnEnemyFarOutsideTheViewport_hidesIt', () => {
    const enemy = makeEnemy(1, FAR_OUTSIDE);

    cull(makeCuller([enemy]));

    expect(isCulled(enemy)).toBe(true);
});

test('update_givenAnEnemyInsideTheViewport_leavesItRendered', () => {
    const enemy = makeEnemy(1, INSIDE);

    cull(makeCuller([enemy]));

    expect(isCulled(enemy)).toBe(false);
});

test('update_givenAnEnemyWithinThePadMargin_leavesItRendered', () => {
    // Off screen, but close enough that a pan or a zoom-out would otherwise pop it into view
    // before the next (throttled) pass got to it.
    const enemy = makeEnemy(1, IN_PAD_MARGIN);
    expect(BOUNDS.contains({lat: IN_PAD_MARGIN})).toBe(false);
    expect(BOUNDS.pad(ENEMY_CULL_BOUNDS_PADDING).contains({lat: IN_PAD_MARGIN})).toBe(true);

    cull(makeCuller([enemy]));

    expect(isCulled(enemy)).toBe(false);
});

test('update_givenACulledEnemyThatCameBackIntoView_rendersItAgain', () => {
    let lat = FAR_OUTSIDE;
    const enemy = makeEnemy(1, INSIDE);
    enemy.layer.getLatLng = () => ({lat: lat});
    const culler = makeCuller([enemy]);

    cull(culler);
    expect(isCulled(enemy)).toBe(true);

    lat = INSIDE;
    cull(culler);

    expect(isCulled(enemy)).toBe(false);
});

test('update_givenTheHoveredEnemyOffScreen_leavesItRendered', () => {
    const enemy = makeEnemy(1, FAR_OUTSIDE);

    cull(makeCuller([enemy], enemy));

    expect(isCulled(enemy)).toBe(false);
});

test('update_givenAnEnemyWithAnOpenCircleMenuOffScreen_leavesItRendered', () => {
    const enemy = makeEnemy(1, FAR_OUTSIDE, {_circleMenu: {}});

    cull(makeCuller([enemy]));

    expect(isCulled(enemy)).toBe(false);
});

test('update_givenAHighlightedEnemyOffScreen_leavesItRendered', () => {
    const enemy = makeEnemy(1, FAR_OUTSIDE, {isHighlighted: () => true});

    cull(makeCuller([enemy]));

    expect(isCulled(enemy)).toBe(false);
});

test('update_givenAnEnemyWithoutAVisual_leavesItRendered', () => {
    const enemy = makeEnemy(1, FAR_OUTSIDE, null);

    cull(makeCuller([enemy]));

    expect(isCulled(enemy)).toBe(false);
});

test('update_givenAnEnemyWhoseLayerHasNoIcon_doesNotThrow', () => {
    const detached = makeEnemy(1, FAR_OUTSIDE);
    detached.layer._icon = null;
    const noLayer = makeEnemy(2, FAR_OUTSIDE);
    noLayer.layer = null;

    expect(() => cull(makeCuller([detached, noLayer]))).not.toThrow();
});

test('update_givenFewerEnemiesThanTheThreshold_doesNothing', () => {
    const enemy = makeEnemy(1, FAR_OUTSIDE);
    const culler = new EnemyMarkerCuller(null, [enemy], () => null);

    culler.update(BOUNDS);

    expect(isCulled(enemy)).toBe(false);
});

test('update_givenAnyEnemies_neverChangesMapObjectVisibility', () => {
    // Culling must not go through setVisible(): KillZone builds its pull hull from the enemies that
    // pass isVisible(), so the polygon would change shape as the user pans.
    const offScreen = makeEnemy(1, FAR_OUTSIDE);
    const onScreen = makeEnemy(2, INSIDE);
    const culler = makeCuller([offScreen, onScreen]);

    cull(culler);

    expect(offScreen.setVisible).not.toHaveBeenCalled();
    expect(onScreen.setVisible).not.toHaveBeenCalled();
});

test('update_givenTheEnemyCountDroppingBelowTheThreshold_rendersWhatItHadHidden', () => {
    // Enemies can be deleted in the map editor. Without this the markers culled while the floor was
    // above the threshold would stay hidden for the rest of the session.
    const offScreen = makeEnemy(1, FAR_OUTSIDE);
    const culler = makeCuller([offScreen]);

    cull(culler);
    expect(isCulled(offScreen)).toBe(true);

    culler._allEnemies = [offScreen];
    cull(culler);

    expect(isCulled(offScreen)).toBe(false);
});

test('scheduleUpdate_givenMultipleCallsBeforeTheNextFrame_coalescesIntoASingleUpdate', () => {
    const rafSpy = vi.spyOn(window, 'requestAnimationFrame').mockImplementation(() => 1);
    const enemy = makeEnemy(1, FAR_OUTSIDE);
    const culler = makeCuller([enemy]);
    culler.map = {leafletMap: {getBounds: () => BOUNDS}};

    culler.scheduleUpdate();
    culler.scheduleUpdate();
    culler.scheduleUpdate();

    expect(rafSpy).toHaveBeenCalledTimes(1);
});

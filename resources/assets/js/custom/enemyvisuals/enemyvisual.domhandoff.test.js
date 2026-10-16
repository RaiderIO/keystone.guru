// The raid marker circle menu on a canvas-drawn enemy: the enemy is promoted to its DOM marker while
// the menu is open, and demoted back to the canvas on every way the menu goes away.
//
// Runs the real jQuery and zikes-circlemenu plugin, and the real EnemyVisual constructor so the
// enemy's own signal handlers (open, hidden) are the ones under test. The enemy map object group is
// a stand-in whose promote/demote put the marker's DOM in and out of the document, as Leaflet would.

global.$ = global.jQuery = require('jquery');
require('zikes-circlemenu');

const fs = require('fs');
const path = require('path');
const HandlebarsRuntime = require('handlebars');

global.Signalable = class Signalable {
    signal() {
    }
};
global.DungeonMap = class DungeonMap {
};
global.MapObject = class MapObject {
};
global.Enemy = class Enemy extends global.MapObject {
};

const {MapState} = require('../mapstate/mapstate');
global.MapState = MapState;
const {MapObjectMapState} = require('../mapstate/mapobjectmapstate');
global.MapObjectMapState = MapObjectMapState;
const {RaidMarkerSelectMapState} = require('../mapstate/raidmarkerselectmapstate');
global.RaidMarkerSelectMapState = RaidMarkerSelectMapState;

const {EnemyVisual} = require('./enemyvisual');

const radialTemplate = HandlebarsRuntime.compile(
    fs.readFileSync(
        path.join(__dirname, '../../handlebars/map_enemy_raid_marker_template.handlebars'),
        'utf8'
    )
);

global.Handlebars = {templates: {map_enemy_raid_marker_template: radialTemplate}};
global.getHandlebarsDefaultVariables = () => ({});
global.refreshTooltips = () => {
};
global.removeStrayTooltips = () => {
};
global.bootstrap = {Tooltip: {getInstance: () => null}};
global.c = {map: {enemy: {calculateMargin: () => 4}}};
global.getState = () => ({getEnemyDisplayType: () => 'npc_class'});
global.L = {Layer: class Layer {
}};

const ENEMY_ID = 42;

/**
 * A real EnemyVisual on a canvas-rendered (or, with canvasRendered false, DOM-rendered) map. Its
 * size refresh is stubbed out: what it draws is covered by the canvas tests and the screenshots.
 * @param canvasRendered {boolean}
 */
function makeEnemyVisual(canvasRendered = true) {
    const handlers = {};
    const enemy = Object.assign(new global.Enemy(), {
        id: ENEMY_ID,
        register: (names, context, callback) => {
            for (const name of [].concat(names)) {
                handlers[name] = callback;
            }
        },
        shouldBeVisible: () => true,
    });

    const markerElement = document.createElement('div');
    markerElement.className = 'leaflet-marker-icon leaflet-div-icon';
    markerElement.innerHTML = `<div id="map_enemy_visual_${ENEMY_ID}"><div class="enemy_icon"></div></div>`;

    const layer = Object.assign(new global.L.Layer(), {
        getElement: () => (document.contains(markerElement) ? markerElement : undefined),
    });

    const enemyMapObjectGroup = {
        promoteCalls: [],
        demoteCalls: [],
        isCanvasRendered: () => canvasRendered,
        getCanvasPath: () => ({setProjectedCallback: () => {
        }}),
        promoteToDomMarker: (marker) => {
            enemyMapObjectGroup.promoteCalls.push(marker);
            if (!canvasRendered) {
                return false;
            }
            document.body.appendChild(markerElement);

            return true;
        },
        demoteToCanvas: (marker) => {
            enemyMapObjectGroup.demoteCalls.push(marker);
            markerElement.remove();

            return true;
        },
    };

    const leafletHandlers = {};
    let mapState = null;
    const map = Object.assign(new global.DungeonMap(), {
        mapObjectGroupManager: {getEnemyMapObjectGroup: () => enemyMapObjectGroup},
        leafletMap: {
            on: (name, callback) => (leafletHandlers[name] = callback),
            off: (name, callback) => {
                if (leafletHandlers[name] === callback) {
                    delete leafletHandlers[name];
                }
            },
        },
        register: () => {
        },
        getMapState: () => mapState,
        setMapState: (newMapState) => (mapState = newMapState),
    });

    vi.spyOn(EnemyVisual.prototype, 'setVisualType').mockImplementation(function () {
        this.mainVisual = {getSize: () => ({iconSize: [30, 30]})};
    });
    vi.spyOn(EnemyVisual.prototype, 'refreshSize').mockImplementation(() => {
    });

    const visual = new EnemyVisual(map, enemy, layer);

    if (!canvasRendered) {
        document.body.appendChild(markerElement);
    }

    return {visual, handlers, layer, markerElement, enemyMapObjectGroup, leafletHandlers};
}

/**
 * Shift+right-click on the enemy, as Enemy#onLayerInit signals it, and let the menu finish opening.
 */
function openRaidMarkerMenu(handlers) {
    handlers['enemy:raidmarker_contextmenu']({data: {}});
    vi.advanceTimersByTime(1000);
}

function isOnScreen(markerElement) {
    return document.contains(markerElement);
}

describe('EnemyVisual raid marker menu DOM handoff', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        vi.restoreAllMocks();
        document.body.innerHTML = '';
    });

    test('raidMarkerContextMenu_givenCanvasRenderedEnemy_opensTheMenuOnItsPromotedDomMarker', () => {
        // Arrange
        const {visual, handlers, layer, markerElement, enemyMapObjectGroup} = makeEnemyVisual();

        // Act
        openRaidMarkerMenu(handlers);

        // Assert
        expect(enemyMapObjectGroup.promoteCalls).toEqual([layer]);
        expect(isOnScreen(markerElement)).toBe(true);
        expect(markerElement.querySelector(`#map_enemy_raid_marker_radial_${ENEMY_ID}`)).not.toBeNull();
        expect(visual.map.getMapState()).toBeInstanceOf(RaidMarkerSelectMapState);
    });

    test('circleMenuClose_givenPromotedEnemy_demotesItToTheCanvas', () => {
        // Arrange
        const {visual, handlers, layer, markerElement, enemyMapObjectGroup} = makeEnemyVisual();
        openRaidMarkerMenu(handlers);

        // Act
        $(`#map_enemy_raid_marker_radial_${ENEMY_ID} > li:first-child`).trigger('click');
        vi.advanceTimersByTime(1000);

        // Assert
        expect(enemyMapObjectGroup.demoteCalls).toEqual([layer]);
        expect(isOnScreen(markerElement)).toBe(false);
        expect(visual._circleMenu).toBeNull();
    });

    test('keydown_givenEscapeWhilePromoted_closesTheMenuAndDemotesTheEnemy', () => {
        // Arrange
        const {visual, handlers, markerElement} = makeEnemyVisual();
        openRaidMarkerMenu(handlers);

        // Act
        document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));

        // Assert
        expect(visual._circleMenu).toBeNull();
        expect(isOnScreen(markerElement)).toBe(false);
        expect(visual.map.getMapState()).toBeNull();
    });

    test('keydown_givenOtherKeyWhilePromoted_keepsTheMenuOpen', () => {
        // Arrange
        const {visual, handlers, markerElement} = makeEnemyVisual();
        openRaidMarkerMenu(handlers);

        // Act
        document.dispatchEvent(new KeyboardEvent('keydown', {key: 'a', bubbles: true}));

        // Assert
        expect(visual._circleMenu).not.toBeNull();
        expect(isOnScreen(markerElement)).toBe(true);
    });

    test('pointerdown_givenPressOutsideThePromotedMarker_closesTheMenuAndDemotesTheEnemy', () => {
        // Arrange
        const {visual, handlers, markerElement} = makeEnemyVisual();
        const elsewhere = document.createElement('div');
        document.body.appendChild(elsewhere);
        openRaidMarkerMenu(handlers);

        // Act
        elsewhere.dispatchEvent(new MouseEvent('pointerdown', {bubbles: true}));

        // Assert
        expect(visual._circleMenu).toBeNull();
        expect(isOnScreen(markerElement)).toBe(false);
    });

    test('pointerdown_givenPressOnTheMenu_keepsTheMenuOpen', () => {
        // Arrange
        const {visual, handlers, markerElement} = makeEnemyVisual();
        openRaidMarkerMenu(handlers);
        const menuItem = markerElement.querySelector(`#map_enemy_raid_marker_radial_${ENEMY_ID} li`);

        // Act
        menuItem.dispatchEvent(new MouseEvent('pointerdown', {bubbles: true}));

        // Assert
        expect(visual._circleMenu).not.toBeNull();
        expect(isOnScreen(markerElement)).toBe(true);
    });

    test('zoomstart_givenPromotedEnemy_closesTheMenuAndDemotesTheEnemy', () => {
        // Arrange
        const {visual, handlers, markerElement, leafletHandlers} = makeEnemyVisual();
        openRaidMarkerMenu(handlers);

        // Act
        leafletHandlers['zoomstart']?.();

        // Assert
        expect(visual._circleMenu).toBeNull();
        expect(isOnScreen(markerElement)).toBe(false);
        expect(leafletHandlers['zoomstart']).toBeUndefined();
    });

    test('hidden_givenPromotedEnemyOnAFloorSwitch_closesTheMenuAndDemotesTheEnemy', () => {
        // Arrange
        const {visual, handlers, layer, markerElement, enemyMapObjectGroup} = makeEnemyVisual();
        openRaidMarkerMenu(handlers);

        // Act
        handlers['hidden']({data: {visible: false}});

        // Assert
        expect(visual._circleMenu).toBeNull();
        expect(enemyMapObjectGroup.demoteCalls).toEqual([layer]);
        expect(isOnScreen(markerElement)).toBe(false);
    });

    test('keydown_givenEscapeAfterTheMenuClosed_doesNotReachTheEnemyAnymore', () => {
        // Arrange
        const {visual, handlers, enemyMapObjectGroup} = makeEnemyVisual();
        openRaidMarkerMenu(handlers);
        document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));
        const cleanupSpy = vi.spyOn(visual, '_cleanupCircleMenu');

        // Act
        document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));

        // Assert
        expect(cleanupSpy).not.toHaveBeenCalled();
        expect(enemyMapObjectGroup.demoteCalls).toHaveLength(1);
    });

    test('keydown_givenEscapeOnDomRenderedEnemy_keepsTheMenuOpen', () => {
        // Arrange
        const {visual, handlers, markerElement} = makeEnemyVisual(false);
        openRaidMarkerMenu(handlers);

        // Act
        document.dispatchEvent(new KeyboardEvent('keydown', {key: 'Escape', bubbles: true}));

        // Assert
        expect(visual._circleMenu).not.toBeNull();
        expect(isOnScreen(markerElement)).toBe(true);
    });
});

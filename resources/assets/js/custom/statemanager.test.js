// ---------------------------------------------------------------------------
// Coverage for StateManager (#3674, target C): state transitions and subscriber
// notification - the global state every map page reads, where a regression is
// felt everywhere at once.
//
// Follows the global-script recipe documented at the top of killzone.test.js, with
// one deliberate difference: Signalable is NOT stubbed here. Subscriber notification
// IS the subject of this file, so the tests register real listeners on the real event
// bus (signalable.js) and assert on what they receive - a stub would only prove the
// stub works.
// ---------------------------------------------------------------------------

// 1a. The real event bus StateManager extends.
const {Signalable} = require('./signalable');
global.Signalable = Signalable;

// 1b. Constants referenced as bare globals, with their real values from constants.js.
global.MAP_CONTEXT_TYPE_DUNGEON_ROUTE = 'dungeonroute';
global.MAP_CONTEXT_TYPE_LIVE_SESSION = 'livesession';
global.MAP_CONTEXT_TYPE_MAPPING_VERSION_EDIT = 'mappingVersionEdit';
global.MAP_CONTEXT_TYPE_DUNGEON_EXPLORE = 'dungeonExplore';
global.MAP_CONTEXT_TYPE_DUNGEON_ROUTE_SEARCH = 'dungeonRouteSearch';
global.MAP_FACADE_STYLE_FACADE = 'facade';
global.MAP_FACADE_STYLE_SPLIT_FLOORS = 'split_floors';
global.NUMBER_STYLE_PERCENTAGE = 'percentage';
global.NUMBER_STYLE_ENEMY_FORCES = 'enemy_forces';
global.DISPLAY_TYPE_NPC_CLASS = 'npc_class';
global.cookieDefaultAttributes = undefined;

// 1c. Map context classes. Only their identity matters to StateManager: setMapContext() picks one
// by `type` and isMapAdmin() answers `instanceof MapContextMappingVersionEdit`. Each records the
// raw context it was constructed with so a test can prove the payload was handed through.
// The `extends` chain mirrors the real one (custom/mapcontext/*.js) exactly:
//
//   MapContext
//   |- MapContextDungeonRoute -> MapContextLiveSession
//   `- MapContextMappingVersion
//      |- MapContextDungeonExplore -> MapContextDungeonRouteSearch
//      `- MapContextMappingVersionEdit
//
// isMapAdmin() would answer identically under a flatter stub, but only this shape can tell a
// widening to `instanceof MapContextMappingVersion` (which would wrongly admit explore/search)
// apart from a widening to `instanceof MapContextDungeonRoute` (which would not).
global.MapContext = class MapContext {
    constructor(mapContext) {
        this.mapContext = mapContext;
    }
};
global.MapContextDungeonRoute = class MapContextDungeonRoute extends global.MapContext {
};
global.MapContextLiveSession = class MapContextLiveSession extends global.MapContextDungeonRoute {
};
global.MapContextMappingVersion = class MapContextMappingVersion extends global.MapContext {
};
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit extends global.MapContextMappingVersion {
};
global.MapContextDungeonExplore = class MapContextDungeonExplore extends global.MapContextMappingVersion {
};
global.MapContextDungeonRouteSearch = class MapContextDungeonRouteSearch extends global.MapContextDungeonExplore {
};

// 1d. A cookie jar with real read-back, unlike the shared setup's write-less stub. The settings
// getters read straight back out of Cookies, so a jar that forgets would make them untestable.
const cookieJar = new Map();
global.Cookies = {
    get: (key) => cookieJar.get(key),
    set: (key, value) => cookieJar.set(key, `${value}`),
};

// 1e. lodash (snackbar bookkeeping) and the `c` constants object (pull gradient defaults).
global._ = {
    indexOf: (array, value) => array.indexOf(value),
    without: (array, value) => array.filter((entry) => entry !== value),
};
global.c = {
    map: {
        editsidebar: {
            pullGradient: {defaultHandlers: [[0, '#000000'], [100, '#ffffff']]},
        },
        // Real value from constants.js - getKillZonePathWeight() falls back to it.
        polyline: {killzonepath: {weight: 5}},
    },
};

const {StateManager} = require('./statemanager');

/**
 * A fake map context exposing only what StateManager asks of it.
 *
 * @param {Object} options
 * @param {Array<Object>} options.visibleFloors Floors the context considers visible.
 * @param {String|undefined} options.pullGradient The stored pull gradient string.
 * @param {boolean} options.facadeEnabled Whether the dungeon supports a facade.
 * @returns {Object}
 */
function makeFakeMapContext({visibleFloors = [{id: 1}, {id: 2}], pullGradient = '', facadeEnabled = false} = {}) {
    return {
        getVisibleFloors: () => visibleFloors,
        getPullGradient: () => pullGradient,
        getMappingVersion: () => ({facade_enabled: facadeEnabled}),
    };
}

/**
 * A StateManager with a fake map context already installed, bypassing setMapContext()'s
 * type-based construction (which has its own tests below).
 *
 * @param {Object} mapContext
 * @returns {StateManager}
 */
function makeStateManager(mapContext = makeFakeMapContext()) {
    const stateManager = new StateManager();
    stateManager._mapContext = mapContext;

    return stateManager;
}

/**
 * Records every payload delivered for `event` on the real Signalable bus.
 *
 * @param {StateManager} stateManager
 * @param {String} event
 * @returns {Array<Object>}
 */
function listenFor(stateManager, event) {
    const received = [];
    stateManager.register(event, {}, (signal) => received.push(signal));

    return received;
}

beforeEach(() => {
    cookieJar.clear();
});

describe('StateManager.setMapContext', () => {
    test.each([
        ['dungeon route', MAP_CONTEXT_TYPE_DUNGEON_ROUTE, 'MapContextDungeonRoute'],
        ['live session', MAP_CONTEXT_TYPE_LIVE_SESSION, 'MapContextLiveSession'],
        ['mapping version edit', MAP_CONTEXT_TYPE_MAPPING_VERSION_EDIT, 'MapContextMappingVersionEdit'],
        ['dungeon explore', MAP_CONTEXT_TYPE_DUNGEON_EXPLORE, 'MapContextDungeonExplore'],
        ['dungeon route search', MAP_CONTEXT_TYPE_DUNGEON_ROUTE_SEARCH, 'MapContextDungeonRouteSearch'],
    ])('setMapContext_given%sType_buildsThatContext', (label, type, expectedClass) => {
        const stateManager = new StateManager();

        stateManager.setMapContext({type});

        expect(stateManager.getMapContext()).toBeInstanceOf(global[expectedClass]);
        expect(stateManager.getMapContext().mapContext).toEqual({type});
    });

    test('setMapContext_givenAnUnknownType_leavesTheContextUnchanged', () => {
        const stateManager = new StateManager();
        stateManager.setMapContext({type: MAP_CONTEXT_TYPE_DUNGEON_ROUTE});
        const before = stateManager.getMapContext();
        // The unknown branch only console.error()s; silence it so the run stays readable.
        const consoleError = vi.spyOn(console, 'error').mockImplementation(() => {
        });

        stateManager.setMapContext({type: 'somethingElse'});

        expect(stateManager.getMapContext()).toBe(before);
        expect(consoleError).toHaveBeenCalled();
    });
});

describe('StateManager.isMapAdmin', () => {
    test('isMapAdmin_givenTheMappingEditorContext_returnsTrue', () => {
        const stateManager = new StateManager();

        stateManager.setMapContext({type: MAP_CONTEXT_TYPE_MAPPING_VERSION_EDIT});

        expect(stateManager.isMapAdmin()).toBe(true);
    });

    test.each([
        ['dungeon route', MAP_CONTEXT_TYPE_DUNGEON_ROUTE],
        ['live session', MAP_CONTEXT_TYPE_LIVE_SESSION],
        ['dungeon explore', MAP_CONTEXT_TYPE_DUNGEON_EXPLORE],
    ])('isMapAdmin_given%sContext_returnsFalse', (label, type) => {
        const stateManager = new StateManager();

        stateManager.setMapContext({type});

        expect(stateManager.isMapAdmin()).toBe(false);
    });
});

describe('StateManager floor state', () => {
    test('setFloorId_givenAFloor_notifiesSubscribersWithTheCenterAndZoom', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'floorid:changed');

        stateManager.setFloorId(2, [1, 2], 3);

        expect(received).toHaveLength(1);
        expect(received[0].data).toEqual({floorId: 2, center: [1, 2], zoom: 3});
    });

    test('setFloorId_givenNoCenterOrZoom_notifiesWithNulls', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'floorid:changed');

        stateManager.setFloorId(2);

        expect(received[0].data).toEqual({floorId: 2, center: null, zoom: null});
    });

    test('setFloorId_givenTheSameFloorAgain_notifiesAgain', () => {
        // Unlike setMapZoomLevel(), this one has no changed-check: consumers rely on the signal
        // firing on every set, including a re-select of the floor already displayed.
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'floorid:changed');

        stateManager.setFloorId(2);
        stateManager.setFloorId(2);

        expect(received).toHaveLength(2);
    });

    test('getCurrentFloor_givenTheCurrentFloorId_returnsThatFloor', () => {
        const floor = {id: 2, name: 'Second floor'};
        const stateManager = makeStateManager(makeFakeMapContext({visibleFloors: [{id: 1}, floor]}));

        stateManager.setFloorId(2);

        expect(stateManager.getCurrentFloor()).toBe(floor);
    });

    test('getCurrentFloor_givenAFloorIdFromTheDom_stillResolvesTheFloor', () => {
        // The real asymmetry both Number() calls exist for: visibleFloors comes from
        // MapContextBase::toArray() with integer ids, while setFloorId() is fed a STRING by the
        // floor <select> (sidebarnavigation.js) and the map's floor switch. Model both sides as
        // strings and the lookup would match without any coercion at all.
        const floor = {id: 2};
        const stateManager = makeStateManager(makeFakeMapContext({visibleFloors: [floor]}));

        stateManager.setFloorId('2');

        expect(stateManager.getCurrentFloor()).toBe(floor);
    });

    test('getCurrentFloor_givenAFloorThatIsNotVisible_returnsFalse', () => {
        const stateManager = makeStateManager(makeFakeMapContext({visibleFloors: [{id: 1}]}));

        stateManager.setFloorId(99);

        expect(stateManager.getCurrentFloor()).toBe(false);
    });

    test('getCurrentFloor_givenTheContextChangedAfterTheFirstCall_keepsTheStaleFloorList', () => {
        // The visible-floor lookup is built once and never invalidated - not by setMapContext(),
        // not by setFloorId(). Nothing swaps _mapContext on a live StateManager today:
        // setMapContext() has exactly one caller (statemanager.blade.php, once per page load) and
        // the facade toggle reloads the page rather than rebuilding in place. So this is latent,
        // not live - pinned so that whoever introduces the first in-page context swap finds out
        // here rather than through floors silently resolving against the old list.
        const stateManager = makeStateManager(makeFakeMapContext({visibleFloors: [{id: 1}]}));
        stateManager.setFloorId(1);
        expect(stateManager.getCurrentFloor()).toEqual({id: 1});

        stateManager._mapContext = makeFakeMapContext({visibleFloors: [{id: 1, name: 'Renamed'}, {id: 5}]});
        stateManager.setFloorId(5);

        expect(stateManager.getCurrentFloor()).toBe(false);
    });
});

describe('StateManager.setMapZoomLevel', () => {
    test('setMapZoomLevel_givenANewZoom_notifiesWithBothTheOldAndNewLevel', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'mapzoomlevel:changed');

        stateManager.setMapZoomLevel(5);

        expect(stateManager.getMapZoomLevel()).toBe(5);
        expect(received[0].data).toEqual({mapZoomLevel: 5, previousMapZoomLevel: 2});
    });

    test('setMapZoomLevel_givenTheSameZoom_doesNotNotify', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'mapzoomlevel:changed');

        stateManager.setMapZoomLevel(5);
        stateManager.setMapZoomLevel(5);

        expect(received).toHaveLength(1);
    });
});

describe('StateManager cookie-backed display settings', () => {
    test('setEnemyDisplayType_givenAType_storesItAndNotifies', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'enemydisplaytype:changed');

        stateManager.setEnemyDisplayType(DISPLAY_TYPE_NPC_CLASS);

        expect(stateManager.getEnemyDisplayType()).toBe(DISPLAY_TYPE_NPC_CLASS);
        expect(Cookies.get('enemy_display_type')).toBe(DISPLAY_TYPE_NPC_CLASS);
        expect(received[0].data).toEqual({enemyDisplayType: DISPLAY_TYPE_NPC_CLASS});
    });

    test('setUnkilledEnemyOpacity_givenAnOpacity_storesItAndNotifies', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'unkilledenemyopacity:changed');

        stateManager.setUnkilledEnemyOpacity(0.5);

        expect(stateManager.getUnkilledEnemyOpacity()).toBe(0.5);
        expect(received[0].data).toEqual({opacity: 0.5});
    });

    test('setUnkilledImportantEnemyOpacity_givenAnOpacity_storesItAndNotifies', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'unkilledimportantenemyopacity:changed');

        stateManager.setUnkilledImportantEnemyOpacity(0.8);

        expect(stateManager.getUnkilledImportantEnemyOpacity()).toBe(0.8);
        expect(received[0].data).toEqual({opacity: 0.8});
    });

    test.each([
        ['aggressiveness', 'setEnemyAggressivenessBorder', 'hasEnemyAggressivenessBorder', 'enemyaggressivenessborder:changed'],
        ['dangerous', 'setEnemyDangerousBorder', 'hasEnemyDangerousBorder', 'enemydangerousborder:changed'],
    ])('set%sBorder_givenABoolean_storesItAsAFlagAndNotifies', (label, setter, getter, event) => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, event);

        stateManager[setter](true);
        expect(stateManager[getter]()).toBe(true);

        stateManager[setter](false);
        expect(stateManager[getter]()).toBe(false);

        expect(received.map((signal) => signal.data)).toEqual([{visible: true}, {visible: false}]);
    });

    test('hasEnemyAggressivenessBorder_givenNoStoredValue_returnsFalse', () => {
        // parseInt(undefined) is NaN, which is never 1 - the unset cookie must read as "off".
        expect(makeStateManager().hasEnemyAggressivenessBorder()).toBe(false);
    });

    test.each([
        ['heatmap tooltips', 'setHeatmapShowTooltips', 'map_heatmap_show_tooltips', 'heatmapshowtooltips:changed', 'visible'],
        ['heatmap on top', 'setHeatmapShowOnTop', 'map_heatmap_show_on_top', 'heatmapshowontop:changed', 'onTop'],
    ])('set%s_givenABoolean_storesOneOrZeroAndNotifies', (label, setter, cookie, event, payloadKey) => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, event);

        stateManager[setter](true);

        expect(Cookies.get(cookie)).toBe('1');
        expect(received[0].data).toEqual({[payloadKey]: true});
    });

    test('getMapFacadeStyle_givenNoStoredValue_defaultsToFacade', () => {
        expect(makeStateManager().getMapFacadeStyle()).toBe(MAP_FACADE_STYLE_FACADE);
    });

    test('setMapFacadeStyle_givenAStyle_storesItAndNotifies', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'mapfacadestyle:changed');

        stateManager.setMapFacadeStyle(MAP_FACADE_STYLE_SPLIT_FLOORS);

        expect(stateManager.getMapFacadeStyle()).toBe(MAP_FACADE_STYLE_SPLIT_FLOORS);
        expect(received).toHaveLength(1);
    });

    test('getMapNumberStyle_givenNoStoredValue_defaultsToPercentage', () => {
        expect(makeStateManager().getMapNumberStyle()).toBe(NUMBER_STYLE_PERCENTAGE);
    });

    test('getKillZonePathWeight_givenNoStoredValue_fallsBackToTheDefaultWeight', () => {
        // parseInt(undefined) is NaN; returning that would draw invisible lines. The cookie really
        // can be missing - cookie-less headless renders reject the secure cookie.
        expect(makeStateManager().getKillZonePathWeight()).toBe(c.map.polyline.killzonepath.weight);
    });

    test('setKillZonePathWeight_givenAWeight_storesItAndNotifies', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'killzonepathweight:changed');

        stateManager.setKillZonePathWeight(9);

        expect(stateManager.getKillZonePathWeight()).toBe(9);
        expect(received[0].data).toEqual({weight: 9});
    });

    test('getMapZoomSpeed_givenAStoredValue_parsesItBackToANumber', () => {
        const stateManager = makeStateManager();

        stateManager.setMapZoomSpeed(3);

        expect(stateManager.getMapZoomSpeed()).toBe(3);
    });

    test('getMapZoomSpeed_givenNoStoredValue_returnsTheDefault', () => {
        // Mirrors getKillZonePathWeight()'s isNaN() guard: a missing cookie now falls back to the
        // same default (50) that map.js/mapsettings.blade.php use when first setting the cookie,
        // instead of returning NaN.
        expect(makeStateManager().getMapZoomSpeed()).toBe(50);
    });
});

describe('StateManager.isCurrentDungeonFacadeEnabled', () => {
    test('isCurrentDungeonFacadeEnabled_givenAFacadeDungeonAndTheFacadeStyle_returnsTrue', () => {
        const stateManager = makeStateManager(makeFakeMapContext({facadeEnabled: true}));

        stateManager.setMapFacadeStyle(MAP_FACADE_STYLE_FACADE);

        expect(stateManager.isCurrentDungeonFacadeEnabled()).toBe(true);
    });

    test('isCurrentDungeonFacadeEnabled_givenAFacadeDungeonButSplitFloors_returnsFalse', () => {
        const stateManager = makeStateManager(makeFakeMapContext({facadeEnabled: true}));

        stateManager.setMapFacadeStyle(MAP_FACADE_STYLE_SPLIT_FLOORS);

        expect(stateManager.isCurrentDungeonFacadeEnabled()).toBe(false);
    });

    test('isCurrentDungeonFacadeEnabled_givenADungeonWithoutAFacade_returnsFalse', () => {
        const stateManager = makeStateManager(makeFakeMapContext({facadeEnabled: false}));

        stateManager.setMapFacadeStyle(MAP_FACADE_STYLE_FACADE);

        expect(stateManager.isCurrentDungeonFacadeEnabled()).toBe(false);
    });
});

describe('StateManager.setFocusedEnemy', () => {
    test('setFocusedEnemy_givenAnEnemy_notifiesSubscribers', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'focusedenemy:changed');
        const enemy = {id: 5};

        stateManager.setFocusedEnemy(enemy);

        expect(stateManager.getFocusedEnemy()).toBe(enemy);
        expect(received[0].data).toEqual({focusedenemy: enemy});
    });

    test('setFocusedEnemy_givenNull_notifiesWithNull', () => {
        const stateManager = makeStateManager();
        stateManager.setFocusedEnemy({id: 5});
        const received = listenFor(stateManager, 'focusedenemy:changed');

        stateManager.setFocusedEnemy(null);

        expect(received[0].data).toEqual({focusedenemy: null});
    });
});

describe('StateManager.setMdtMappingModeEnabled', () => {
    test('setMdtMappingModeEnabled_givenTrue_notifiesSubscribers', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'mdtmappingmodeenabled:changed');

        stateManager.setMdtMappingModeEnabled(true);

        expect(stateManager.getMdtMappingModeEnabled()).toBe(true);
        expect(received[0].data).toEqual({mdtmappingmodeenabled: true});
    });
});

describe('StateManager user state', () => {
    test('userHasRole_givenNoUser_returnsFalse', () => {
        expect(makeStateManager().userHasRole('admin')).toBe(false);
    });

    test('userHasRole_givenAUserWithThatRole_returnsTrue', () => {
        const stateManager = makeStateManager();

        stateManager.setUserData({roles: [{name: 'user'}, {name: 'admin'}]});

        expect(stateManager.userHasRole('admin')).toBe(true);
        expect(stateManager.userHasRole('moderator')).toBe(false);
    });

    test('getUser_givenUserData_returnsIt', () => {
        const stateManager = makeStateManager();
        const userData = {name: 'Wotuu', roles: []};

        stateManager.setUserData(userData);

        expect(stateManager.getUser()).toBe(userData);
    });

    test('hasPatreonBenefit_givenTheBenefit_returnsTrue', () => {
        const stateManager = makeStateManager();

        stateManager.setPatreonBenefits(['adfree', 'animated-polylines']);

        expect(stateManager.hasPatreonBenefit('adfree')).toBe(true);
        expect(stateManager.hasPatreonBenefit('unlimited-dungeonroutes')).toBe(false);
    });

    test('hasPatreonBenefit_givenNoBenefits_returnsFalse', () => {
        expect(makeStateManager().hasPatreonBenefit('adfree')).toBe(false);
    });
});

describe('StateManager snackbars', () => {
    test('addSnackbar_givenHtml_returnsAnIncrementingIdAndNotifies', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'snackbar:add');

        const first = stateManager.addSnackbar('<div>one</div>');
        const second = stateManager.addSnackbar('<div>two</div>');

        expect(first).toBe('snackbar-1');
        expect(second).toBe('snackbar-2');
        expect(received[0].data).toEqual({
            id: 'snackbar-1',
            html: '<div>one</div>',
            compact: false,
            onDomAdded: null,
        });
    });

    test('addSnackbar_givenOptions_passesThemAlong', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'snackbar:add');
        const onDomAdded = () => {
        };

        stateManager.addSnackbar('<div>one</div>', {compact: true, onDomAdded});

        expect(received[0].data.compact).toBe(true);
        expect(received[0].data.onDomAdded).toBe(onDomAdded);
    });

    test('addSnackbar_givenANonFunctionOnDomAdded_passesNull', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'snackbar:add');

        stateManager.addSnackbar('<div>one</div>', {onDomAdded: 'not a function'});

        expect(received[0].data.onDomAdded).toBeNull();
    });

    test('removeSnackbar_givenAKnownId_notifiesAndForgetsIt', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'snackbar:remove');
        const snackbarId = stateManager.addSnackbar('<div>one</div>');

        stateManager.removeSnackbar(snackbarId);

        expect(received).toHaveLength(1);
        expect(received[0].data).toEqual({id: snackbarId});
        expect(stateManager.snackbarIds).toEqual([]);
    });

    test('removeSnackbar_givenTheSameIdTwice_onlyNotifiesOnce', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'snackbar:remove');
        const snackbarId = stateManager.addSnackbar('<div>one</div>');

        stateManager.removeSnackbar(snackbarId);
        stateManager.removeSnackbar(snackbarId);

        expect(received).toHaveLength(1);
    });

    test('removeSnackbar_givenAnUnknownId_doesNotNotify', () => {
        const stateManager = makeStateManager();
        const received = listenFor(stateManager, 'snackbar:remove');

        stateManager.removeSnackbar('snackbar-999');

        expect(received).toHaveLength(0);
    });
});

describe('StateManager.updateKillZones', () => {
    test('updateKillZones_givenKillZones_flattensThemIntoTheSaveShape', () => {
        const stateManager = makeStateManager();

        stateManager.updateKillZones([{
            id: 1,
            floor_id: 2,
            color: '#ff0000',
            enemies: [10, 11],
            layer: {getLatLng: () => ({lat: 1.5, lng: 2.5})},
        }]);

        expect(stateManager.killZones).toEqual([{
            id: 1,
            floor_id: 2,
            color: '#ff0000',
            killzonenemies: [{enemy_id: 10}, {enemy_id: 11}],
            lat: 1.5,
            lng: 2.5,
        }]);
    });

    test('updateKillZones_givenAKillZoneWithoutALayer_sendsNullCoordinates', () => {
        const stateManager = makeStateManager();

        stateManager.updateKillZones([{id: 1, floor_id: 2, color: '#ff0000', enemies: [], layer: null}]);

        expect(stateManager.killZones[0].lat).toBeNull();
        expect(stateManager.killZones[0].lng).toBeNull();
        expect(stateManager.killZones[0].killzonenemies).toEqual([]);
    });

    test('updateKillZones_givenAnEmptyList_clearsThePreviousOne', () => {
        const stateManager = makeStateManager();
        stateManager.updateKillZones([{id: 1, floor_id: 2, color: '#ff0000', enemies: [], layer: null}]);

        stateManager.updateKillZones([]);

        expect(stateManager.killZones).toEqual([]);
    });
});

describe('StateManager.getPullGradientHandlers', () => {
    test('getPullGradientHandlers_givenAStoredGradient_parsesThePercentagesAndColors', () => {
        const stateManager = makeStateManager(makeFakeMapContext({pullGradient: '0% #ff0000, 100% #00ff00'}));

        expect(stateManager.getPullGradientHandlers()).toEqual([[0, '#ff0000'], [100, '#00ff00']]);
    });

    test('getPullGradientHandlers_givenNoStoredGradient_returnsTheDefaults', () => {
        const stateManager = makeStateManager(makeFakeMapContext({pullGradient: ''}));

        expect(stateManager.getPullGradientHandlers()).toBe(c.map.editsidebar.pullGradient.defaultHandlers);
    });

    test('getPullGradientHandlers_givenAHandlerWithoutAColor_skipsIt', () => {
        const stateManager = makeStateManager(makeFakeMapContext({pullGradient: '0% #ff0000, 50% notacolor'}));
        const consoleWarn = vi.spyOn(console, 'warn').mockImplementation(() => {
        });

        expect(stateManager.getPullGradientHandlers()).toEqual([[0, '#ff0000']]);
        expect(consoleWarn).toHaveBeenCalled();
    });
});

describe('StateManager echo state', () => {
    test('isEchoEnabled_givenAFreshStateManager_returnsFalse', () => {
        expect(makeStateManager().isEchoEnabled()).toBe(false);
    });

    test('enableLaravelEcho_givenAnAppKey_enablesEchoAndStoresTheKey', () => {
        const stateManager = makeStateManager();

        stateManager.enableLaravelEcho('app-key');

        expect(stateManager.isEchoEnabled()).toBe(true);
        expect(stateManager.getLaravelEchoAppKey()).toBe('app-key');
    });
});

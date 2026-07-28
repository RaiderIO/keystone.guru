// EnemyVisual#_cleanupCircleMenu is the only place that ends RaidMarkerSelectMapState, so it must run
// even when the circle menu's DOM is already gone (#3703).
//
// The radial lives inside the enemy's divIcon, which is destroyed the moment the enemy's layer is
// removed from the map - a floor switch does that for every enemy on the old floor, and deliberately
// keeps the map state. The fade-out path resolved the radial with a jQuery selector and called
// .delay().queue() on it, which is a silent no-op on an empty set: the map state then stayed active
// for the rest of the session, suppressing every enemy tooltip on the map.
//
// The mirror image matters just as much: the user can start another map state while the menu is open
// ("New pull" leaves the radial on screen), so a cleanup triggered by the enemy being hidden must not
// clear whatever state is running then - and neither must the re-open that follows a rebuild (#3723).
//
// Follows the load-time-stub recipe from models/killzone.test.js. Most tests here call the method off
// the prototype with a fake `this`, since EnemyVisual's constructor pulls in the whole
// EnemyVisualMain* hierarchy; the ones covering what that constructor wires up stub setVisualType()
// instead, which is the only part of it that needs that hierarchy.
//
// What the circle menu plugin's own jQuery state does on a detached radial - the reason cleaning up
// off `_circleMenu` rather than off a selector matters at all - is covered against real jQuery and
// the real plugin in enemyvisual.circlemenu.test.js.

global.Signalable = class Signalable {
};

// Collaborators the constructor asserts on. The fakes below are instances of these, so the assertions
// hold and the console stays quiet.
global.DungeonMap = class DungeonMap {
};
global.Enemy = class Enemy {
};
global.L = {
    Layer: class Layer {
    },
    divIcon: class divIcon {
    },
};

// The real chain, so the `instanceof RaidMarkerSelectMapState` guards are the real ones.
const {MapState} = require('../mapstate/mapstate');
global.MapState = MapState;
const {MapObjectMapState} = require('../mapstate/mapobjectmapstate');
global.MapObjectMapState = MapObjectMapState;
const {RaidMarkerSelectMapState} = require('../mapstate/raidmarkerselectmapstate');
global.RaidMarkerSelectMapState = RaidMarkerSelectMapState;

const {EnemyVisual} = require('./enemyvisual');

// Called at the top of _cleanupCircleMenu.
global.removeStrayTooltips = () => {
};

/**
 * A stand-in for the jQuery set the circle menu plugin hands back. It always holds exactly one
 * element - the plugin's set does not shrink when the enemy's icon is removed from the DOM - so
 * whether that element is still attached is what decides the fade-out.
 */
function makeFakeSet(attached) {
    const element = document.createElement('ul');
    if (attached) {
        document.body.appendChild(element);
    }

    const set = {
        length: 1,
        0: element,
        delayCalls: 0,
        queueCalls: 0,
        delay() {
            set.delayCalls++;

            return set;
        },
        queue() {
            set.queueCalls++;

            return set;
        },
        find() {
            return {
                each() {
                },
            };
        },
        remove() {
            element.remove();

            return set;
        },
        dequeue() {
        },
    };

    return set;
}

/**
 * A map state instance without running its constructor, which asserts on collaborators (DungeonMap,
 * MapObject) these tests have no use for. Only its type matters here.
 */
function makeMapState(cls) {
    return Object.create(cls.prototype);
}

/**
 * Builds the collaborators _cleanupCircleMenu touches: an open circle menu, an enemy with an id, and a
 * map recording what it was asked to set. `this` is created off the prototype so the method's
 * `console.assert(this instanceof EnemyVisual)` holds.
 */
function makeCleanupContext(radialAttached, activeMapState = makeMapState(RaidMarkerSelectMapState)) {
    const radial = makeFakeSet(radialAttached);
    const setMapStateCalls = [];
    const signals = [];

    const self = Object.create(EnemyVisual.prototype);
    self._circleMenu = radial;
    self.enemy = {id: 42};
    self.map = {
        getMapState: () => activeMapState,
        setMapState: (mapState) => setMapStateCalls.push(mapState),
    };
    self.signal = (name) => signals.push(name);

    return {self, radial, setMapStateCalls, signals};
}

describe('EnemyVisual#_cleanupCircleMenu (#3703)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('_cleanupCircleMenu_givenTheRadialDomIsGone_cleansUpWithoutWaitingForTheFadeOut', () => {
        // Arrange: the enemy was hidden (floor switch), taking the radial's DOM with it
        const {self, radial, setMapStateCalls} = makeCleanupContext(false);

        // Act: the default fade-out path
        const result = EnemyVisual.prototype._cleanupCircleMenu.call(self, true);

        // Assert: nothing to animate, so the plugin's handlers come off and the map state ends now
        // rather than 500ms from now - long enough for a pending open hook to start a state for a
        // menu nobody can see
        expect(result).toBe(true);
        expect(radial.delayCalls).toBe(0);
        expect(radial.queueCalls).toBe(0);
        expect(setMapStateCalls).toEqual([null]);
        expect(self._circleMenu).toBeNull();
    });

    test('_cleanupCircleMenu_givenTheRadialIsStillThere_fadesOutBeforeEndingTheMapState', () => {
        // Arrange
        const {self, radial, setMapStateCalls} = makeCleanupContext(true);

        // Act
        EnemyVisual.prototype._cleanupCircleMenu.call(self, true);

        // Assert: the 500ms fade-out is preserved - the state ends only when the queue runs
        expect(radial.delayCalls).toBe(1);
        expect(radial.queueCalls).toBe(1);
        expect(setMapStateCalls).toEqual([]);
        expect(self._circleMenu).not.toBeNull();
    });

    test('_cleanupCircleMenu_givenNoFadeOut_endsTheMapStateImmediately', () => {
        // Arrange
        const {self, radial, setMapStateCalls, signals} = makeCleanupContext(true);

        // Act: what buildVisual() and the hidden branch of the shown/hidden handler use
        EnemyVisual.prototype._cleanupCircleMenu.call(self, false);

        // Assert
        expect(radial.delayCalls).toBe(0);
        expect(setMapStateCalls).toEqual([null]);
        expect(signals).toContain('circlemenu:close');
    });

    test('_cleanupCircleMenu_givenAnotherMapStateWasStarted_leavesItRunning', () => {
        // Arrange: the menu stayed on screen while the user hit "New pull", then the enemy was hidden
        const pullBuilding = makeMapState(MapObjectMapState);
        const {self, setMapStateCalls, signals} = makeCleanupContext(false, pullBuilding);

        // Act
        EnemyVisual.prototype._cleanupCircleMenu.call(self, false);

        // Assert: the menu is cleaned up, but the selection the user is in the middle of survives
        expect(self._circleMenu).toBeNull();
        expect(signals).toContain('circlemenu:close');
        expect(setMapStateCalls).toEqual([]);
    });

    test('_cleanupCircleMenu_givenNoOpenCircleMenu_doesNothing', () => {
        // Arrange
        const {self, setMapStateCalls} = makeCleanupContext(false);
        self._circleMenu = null;

        // Act
        const result = EnemyVisual.prototype._cleanupCircleMenu.call(self, false);

        // Assert: hiding an enemy that never had a menu open must not clear an unrelated map state
        expect(result).toBe(false);
        expect(setMapStateCalls).toEqual([]);
    });
});

// ---------------------------------------------------------------------------
// Rebuilding the visual re-opens a circle menu that was open, and re-opening it starts a
// RaidMarkerSelectMapState. Several of the map states that trigger that rebuild in the first place
// (EditMapState, DeleteMapState, the EnemySelection states) would therefore be replaced by a raid
// marker selection the user never asked for (#3723).
// ---------------------------------------------------------------------------

global.MAP_OBJECT_GROUP_ENEMY = 'enemy';
global.KillZone = class KillZone {
};
global.EditMapState = class EditMapState extends MapState {
};
global.DeleteMapState = class DeleteMapState extends MapState {
};
global.EnemySelection = class EnemySelection extends MapState {
    // Matching the real base, which the states that draw a selection border override.
    drawsEnemyEditBorder() {
        return false;
    }
};
global.Handlebars = {templates: {map_enemy_visual_template: () => '<div></div>'}};
global.c = {map: {enemy: {calculateMargin: () => 4}}};
global.getState = () => ({
    getEnemyDisplayType: () => 'enemy_forces',
    getMapZoomLevel: () => 1,
    getUnkilledEnemyOpacity: () => 50,
    getUnkilledImportantEnemyOpacity: () => 100,
});

// buildVisual() merges its template data with $.extend.
global.$ = Object.assign(() => makeFakeSet(true), {
    extend: (target, ...sources) => Object.assign(target, ...sources),
});

/**
 * An EnemyVisual whose circle menu was open when the rebuild started. Everything buildVisual() does
 * beyond deciding whether to put that menu back - resolving jQuery selectors, sizing the icon,
 * creating modifiers - is stubbed out on the instance.
 */
function makeBuildVisualContext(activeMapState) {
    const initCalls = [];

    const self = Object.create(EnemyVisual.prototype);
    self._highlighted = false;
    self._modifiers = [];
    self.enemy = {
        id: 42,
        is_mdt: false,
        getKillZone: () => null,
        getOverpulledKillZoneId: () => null,
        isObsolete: () => false,
        isImportant: () => false,
        isDeletable: () => false,
        isSelectable: () => false,
        isEditable: () => false,
    };
    self.layer = {
        setIcon: () => {
        },
    };
    self.mainVisual = {
        _getTemplateData: () => ({}),
        getSize: () => ({iconSize: [30, 30]}),
    };
    self.map = {
        getMapState: () => activeMapState,
        mapObjectGroupManager: {getByName: () => ({isMapObjectVisible: () => true})},
    };
    self.signal = () => {
    };

    // The menu was open: report it as cleaned up, then record whether it is put back.
    self._cleanupCircleMenu = () => true;
    self._initCircleMenu = (fadeIn) => initCalls.push(fadeIn);
    self._createModifiers = () => [];
    self.refreshJQuerySelectors = () => {
    };
    self.refreshSize = () => {
    };

    return {self, initCalls};
}

describe('EnemyVisual#buildVisual circle menu re-open (#3723)', () => {
    test('buildVisual_givenNoMapState_reopensTheCircleMenu', () => {
        // Arrange: the rebuild came from something that leaves no map state behind (a killzone
        // attach, an enemy being marked obsolete)
        const {self, initCalls} = makeBuildVisualContext(null);

        // Act
        EnemyVisual.prototype.buildVisual.call(self);

        // Assert: back on screen, without fading in a second time
        expect(initCalls).toEqual([false]);
    });

    test('buildVisual_givenTheUserStartedAnotherMapState_leavesTheCircleMenuClosed', () => {
        // Arrange: the menu was open when the user hit "Edit", which rebuilds every enemy visual
        const {self, initCalls} = makeBuildVisualContext(new global.EditMapState());

        // Act
        EnemyVisual.prototype.buildVisual.call(self);

        // Assert: re-opening would have replaced EditMapState with a RaidMarkerSelectMapState
        expect(initCalls).toEqual([]);
    });

    test('buildVisual_givenAPullIsBeingBuilt_leavesTheCircleMenuClosed', () => {
        // Arrange: the most common way to get here - attaching the enemy to a killzone rebuilds its
        // visual while the enemy selection for that pull is still running
        const {self, initCalls} = makeBuildVisualContext(new global.EnemySelection());

        // Act
        EnemyVisual.prototype.buildVisual.call(self);

        // Assert
        expect(initCalls).toEqual([]);
    });

    test('buildVisual_givenReopeningIsNotWanted_leavesTheCircleMenuClosed', () => {
        // Arrange: assigning a raid marker rebuilds the visual with the menu deliberately closed
        const {self, initCalls} = makeBuildVisualContext(null);

        // Act
        EnemyVisual.prototype.buildVisual.call(self, false);

        // Assert
        expect(initCalls).toEqual([]);
    });

    test('_canReopenCircleMenu_givenOurOwnMapState_returnsTrue', () => {
        // Arrange: a cleanup that ran while the menu's own state was active leaves it in place, so
        // the re-open has to accept it too
        const self = Object.create(EnemyVisual.prototype);
        self.map = {getMapState: () => makeMapState(RaidMarkerSelectMapState)};

        // Act & Assert
        expect(EnemyVisual.prototype._canReopenCircleMenu.call(self)).toBe(true);
    });
});

// ---------------------------------------------------------------------------
// The hidden branch of the shown/hidden handler is what makes the cleanup happen on a floor switch at
// all. It is wired up in the constructor, so covering it means running that constructor (#3723).
// ---------------------------------------------------------------------------

/**
 * A real EnemyVisual, with setVisualType() - the one part of the constructor that needs the whole
 * EnemyVisualMain* hierarchy - stubbed out. Also returns the callbacks it registered on the enemy.
 */
function makeVisual() {
    const handlers = {};

    const enemy = Object.assign(new global.Enemy(), {
        id: 42,
        shouldBeVisible: () => true,
        isVisible: () => false,
        register: (events, context, callback) => {
            for (const event of [].concat(events)) {
                handlers[event] = callback;
            }
        },
        unregister: () => {
        },
    });
    const map = Object.assign(new global.DungeonMap(), {
        getMapState: () => null,
        register: () => {
        },
        unregister: () => {
        },
    });

    vi.spyOn(EnemyVisual.prototype, 'setVisualType').mockImplementation(() => {
    });

    const visual = new EnemyVisual(map, enemy, new global.L.Layer());

    return {visual, handlers};
}

describe('EnemyVisual shown/hidden handling (#3723)', () => {
    test('constructor_givenTheEnemyIsHidden_cleansUpTheCircleMenuWithoutFadingOut', () => {
        // Arrange
        const {visual, handlers} = makeVisual();
        const cleanupCalls = [];
        visual._cleanupCircleMenu = (fadeOut) => cleanupCalls.push(fadeOut);

        // Act: a floor switch hides every enemy on the old floor, destroying the radial's DOM
        handlers.hidden({data: {visible: false}});

        // Assert: nothing is left to animate out, and the cleanup must not be skipped - it is the
        // only thing that can end a RaidMarkerSelectMapState
        expect(cleanupCalls).toEqual([false]);
    });

    test('constructor_givenTheEnemyIsShownWithoutAVisual_buildsItInsteadOfCleaningUp', () => {
        // Arrange
        const {visual, handlers} = makeVisual();
        const cleanupCalls = [];
        let buildCalls = 0;
        visual._cleanupCircleMenu = (fadeOut) => cleanupCalls.push(fadeOut);
        visual.buildVisual = () => buildCalls++;

        // Act
        handlers.shown({data: {visible: true}});

        // Assert
        expect(buildCalls).toBe(1);
        expect(cleanupCalls).toEqual([]);
    });

    test('cleanup_givenAnOpenCircleMenu_cleansItUpWithoutFadingOut', () => {
        // Arrange
        const {visual} = makeVisual();
        const cleanupCalls = [];
        visual._cleanupCircleMenu = (fadeOut) => cleanupCalls.push(fadeOut);

        // Act: the visual is being discarded (a layer swap, the map being torn down)
        visual.cleanup();

        // Assert: a queued cleanup would call setMapState(null) 500ms later on behalf of a visual
        // that no longer exists - and it has already unregistered from map:mapstatechanged by then
        expect(cleanupCalls).toEqual([false]);
    });
});

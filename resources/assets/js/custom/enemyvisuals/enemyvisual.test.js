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
// clear whatever state is running then.
//
// Follows the load-time-stub recipe from models/killzone.test.js. EnemyVisual's constructor pulls in
// the whole EnemyVisualMain* hierarchy, so these tests call the method off the prototype with a fake
// `this` instead of building one.

global.Signalable = class Signalable {
};

// The real chain, so the `instanceof RaidMarkerSelectMapState` guard is the real one.
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
 * A stand-in for a jQuery result set. `found` false models a selector that matched nothing, which is
 * what happens once the enemy's icon (and with it the radial) has been removed from the DOM.
 */
function makeFakeSet(found) {
    const set = {
        length: found ? 1 : 0,
        0: found ? {} : undefined,
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
            return {
                dequeue() {
                },
            };
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
function makeCleanupContext(radialFound, activeMapState = makeMapState(RaidMarkerSelectMapState)) {
    const radial = makeFakeSet(radialFound);
    const setMapStateCalls = [];
    const signals = [];

    const self = Object.create(EnemyVisual.prototype);
    self._circleMenu = {};
    self.enemy = {id: 42};
    self.map = {
        getMapState: () => activeMapState,
        setMapState: (mapState) => setMapStateCalls.push(mapState),
    };
    self.signal = (name) => signals.push(name);

    // The method resolves the radial through `$('#map_enemy_raid_marker_radial_<id>')` and then wraps
    // raw elements with `$(element)` inside its cleanup callback.
    global.$ = (selectorOrElement) => (typeof selectorOrElement === 'string' ? radial : makeFakeSet(true));

    return {self, radial, setMapStateCalls, signals};
}

describe('EnemyVisual#_cleanupCircleMenu (#3703)', () => {
    test('_cleanupCircleMenu_givenTheRadialDomIsGone_stillEndsTheMapState', () => {
        // Arrange: the enemy was hidden (floor switch), taking the radial's DOM with it
        const {self, radial, setMapStateCalls} = makeCleanupContext(false);

        // Act: the default fade-out path
        const result = EnemyVisual.prototype._cleanupCircleMenu.call(self, true);

        // Assert: cleaned up synchronously instead of queueing onto an empty set
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

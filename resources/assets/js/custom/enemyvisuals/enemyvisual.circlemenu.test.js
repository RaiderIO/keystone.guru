// Integration coverage for the raid marker circle menu's teardown (#3723).
//
// Unlike enemyvisual.test.js - which hands _cleanupCircleMenu() a hand-rolled jQuery stand-in to pin
// its guards - these tests run the real jQuery and the real zikes-circlemenu plugin against a real
// (jsdom) DOM, because both findings are about the plugin's own jQuery state on a *detached* node:
//
//  - The plugin registers its `open` hook as a jQuery handler on the radial <ul> itself and fires it
//    from a setTimeout. Leaflet destroys the enemy's icon with a native removeChild, which does not
//    run jQuery's cleanData, so that handler survives the enemy being hidden. Cleaning up by
//    re-querying `#map_enemy_raid_marker_radial_<id>` matched nothing at that point, leaving the
//    pending timer free to start a RaidMarkerSelectMapState for a menu that no longer exists - with
//    _circleMenu already nulled, nothing could ever end that state again.
//  - The same re-query made the Bootstrap Tooltip disposal loop a no-op, leaking one instance per
//    tooltip item (Bootstrap keys them by element in a plain Map, not a WeakMap).
//
// Both are fixed by cleaning up off `self._circleMenu`, which *is* the plugin's own jQuery set and
// stays valid on a detached node.

// Real jQuery, replacing the minimal stub from test/setup.js. Must be global before the plugin is
// required: it is an IIFE closing over the `jQuery` global.
global.$ = global.jQuery = require('jquery');
require('zikes-circlemenu');

const fs = require('fs');
const path = require('path');
const HandlebarsRuntime = require('handlebars');

global.Signalable = class Signalable {
};

// Stand-ins for the collaborators the map state constructors assert on. The fakes below are built as
// instances of these so those assertions hold.
global.DungeonMap = class DungeonMap {
};
global.MapObject = class MapObject {
};

// The real chain, so the map state the menu starts is the real one.
const {MapState} = require('../mapstate/mapstate');
global.MapState = MapState;
const {MapObjectMapState} = require('../mapstate/mapobjectmapstate');
global.MapObjectMapState = MapObjectMapState;
const {RaidMarkerSelectMapState} = require('../mapstate/raidmarkerselectmapstate');
global.RaidMarkerSelectMapState = RaidMarkerSelectMapState;

const {EnemyVisual} = require('./enemyvisual');

// The real radial markup, so the tooltip items the disposal loop looks for are the real ones.
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
global.c = {map: {enemy: {calculateMargin: () => 4}}};

/**
 * Opens the circle menu on an enemy the way _openRaidMarkerMenu() does, without advancing any timers:
 * the plugin fires its `open` hook - and with it setMapState() - from a setTimeout, so on return the
 * menu is on screen but no map state has started yet.
 */
function makeOpenCircleMenu() {
    const id = 42;

    document.body.innerHTML = `<div id="map_enemy_visual_${id}"><div class="enemy_icon"></div></div>`;
    const container = document.getElementById(`map_enemy_visual_${id}`);

    const setMapStateCalls = [];
    const signals = [];
    let mapState = null;

    const self = Object.create(EnemyVisual.prototype);
    self._circleMenu = null;
    self.enemy = Object.assign(new global.MapObject(), {id: id});
    self.mainVisual = {getSize: () => ({iconSize: [30, 30]})};
    self.map = Object.assign(new global.DungeonMap(), {
        getMapState: () => mapState,
        setMapState: (newMapState) => {
            mapState = newMapState;
            setMapStateCalls.push(newMapState);
        },
    });
    self.signal = (name) => signals.push(name);

    EnemyVisual.prototype._initCircleMenu.call(self);

    // Bootstrap Tooltip instances as they exist once refreshTooltips() has run over the new radial.
    const tooltips = [...container.querySelectorAll('[data-bs-toggle="tooltip"]')].map((element) => {
        const tooltip = {element: element, disposed: false, dispose: () => (tooltip.disposed = true)};

        return tooltip;
    });
    global.bootstrap = {
        Tooltip: {
            getInstance: (element) => tooltips.find((tooltip) => tooltip.element === element) ?? null,
        },
    };

    return {self, container, setMapStateCalls, signals, tooltips};
}

describe('EnemyVisual circle menu teardown (#3723)', () => {
    beforeEach(() => {
        vi.useFakeTimers();
    });

    afterEach(() => {
        vi.useRealTimers();
        document.body.innerHTML = '';
    });

    test('_initCircleMenu_givenTheMenuFinishesOpening_startsTheRaidMarkerSelectMapState', () => {
        // Arrange
        const {setMapStateCalls} = makeOpenCircleMenu();
        expect(setMapStateCalls).toEqual([]);

        // Act: the plugin's open hook only fires a macrotask later
        vi.advanceTimersByTime(1000);

        // Assert
        expect(setMapStateCalls).toHaveLength(1);
        expect(setMapStateCalls[0]).toBeInstanceOf(RaidMarkerSelectMapState);
    });

    test('_cleanupCircleMenu_givenTheEnemyIsHiddenBeforeTheMenuOpens_neverStartsTheMapState', () => {
        // Arrange: the menu is on screen, its open hook still pending
        const {self, container, setMapStateCalls, signals} = makeOpenCircleMenu();

        // Act: Leaflet drops the enemy's icon (a floor switch, a faction toggle, an Echo update). A
        // native removal, so the radial keeps its jQuery handlers - then the hidden branch cleans up.
        container.remove();
        EnemyVisual.prototype._cleanupCircleMenu.call(self, false);
        vi.advanceTimersByTime(1000);

        // Assert: no map state was ever started, so nothing is left that only the (now gone) menu
        // could have ended
        expect(setMapStateCalls).toEqual([]);
        expect(self._circleMenu).toBeNull();
        expect(signals).toContain('circlemenu:close');
        expect(signals).not.toContain('circlemenu:open');
    });

    test('_cleanupCircleMenu_givenTheRadialWasAlreadyDetached_stillDisposesItsTooltips', () => {
        // Arrange
        const {self, container, tooltips} = makeOpenCircleMenu();
        expect(tooltips).not.toHaveLength(0);

        // Act
        container.remove();
        EnemyVisual.prototype._cleanupCircleMenu.call(self, false);
        vi.advanceTimersByTime(1000);

        // Assert: Bootstrap keys instances by element in a plain Map, so skipping this leaks one per
        // tooltip item for the rest of the session
        expect(tooltips.every((tooltip) => tooltip.disposed)).toBe(true);
    });

    test('_cleanupCircleMenu_givenTheRadialIsStillOnScreen_fadesOutBeforeRemovingIt', () => {
        // Arrange: the menu opened normally, so a map state of ours is running
        const {self, setMapStateCalls} = makeOpenCircleMenu();
        vi.advanceTimersByTime(1000);
        expect(document.getElementById('map_enemy_raid_marker_radial_42')).not.toBeNull();

        // Act: the default fade-out path, as used by the close and select callbacks
        EnemyVisual.prototype._cleanupCircleMenu.call(self, true);

        // Assert: still on screen while it animates out
        expect(document.getElementById('map_enemy_raid_marker_radial_42')).not.toBeNull();
        expect(setMapStateCalls).toHaveLength(1);

        // Act: once the 500ms fade-out has run
        vi.advanceTimersByTime(1000);

        // Assert
        expect(document.getElementById('map_enemy_raid_marker_radial_42')).toBeNull();
        expect(self._circleMenu).toBeNull();
        expect(setMapStateCalls[1]).toBeNull();
    });

    test('_cleanupCircleMenu_givenARebuildLandsWhileTheMenuIsFadingOut_doesNotReportAReopenableMenu', () => {
        // Arrange: the menu opened normally, then the user closed it - the close/select callback's
        // default fadeOut=true call, which queues cleanupFn 500ms out and leaves _circleMenu non-null
        // in the meantime
        const {self, setMapStateCalls} = makeOpenCircleMenu();
        vi.advanceTimersByTime(1000);
        EnemyVisual.prototype._cleanupCircleMenu.call(self, true);
        expect(setMapStateCalls).toHaveLength(1);

        // Act: a rebuild lands inside that 500ms window - killzone:attached, overpulled:changed, an
        // Echo object:changed - and buildVisual() calls this exact form to tear down before rebuilding
        const hadCircleMenu = EnemyVisual.prototype._cleanupCircleMenu.call(self, false);

        // Assert: not reported as an interrupted, reopenable menu (#3730) - the menu the user just
        // dismissed must not be treated as one buildVisual() should put back afterwards. Nothing about
        // the already-queued fade-out is disturbed either.
        expect(hadCircleMenu).toBe(false);
        expect(document.getElementById('map_enemy_raid_marker_radial_42')).not.toBeNull();
        expect(setMapStateCalls).toHaveLength(1);

        // Act: the original fade-out still completes on its own
        vi.advanceTimersByTime(1000);

        // Assert
        expect(document.getElementById('map_enemy_raid_marker_radial_42')).toBeNull();
        expect(self._circleMenu).toBeNull();
        expect(setMapStateCalls[1]).toBeNull();
    });

    test('_cleanupCircleMenu_givenTheRebuildDetachesTheRadialWhileClosing_stillEndsTheMapStateLater', () => {
        // Arrange: the menu opened normally, then the user closed it, queuing the 500ms fade-out
        const {self, container, setMapStateCalls} = makeOpenCircleMenu();
        vi.advanceTimersByTime(1000);
        EnemyVisual.prototype._cleanupCircleMenu.call(self, true);
        expect(setMapStateCalls).toHaveLength(1);

        // Act: a rebuild lands inside that window - the no-op call from #3730 above - and then goes
        // on to replace the enemy's icon HTML entirely (buildVisual() -> layer.setIcon()), which
        // detaches the still-fading radial from the document exactly like Leaflet destroying the
        // enemy's icon natively does elsewhere (#3723)
        EnemyVisual.prototype._cleanupCircleMenu.call(self, false);
        container.remove();

        // Act: the original fade-out's queue still runs against the now-detached radial
        vi.advanceTimersByTime(1000);

        // Assert: cleanupFn must not have been skipped just because its target left the document -
        // the plugin's own jQuery set (self._circleMenu) stays valid regardless (#3723), so the map
        // state this menu started still gets ended, exactly once
        expect(self._circleMenu).toBeNull();
        expect(setMapStateCalls).toHaveLength(2);
        expect(setMapStateCalls[1]).toBeNull();
    });
});

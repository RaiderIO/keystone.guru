// `killzonessidebar.js` references its collaborators as bare globals in the browser bundle, so
// they are stubbed on `globalThis` before the class body is evaluated. The map is a stub too.
const jQuery = require('jquery');
const _      = require('lodash');

const {InlineCode}    = require('../../inlinecode');
globalThis.InlineCode = InlineCode;

class KillZone {
    constructor(id, index) {
        this.id    = id;
        this.index = index;
    }
}

class MapState {
    constructor(map, mapObject) {
        this.map       = map;
        this.mapObject = mapObject;
    }

    getMapObject() {
        return this.mapObject;
    }
}

class SelectKillZoneEnemySelectionOverpull extends MapState {}

class EditKillZoneEnemySelection extends MapState {}

class ViewKillZoneEnemySelection extends MapState {}

class MapContextLiveSession {}

globalThis.KillZone                            = KillZone;
globalThis.SelectKillZoneEnemySelectionOverpull = SelectKillZoneEnemySelectionOverpull;
globalThis.EditKillZoneEnemySelection          = EditKillZoneEnemySelection;
globalThis.ViewKillZoneEnemySelection          = ViewKillZoneEnemySelection;
globalThis.MapContextLiveSession               = MapContextLiveSession;
globalThis.Sidebar                             = class {};
globalThis.PullWorkBench                       = class {};

const {CommonMapsKillzonessidebar} = require('./killzonessidebar');

describe('CommonMapsKillzonessidebar hotkeys', () => {
    let previousJquery;
    let previousLodash;
    let previousGetState;
    let sidebar;
    let map;
    let killZones;

    /**
     * @param {Object} target
     * @param {Object} [init]
     * @returns {jQuery.Event}
     */
    function keyDown(target, init = {}) {
        const event = jQuery.Event('keydown', {key: 'd', ...init});
        jQuery(target).trigger(event);

        return event;
    }

    beforeEach(() => {
        previousJquery   = globalThis.$;
        previousLodash   = globalThis._;
        previousGetState = globalThis.getState;
        globalThis.$     = jQuery;
        globalThis._     = _;

        document.body.innerHTML = '<div id="map"></div><textarea id="notes"></textarea><div id="editable" contenteditable="true"></div><div id="row"></div>';

        // Deliberately not in index order.
        killZones = [new KillZone(12, 3), new KillZone(10, 1), new KillZone(11, 2)];
        map       = {
            options:              {edit: true},
            mapState:             null,
            popupOpen:            false,
            focused:              [],
            mapObjectGroupManager: {
                getKillZoneMapObjectGroup: () => ({objects: killZones}),
            },
            getMapState() {
                return this.mapState;
            },
            setMapState(mapState) {
                this.mapState = mapState;
            },
            focusOnKillZone(killZone) {
                this.focused.push(killZone);
            },
            hasPopupOpen() {
                return this.popupOpen;
            },
        };

        globalThis.getState = () => ({getMapContext: () => ({})});

        sidebar     = new CommonMapsKillzonessidebar('killzonessidebar', 'common/maps/killzonessidebar', {});
        sidebar.map = map;
        $(document).on('keydown.killzonessidebar', sidebar._onDocumentKeyDown.bind(sidebar));
    });

    afterEach(() => {
        $(document).off('keydown.killzonessidebar');
        globalThis.$        = previousJquery;
        globalThis._        = previousLodash;
        globalThis.getState = previousGetState;
    });

    describe('_getAdjacentKillZone', () => {
        test('_getAdjacentKillZone_givenNextOnLastPull_returnsNull', () => {
            // Arrange
            const last = killZones[0];

            // Act
            const result = sidebar._getAdjacentKillZone(last, 1);

            // Assert
            expect(result).toBeNull();
        });

        test('_getAdjacentKillZone_givenPreviousOnFirstPull_returnsNull', () => {
            // Arrange
            const first = killZones[1];

            // Act
            const result = sidebar._getAdjacentKillZone(first, -1);

            // Assert
            expect(result).toBeNull();
        });

        test('_getAdjacentKillZone_givenObjectsOutOfIndexOrder_returnsIndexNeighbour', () => {
            // Arrange
            const middle = killZones[2];

            // Act
            const next     = sidebar._getAdjacentKillZone(middle, 1);
            const previous = sidebar._getAdjacentKillZone(middle, -1);

            // Assert
            expect(next.index).toBe(3);
            expect(previous.index).toBe(1);
        });

        test('_getAdjacentKillZone_givenNoSelection_returnsFirstOrLast', () => {
            // Arrange / Act
            const next     = sidebar._getAdjacentKillZone(null, 1);
            const previous = sidebar._getAdjacentKillZone(null, -1);

            // Assert
            expect(next.index).toBe(1);
            expect(previous.index).toBe(3);
        });
    });

    describe('keydown handler', () => {
        test('keydown_givenDFromBody_selectsFirstPullThenNext', () => {
            // Arrange
            const target = document.getElementById('row');

            // Act
            keyDown(target, {key: 'D'});
            keyDown(target, {key: 'd'});

            // Assert
            expect(map.mapState).toBeInstanceOf(EditKillZoneEnemySelection);
            expect(map.mapState.getMapObject().index).toBe(2);
            expect(map.focused.map((killZone) => killZone.index)).toEqual([1, 2]);
        });

        test('keydown_givenAFromBody_selectsPreviousPull', () => {
            // Arrange
            map.mapState = new EditKillZoneEnemySelection(map, killZones[2]);

            // Act
            keyDown(document.body, {key: 'a'});

            // Assert
            expect(map.mapState.getMapObject().index).toBe(1);
        });

        test('keydown_givenBracketKeys_cyclesPulls', () => {
            // Arrange
            map.mapState = new EditKillZoneEnemySelection(map, killZones[2]);

            // Act
            keyDown(document.body, {key: ']'});
            keyDown(document.body, {key: '['});

            // Assert
            expect(map.mapState.getMapObject().index).toBe(2);
        });

        test('keydown_givenNonEditMap_usesViewMapState', () => {
            // Arrange
            map.options.edit = false;

            // Act
            keyDown(document.body);

            // Assert
            expect(map.mapState).toBeInstanceOf(ViewKillZoneEnemySelection);
        });

        test('keydown_givenLiveSession_usesOverpullMapState', () => {
            // Arrange
            globalThis.getState = () => ({getMapContext: () => new MapContextLiveSession()});

            // Act
            keyDown(document.body);

            // Assert
            expect(map.mapState).toBeInstanceOf(SelectKillZoneEnemySelectionOverpull);
        });

        test('keydown_givenScrollableRow_scrollsRowIntoView', () => {
            // Arrange
            const row = document.createElement('div');
            row.id    = 'map_killzonessidebar_killzone_10';
            row.scrollIntoView = vi.fn();
            document.body.appendChild(row);

            // Act
            keyDown(document.body);

            // Assert
            expect(row.scrollIntoView).toHaveBeenCalledWith({block: 'nearest'});
        });

        test.each([
            ['textarea', 'notes'],
            ['contenteditable', 'editable'],
        ])('keydown_givenFocusInA%s_doesNothing', (_label, id) => {
            // Arrange
            const target = document.getElementById(id);

            // Act
            keyDown(target);

            // Assert
            expect(map.mapState).toBeNull();
        });

        test.each([['ctrlKey'], ['metaKey'], ['altKey']])('keydown_given%sModifier_doesNothing', (modifier) => {
            // Arrange / Act
            keyDown(document.body, {[modifier]: true});

            // Assert
            expect(map.mapState).toBeNull();
        });

        test('keydown_givenOpenModal_doesNothing', () => {
            // Arrange
            document.body.insertAdjacentHTML('beforeend', '<div class="modal show"></div>');

            // Act
            keyDown(document.body);

            // Assert
            expect(map.mapState).toBeNull();
        });

        test('keydown_givenOpenPopup_doesNothing', () => {
            // Arrange
            map.popupOpen = true;

            // Act
            keyDown(document.body);

            // Assert
            expect(map.mapState).toBeNull();
        });

        test('keydown_givenOtherMapStateActive_doesNotCancelIt', () => {
            // Arrange
            const drawing = new MapState(map, null);
            map.mapState  = drawing;

            // Act
            keyDown(document.body);

            // Assert
            expect(map.mapState).toBe(drawing);
        });

        test('keydown_givenUnrelatedKey_doesNothing', () => {
            // Arrange / Act
            keyDown(document.body, {key: 'x'});

            // Assert
            expect(map.mapState).toBeNull();
        });

        test('cleanup_givenActivatedHandler_removesKeydownHandler', () => {
            // Arrange
            map.unregister                 = vi.fn();
            map.mapObjectGroupManager.getKillZoneMapObjectGroup = () => ({objects: killZones, unregister: vi.fn()});
            globalThis.getState            = () => ({getMapContext: () => ({}), unregister: vi.fn()});

            // Act
            sidebar.cleanup();
            keyDown(document.body);

            // Assert
            expect(map.mapState).toBeNull();
        });
    });
});

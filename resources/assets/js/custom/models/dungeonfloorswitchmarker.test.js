// ---------------------------------------------------------------------------
// Regression coverage for #4408, following the global-script model recipe
// documented at the top of killzone.test.js: define the globals
// dungeonfloorswitchmarker.js touches at LOAD time before require()-ing it,
// then hand the class fake collaborators instead of building a real DungeonMap.
//
// Attributes are assigned in the order MapObject#loadRemoteMapObject() walks
// _getAttributes(), and Icon's map_icon_type_id setter eagerly calls
// bindTooltip() as soon as it runs - before DungeonFloorSwitchMarker's own
// target_floor_id attribute (appended after the inherited Icon attributes) has
// been copied onto the instance. getDisplayText() then can't resolve the
// target floor, falls back to the 'unknown floor' label, and Leaflet's
// bindTooltip() caches that text on the layer - nothing rebinds it afterward,
// so the tooltip is stuck showing "Unknown" even though the marker does have a
// valid target floor.
//
// The fix (mirroring Enemy's #3670 fix) rebinds the tooltip once more when the
// object:initialized signal fires, i.e. once loadRemoteMapObject() has finished
// walking every attribute.
// ---------------------------------------------------------------------------

// 1a. Leaflet: dungeonfloorswitchmarker.js builds div icons and draw handlers at load time.
global.L = {
    divIcon: function () {
    },
    Marker: {extend: () => function () {}},
    Draw: {
        Marker: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
};

// 1b. The shared setup's `$` stub has no extend().
global.$.extend = Object.assign;

// 1c. The mapping editor's map context, which shouldBeVisible() and the icon choice test with instanceof, and the
// facade style constant the fake state's style cookie reports.
global.MapContextMappingVersionEdit = class MapContextMappingVersionEdit {
};
global.MAP_FACADE_STYLE_FACADE = 'facade';

// 1d. Lightweight immediate base class standing in for Icon, providing only what
// DungeonFloorSwitchMarker calls on `super`/`this`. register/unregister/signal are a real,
// working pub-sub (unlike a no-op fake) because the fix under test relies on the
// 'object:initialized' signal actually reaching its listener. bindTooltip() mirrors the real
// Icon#bindTooltip (icon.js): unbind first, then compute+cache the text via getDisplayText().
global.Icon = class Icon {
    constructor(map, layer = null, options = {}) {
        this.map = map;
        this.layer = layer;
        this.options = options;
        this.id = 0;
        this._cachedAttributes = null;
        this._signals = [];
        this.comment = null;
        this.map_icon_type = {id: 1, name: 'Floor switch'};
    }

    register(name, listener, fn) {
        this._signals.push({name, listener, fn});
    }

    unregister(name, listener) {
        this._signals = this._signals.filter((s) => !(s.name === name && s.listener === listener));
    }

    signal(name, data = {}) {
        for (const s of this._signals.slice()) {
            if (s.name === name) {
                s.fn({name, context: this, data});
            }
        }
    }

    unbindTooltip() {
        this.layer.unbindTooltip();
    }

    getTooltipOptions() {
        return {};
    }

    setMapIconType(mapIconType) {
        this.map_icon_type = mapIconType;
    }

    _refreshVisual() {
        this.refreshVisualCount = (this.refreshVisualCount ?? 0) + 1;
    }

    shouldBeVisible() {
        return true;
    }

    bindTooltip() {
        this.unbindTooltip();

        if ((this.comment !== null && this.comment.length > 0) ||
            (this.map_icon_type !== null && this.map_icon_type.name.length > 0)) {
            let text = lang.get(this.getDisplayText());

            if (text.length > 0) {
                this.layer.bindTooltip(text, this.getTooltipOptions());
            }
        }
    }

    cleanup() {
    }
};

const {DungeonFloorSwitchMarker} = require('./dungeonfloorswitchmarker');

/**
 * A fake Leaflet layer mirroring Leaflet's own tooltip semantics: getTooltip() is undefined before
 * the first bind and null after unbindTooltip(). Records how often bindTooltip() was called.
 */
function makeFakeLayer() {
    return {
        _tooltip: undefined,
        bindCount: 0,
        getTooltip() {
            return this._tooltip;
        },
        bindTooltip(text) {
            this._tooltip = {text};
            this.bindCount++;
        },
        unbindTooltip() {
            this._tooltip = null;
        },
    };
}

/**
 * A fake DungeonMap exposing only what DungeonFloorSwitchMarker touches.
 */
function makeFakeMap(dungeonFloorSwitchMarkersById = {}) {
    return {
        options: {edit: false},
        register: () => {},
        unregister: () => {},
        getMapState: () => null,
        mapObjectGroupManager: {
            getDungeonFloorSwitchMarkerMapObjectGroup: () => ({
                findMapObjectById: (id) => dungeonFloorSwitchMarkersById[id] ?? null,
            }),
        },
    };
}

/**
 * A fake global state, mirroring statemanager.js just enough for getDisplayText()/bindTooltip() to
 * run. `floorsById` starts empty, so target_floor_id lookups fail until a test populates it -
 * matching the marker not knowing its target floor yet while attributes are still loading.
 */
function makeFakeState(floorsById = {}) {
    return {
        register: () => {},
        unregister: () => {},
        isEchoEnabled: () => false,
        isCurrentDungeonFacadeEnabled: () => false,
        getMapContext: () => ({
            getFloorById: (id) => (Object.prototype.hasOwnProperty.call(floorsById, id) ? floorsById[id] : false),
            getMapIconTypeByKey: (key) => ({key: key, name: 'Floor switch'}),
        }),
    };
}

describe('DungeonFloorSwitchMarker tooltip (#4408)', () => {
    test('bindTooltip_givenBoundBeforeTargetFloorIdLoaded_showsUnknownLabel', () => {
        // Arrange: target_floor_id has not been assigned yet, mirroring the point mid-attribute-load
        // where Icon's map_icon_type_id setter eagerly triggers a tooltip bind.
        const state = makeFakeState();
        global.getState = () => state;
        const layer = makeFakeLayer();
        const marker = new DungeonFloorSwitchMarker(makeFakeMap(), layer);

        // Act
        marker.bindTooltip();

        // Assert: falls back to the 'unknown floor' label
        expect(layer.getTooltip().text).toBe('js.dungeonfloorswitchmarker_unknown_label');
    });

    test('bindTooltip_givenObjectInitializedFiresAfterTargetFloorIdLoaded_correctsTheTooltip', () => {
        // Arrange: same early, wrong bind as above
        const floorsById = {};
        const state = makeFakeState(floorsById);
        global.getState = () => state;
        const layer = makeFakeLayer();
        const marker = new DungeonFloorSwitchMarker(makeFakeMap(), layer);
        marker.bindTooltip();
        expect(layer.getTooltip().text).toBe('js.dungeonfloorswitchmarker_unknown_label');

        // Act: the rest of loadRemoteMapObject() finishes assigning attributes, then fires
        // 'object:initialized' (mirrors MapObject#_setInitialized()).
        marker.target_floor_id = 5;
        floorsById[5] = {id: 5, name: 'floor.floor_5_name'};
        marker.signal('object:initialized');

        // Assert: the tooltip was rebound with the now-resolvable target floor
        expect(layer.bindCount).toBe(2);
        expect(layer.getTooltip().text).toBe('js.dungeonfloorswitchmarker_go_to_label');
    });

    test('cleanup_unregistersTheObjectInitializedListener', () => {
        // Arrange
        const state = makeFakeState();
        global.getState = () => state;
        const layer = makeFakeLayer();
        const marker = new DungeonFloorSwitchMarker(makeFakeMap(), layer);

        // Act
        marker.cleanup();
        marker.target_floor_id = 5;
        marker.signal('object:initialized');

        // Assert: no rebind happened after cleanup unregistered the listener
        expect(layer.bindCount).toBe(0);
    });
});

/**
 * A fake global state for the facade checks. `facadeEnabled` is what isCurrentDungeonFacadeEnabled() reports
 * (the mapping version has a facade and the user views it), while the style cookie stays on its facade default
 * throughout. getMapIconTypeByKey() mirrors MapContext's fallback to the unknown type for a key it does not know.
 */
function makeFacadeState({facadeEnabled = true, mappingEditor = false} = {}) {
    const mapContext = mappingEditor ? new MapContextMappingVersionEdit() : {};
    mapContext.getFloorById = () => false;
    mapContext.getMapIconTypeByKey = (key) => ({key: key ?? 'unknown', name: 'Floor switch'});

    return {
        register: () => {},
        unregister: () => {},
        isEchoEnabled: () => false,
        isCurrentDungeonFacadeEnabled: () => facadeEnabled,
        getMapFacadeStyle: () => MAP_FACADE_STYLE_FACADE,
        getMapContext: () => mapContext,
    };
}

/**
 * Builds a linked pair of markers on one map, the way the facade map context delivers a zone-to-zone link: the
 * first is shown, the second carries hidden_in_facade. Neither has signalled object:initialized yet.
 */
function makeLinkedPair({
    direction = null,
    floorCouplingDirection = 'right',
    linkedHiddenInFacade = true,
    linked = true,
} = {}) {
    const markersById = {};
    const map = makeFakeMap(markersById);

    const marker = new DungeonFloorSwitchMarker(map, makeFakeLayer());
    marker.id = 1;
    marker.linked_dungeon_floor_switch_marker_id = linked ? 2 : null;
    marker.floorCouplingDirection = floorCouplingDirection;
    marker.direction = direction;
    marker.hidden_in_facade = false;

    const linkedMarker = new DungeonFloorSwitchMarker(map, makeFakeLayer());
    linkedMarker.id = 2;
    linkedMarker.linked_dungeon_floor_switch_marker_id = linked ? 1 : null;
    linkedMarker.floorCouplingDirection = null;
    linkedMarker.direction = null;
    linkedMarker.hidden_in_facade = linkedHiddenInFacade;

    markersById[1] = marker;
    markersById[2] = linkedMarker;

    return {marker, linkedMarker};
}

describe('DungeonFloorSwitchMarker shouldBeVisible on the facade', () => {
    test('shouldBeVisible_givenHiddenInFacadeOnFacade_returnsFalse', () => {
        // Arrange
        global.getState = () => makeFacadeState();
        const {linkedMarker} = makeLinkedPair();

        // Act
        const visible = linkedMarker.shouldBeVisible();

        // Assert
        expect(visible).toBe(false);
    });

    test('shouldBeVisible_givenHiddenInFacadeOnDungeonWithoutFacade_returnsTrue', () => {
        // Arrange: the style cookie is on its facade default, but this mapping version has no facade
        global.getState = () => makeFacadeState({facadeEnabled: false});
        const {linkedMarker} = makeLinkedPair();

        // Act
        const visible = linkedMarker.shouldBeVisible();

        // Assert
        expect(visible).toBe(true);
    });

    test('shouldBeVisible_givenNotHiddenInFacadeOnFacade_returnsTrue', () => {
        // Arrange
        global.getState = () => makeFacadeState();
        const {marker} = makeLinkedPair();

        // Act
        const visible = marker.shouldBeVisible();

        // Assert
        expect(visible).toBe(true);
    });

    test('shouldBeVisible_givenHiddenInFacadeInMappingEditor_returnsTrue', () => {
        // Arrange
        global.getState = () => makeFacadeState({mappingEditor: true});
        const {linkedMarker} = makeLinkedPair();

        // Act
        const visible = linkedMarker.shouldBeVisible();

        // Assert
        expect(visible).toBe(true);
    });
});

describe('DungeonFloorSwitchMarker icon on the facade', () => {
    test.each([
        ['left', 'door_left_right'],
        ['right', 'door_left_right'],
        ['up', 'door_up_down'],
        ['down', 'door_up_down'],
    ])('objectInitialized_givenLinkedMarkerHiddenAndFloorCouplingDirection%s_showsTwoWayDoor', (floorCouplingDirection, expectedKey) => {
        // Arrange
        global.getState = () => makeFacadeState();
        const {marker, linkedMarker} = makeLinkedPair({floorCouplingDirection});
        linkedMarker.signal('object:initialized');

        // Act
        marker.signal('object:initialized');

        // Assert
        expect(marker.map_icon_type.key).toBe(expectedKey);
    });

    test('objectInitialized_givenLinkedMarkerHiddenAndDirectionOverride_usesTheOverrideAxis', () => {
        // Arrange
        global.getState = () => makeFacadeState();
        const {marker, linkedMarker} = makeLinkedPair({direction: 'up', floorCouplingDirection: 'right'});
        linkedMarker.signal('object:initialized');

        // Act
        marker.signal('object:initialized');

        // Assert
        expect(marker.map_icon_type.key).toBe('door_up_down');
    });

    test('objectInitialized_givenLinkedMarkerLoadsAfterwards_switchesToTwoWayDoor', () => {
        // Arrange: the shown marker initialises while its partner has not loaded hidden_in_facade yet
        global.getState = () => makeFacadeState();
        const {marker, linkedMarker} = makeLinkedPair();
        linkedMarker.hidden_in_facade = undefined;
        marker.signal('object:initialized');
        expect(marker.map_icon_type.key).toBe('door_right');

        // Act
        linkedMarker.hidden_in_facade = true;
        linkedMarker.signal('object:initialized');

        // Assert
        expect(marker.map_icon_type.key).toBe('door_left_right');
    });

    test('objectInitialized_givenLinkedMarkerShownOnFacade_showsOneWayDoor', () => {
        // Arrange
        global.getState = () => makeFacadeState();
        const {marker, linkedMarker} = makeLinkedPair({linkedHiddenInFacade: false});
        linkedMarker.signal('object:initialized');

        // Act
        marker.signal('object:initialized');

        // Assert
        expect(marker.map_icon_type.key).toBe('door_right');
    });

    test('objectInitialized_givenNoLinkedMarker_showsOneWayDoor', () => {
        // Arrange
        global.getState = () => makeFacadeState();
        const {marker} = makeLinkedPair({linked: false});

        // Act
        marker.signal('object:initialized');

        // Assert
        expect(marker.map_icon_type.key).toBe('door_right');
    });

    test('objectInitialized_givenDungeonWithoutFacade_showsOneWayDoor', () => {
        // Arrange
        global.getState = () => makeFacadeState({facadeEnabled: false});
        const {marker, linkedMarker} = makeLinkedPair();
        linkedMarker.signal('object:initialized');

        // Act
        marker.signal('object:initialized');

        // Assert
        expect(marker.map_icon_type.key).toBe('door_right');
    });

    test('objectInitialized_givenMappingEditor_showsOneWayDoor', () => {
        // Arrange
        global.getState = () => makeFacadeState({mappingEditor: true});
        const {marker, linkedMarker} = makeLinkedPair();
        linkedMarker.signal('object:initialized');

        // Act
        marker.signal('object:initialized');

        // Assert
        expect(marker.map_icon_type.key).toBe('door_right');
    });

    test('shown_givenMarkerBecomesVisible_redrawsItsIcon', () => {
        // Arrange: the icon type is decided while the markers are still hidden, which Icon does not redraw
        global.getState = () => makeFacadeState();
        const {marker} = makeLinkedPair();

        // Act
        marker.signal('shown');

        // Assert
        expect(marker.refreshVisualCount).toBe(1);
    });

    test('cleanup_unregistersTheShownListener', () => {
        // Arrange
        global.getState = () => makeFacadeState();
        const {marker} = makeLinkedPair();

        // Act
        marker.cleanup();
        marker.signal('shown');

        // Assert
        expect(marker.refreshVisualCount).toBeUndefined();
    });

    test('objectInitialized_givenNoDirection_doesNotShowTwoWayDoor', () => {
        // Arrange
        global.getState = () => makeFacadeState();
        const {marker, linkedMarker} = makeLinkedPair({floorCouplingDirection: null});
        linkedMarker.signal('object:initialized');

        // Act
        marker.signal('object:initialized');

        // Assert: the same unknown-type fallback a marker without a direction gets off the facade
        expect(marker.map_icon_type.key).toBe('unknown');
    });
});

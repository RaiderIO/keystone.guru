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

// 1c. Lightweight immediate base class standing in for Icon, providing only what
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
function makeFakeMap() {
    return {
        options: {edit: false},
        register: () => {},
        unregister: () => {},
        getMapState: () => null,
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

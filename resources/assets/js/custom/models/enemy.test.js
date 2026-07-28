// ---------------------------------------------------------------------------
// Regression coverage for #3670, following the global-script model recipe
// documented at the top of killzone.test.js: define the globals enemy.js touches
// at LOAD time before require()-ing it, then hand the class fake collaborators
// instead of building a real DungeonMap.
//
// Enemy#bindTooltip() only rebinds when the rendered text changed. That guard
// checked the text but not whether a tooltip was actually bound, so any code
// path that unbinds without also resetting `tooltipText` (a layer swap in
// MapObjectGroup#setLayerToMapObject) left the enemy without a tooltip
// permanently - the recomputed text is identical, so the guard short-circuited
// and nothing was ever rebound.
// ---------------------------------------------------------------------------

// 1a. Leaflet: enemy.js builds div icons and draw handlers at load time.
global.L = {
    divIcon: function () {
    },
    Marker: {extend: () => function () {}},
    Draw: {
        Marker: {extend: () => function () {}},
        Feature: {prototype: {initialize() {}}},
    },
};

// 1b. Map states referenced by the constructor and by bindTooltip(). The fake map below never
// returns an instance of either, so the tooltip is always considered enabled.
global.MapState = class MapState {};
global.EditMapState = class EditMapState extends global.MapState {};

// 1c. Lightweight base class standing in for VersionableMapObject -> MapObject, providing only
// what Enemy calls on `super` / `this`. unbindTooltip() mirrors the real MapObject implementation,
// which forwards to the layer without touching tooltipText - that asymmetry is the bug under test.
// _assignPopup() likewise mirrors MapObject#_assignPopup (mapobject.js): unbind first, then bind
// again only when the map is editable and this object wants a popup.
global.VersionableMapObject = class VersionableMapObject {
    constructor(map, layer = null, options = {}) {
        this.map = map;
        this.layer = layer;
        this.options = options;
        this.id = 0;
        this._cachedAttributes = null;
    }

    register() {
    }

    unregister() {
    }

    signal() {
    }

    unbindTooltip() {
        this.layer.unbindTooltip();
    }

    isEditableByPopup() {
        return true;
    }

    _getPopupHtml() {
        return '<div>popup</div>';
    }

    _assignPopup(layer = null) {
        if (layer === null) {
            layer = this.layer;
        }

        if (layer !== null) {
            layer.off('popupopen');
            layer.unbindPopup();

            if (this.map.options.edit && this.isEditable() && this.isEditableByPopup()) {
                layer.bindPopup(this._getPopupHtml(), {});
                layer.on('popupopen', () => {
                });
            }
        }
    }
};

// 1d. Handlebars template + default variables used to render the tooltip body. The template echoes
// the name so a test can change the rendered text by changing the npc, and renders the raid marker
// shortcut hint the same way the real template does - conditionally on show_raid_marker_shortcut.
global.Handlebars = {
    templates: {
        enemy_tooltip_template: (data) =>
            `<div>${data.name}</div>${data.show_raid_marker_shortcut ? '<div>shift+right-click</div>' : ''}`,
    },
};
global.getHandlebarsDefaultVariables = () => ({});

// 1e. The shared setup's `$` stub has no extend().
global.$.extend = Object.assign;

global.getState = () => ({
    getMapContext: () => ({register: () => {}}),
});

const {Enemy} = require('./enemy');

// 1f. Only ever used through `instanceof` (raid markers are not available on the admin mapping page).
global.AdminEnemy = class AdminEnemy extends Enemy {
};

/**
 * A fake Leaflet layer mirroring Leaflet's own tooltip semantics: getTooltip() is undefined before
 * the first bind and null after unbindTooltip(). Records how often bindTooltip() was called so a
 * test can assert that redundant rebinds are still avoided.
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
 * A fake DungeonMap exposing only what Enemy touches: an event bus, edit/state accessors.
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
 * Builds an Enemy with a fake map/layer and an npc, with its tooltip already bound once.
 */
function makeBoundEnemy(npcName = 'Murkbrine Shorerunner') {
    const layer = makeFakeLayer();
    const enemy = new Enemy(makeFakeMap(), layer);
    enemy.npc = {id: 1, name: npcName};
    enemy.getVisualData = () => ({info: [], custom: []});
    enemy.bindTooltip();

    return {enemy, layer};
}

describe('Enemy#bindTooltip (#3670)', () => {
    test('bindTooltip_givenTooltipWasUnboundAndTextUnchanged_rebindsTheTooltip', () => {
        // Arrange: a layer swap unbinds without resetting tooltipText
        const {enemy, layer} = makeBoundEnemy();
        const textBefore = enemy.tooltipText;
        enemy.unbindTooltip();
        expect(layer.getTooltip()).toBeNull();

        // Act
        enemy.bindTooltip();

        // Assert
        expect(layer.getTooltip()).not.toBeNull();
        expect(layer.getTooltip().text).toBe(textBefore);
    });

    test('bindTooltip_givenTooltipStillBoundAndTextUnchanged_doesNotRebind', () => {
        // Arrange
        const {enemy, layer} = makeBoundEnemy();
        expect(layer.bindCount).toBe(1);

        // Act
        enemy.bindTooltip();

        // Assert: the guard still short-circuits redundant work
        expect(layer.bindCount).toBe(1);
    });

    test('bindTooltip_givenTheTextChanged_rebindsWithTheNewText', () => {
        // Arrange
        const {enemy, layer} = makeBoundEnemy();
        enemy.npc = {id: 2, name: 'Deathwhisper Necrolyte'};

        // Act
        enemy.bindTooltip();

        // Assert
        expect(layer.bindCount).toBe(2);
        expect(layer.getTooltip().text).toContain('Deathwhisper Necrolyte');
    });

    test('bindTooltip_givenNoLayer_doesNothing', () => {
        // Arrange
        const enemy = new Enemy(makeFakeMap(), null);
        enemy.npc = {id: 1, name: 'Murkbrine Shorerunner'};
        enemy.getVisualData = () => ({info: [], custom: []});

        // Act + Assert: no layer to bind to, so this must not throw
        expect(() => enemy.bindTooltip()).not.toThrow();
        expect(enemy.tooltipText).toBe('');
    });
});

// ---------------------------------------------------------------------------
// Enemy#_assignPopup: an enemy's edit popup must stay unbound for as long as a map state is active.
//
// setPopupEnabled() unbinds it when a map state starts, but save()/setSynced() call _assignPopup()
// straight through and bypass that gate. During an EnemySelection that survives more than one click -
// the enemy forces checkpoint flow, which deliberately never stops itself - every click saves the
// clicked enemy AND each of its pack buddies, so their popups came back mid-selection. The next click
// on any of them opened the edit popup, which the closePopup() in that click's own save-success shut
// again: a popup flashing up on nearly every assignment.
// ---------------------------------------------------------------------------

/**
 * Extends the fake layer with Leaflet's popup surface: getPopup() is undefined before the first bind
 * and null after unbindPopup(), and event handlers are recorded so the popupopen cleanup is visible.
 */
function makeFakePopupLayer() {
    return Object.assign(makeFakeLayer(), {
        _popup: undefined,
        handlers: {},
        getPopup() {
            return this._popup;
        },
        bindPopup(html) {
            this._popup = {html};
        },
        unbindPopup() {
            this._popup = null;
        },
        on(event, fn) {
            (this.handlers[event] = this.handlers[event] || []).push(fn);
        },
        off(event) {
            delete this.handlers[event];
        },
    });
}

/**
 * A fake DungeonMap in edit mode that drives the real `map:mapstatechanged` chain: setMapState()
 * flips what getMapState() returns and then calls the handler Enemy registered in its constructor,
 * exactly as DungeonMap does.
 */
function makeFakeEditMap() {
    return {
        options: {edit: true},
        _mapState: null,
        _handlers: [],
        register(event, context, fn) {
            if (event === 'map:mapstatechanged') {
                this._handlers.push(fn);
            }
        },
        unregister() {
        },
        getMapState() {
            return this._mapState;
        },
        setMapState(mapState) {
            this._mapState = mapState;
            for (const fn of this._handlers) {
                fn({data: {newMapState: mapState}});
            }
        },
    };
}

/**
 * An enemy on an editable map that wants a popup - i.e. what an AdminEnemy is on the admin mapping
 * page (plain enemies return false from isEditable(), so they never get one at all).
 */
function makeEditableEnemy() {
    const map = makeFakeEditMap();
    const layer = makeFakePopupLayer();
    const enemy = new Enemy(map, layer);
    enemy.npc = {id: 1, name: 'Murkbrine Shorerunner'};
    enemy.getVisualData = () => ({info: [], custom: []});
    enemy.isEditable = () => true;

    return {enemy, layer, map};
}

describe('Enemy#_assignPopup during a map state', () => {
    test('_assignPopup_givenNoMapState_bindsThePopup', () => {
        // Arrange
        const {enemy, layer} = makeEditableEnemy();

        // Act
        enemy._assignPopup();

        // Assert: the guard must not break the ordinary case it wraps
        expect(layer.getPopup()).not.toBeNull();
    });

    test('_assignPopup_givenAnActiveMapState_leavesThePopupUnbound', () => {
        // Arrange: the enemy was saved while an enemy selection is running (setSynced -> _assignPopup)
        const {enemy, layer, map} = makeEditableEnemy();
        map.setMapState(new global.MapState());

        // Act
        enemy._assignPopup();

        // Assert
        expect(layer.getPopup()).toBeNull();
        expect(layer.handlers['popupopen']).toBeUndefined();
    });

    test('_assignPopup_givenAnActiveMapStateAndAPreviouslyBoundPopup_unbindsIt', () => {
        // Arrange
        const {enemy, layer, map} = makeEditableEnemy();
        enemy._assignPopup();
        expect(layer.getPopup()).not.toBeNull();

        // Act
        map.setMapState(new global.MapState());
        enemy._assignPopup();

        // Assert
        expect(layer.getPopup()).toBeNull();
    });

    test('setPopupEnabled_givenAPopupBoundOutsideTheFlag_stillUnbindsWhenAMapStateStarts', () => {
        // Arrange: exactly the page-load situation - setSynced(true) bound the popup through
        // _assignPopup(), which never touches isPopupEnabled, so the flag still reads false.
        const {enemy, layer, map} = makeEditableEnemy();
        enemy._assignPopup();
        expect(layer.getPopup()).not.toBeNull();
        expect(enemy.isPopupEnabled).toBe(false);

        // Act: the mapper hits "Select enemies"
        map.setMapState(new global.MapState());

        // Assert: the old `!enabled && this.isPopupEnabled` guard short-circuited here, leaving the
        // popup bound - so the first enemy click of the selection opened it.
        expect(layer.getPopup()).toBeNull();
    });

    test('_assignPopup_givenTheMapStateEnded_bindsThePopupAgain', () => {
        // Arrange: a save during the selection must not leave the popup dead afterwards
        const {enemy, layer, map} = makeEditableEnemy();
        map.setMapState(new global.MapState());
        enemy._assignPopup();
        expect(layer.getPopup()).toBeNull();

        // Act: DungeonMap clears the state, which runs setPopupEnabled(true) via mapstatechanged
        map.setMapState(null);

        // Assert
        expect(layer.getPopup()).not.toBeNull();
        expect(enemy.isPopupEnabled).toBe(true);
    });
});

// ---------------------------------------------------------------------------
// The tooltip's "shift + right-click to assign a raid marker" hint (#3703).
//
// bindTooltip() caches the rendered text and only rebinds when it changes, so anything baked into it
// that depends on the current map state sticks. canOpenRaidMarkerMenu() is exactly that (it requires
// no active map state), and assigning a raid marker rebinds the tooltip while
// RaidMarkerSelectMapState is still active - which dropped the hint from the tooltip for good, until
// something else happened to change the text.
// ---------------------------------------------------------------------------

/**
 * An enemy on an editable map, i.e. the route editor where raid markers can be assigned.
 */
function makeRaidMarkerEnemy() {
    const map = makeFakeEditMap();
    // The popup surface is needed too: starting/stopping a map state runs setPopupEnabled().
    const layer = makeFakePopupLayer();
    const enemy = new Enemy(map, layer);
    enemy.npc = {id: 1, name: 'Murkbrine Shorerunner'};
    enemy.getVisualData = () => ({info: [], custom: []});

    return {enemy, layer, map};
}

describe('Enemy raid marker shortcut hint (#3703)', () => {
    test('supportsRaidMarkerMenu_givenAnActiveMapState_staysTrue', () => {
        // Arrange
        const {enemy, map} = makeRaidMarkerEnemy();
        expect(enemy.supportsRaidMarkerMenu()).toBe(true);

        // Act
        map.setMapState(new global.MapState());

        // Assert: the tooltip hint is built from this, so it must not follow the map state
        expect(enemy.supportsRaidMarkerMenu()).toBe(true);
    });

    test('canOpenRaidMarkerMenu_givenAnActiveMapState_returnsFalse', () => {
        // Arrange
        const {enemy, map} = makeRaidMarkerEnemy();

        // Act
        map.setMapState(new global.MapState());

        // Assert: opening a second circle menu on top of the current one stays blocked
        expect(enemy.canOpenRaidMarkerMenu()).toBe(false);
    });

    test('bindTooltip_givenARebindWhileAMapStateIsActive_keepsTheShortcutHint', () => {
        // Arrange: the tooltip as it renders before the raid marker menu is opened
        const {enemy, layer, map} = makeRaidMarkerEnemy();
        enemy.bindTooltip();
        expect(layer.getTooltip().text).toContain('shift+right-click');

        // Act: assigning a marker saves the enemy (setSynced -> bindTooltip) while
        // RaidMarkerSelectMapState is still active
        map.setMapState(new global.MapState());
        enemy.npc = {id: 2, name: 'Deathwhisper Necrolyte'};
        enemy.bindTooltip();
        map.setMapState(null);

        // Assert
        expect(layer.getTooltip().text).toContain('shift+right-click');
    });

    test('supportsRaidMarkerMenu_givenANonEditableMap_returnsFalse', () => {
        // Arrange
        const enemy = new Enemy(makeFakeMap(), makeFakeLayer());

        // Act / Assert: no hint when the route cannot be edited at all
        expect(enemy.supportsRaidMarkerMenu()).toBe(false);
    });

    test('supportsRaidMarkerMenu_givenAnAdminEnemy_returnsFalse', () => {
        // Arrange
        const adminEnemy = new global.AdminEnemy(makeFakeEditMap(), makeFakePopupLayer());

        // Act / Assert: raid markers do not exist on the admin mapping page
        expect(adminEnemy.supportsRaidMarkerMenu()).toBe(false);
    });
});

// ---------------------------------------------------------------------------
// Regression coverage for #3670, following the global-script model recipe
// documented at the top of killzone.test.js: define the globals enemy.js touches
// at LOAD time before require()-ing it, then hand the class fake collaborators
// instead of building a real DungeonMap.
//
// Enemy#bindTooltip() only rebinds when the rendered text changed. That guard
// checked the text but not whether a tooltip was actually bound, so any code
// path that unbinds without also resetting `tooltipText` (the raid marker circle
// menu in EnemyVisual, a layer swap in MapObjectGroup#setLayerToMapObject) left
// the enemy without a tooltip permanently - the recomputed text is identical, so
// the guard short-circuited and nothing was ever rebound.
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
};

// 1d. Handlebars template + default variables used to render the tooltip body. The template simply
// echoes the name so a test can change the rendered text by changing the npc.
global.Handlebars = {
    templates: {
        enemy_tooltip_template: (data) => `<div>${data.name}</div>`,
    },
};
global.getHandlebarsDefaultVariables = () => ({});

// 1e. The shared setup's `$` stub has no extend().
global.$.extend = Object.assign;

global.getState = () => ({
    getMapContext: () => ({register: () => {}}),
});

const {Enemy} = require('./enemy');

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
        // Arrange: the circle menu / a layer swap unbinds without resetting tooltipText
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

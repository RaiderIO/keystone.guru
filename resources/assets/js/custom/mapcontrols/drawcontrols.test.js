// ---------------------------------------------------------------------------
// Covers #3966: adding a new vertex to an existing line during edit.
//
// Leaflet.draw inserts a vertex when you drag the translucent "middle marker"
// it renders halfway between two vertices. drawcontrols.js monkeypatches
// L.Edit.PolyVerticesEdit#_createMiddleMarker so arrows - which are always
// exactly two vertices - do not get them. That guard has to be opt-OUT: every
// other polyline (path, brushline, killzone path, enemy patrol, floor union
// area) must keep its middle markers.
//
// These tests drive the REAL leaflet + leaflet-draw packages so the patch is
// exercised through Leaflet.draw's own editing handler, not a stand-in.
// ---------------------------------------------------------------------------

// leaflet-draw binds to the global `L` at require time, so real Leaflet has to
// replace the setup.js stub before it is pulled in.
global.L = require('leaflet');
global.window.L = global.L;
require('leaflet-draw');

// drawcontrols.js is a global-script file: `$.extend` and these two base
// classes are all it touches at load time.
global.$ = require('jquery');
global.MapControl = class MapControl {
};
global.DungeonMap = class DungeonMap {
};

require('./drawcontrols');

// Arrow extends Polyline, which it only needs as a base class here; the flag
// under test is set in its own onLayerInit().
global.Polyline = class Polyline {
    constructor(map, layer) {
        this.layer = layer;
    }

    onLayerInit() {
    }
};

const Arrow = require('../models/arrow');

/**
 * Builds a real Leaflet map on a sized jsdom container. jsdom reports every
 * element as 0x0, so the container's dimensions are stubbed - Leaflet refuses
 * to lay out markers in a zero-sized viewport.
 *
 * @returns {L.Map}
 */
function createMap() {
    const container = document.createElement('div');
    Object.defineProperty(container, 'clientWidth', {value: 800});
    Object.defineProperty(container, 'clientHeight', {value: 600});
    document.body.appendChild(container);

    return L.map(container, {center: [0, 0], zoom: 2});
}

/**
 * Enables Leaflet.draw's vertex editing on a 3-point polyline and counts the
 * markers it put on the map. 3 vertices yield 3 vertex markers, plus one middle
 * marker per segment when vertex creation is allowed.
 *
 * @param options {object} Layer options, e.g. {allowVertexCreationDuringEdit: false}
 * @returns {{total: Number, middle: Number}}
 */
function countEditMarkers(options) {
    const map = createMap();
    const polyline = L.polyline([[0, 0], [1, 1], [2, 0]], options).addTo(map);

    polyline.editing.enable();

    // L.Edit.Poly delegates to one L.Edit.PolyVerticesEdit per ring; a plain
    // polyline has exactly one. Its _markerGroup holds the vertex markers and
    // whatever middle markers _createMiddleMarker produced.
    const verticesHandler = polyline.editing._verticesHandlers[0];
    const markers = [];
    verticesHandler._markerGroup.eachLayer((marker) => markers.push(marker));

    return {
        total: markers.length,
        middle: markers.length - verticesHandler._markers.length,
    };
}

describe('DrawControls middle marker patch (#3966)', () => {
    afterEach(() => {
        document.body.innerHTML = '';
    });

    test('createMiddleMarker_givenPolylineWithoutFlag_createsMiddleMarker', () => {
        // A path/brushline/killzone path never sets the flag; it must still be
        // able to gain vertices. This is the assertion that fails on master,
        // where the guard was `!options.allowVertexCreationDuringEdit`.
        const {total, middle} = countEditMarkers({});

        expect(middle).toBe(2);
        expect(total).toBe(5);
    });

    test('createMiddleMarker_givenArrowWithFlagFalse_skipsMiddleMarker', () => {
        // Arrows opt out - they are start + tip and nothing in between.
        const {total, middle} = countEditMarkers({allowVertexCreationDuringEdit: false});

        expect(middle).toBe(0);
        expect(total).toBe(3);
    });
});

describe('Arrow vertex creation opt-out (#3966)', () => {
    test('onLayerInit_givenRebuiltLayer_reappliesVertexCreationFlag', () => {
        // MapObjectGroup#setLayerToMapObject() replaces mapObject.layer wholesale
        // on every rebuild (floor switch, live session update), so a flag set once
        // in the constructor would be lost and the arrow would regain its middle
        // markers.
        const arrow = Object.create(Arrow.prototype);
        arrow.layer = L.polyline([[0, 0], [1, 1]]);

        arrow.onLayerInit();

        expect(arrow.layer.options.allowVertexCreationDuringEdit).toBe(false);
    });
});

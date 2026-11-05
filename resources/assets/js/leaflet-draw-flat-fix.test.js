// ---------------------------------------------------------------------------
// leaflet-draw calls the deprecated L.Polyline._flat() whenever a polyline's
// vertex editor is enabled, and stock leaflet warns on every call. The first
// test is the control: it proves the warning really fires without the patch,
// so an upgrade that fixes it upstream turns that test red.
// ---------------------------------------------------------------------------

const {applyLeafletDrawFlatFix} = require('./leaflet-draw-flat-fix');

const DEPRECATION_WARNING = 'Deprecated use of _flat, please use L.LineUtil.isFlat instead.';

/**
 * A fresh, unpatched leaflet module with leaflet-draw installed on it. Both mutate leaflet's own
 * objects, so every test needs its own copy - hence the require-cache reset.
 *
 * @returns {Object}
 */
function loadLeafletWithDraw() {
    delete require.cache[require.resolve('leaflet')];
    delete require.cache[require.resolve('leaflet-draw')];

    const leaflet = require('leaflet');
    window.L = leaflet;
    require('leaflet-draw');

    return leaflet;
}

/**
 * Builds a map and adds the given polyline-ish layer to it.
 *
 * @param {Object} leaflet
 * @param {Object} layer
 * @returns {Object} The map.
 */
function addToMap(leaflet, layer) {
    const container = document.createElement('div');
    Object.defineProperty(container, 'clientWidth', {value: 800});
    Object.defineProperty(container, 'clientHeight', {value: 600});
    document.body.appendChild(container);

    const map = leaflet.map(container, {center: [0, 0], zoom: 1, zoomControl: false, attributionControl: false});
    layer.addTo(map);

    return map;
}

/**
 * @param {Object} warnSpy
 * @returns {number}
 */
function countDeprecationWarnings(warnSpy) {
    return warnSpy.mock.calls.filter((args) => args[0] === DEPRECATION_WARNING).length;
}

describe('leaflet-draw _flat deprecation', () => {
    test('enableEditing_givenStockLeaflet_warnsAboutDeprecatedFlat', () => {
        const leaflet = loadLeafletWithDraw();
        const polyline = leaflet.polyline([[0, 0], [1, 1], [2, 0]]);
        addToMap(leaflet, polyline);
        const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {
        });

        polyline.editing.enable();

        expect(countDeprecationWarnings(warnSpy)).toBeGreaterThan(0);
    });

    test('enableEditing_givenThePatchAndAPolyline_doesNotWarnAndCreatesAMarkerPerVertex', () => {
        const leaflet = loadLeafletWithDraw();
        applyLeafletDrawFlatFix(leaflet);
        const polyline = leaflet.polyline([[0, 0], [1, 1], [2, 0]]);
        addToMap(leaflet, polyline);
        const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {
        });

        polyline.editing.enable();

        expect(countDeprecationWarnings(warnSpy)).toBe(0);
        expect(polyline.editing._verticesHandlers[0]._markers).toHaveLength(3);
    });

    test('enableEditing_givenThePatchAndAPolygonWithAHole_editsOnlyTheOuterRing', () => {
        const leaflet = loadLeafletWithDraw();
        applyLeafletDrawFlatFix(leaflet);
        const polygon = leaflet.polygon([
            [[0, 0], [0, 10], [10, 10], [10, 0]],
            [[2, 2], [2, 4], [4, 4]],
        ]);
        addToMap(leaflet, polygon);
        const warnSpy = vi.spyOn(console, 'warn').mockImplementation(() => {
        });

        polygon.editing.enable();

        expect(countDeprecationWarnings(warnSpy)).toBe(0);
        expect(polygon.editing._verticesHandlers.map((handler) => handler._markers.length)).toEqual([4]);
    });
});

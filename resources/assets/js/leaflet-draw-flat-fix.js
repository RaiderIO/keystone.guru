// ---------------------------------------------------------------------------
// leaflet-draw deprecated _flat() fix
//
// leaflet-draw 1.0.4 (its final release) picks a polyline's outer ring with
// L.Polyline._flat() every time a vertex editor is enabled. Leaflet 1.9.4
// aliases that to LineUtil._flat(), a deprecated wrapper around
// LineUtil.isFlat() that console.warns on every call - so each press of the
// editor's Edit button logs one warning per polyline/patrol on the map.
//
// Point the alias at isFlat() itself: identical result, no warning. Must run
// after leaflet has been required. leaflet-draw-flat-fix.test.js goes red once
// either library stops needing this.
// ---------------------------------------------------------------------------

/**
 * Re-points the given leaflet module's deprecated Polyline._flat alias at LineUtil.isFlat.
 *
 * @param {Object} leaflet The leaflet module (`require('leaflet')`).
 * @returns {void}
 */
function applyLeafletDrawFlatFix(leaflet) {
    leaflet.Polyline._flat = leaflet.LineUtil.isFlat;
}

module.exports = {applyLeafletDrawFlatFix};

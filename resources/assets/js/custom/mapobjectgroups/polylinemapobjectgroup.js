class PolylineMapObjectGroup extends MapObjectGroup {
    constructor(manager, names, editable) {
        super(manager, names, editable);
    }

    _getPolylineOptions() {
        return {};
    }

    /**
     * Whether the polylines in this group are closed shapes, drawn as a polygon, rather than open lines.
     * @returns {boolean}
     * @protected
     */
    _isClosedShape() {
        return false;
    }

    /**
     * Converts polyline.vertices_json to a list of L.LatLngs
     * @param remoteMapObject {Object}
     * @returns {[]}
     * @protected
     */
    _restorePoints(remoteMapObject) {
        return this._verticesJsonToPoints(remoteMapObject.polyline?.vertices_json);
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let points = this._restorePoints(remoteMapObject);

        if (this._isClosedShape()) {
            return points.length > 0 ? L.polygon(points, this._getPolylineOptions()) : null;
        }

        return L.polyline(points, this._getPolylineOptions());
    }

    /**
     * @inheritDoc
     */
    _updateMapObject(remoteMapObject, mapObject, options = {}) {
        console.assert(mapObject instanceof MapObject, 'mapObject is not of type MapObject', mapObject);
        mapObject.layer.setLatLngs(this._restorePoints(remoteMapObject));

        return mapObject;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {PolylineMapObjectGroup};
}

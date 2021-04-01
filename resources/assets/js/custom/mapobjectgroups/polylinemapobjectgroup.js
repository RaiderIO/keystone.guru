class PolylineMapObjectGroup extends MapObjectGroup {
    constructor(manager, names, editable) {
        super(manager, names, editable);
    }

    /**
     * Converts polyline.vertices_json to a list of L.LatLngs
     * @param remoteMapObject {Object}
     * @returns {[]}
     * @protected
     */
    _restorePoints(remoteMapObject) {
        // Create the polyline first
        let polyline = remoteMapObject.polyline;
        let points = [];
        if (polyline !== null && typeof polyline.vertices_json !== 'undefined') {
            let vertices = JSON.parse(polyline.vertices_json);

            for (let j = 0; j < vertices.length; j++) {
                let vertex = vertices[j];
                points.push([vertex.lat, vertex.lng]);
            }
        }

        return points;
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        return L.polyline(this._restorePoints(remoteMapObject));
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
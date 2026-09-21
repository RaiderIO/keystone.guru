class PolygonMapObjectGroup extends MapObjectGroup {
    constructor(manager, names, editable) {
        super(manager, names, editable);

        this.options = {};
    }

    /**
     *
     * @param color
     * @protected
     */
    _setColor(color) {
        this.options.color = color;
    }

    /**
     * Converts vertices_json to a list of L.LatLngs
     * @param remoteMapObject {Object}
     * @returns {[]}
     * @protected
     */
    _restorePoints(remoteMapObject) {
        return this._verticesJsonToPoints(remoteMapObject.vertices_json);
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        let points = this._restorePoints(remoteMapObject);
        return points.length > 0 ? L.polygon(points, this.options) : null;
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

class PathMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_PATH, editable);

        this.title = 'Hide/show route';
        this.fa_class = 'fa-route';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getPaths();
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof PathMapObjectGroup, 'this is not an PathMapObjectGroup', this);

        return new Path(this.manager.map, layer);
    }

    /**
     * Creates a new Path based on some vertices and save it to the server.
     * @param vertices {Object}
     * @param options {Object}
     * @returns {Path}
     */
    createNewPath(vertices, options) {
        let path = this._loadMapObject($.extend({}, {
            polyline: {
                color: c.map.polyline.awakenedObeliskGatewayPolylineColor,
                color_animated: getState().hasPatreonBenefit(c.patreonbenefits.animated_polylines) ? c.map.polyline.awakenedObeliskGatewayPolylineColorAnimated : null,
                weight: c.map.polyline.awakenedObeliskGatewayPolylineWeight,
                vertices_json: JSON.stringify(vertices)
            }
        }, options));

        path.save();

        this.signal('path:new', {newPath: path});
        return path;
    }
}

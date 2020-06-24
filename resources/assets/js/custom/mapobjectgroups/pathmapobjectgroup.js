class PathMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_PATH, 'path', editable);

        let self = this;

        this.title = 'Hide/show route';
        this.fa_class = 'fa-route';

        if (this.manager.map.options.echo) {
            window.Echo.join(this.manager.map.options.appType + '-route-edit.' + getState().getDungeonRoute().publicKey)
                .listen('.path-changed', (e) => {
                    if (e.path.floor_id === getState().getCurrentFloor().id) {
                        self._restoreObject(e.path, e.user);
                    }
                })
                .listen('.path-deleted', (e) => {
                    let mapObject = self.findMapObjectById(e.id);
                    if (mapObject !== null) {
                        mapObject.localDelete();
                        self._showDeletedFromEcho(mapObject, e.user);
                    }
                });
        }
    }

    _createObject(layer) {
        console.assert(this instanceof PathMapObjectGroup, 'this is not an PathMapObjectGroup', this);

        return new Path(this.manager.map, layer);
    }

    /**
     * @inheritDoc
     */
    _restoreObject(remoteMapObject, username = null) {
        // Fetch the existing path if it exists
        let path = this.findMapObjectById(remoteMapObject.id);

        // Create the polyline first
        let polyline = remoteMapObject.polyline;
        let vertices = JSON.parse(polyline.vertices_json);

        let points = [];
        for (let j = 0; j < vertices.length; j++) {
            let vertex = vertices[j];
            points.push([vertex.lat, vertex.lng]);
        }

        // Only create a new one if it's new for us
        if (path === null) {
            // Create new layer
            let layer = L.polyline(points);
            path = this.createNew(layer);
        } else {
            // Update latlngs
            path.layer.setLatLngs(points);
        }

        path.id = remoteMapObject.id;
        path.loadRemoteMapObject(remoteMapObject);
        path.loadRemoteMapObject(remoteMapObject.polyline);

        // We just downloaded the path, it's synced alright!
        path.setSynced(true);

        // Show echo notification or not
        this._showReceivedFromEcho(path, username);

        return path;
    }

    /**
     * Creates a new Path based on some vertices and save it to the server.
     * @param vertices {Object}
     * @param options {Object}
     * @returns {Path}
     */
    createNewPath(vertices, options) {
        let path = this._restoreObject($.extend({}, {
            polyline: {
                color: c.map.mapicon.awakenedObeliskGatewayPolylineColor,
                weight: c.map.mapicon.awakenedObeliskGatewayPolylineWeight,
                vertices_json: JSON.stringify(vertices)
            }
        }, options));

        path.save();

        this.signal('path:new', {newPath: path});
        return path;
    }
}
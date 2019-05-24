class PathMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        let self = this;

        this.title = 'Hide/show route';
        this.fa_class = 'fa-route';

        // this.manager.unregister('fetchsuccess', this);
        window.Echo.channel('route-edit')
            .listen('PathChangedEvent', (e) => {
                self._restoreObject(e.path);
            });
    }

    _createObject(layer) {
        console.assert(this instanceof PathMapObjectGroup, this, 'this is not an PathMapObjectGroup');

        return new Path(this.manager.map, layer);
    }

    _restoreObject(remoteMapObject) {
        // Fetch the existing path if it exists
        let path = this.findMapObjectById(remoteMapObject.id);

        // Create the polyline first
        let polyline = remoteMapObject.polyline;
        let vertices = JSON.parse(polyline.vertices_json);

        let points = [];
        for (let j = 0; j < vertices.length; j++) {
            let vertex = vertices[j];
            points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
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
        path.setColor(polyline.color);
        path.setWeight(polyline.weight);

        // We just downloaded the path, it's synced alright!
        path.setSynced(true);
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);

        console.assert(this instanceof PathMapObjectGroup, this, 'this is not a PathMapObjectGroup');

        // Should always exist
        let paths = response.path;

        // Now draw the paths on the map
        for (let index in paths) {
            if (paths.hasOwnProperty(index)) {
                this._restoreObject(paths[index]);
            }
        }
    }
}
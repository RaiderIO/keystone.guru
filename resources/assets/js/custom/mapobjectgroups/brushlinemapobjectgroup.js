class BrushlineMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        let self = this;

        this.title = 'Hide/show brushlines';
        this.fa_class = 'fa-paint-brush';

        window.Echo.private('route-edit.' + this.manager.map.getDungeonRoute().publicKey)
            .listen('.brushline-changed', (e) => {
                self._restoreObject(e.brushline);
            })
            .listen('.brushline-deleted', (e) => {
                let mapObject = self.findMapObjectById(e.id);
                if (mapObject !== null) {
                    mapObject.localDelete();
                }
            });
    }

    _createObject(layer) {
        console.assert(this instanceof BrushlineMapObjectGroup, 'this is not an BrushlineMapObjectGroup', this);

        return new Brushline(this.manager.map, layer);
    }

    _restoreObject(remoteMapObject) {
        console.assert(this instanceof BrushlineMapObjectGroup, 'this is not an BrushlineMapObjectGroup', this);

        // Fetch the existing path if it exists
        let brushline = this.findMapObjectById(remoteMapObject.id);

        // Create the polyline first
        let polyline = remoteMapObject.polyline;
        let vertices = JSON.parse(polyline.vertices_json);

        let points = [];
        for (let j = 0; j < vertices.length; j++) {
            let vertex = vertices[j];
            points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
        }

        // Only create a new one if it's new for us
        if (brushline === null) {
            // Create new layer
            let layer = L.polyline(points);
            brushline = this.createNew(layer);
        } else {
            // Update latlngs
            brushline.layer.setLatLngs(points);
        }

        // Now that we have the layer, create the brushline
        brushline.id = remoteMapObject.id;
        brushline.setColor(polyline.color);
        brushline.setWeight(polyline.weight);

        // We just downloaded the brushline, make it synced
        brushline.setSynced(true);
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof BrushlineMapObjectGroup, 'this is not a BrushlineMapObjectGroup', this);

        let brushlines = response.brushline;

        // Now draw the brushlines on the map
        for (let index in brushlines) {
            if (brushlines.hasOwnProperty(index)) {
                this._restoreObject(brushlines[index]);
            }
        }
    }
}
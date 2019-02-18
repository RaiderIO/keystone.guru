class BrushlineMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show brushlines';
        this.fa_class = 'fa-paint-brush';
    }

    _createObject(layer) {
        console.assert(this instanceof BrushlineMapObjectGroup, 'this is not an BrushlineMapObjectGroup');

        return new Brushline(this.manager.map, layer);
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof BrushlineMapObjectGroup, this, 'this is not a BrushlineMapObjectGroup');

        let brushlines = response.brushline;

        // Now draw the patrols on the map
        for (let index in brushlines) {
            if (brushlines.hasOwnProperty(index)) {
                let points = [];
                let remoteBrushline = brushlines[index];

                // Create the polyline first
                let polyline = remoteBrushline.polyline;
                let vertices = JSON.parse(polyline.vertices_json);

                for (let j = 0; j < vertices.length; j++) {
                    let vertex = vertices[j];
                    points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                }

                let layer = L.polyline(points);

                // Now that we have the layer, create the brushline
                let brushLine = this.createNew(layer);
                brushLine.id = remoteBrushline.id;
                brushLine.setColor(polyline.color);
                brushLine.setWeight(polyline.weight);

                // We just downloaded the brushline, make it synced
                brushLine.setSynced(true);
            }
        }
    }
}
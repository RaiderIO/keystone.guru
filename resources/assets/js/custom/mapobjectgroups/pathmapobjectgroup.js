class PathMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show route';
        this.fa_class = 'fa-route';
    }

    _createObject(layer) {
        console.assert(this instanceof PathMapObjectGroup, this, 'this is not an PathMapObjectGroup');

        return new Path(this.manager.map, layer);
    }


    _fetchSuccess(response) {
        super._fetchSuccess(response);

        console.assert(this instanceof PathMapObjectGroup, this, 'this is not a PathMapObjectGroup');

        // Should always exist
        let paths = response.path;

        // Now draw the paths on the map
        for (let index in paths) {
            if (paths.hasOwnProperty(index)) {
                let points = [];
                let remotePath = paths[index];

                // Create the polyline first
                let polyline = remotePath.polyline;
                let vertices = JSON.parse(polyline.vertices_json);

                for (let j = 0; j < vertices.length; j++) {
                    let vertex = vertices[j];
                    points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                }

                let layer = L.polyline(points);

                let route = this.createNew(layer);
                route.id = remotePath.id;
                route.setColor(polyline.color);
                route.setWeight(polyline.weight);

                // We just downloaded the enemy pack, it's synced alright!
                route.setSynced(true);
            }
        }
    }
}
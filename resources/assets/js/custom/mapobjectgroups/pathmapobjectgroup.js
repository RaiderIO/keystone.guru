class PathMapObjectGroup extends MapObjectGroup {
    constructor(map, name, editable) {
        super(map, name, editable);

        this.title = 'Hide/show route';
        this.fa_class = 'fa-route';
    }

    _createObject(layer) {
        console.assert(this instanceof PathMapObjectGroup, this, 'this is not an PathMapObjectGroup');

        return new Path(this.map, layer);
    }


    fetchFromServer(floor) {
        // no super call required
        console.assert(this instanceof PathMapObjectGroup, this, 'this is not a PathMapObjectGroup');

        let self = this;

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'GET',
                url: '/ajax/paths',
                dataType: 'json',
                data: {
                    dungeonroute: this.map.getDungeonRoute().publicKey,
                    floor_id: floor.id
                },
                success: function (json) {
                    // Now draw the patrols on the map
                    for (let index in json) {
                        if (json.hasOwnProperty(index)) {
                            let points = [];
                            let remotePath = json[index];

                            // Create the polyline first
                            let polyline = remotePath.polyline;
                            let vertices = JSON.parse(polyline.vertices_json);

                            for (let j = 0; j < vertices.length; j++) {
                                let vertex = vertices[j];
                                points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                            }

                            let layer = L.polyline(points);

                            let route = self.createNew(layer);
                            route.id = remotePath.id;
                            route.setColor(polyline.color);
                            route.setWeight(polyline.weight);

                            // We just downloaded the enemy pack, it's synced alright!
                            route.setSynced(true);
                        }
                    }

                    self.signal('fetchsuccess');
                }
            });
        } else {
            // At least let the map know we're done
            self.signal('fetchsuccess');
        }
    }
}
class BrushlineMapObjectGroup extends MapObjectGroup {
    constructor(map, name, editable) {
        super(map, name, editable);

        this.title = 'Hide/show brushlines';
        this.fa_class = 'fa-paint-brush';
    }

    _createObject(layer) {
        console.assert(this instanceof BrushlineMapObjectGroup, 'this is not an BrushlineMapObjectGroup');

        return new Brushline(this.map, layer);
    }


    fetchFromServer(floor) {
        // no super call required
        console.assert(this instanceof BrushlineMapObjectGroup, this, 'this is not a BrushlineMapObjectGroup');

        let self = this;

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'GET',
                url: '/ajax/brushlines',
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
                            let remoteBrushline = json[index];

                            // Create the polyline first
                            let polyline = remoteBrushline.polyline;
                            let vertices = JSON.parse(polyline.vertices_json);

                            for (let j = 0; j < vertices.length; j++) {
                                let vertex = vertices[j];
                                points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                            }

                            let layer = L.polyline(points);

                            // Now that we have the layer, create the brushline
                            let brushLine = self.createNew(layer);
                            brushLine.id = remoteBrushline.id;
                            brushLine.setColor(polyline.color);
                            brushLine.setWeight(polyline.weight);

                            // We just downloaded the brushline, make it synced
                            brushLine.setSynced(true);
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
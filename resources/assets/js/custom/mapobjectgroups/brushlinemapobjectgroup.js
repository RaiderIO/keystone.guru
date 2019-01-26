class BrushLineMapObjectGroup extends MapObjectGroup {
    constructor(map, name, editable) {
        super(map, name, editable);

        this.title = 'Hide/show brush lines';
        this.fa_class = 'fa-paint-brush';
    }

    _createObject(layer) {
        console.assert(this instanceof BrushLineMapObjectGroup, 'this is not an BrushLineMapObjectGroup');

        return new BrushLine(this.map, layer);
    }


    fetchFromServer(floor, callback) {
        // no super call required
        console.assert(this instanceof BrushLineMapObjectGroup, this, 'this is not a BrushLineMapObjectGroup');

        let self = this;

        // No network traffic if this is enabled!
        if (!this.map.isTryModeEnabled()) {
            $.ajax({
                type: 'GET',
                url: '/ajax/polylines',
                dataType: 'json',
                data: {
                    dungeonroute: this.map.getDungeonRoute().publicKey,
                    floor_id: floor.id,
                    type: 'brushline'
                },
                success: function (json) {
                    // Now draw the patrols on the map
                    for (let index in json) {
                        if (json.hasOwnProperty(index)) {
                            let points = [];
                            let remoteBrushLine = json[index];
                            let vertices = JSON.parse(remoteBrushLine.vertices_json);

                            for (let j = 0; j < vertices.length; j++) {
                                let vertex = vertices[j];
                                points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                            }

                            let layer = L.polyline(points);

                            let brushLine = self.createNew(layer);
                            brushLine.id = remoteBrushLine.id;
                            brushLine.setColor(remoteBrushLine.color);
                            brushLine.setWeight(remoteBrushLine.weight);
                            // We just downloaded the enemy pack, it's synced alright!
                            brushLine.setSynced(true);
                        }
                    }

                    callback();
                }
            });
        } else {
            // At least let the map know we're done
            callback();
        }
    }
}
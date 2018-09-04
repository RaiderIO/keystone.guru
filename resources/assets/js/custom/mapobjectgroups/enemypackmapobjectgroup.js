class EnemyPackMapObjectGroup extends MapObjectGroup {
    constructor(map, name, classname, editable) {
        super(map, name, editable);

        this.classname = classname;
        this.title = 'Hide/show enemy packs';
        this.fa_class = 'fa-draw-polygon';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPackMapObjectGroup, 'this is not an EnemyPackMapObjectGroup');

        switch (this.classname) {
            case "AdminEnemyPack":
                return new AdminEnemyPack(this.map, layer);
            default:
                return new EnemyPack(this.map, layer);
        }
    }

    fetchFromServer(floor, callback) {
        // no super call required
        console.assert(this instanceof EnemyPackMapObjectGroup, this, 'this is not a EnemyPackMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/ajax/enemypacks',
            dataType: 'json',
            data: {
                floor_id: floor.id
            },
            success: function (json) {
                // Remove any layers that were added before
                self._removeObjectsFromLayer.call(self);

                // Now draw the packs on the map
                for (let i = 0; i < json.length; i++) {
                    let points = [];
                    let remoteEnemyPack = json[i];
                    for (let j = 0; j < remoteEnemyPack.vertices.length; j++) {
                        let vertex = remoteEnemyPack.vertices[j];
                        // I.. don't really know why this needs to be lng/lat but it needs to be
                        points.push([vertex.lng, vertex.lat]);
                    }

                    let layer = L.polygon(points);

                    let enemyPack = self.createNew(layer);
                    enemyPack.id = remoteEnemyPack.id;
                    // We just downloaded the enemy pack, it's synced alright!
                    enemyPack.setSynced(true);
                }

                callback();
            }
        });
    }
}
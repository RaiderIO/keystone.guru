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

    fetchFromServer(floor) {
        // no super call required
        console.assert(this instanceof EnemyPackMapObjectGroup, this, 'this is not a EnemyPackMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/ajax/enemypacks',
            dataType: 'json',
            data: {
                floor_id: floor.id,
                // Non-admin = get enemy locations instead
                vertices: isAdmin ? 1 : 0,
                teeming: self.map.teeming ? 1 : 0
            },
            success: function (json) {
                // Now draw the packs on the map
                for (let i = 0; i < json.length; i++) {
                    let points = [];
                    let remoteEnemyPack = json[i];

                    let faction = self.map.getDungeonRoute().faction;

                    if (remoteEnemyPack.faction !== 'any' && faction !== 'any' && faction !== remoteEnemyPack.faction) {
                        console.log('Skipping enemy pack that does not belong to the requested faction ', remoteEnemyPack, faction);
                        continue;
                    }

                    // Fetch the correct location for the vertices
                    let isVertices = typeof remoteEnemyPack.vertices !== 'undefined';
                    let vertices = isVertices ? remoteEnemyPack.vertices : remoteEnemyPack.enemies;

                    for (let j = 0; j < vertices.length; j++) {
                        let vertex = vertices[j];
                        if (isVertices) {
                            // I.. don't really know why this needs to be lng/lat but it needs to be
                            points.push([vertex.lng, vertex.lat]);
                        } else {
                            points.push([vertex.lat, vertex.lng]);
                        }
                    }

                    // Build a layer based off a hull if we're supposed to
                    let layer = null;
                    if (!isVertices) {
                        let p = hull(points, 100);
                        // Only if we can actually make an offset
                        if (p.length > 1) {
                            let offset = new Offset();
                            p = offset.data(p).arcSegments(c.map.enemypack.arcSegments(p.length)).margin(c.map.enemypack.margin);

                            layer = L.polygon(p, c.map.enemypack.polygonOptions);
                        }
                    }

                    // If a layer wasn't created before
                    if (layer === null) {
                        // Make one now with those exact points
                        layer = L.polygon(points);
                    }

                    let enemyPack = self.createNew(layer);
                    enemyPack.id = remoteEnemyPack.id;
                    enemyPack.faction = remoteEnemyPack.faction;
                    // We just downloaded the enemy pack, it's synced alright!
                    enemyPack.setSynced(true);
                }

                self.signal('fetchsuccess');
            }
        });
    }
}
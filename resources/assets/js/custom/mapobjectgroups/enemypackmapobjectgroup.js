class EnemyPackMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show enemy packs';
        this.fa_class = 'fa-draw-polygon';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPackMapObjectGroup, 'this is not an EnemyPackMapObjectGroup');

        if (isMapAdmin) {
            return new AdminEnemyPack(this.manager.map, layer);
        } else {
            return new EnemyPack(this.manager.map, layer);
        }
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);

        // no super call required
        console.assert(this instanceof EnemyPackMapObjectGroup, this, 'this is not a EnemyPackMapObjectGroup');

        let enemyPacks = response.enemypack;

        // Now draw the packs on the map
        for (let i = 0; i < enemyPacks.length; i++) {
            let points = [];
            let layer = null;
            let remoteEnemyPack = enemyPacks[i];

            let faction = this.manager.map.getDungeonRoute().faction;

            if (remoteEnemyPack.faction !== 'any' && faction !== 'any' && faction !== remoteEnemyPack.faction) {
                console.log('Skipping enemy pack that does not belong to the requested faction ', remoteEnemyPack, faction);
                continue;
            }

            // Create a polygon from the vertices as normal
            if (typeof remoteEnemyPack.vertices_json !== 'undefined') {
                let vertices = JSON.parse(remoteEnemyPack.vertices_json);

                for (let j = 0; j < vertices.length; j++) {
                    let vertex = vertices[j];
                    points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                }

                layer = L.polygon(points);
            }
            // Create a polygon based on a hull of points from the enemies in this pack
            else {
                let vertices = remoteEnemyPack.enemies;

                for (let j = 0; j < vertices.length; j++) {
                    let vertex = vertices[j];
                    points.push([vertex.lat, vertex.lng]);
                }

                // Build a layer based off a hull if we're supposed to
                let p = hull(points, 100);
                // Only if we can actually make an offset
                if (p.length > 1) {
                    let offset = new Offset();
                    p = offset.data(p).arcSegments(c.map.enemypack.arcSegments(p.length)).margin(c.map.enemypack.margin);

                    layer = L.polygon(p, c.map.enemypack.polygonOptions);
                }
            }

            if (layer !== null) {
                let enemyPack = this.createNew(layer);
                enemyPack.id = remoteEnemyPack.id;
                enemyPack.faction = remoteEnemyPack.faction;

                // We just downloaded the enemy pack, it's synced alright!
                enemyPack.setSynced(true);
            } else {
                console.error('Unable to create layer for enemypack ' + remoteEnemyPack.id + '; not enough data points');
            }
        }
    }
}
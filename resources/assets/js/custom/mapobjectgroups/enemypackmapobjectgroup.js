class EnemyPackMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show enemy packs';
        this.fa_class = 'fa-draw-polygon';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPackMapObjectGroup, 'this is not an EnemyPackMapObjectGroup', this);

        if (isMapAdmin) {
            return new AdminEnemyPack(this.manager.map, layer);
        } else {
            return new EnemyPack(this.manager.map, layer);
        }
    }

    _restoreObject(remoteMapObject) {
        console.assert(this instanceof EnemyPackMapObjectGroup, 'this is not an EnemyPackMapObjectGroup', this);

        // Check teeming, faction status
        if (this._isObjectVisible(remoteMapObject)) {
            let points = [];
            let layer = null;

            // Create a polygon from the vertices as normal
            if (typeof remoteMapObject.vertices_json !== 'undefined') {
                let vertices = JSON.parse(remoteMapObject.vertices_json);

                for (let j = 0; j < vertices.length; j++) {
                    let vertex = vertices[j];
                    points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                }

                layer = L.polygon(points);
            }
            // Create a polygon based on a hull of points from the enemies in this pack
            else {
                let vertices = remoteMapObject.enemies;

                for (let j = 0; j < vertices.length; j++) {
                    let vertex = vertices[j];
                    points.push([vertex.lat, vertex.lng]);
                }

                // Build a layer based off a hull if we're supposed to
                let p = hull(points, 100);
                // Only if we can actually make an offset
                if (p.length > 1) {
                    try {
                        let offset = new Offset();
                        p = offset.data(p).arcSegments(c.map.enemypack.arcSegments(p.length)).margin(c.map.enemypack.margin);

                        layer = L.polygon(p, c.map.enemypack.polygonOptions);
                    } catch (error) {
                        // Not particularly interesting to spam the console with
                        // console.error('Unable to create offset for pack', remoteMapObject.id, error);
                    }
                }
            }

            if (layer !== null) {
                let enemyPack = this.createNew(layer);
                enemyPack.id = remoteMapObject.id;
                enemyPack.teeming = remoteMapObject.teeming;
                enemyPack.faction = remoteMapObject.faction;

                // Restore beguiling enemies
                // 1. Make sure we can resolve the IDs of the enemies
                /** @var enemyMapObjectGroup MapObjectGroup */

                    // Our data
                let enemyMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_ENEMY);
                for (let i = 0; i < remoteMapObject.beguilingenemyids.length; i++) {
                    let id = remoteMapObject.beguilingenemyids[i];

                    // Resolve it
                    let enemy = enemyMapObjectGroup.findMapObjectById(id);
                    // Verify
                    console.assert(enemy !== null, 'Unable to find beguiling enemy!', this);

                    // Restore it on the enemy pack
                    enemyPack.beguilingenemies.push(enemy);
                }

                // We just downloaded the enemy pack, it's synced alright!
                enemyPack.setSynced(true);
            } else {
                console.warn('Unable to create layer for enemypack ' + remoteMapObject.id + '; not enough data points');
            }
        }
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);

        // no super call required
        console.assert(this instanceof EnemyPackMapObjectGroup, 'this is not a EnemyPackMapObjectGroup', this);

        let enemyPacks = response.enemypack;

        // Now draw the packs on the map
        for (let i = 0; i < enemyPacks.length; i++) {
            this._restoreObject(enemyPacks[i]);
        }
    }
}
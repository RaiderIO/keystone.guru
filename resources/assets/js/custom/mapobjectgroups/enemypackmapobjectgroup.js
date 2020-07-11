class EnemyPackMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY_PACK, 'enemypack', editable);

        this.title = 'Hide/show enemy packs';
        this.fa_class = 'fa-draw-polygon';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPackMapObjectGroup, 'this is not an EnemyPackMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemyPack(this.manager.map, layer);
        } else {
            return new EnemyPack(this.manager.map, layer);
        }
    }

    /**
     * @inheritDoc
     */
    _restoreObject(remoteMapObject, username = null) {
        console.assert(this instanceof EnemyPackMapObjectGroup, 'this is not an EnemyPackMapObjectGroup', this);

        let enemyPack = null;

        // Check teeming, faction status
        let points = [];
        let layer = null;

        // Create a polygon from the vertices as normal
        if (typeof remoteMapObject.vertices_json !== 'undefined') {
            let vertices = JSON.parse(remoteMapObject.vertices_json);

            for (let j = 0; j < vertices.length; j++) {
                let vertex = vertices[j];
                points.push([vertex.lat, vertex.lng]);
            }

            layer = L.polygon(points);
        }

        /** @type {EnemyPack} */
        enemyPack = this.createNew(layer);
        enemyPack.loadRemoteMapObject(remoteMapObject);

        // Only called when not in admin state
        if (layer === null) {
            // Re-set the layer now that we know of the raw enemies
            enemyPack.setRawEnemies(remoteMapObject.enemies);
            this.setLayerToMapObject(enemyPack.createHullLayer(), enemyPack);
        }

        // We just downloaded the enemy pack, it's synced alright!
        enemyPack.setSynced(true);

        return enemyPack;
    }
}
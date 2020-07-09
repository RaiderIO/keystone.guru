class EnemyPatrolMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_ENEMY_PATROL, 'enemypatrol', editable);

        this.title = 'Hide/show enemy patrol routes';
        this.fa_class = 'fa-exchange-alt';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not an EnemyPatrolMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminEnemyPatrol(this.manager.map, layer);
        } else {
            return new EnemyPatrol(this.manager.map, layer);
        }
    }

    /**
     * @inheritDoc
     */
    _restoreObject(remoteMapObject, username = null) {
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not a EnemyPatrolMapObjectGroup', this);

        let enemyPatrol = null;
        let points = [];

        // Create the polyline first
        let polyline = remoteMapObject.polyline;
        let vertices = JSON.parse(polyline.vertices_json);

        for (let j = 0; j < vertices.length; j++) {
            let vertex = vertices[j];
            points.push([vertex.lat, vertex.lng]);
        }

        let layer = L.polyline(points);

        enemyPatrol = this.createNew(layer);
        enemyPatrol.loadRemoteMapObject(remoteMapObject);
        enemyPatrol.loadRemoteMapObject(remoteMapObject.polyline);

        // We just downloaded the enemy patrol, it's synced alright!
        enemyPatrol.setSynced(true);

        return enemyPatrol;
    }
}
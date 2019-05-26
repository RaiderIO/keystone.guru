class EnemyPatrolMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show enemy patrol routes';
        this.fa_class = 'fa-exchange-alt';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not an EnemyPatrolMapObjectGroup', this);

        if (isMapAdmin) {
            return new AdminEnemyPatrol(this.manager.map, layer);
        } else {
            return new EnemyPatrol(this.manager.map, layer);
        }
    }

    _restoreObject(remoteMapObject) {
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not a EnemyPatrolMapObjectGroup', this);
        let points = [];

        let faction = this.manager.map.getDungeonRoute().faction;
        if (remoteMapObject.faction !== 'any' && faction !== 'any' && faction !== remoteMapObject.faction) {
            console.log('Skipping enemy patrol that does not belong to the requested faction ', remoteMapObject, faction);
            return;
        }

        // Create the polyline first
        let polyline = remoteMapObject.polyline;
        let vertices = JSON.parse(polyline.vertices_json);

        for (let j = 0; j < vertices.length; j++) {
            let vertex = vertices[j];
            points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
        }

        let layer = L.polyline(points);

        let enemyPatrol = this.createNew(layer);
        enemyPatrol.id = remoteMapObject.id;
        enemyPatrol.enemy_id = remoteMapObject.enemy_id;
        enemyPatrol.faction = remoteMapObject.faction;

        // We just downloaded the enemy patrol, it's synced alright!
        enemyPatrol.setSynced(true);
    }

    _fetchSuccess(response) {
        // no super call required
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not a EnemyPatrolMapObjectGroup', this);

        let enemyPatrols = response.enemypatrol;

        // Now draw the patrols on the map
        for (let index in enemyPatrols) {
            if (enemyPatrols.hasOwnProperty(index)) {
                this._restoreObject(enemyPatrols[index]);
            }
        }
    }
}
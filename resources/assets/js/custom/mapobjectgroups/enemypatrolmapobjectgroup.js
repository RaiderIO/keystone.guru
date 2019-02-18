class EnemyPatrolMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, classname, editable) {
        super(manager, name, editable);

        this.classname = classname;
        this.title = 'Hide/show enemy patrol routes';
        this.fa_class = 'fa-exchange-alt';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not an EnemyPatrolMapObjectGroup');

        switch (this.classname) {
            case "AdminEnemyPatrol":
                return new AdminEnemyPatrol(this.manager.map, layer);
            default:
                return new EnemyPatrol(this.manager.map, layer);
        }
    }


    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof EnemyPatrolMapObjectGroup, this, 'this is not a EnemyPatrolMapObjectGroup');

        let enemyPatrols = response.enemypatrol;

        // Now draw the patrols on the map
        for (let index in enemyPatrols) {
            if (enemyPatrols.hasOwnProperty(index)) {
                let points = [];
                let remoteEnemyPatrol = enemyPatrols[index];

                let faction = this.manager.map.getDungeonRoute().faction;
                if (remoteEnemyPatrol.faction !== 'any' && faction !== 'any' && faction !== remoteEnemyPatrol.faction) {
                    console.log('Skipping enemy patrol that does not belong to the requested faction ', remoteEnemyPatrol, faction);
                    continue;
                }

                // Create the polyline first
                let polyline = remoteEnemyPatrol.polyline;
                let vertices = JSON.parse(polyline.vertices_json);

                for (let j = 0; j < vertices.length; j++) {
                    let vertex = vertices[j];
                    points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                }

                let layer = L.polyline(points);

                let enemyPatrol = this.createNew(layer);
                enemyPatrol.id = remoteEnemyPatrol.id;
                enemyPatrol.enemy_id = remoteEnemyPatrol.enemy_id;
                enemyPatrol.faction = remoteEnemyPatrol.faction;

                // We just downloaded the enemy patrol, it's synced alright!
                enemyPatrol.setSynced(true);
            }
        }
    }
}
class EnemyPatrolMapObjectGroup extends MapObjectGroup {
    constructor(map, name, classname, editable) {
        super(map, name, editable);

        this.classname = classname;
        this.title = 'Hide/show enemy patrol routes';
        this.fa_class = 'fa-exchange-alt';
    }

    _createObject(layer) {
        console.assert(this instanceof EnemyPatrolMapObjectGroup, 'this is not an EnemyPatrolMapObjectGroup');

        switch (this.classname) {
            case "AdminEnemyPatrol":
                return new AdminEnemyPatrol(this.map, layer);
            default:
                return new EnemyPatrol(this.map, layer);
        }
    }


    fetchFromServer(floor) {
        // no super call required
        console.assert(this instanceof EnemyPatrolMapObjectGroup, this, 'this is not a EnemyPatrolMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/ajax/enemypatrols',
            dataType: 'json',
            data: {
                floor_id: floor.id
            },
            success: function (json) {
                // Now draw the patrols on the map
                for (let index in json) {
                    if (json.hasOwnProperty(index)) {
                        let points = [];
                        let remoteEnemyPatrol = json[index];

                        let faction = self.map.getDungeonRoute().faction;
                        if (remoteEnemyPatrol.faction !== 'any' && faction !== 'any' && faction !== remoteEnemyPatrol.faction) {
                            console.log('Skipping enemy patrol that does not belong to the requested faction ', remoteEnemyPatrol, faction);
                            continue;
                        }

                        let vertices = JSON.parse(remoteEnemyPatrol.vertices_json);

                        for (let j = 0; j < vertices.length; j++) {
                            let vertex = vertices[j];
                            points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                        }

                        let layer = L.polyline(points);

                        let enemyPatrol = self.createNew(layer);
                        enemyPatrol.id = remoteEnemyPatrol.id;
                        enemyPatrol.enemy_id = remoteEnemyPatrol.enemy_id;
                        enemyPatrol.faction = remoteEnemyPatrol.faction;
                        // We just downloaded the enemy pack, it's synced alright!
                        enemyPatrol.setSynced(true);
                    }
                }

                self.signal('fetchsuccess');
            }
        });
    }
}
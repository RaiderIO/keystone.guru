class DungeonStartMarkerMapObjectGroup extends MapObjectGroup {
    constructor(map, name, classname){
        super(map, name);

        this.classname = classname;
        this.title = 'Hide/show dungeon start';
        this.fa_class = 'fa-flag';
    }

    _createObject(layer){
        console.assert(this instanceof DungeonStartMarkerMapObjectGroup, 'this is not an DungeonStartMarkerMapObjectGroup');

        switch (this.classname) {
            case "AdminDungeonStartMarker":
                return new AdminDungeonStartMarker(this.map, layer);
            default:
                return new DungeonStartMarker(this.map, layer);
        }
    }

    fetchFromServer(floor){
        // no super call required
        console.assert(this instanceof DungeonStartMarkerMapObjectGroup, this, 'this is not a DungeonStartMarkerMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/api/v1/enemies',
            dataType: 'json',
            data: {
                floor_id: floor.id
            },
            success: function (json) {
                // Remove any layers that were added before
                self._removeObjectsFromLayer.call(self);

                // Now draw the enemies on the map
                for (let index in json) {
                    if (json.hasOwnProperty(index)) {
                        let remoteEnemy = json[index];

                        let layer = L.circleMarker();
                        layer.setLatLng(L.latLng(remoteEnemy.lat, remoteEnemy.lng));

                        let enemy = self.createNew(layer);
                        enemy.id = remoteEnemy.id;
                        enemy.enemypack = remoteEnemy.enemy_pack_id;
                        enemy.npc_id = remoteEnemy.npc_id;
                        enemy.floor_id = remoteEnemy.floor_id;
                        // We just downloaded the enemy pack, it's synced alright!
                        enemy.setSynced(true);
                    }
                }
            }
        });
    }
}
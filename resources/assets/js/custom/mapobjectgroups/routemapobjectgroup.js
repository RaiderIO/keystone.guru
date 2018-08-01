class RouteMapObjectGroup extends MapObjectGroup {
    constructor(map, name, classname){
        super(map, name);

        this.classname = classname;
    }

    _createObject(layer){
        console.assert(this instanceof RouteMapObjectGroup, 'this is not an RouteMapObjectGroup');

        switch (this.classname) {
            // case "AdminRoute":
            //     return new AdminEnemyPack(this.map, layer);
            default:
                return new Route(this.map, layer);
        }
    }

    fetchFromServer(floor){
        // no super call required
        console.assert(this instanceof RouteMapObjectGroup, this, 'this is not a RouteMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/api/v1/route',
            dataType: 'json',
            data: {
                dungeon_route_id: floor.id
            },
            success: function (json) {
                // Remove any layers that were added before
                self._removeObjectsFromLayer.call(self);

                // // Now draw the packs on the map
                // for (let i = 0; i < json.length; i++) {
                //     let points = [];
                //     let remoteEnemyPack = json[i];
                //     for (let j = 0; j < remoteEnemyPack.vertices.length; j++) {
                //         let vertex = remoteEnemyPack.vertices[j];
                //         points.push([vertex.y, vertex.x]);
                //     }
                //
                //     let layer = L.polygon(points);
                //
                //     let enemyPack = self.createNew(layer);
                //     enemyPack.id = remoteEnemyPack.id;
                //     // We just downloaded the enemy pack, it's synced alright!
                //     enemyPack.setSynced(true);
                // }
            }
        });
    }
}
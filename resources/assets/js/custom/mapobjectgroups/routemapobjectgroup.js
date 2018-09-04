class RouteMapObjectGroup extends MapObjectGroup {
    constructor(map, name, editable){
        super(map, name, editable);

        this.title = 'Hide/show route';
        this.fa_class = 'fa-route';
    }

    _createObject(layer){
        console.assert(this instanceof RouteMapObjectGroup, 'this is not an RouteMapObjectGroup');

        return new Route(this.map, layer);
    }


    fetchFromServer(floor, callback) {
        // no super call required
        console.assert(this instanceof RouteMapObjectGroup, this, 'this is not a RouteMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/ajax/routes',
            dataType: 'json',
            data: {
                dungeonroute: dungeonRoutePublicKey, // defined in map.blade.php
                floor_id: floor.id
            },
            success: function (json) {
                // Remove any layers that were added before
                self._removeObjectsFromLayer.call(self);

                // Now draw the patrols on the map
                for (let index in json) {
                    if (json.hasOwnProperty(index)) {
                        let points = [];
                        let remoteRoute = json[index];

                        for (let j = 0; j < remoteRoute.vertices.length; j++) {
                            let vertex = remoteRoute.vertices[j];
                            points.push([vertex.lng, vertex.lat]); // dunno why it must be lng/lat
                        }

                        let layer = L.polyline(points);

                        let route = self.createNew(layer);
                        route.id = remoteRoute.id;
                        route.setRouteColor(remoteRoute.color);
                        // We just downloaded the enemy pack, it's synced alright!
                        route.setSynced(true);
                    }
                }

                callback();
            }
        });
    }
}
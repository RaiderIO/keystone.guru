class DungeonStartMarkerMapObjectGroup extends MapObjectGroup {
    constructor(map, name, classname, editable){
        super(map, name, editable);

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

    fetchFromServer(floor, callback){
        // no super call required
        console.assert(this instanceof DungeonStartMarkerMapObjectGroup, this, 'this is not a DungeonStartMarkerMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/ajax/dungeonstartmarkers',
            dataType: 'json',
            data: {
                floor_id: floor.id
            },
            success: function (json) {
                // Now draw the enemies on the map
                for (let index in json) {
                    if (json.hasOwnProperty(index)) {
                        let remoteDungeonStartMarker = json[index];

                        let layer = new LeafletDungeonStartMarker();
                        layer.setLatLng(L.latLng(remoteDungeonStartMarker.lat, remoteDungeonStartMarker.lng));

                        let dungeonStartMarker = self.createNew(layer);
                        dungeonStartMarker.id = remoteDungeonStartMarker.id;
                        dungeonStartMarker.floor_id = remoteDungeonStartMarker.floor_id;
                        // We just downloaded the enemy pack, it's synced alright!
                        dungeonStartMarker.setSynced(true);
                    }
                }

                callback();
            }
        });
    }
}
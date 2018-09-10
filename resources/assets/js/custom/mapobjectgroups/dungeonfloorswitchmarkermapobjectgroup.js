class DungeonFloorSwitchMarkerMapObjectGroup extends MapObjectGroup {
    constructor(map, name, classname, editable){
        super(map, name, editable);

        this.classname = classname;
        this.title = 'Hide/show floor switch markers';
        this.fa_class = 'fa-door-open';
    }

    _createObject(layer){
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, 'this is not an DungeonFloorSwitchMarkerMapObjectGroup');

        switch (this.classname) {
            case "AdminDungeonFloorSwitchMarker":
                return new AdminDungeonFloorSwitchMarker(this.map, layer);
            default:
                return new DungeonFloorSwitchMarker(this.map, layer);
        }
    }

    fetchFromServer(floor, callback){
        // no super call required
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, this, 'this is not a DungeonFloorSwitchMarkerMapObjectGroup');

        let self = this;

        $.ajax({
            type: 'GET',
            url: '/ajax/dungeonfloorswitchmarkers',
            dataType: 'json',
            data: {
                floor_id: floor.id
            },
            success: function (json) {
                // Now draw the enemies on the map
                for (let index in json) {
                    if (json.hasOwnProperty(index)) {
                        let remoteDungeonFloorSwitchMarker = json[index];

                        let layer = new LeafletDungeonFloorSwitchMarker();
                        layer.setLatLng(L.latLng(remoteDungeonFloorSwitchMarker.lat, remoteDungeonFloorSwitchMarker.lng));

                        let dungeonFloorSwitchMarker = self.createNew(layer);
                        dungeonFloorSwitchMarker.id = remoteDungeonFloorSwitchMarker.id;
                        dungeonFloorSwitchMarker.floor_id = remoteDungeonFloorSwitchMarker.floor_id;
                        dungeonFloorSwitchMarker.target_floor_id = remoteDungeonFloorSwitchMarker.target_floor_id;
                        // We just downloaded the enemy pack, it's synced alright!
                        dungeonFloorSwitchMarker.setSynced(true);
                    }
                }

                callback();
            }
        });
    }
}
class DungeonStartMarkerMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, classname, editable) {
        super(manager, name, editable);

        this.classname = classname;
        this.title = 'Hide/show dungeon start';
        this.fa_class = 'fa-flag';
    }

    _createObject(layer) {
        console.assert(this instanceof DungeonStartMarkerMapObjectGroup, 'this is not an DungeonStartMarkerMapObjectGroup');

        switch (this.classname) {
            case "AdminDungeonStartMarker":
                return new AdminDungeonStartMarker(this.manager.map, layer);
            default:
                return new DungeonStartMarker(this.manager.map, layer);
        }
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof DungeonStartMarkerMapObjectGroup, this, 'this is not a DungeonStartMarkerMapObjectGroup');

        let startMarkers = response.dungeonstartmarker;

        // Now draw the enemies on the map
        for (let index in startMarkers) {
            if (startMarkers.hasOwnProperty(index)) {
                let remoteDungeonStartMarker = startMarkers[index];

                let layer = new LeafletDungeonStartMarker();
                layer.setLatLng(L.latLng(remoteDungeonStartMarker.lat, remoteDungeonStartMarker.lng));

                let dungeonStartMarker = this.createNew(layer);
                dungeonStartMarker.id = remoteDungeonStartMarker.id;
                dungeonStartMarker.floor_id = remoteDungeonStartMarker.floor_id;
                // We just downloaded the enemy pack, it's synced alright!
                dungeonStartMarker.setSynced(true);
            }
        }
    }
}
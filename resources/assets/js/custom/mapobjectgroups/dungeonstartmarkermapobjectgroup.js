class DungeonStartMarkerMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show dungeon start';
        this.fa_class = 'fa-flag';
    }

    _createObject(layer) {
        console.assert(this instanceof DungeonStartMarkerMapObjectGroup, 'this is not an DungeonStartMarkerMapObjectGroup');

        if (isAdmin) {
            return new AdminDungeonStartMarker(this.manager.map, layer);
        } else {
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

                // We just downloaded the start marker, it's synced alright!
                dungeonStartMarker.setSynced(true);
            }
        }
    }
}
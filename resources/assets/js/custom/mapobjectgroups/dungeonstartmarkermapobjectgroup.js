class DungeonStartMarkerMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show dungeon start';
        this.fa_class = 'fa-flag';
    }

    _createObject(layer) {
        console.assert(this instanceof DungeonStartMarkerMapObjectGroup, 'this is not an DungeonStartMarkerMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminDungeonStartMarker(this.manager.map, layer);
        } else {
            return new DungeonStartMarker(this.manager.map, layer);
        }
    }

    _restoreObject(remoteMapObject) {
        let layer = new LeafletDungeonStartMarker();
        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

        let dungeonStartMarker = this.createNew(layer);
        dungeonStartMarker.id = remoteMapObject.id;
        dungeonStartMarker.floor_id = remoteMapObject.floor_id;

        // We just downloaded the start marker, it's synced alright!
        dungeonStartMarker.setSynced(true);
    }

    _fetchSuccess(response) {
        super._fetchSuccess(response);
        // no super call required
        console.assert(this instanceof DungeonStartMarkerMapObjectGroup, 'this is not a DungeonStartMarkerMapObjectGroup', this);

        let startMarkers = response.dungeonstartmarker;

        // Now draw the enemies on the map
        for (let index in startMarkers) {
            if (startMarkers.hasOwnProperty(index)) {
                this._restoreObject(startMarkers[index]);
            }
        }
    }
}
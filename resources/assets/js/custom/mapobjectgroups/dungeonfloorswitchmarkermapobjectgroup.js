class DungeonFloorSwitchMarkerMapObjectGroup extends MapObjectGroup {
    constructor(manager, name, editable) {
        super(manager, name, editable);

        this.title = 'Hide/show floor switch markers';
        this.fa_class = 'fa-door-open';
    }

    _createObject(layer) {
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, 'this is not an DungeonFloorSwitchMarkerMapObjectGroup', this);

        if (isMapAdmin) {
            return new AdminDungeonFloorSwitchMarker(this.manager.map, layer);
        } else {
            return new DungeonFloorSwitchMarker(this.manager.map, layer);
        }
    }

    _restoreObject(remoteMapObject) {
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, 'this is not a DungeonFloorSwitchMarkerMapObjectGroup', this);

        let layer;
        switch(remoteMapObject.direction){
            case 'up':
                layer = new LeafletDungeonFloorSwitchMarkerUp();
                break;
            case 'down':
                layer = new LeafletDungeonFloorSwitchMarkerDown();
                break;
            case 'left':
                layer = new LeafletDungeonFloorSwitchMarkerLeft();
                break;
            case 'right':
                layer = new LeafletDungeonFloorSwitchMarkerRight();
                break;
            default:
                layer = new LeafletDungeonFloorSwitchMarker();
                break;
        }

        layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));

        let dungeonFloorSwitchMarker = this.createNew(layer);
        dungeonFloorSwitchMarker.id = remoteMapObject.id;
        dungeonFloorSwitchMarker.floor_id = remoteMapObject.floor_id;
        dungeonFloorSwitchMarker.target_floor_id = remoteMapObject.target_floor_id;

        // We just downloaded the floor switch marker, it's synced alright!
        dungeonFloorSwitchMarker.setSynced(true);
    }

    _fetchSuccess(response) {
        // no super call required
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, 'this is not a DungeonFloorSwitchMarkerMapObjectGroup', this);

        let floorSwitchMarkers = response.dungeonfloorswitchmarker;

        // Now draw the enemies on the map
        for (let index in floorSwitchMarkers) {
            if (floorSwitchMarkers.hasOwnProperty(index)) {
                this._restoreObject(floorSwitchMarkers[index]);
            }
        }
    }
}
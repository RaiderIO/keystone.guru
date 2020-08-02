class DungeonFloorSwitchMarkerMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER, 'dungeonfloorswitchmarker', editable);

        this.title = 'Hide/show floor switch markers';
        this.fa_class = 'fa-door-open';
    }

    _createObject(layer) {
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, 'this is not an DungeonFloorSwitchMarkerMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminDungeonFloorSwitchMarker(this.manager.map, layer);
        } else {
            return new DungeonFloorSwitchMarker(this.manager.map, layer);
        }
    }

    /**
     * @inheritDoc
     */
    _restoreObject(remoteMapObject, username = null) {
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, 'this is not a DungeonFloorSwitchMarkerMapObjectGroup', this);

        // Fetch the existing dungeonFloorSwitchMarker if it exists
        let dungeonFloorSwitchMarker = this.findMapObjectById(remoteMapObject.id);

        // Only create a new one if it's new for us
        let layer;
        if (dungeonFloorSwitchMarker === null) {
            switch (remoteMapObject.direction) {
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

            /** @type DungeonFloorSwitchMarker */
            dungeonFloorSwitchMarker = this.createNew(layer);
        } else {
            // Update position
            dungeonFloorSwitchMarker.layer.setLatLng(L.latLng(remoteMapObject.lat, remoteMapObject.lng));
        }


        dungeonFloorSwitchMarker.loadRemoteMapObject(remoteMapObject);

        // We just downloaded the floor switch marker, it's synced alright!
        dungeonFloorSwitchMarker.setSynced(true);

        // Show echo notification or not
        this._showReceivedFromEcho(dungeonFloorSwitchMarker, username);

        return dungeonFloorSwitchMarker;
    }
}
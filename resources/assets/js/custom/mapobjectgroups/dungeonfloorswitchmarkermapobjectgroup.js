class DungeonFloorSwitchMarkerMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER, editable);

        this.title = 'Hide/show floor switch markers';
        this.fa_class = 'fa-door-open';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getDungeonFloorSwitchMarkers();
    }

    /**
     * @inheritDoc
     */
    _createLayer(remoteMapObject) {
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, 'this is not an DungeonFloorSwitchMarkerMapObjectGroup', this);

        let layer;
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

        return layer;
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof DungeonFloorSwitchMarkerMapObjectGroup, 'this is not an DungeonFloorSwitchMarkerMapObjectGroup', this);

        if (getState().isMapAdmin()) {
            return new AdminDungeonFloorSwitchMarker(this.manager.map, layer);
        } else {
            return new DungeonFloorSwitchMarker(this.manager.map, layer);
        }
    }
}
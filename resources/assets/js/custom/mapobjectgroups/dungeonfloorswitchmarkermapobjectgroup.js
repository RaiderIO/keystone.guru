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
        let layer = new LeafletIconMarker();
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

    /**
     * Get the closest floor switch marker to a specific floor, on a lat lng on the current floor
     * @param floorId {Number}
     * @param targetFloorId {Number}
     * @param latLng {L.latLng}
     */
    getClosestMarker(floorId, targetFloorId, latLng) {
        let result = null;
        let shortlist = [];

        for (let i = 0; i < this.objects.length; i++) {
            let object = this.objects[i];

            if (object.floor_id === floorId && object.target_floor_id === targetFloorId) {
                shortlist.push(object);
            }
        }

        // If there's only one on the shortlist we don't need to do difficult and just return it
        if (shortlist.length === 1) {
            result = shortlist[0];
        } else {
            // Determine the closest one and return it
            let closestDistance = 999999999999999;

            for (let i = 0; i < shortlist.length; i++) {
                let distance = getLatLngDistance(shortlist[i].layer.getLatLng(), latLng);
                if (closestDistance > distance) {
                    result = shortlist[i];
                    closestDistance = distance;
                }
            }
        }

        return result;
    }
}

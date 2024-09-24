class DungeonFloorSwitchMarkerMapObjectGroup extends MapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER, editable);

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
     *
     * @param sourceFloorId {Number}
     * @param targetFloorId {Number}
     * @param latLng {L.latLng|null}
     * @param facade {Boolean}
     * @private
     */
    _findMarkerByTargetFloorId(sourceFloorId, targetFloorId, latLng, facade = false) {
        if (latLng === null) {
            // Center of the map
            latLng = new L.latLng(-128, 192);
        }

        let result = null;

        let shortlist = [];

        for (let key in this.objects) {
            let object = this.objects[key];

            if (((facade && object.source_floor_id === sourceFloorId) || (!facade && object.floor_id === sourceFloorId)) &&
                object.target_floor_id === targetFloorId) {
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

    /**
     * Get the closest floor switch marker to a specific floor, on a lat lng on the current floor
     * @param floorId {Number}
     * @param targetFloorId {Number}
     * @param latLng {L.latLng|null}
     * @param facade {Boolean}
     */
    getClosestMarker(floorId, targetFloorId, latLng = null, facade = false) {
        let result = this._findMarkerByTargetFloorId(floorId, targetFloorId, latLng, facade);

        // If not found, try to find it across all objects we have
        if (result === null) {
            for (let key in this.objects) {
                let object = this.objects[key];
                let onSameFloor = object.floor_id === floorId;

                // Only consider those objects that originate from our floor
                if (onSameFloor) {
                    // Find the marker from the target of that marker, to what we're looking for
                    let foundMarkerOnTargetFloor = this._findMarkerByTargetFloorId(object.target_floor_id, targetFloorId, {
                        lat: object.lat,
                        lng: object.lng
                    });

                    if (foundMarkerOnTargetFloor !== null) {
                        result = object;

                        break;
                    }
                }

            }
        }

        return result;
    }
}

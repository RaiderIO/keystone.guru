class KillZonePathMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_KILLZONE_PATH, editable);

        this.title = 'Hide/show killzone paths';
        this.fa_class = 'fa-route';
    }

    /**
     * @inheritDoc
     **/
    _getRawObjects() {
        return getState().getMapContext().getPaths();
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not an KillZonePathMapObjectGroup', this);

        return new KillZonePath(this.manager.map, layer);
    }

    load() {
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not an KillZonePathMapObjectGroup', this);

        let killzoneMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        let floorSwitchMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER);

        /** @type KillZone */
        let previousKillZone = null;
        /** @type {L.latLng} */
        let previousKillZoneCenteroid = null;
        /** @type object */
        let previousKillZoneFloorIds = null;
        /** @type boolean */
        let previousKillZoneOnCurrentFloor = false;

        let currentFloorId = getState().getCurrentFloor().id;

        for (let i = 0; i < killzoneMapObjectGroup.objects.length; i++) {
            let killZone = killzoneMapObjectGroup.objects[i];
            // @TODO centeroid does not take floors into account
            let killZoneCenteroid = killZone.getLayerCenteroid();
            let killZoneFloorIds = killZone.getFloorIds();
            let killZoneOnCurrentFloor = killZoneFloorIds.includes(currentFloorId);

            // Only if we have something to connect..
            if (killZone.enemies.length > 0) {
                // Only if the indices are next to each other
                if (previousKillZone instanceof KillZone &&
                    // And only if one of them is on the same floor as us, otherwise ignore them completely
                    (killZoneOnCurrentFloor || previousKillZoneOnCurrentFloor)) {

                    let centeroidSource = null;
                    let centeroidTarget = null

                    // If both are on the same floor
                    if (killZoneOnCurrentFloor && previousKillZoneOnCurrentFloor) {
                        centeroidSource = previousKillZoneCenteroid;
                        centeroidTarget = killZoneCenteroid;
                    } else {
                        if (killZoneOnCurrentFloor) {
                            centeroidSource = floorSwitchMapObjectGroup.getClosestMarker(currentFloorId, previousKillZoneFloorIds[0], previousKillZoneCenteroid).layer.getLatLng();
                            centeroidTarget = killZoneCenteroid;
                        } else if (previousKillZoneOnCurrentFloor) {
                            centeroidSource = previousKillZoneCenteroid;
                            centeroidTarget = floorSwitchMapObjectGroup.getClosestMarker(currentFloorId, killZoneFloorIds[0], killZoneCenteroid).layer.getLatLng();
                        }
                    }

                    // Draw the paths from the
                    this.createNewPath([{
                        lat: centeroidSource.lat,
                        lng: centeroidSource.lng
                    }, {
                        lat: centeroidTarget.lat,
                        lng: centeroidTarget.lng
                    }], {
                        polyline: {
                            // From red to green, trust me
                            color: pickHexFromHandlers([[0, '#ff0000'], [100, '#00ff00']], (i / killzoneMapObjectGroup.objects.length) * 100)
                        }
                    })
                }

                previousKillZone = killZone;
                previousKillZoneCenteroid = killZoneCenteroid;
                previousKillZoneFloorIds = killZoneFloorIds;
                previousKillZoneOnCurrentFloor = killZoneOnCurrentFloor;
            }
        }


        this._initialized = true;
    }

    /**
     * Creates a new Path based on some vertices and save it to the server.
     * @param vertices {Object}
     * @param options {Object}
     * @returns {Path}
     */
    createNewPath(vertices, options) {
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not an KillZonePathMapObjectGroup', this);

        let path = this._loadMapObject($.extend(true, {}, {
            polyline: {
                color: c.map.polyline.killzonepath.color,
                color_animated: c.map.polyline.killzonepath.colorAnimated,
                weight: c.map.polyline.killzonepath.weight,
                vertices_json: JSON.stringify(vertices)
            }
        }, options));

        this.signal('killzonepath:new', {newPath: path});
        return path;
    }
}
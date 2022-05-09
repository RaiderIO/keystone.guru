class KillZonePathMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_KILLZONE_PATH, editable);

        this.title = 'Hide/show killzone paths';
        this.fa_class = 'fa-route';
    }

    /**
     * @returns {string}
     * @protected
     */
    _getMapPane() {
        if (this.manager.map.options.noUI) {
            // Draw it above everything
            return LEAFLET_PANE_TOOLTIP;
        } else {
            // Draw it below everything
            return LEAFLET_PANE_OVERLAY;
        }
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
    _createLayer(remoteMapObject) {
        return L.polyline(this._restorePoints(remoteMapObject), {pane: this._getMapPane()});
    }

    /**
     * @inheritDoc
     */
    _createMapObject(layer, options = {}) {
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not an KillZonePathMapObjectGroup', this);

        return new KillZonePath(this.manager.map, layer);
    }

    /**
     *
     * @private
     */
    _refresh(killZoneChangedEvent) {
        // Bring all layers we just created to the front
        for (let i = 0; i < this.objects.length; i++) {
            this.setLayerToMapObject(null, this.objects[i]);
            this.objects[i].cleanup();
            this.objects[i].localDelete();
        }

        this.objects = [];

        let killzoneMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        /** @type DungeonFloorSwitchMarkerMapObjectGroup */
        let floorSwitchMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_DUNGEON_FLOOR_SWITCH_MARKER);
        let mapIconMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_MAPICON);

        /** @type KillZone */
        let previousKillZone = null;
        /** @type {L.latLng} */
        let previousKillZoneCenteroid = null;
        /** @type object */
        let previousKillZoneFloorIds = null;
        /** @type boolean */
        let previousKillZoneOnCurrentFloor = false;

        let currentFloor = getState().getCurrentFloor();
        let currentFloorId = currentFloor.id;

        // Check if the current floor has a start marker or not
        let dungeonStartOffset = 0;
        let dungeonStartLatLng = null;

        // Only on the first floor!
        if (currentFloor.index === 1) {
            for (let i = 0; i < mapIconMapObjectGroup.objects.length; i++) {
                let mapIcon = mapIconMapObjectGroup.objects[i];

                // 10 = dungeon start
                if (mapIcon.floor_id === currentFloorId && mapIcon.map_icon_type_id === 10) {
                    dungeonStartOffset++;
                    dungeonStartLatLng = mapIcon.layer.getLatLng();
                    break;
                }
            }
        }

        let sortedObjects = _.sortBy(killzoneMapObjectGroup.objects, 'index');
        for (let i = 0; i < sortedObjects.length; i++) {
            let killZone = sortedObjects[i];

            // @TODO centeroid does not take floors into account
            let killZoneCenteroid = killZone.getLayerCenteroid();
            let killZoneFloorIds = killZone.getFloorIds();
            let killZoneOnCurrentFloor = killZoneFloorIds.includes(currentFloorId);

            // If a pull is empty one way or another (no enemies, or all enemies marked obsolete).
            // If the killzone is on another floor we can continue - we will draw a line to the next floor instead
            if (killZoneCenteroid.lat === 0 && killZoneCenteroid.lng === 0 && killZoneOnCurrentFloor) {
                continue;
            }

            // Only if the indices are next to each other
            if (previousKillZone instanceof KillZone) {
                let polylineColor = pickHexFromHandlers([[0, '#ff0000'], [100, '#00ff00']],
                    ((i + dungeonStartOffset)) / (sortedObjects.length + dungeonStartOffset) * 100
                );

                // And only if one of them is on the same floor as us, otherwise ignore them completely
                if (killZoneOnCurrentFloor || previousKillZoneOnCurrentFloor) {

                    let centeroidSource = null;
                    let centeroidTarget = null

                    // If both are on the same floor
                    if (killZoneOnCurrentFloor && previousKillZoneOnCurrentFloor) {
                        centeroidSource = previousKillZoneCenteroid;
                        centeroidTarget = killZoneCenteroid;
                    } else if (killZoneOnCurrentFloor) {
                        let closestMarker = floorSwitchMapObjectGroup.getClosestMarker(currentFloorId, previousKillZoneFloorIds[0], previousKillZoneCenteroid);
                        // It can be null if someone skips a floor and there's no direct connection from previous to current floor
                        if (closestMarker !== null) {
                            centeroidSource = closestMarker.layer.getLatLng();
                            centeroidTarget = killZoneCenteroid;
                        }
                    } else if (previousKillZoneOnCurrentFloor) {

                        let closestMarker = floorSwitchMapObjectGroup.getClosestMarker(currentFloorId, killZoneFloorIds[0], killZoneCenteroid);
                        // It can be null if someone skips a floor and there's no direct connection from previous to current floor
                        if (closestMarker !== null) {
                            centeroidSource = previousKillZoneCenteroid;
                            centeroidTarget = closestMarker.layer.getLatLng();
                        }
                    }

                    if (centeroidSource !== null && centeroidTarget !== null) {
                        // Draw the paths from the
                        this.createNewPath([{
                            lat: centeroidSource.lat,
                            lng: centeroidSource.lng
                        }, {
                            lat: centeroidTarget.lat,
                            lng: centeroidTarget.lng
                        }], {
                            polyline: {
                                // From red to green, add one to compensate for the dungeon start to
                                color: polylineColor
                            }
                        });
                    } else {
                        // The current killzone does not have any enemies assigned to it - skip this pull and keep the
                        // previous killzone the same, so we draw a line from say pull 2 to pull 4 if pull 3 is empty
                        continue;
                    }
                } else {
                    // Current killzone is not on this floor, previous killzone is also not on this floor.
                    let closestMarkerToKillZoneFloor = floorSwitchMapObjectGroup.getClosestMarker(currentFloorId, killZoneFloorIds[0], killZoneCenteroid);
                    let closestMarkerToPreviousKillZoneFloor = floorSwitchMapObjectGroup.getClosestMarker(currentFloorId, previousKillZoneFloorIds[0], previousKillZoneCenteroid);

                    if (closestMarkerToKillZoneFloor !== null && closestMarkerToPreviousKillZoneFloor) {
                        this.createNewPath([{
                            lat: closestMarkerToKillZoneFloor.lat,
                            lng: closestMarkerToKillZoneFloor.lng
                        }, {
                            lat: closestMarkerToPreviousKillZoneFloor.lat,
                            lng: closestMarkerToPreviousKillZoneFloor.lng
                        }], {
                            polyline: {
                                color: polylineColor
                            }
                        });
                    }
                }
            }

            // If we should draw a line from the dungeon start to the first pull, but only if we're processing the first pull
            if (previousKillZone === null && dungeonStartLatLng !== null && i === 0) {
                this.createNewPath([{
                    lat: dungeonStartLatLng.lat,
                    lng: dungeonStartLatLng.lng
                }, {
                    lat: killZoneCenteroid.lat,
                    lng: killZoneCenteroid.lng
                }], {
                    polyline: {
                        // Always red
                        color: '#ff0000'
                    }
                });
            }

            previousKillZone = killZone;
            previousKillZoneCenteroid = killZoneCenteroid;
            previousKillZoneFloorIds = killZoneFloorIds;
            previousKillZoneOnCurrentFloor = killZoneOnCurrentFloor;
        }

        // Bring all layers we just created to the front
        for (let i = 0; i < this.objects.length; i++) {
            let mapObject = this.objects[i];
            // if () {
            //     console.log('bringing to front');
            //     this.layerGroup.bringToFront();
            //     mapObject.layer.bringToFront();
            // } else {
            //     console.log('bringing to back');
            //     this.layerGroup.bringToBack();
            //     mapObject.layer.bringToBack();
            // }
            this.setMapObjectVisibility(mapObject, true);
        }
    }

    load() {
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not an KillZonePathMapObjectGroup', this);

        let killzoneMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        killzoneMapObjectGroup.register('killzone:changed', this, this._refresh.bind(this));

        this._refresh();
        this._initialized = true;
    }

    update() {
        super.update();

        this._refresh();
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
                color_animated: null,
                weight: c.map.polyline.killzonepath.weight,
                vertices_json: JSON.stringify(vertices)
            }
        }, options));

        this.signal('killzonepath:new', {newPath: path});
        return path;
    }
}

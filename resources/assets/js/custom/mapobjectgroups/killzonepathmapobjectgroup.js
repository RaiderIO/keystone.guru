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
        /** @type object */
        let previousKillZoneCenteroid = null;
        /** @type object */
        let previousKillZoneFloorIds = null;

        let currentFloorId = getState().getCurrentFloor().id;

        for (let i = 0; i < killzoneMapObjectGroup.objects.length; i++) {
            let killZone = killzoneMapObjectGroup.objects[i];
            let killZoneCenteroid = killZone.getLayerCenteroid();
            let killZoneFloorIds = killZone.getFloorIds();

            // Only if we have something to connect..
            if (killZone.enemies.length > 0) {
                if (previousKillZone instanceof KillZone &&
                    // Same floor only (for now)
                    killZoneFloorIds.includes(currentFloorId) && previousKillZoneFloorIds.includes(currentFloorId) &&
                    // Only if the indices are next to eachother
                    (killZone.index - previousKillZone.index) === 1
                ) {

                    let floorDifference = _.difference(killZone.getFloorIds(), previousKillZone.getFloorIds());

                    // If the two killzones are on the same floor
                    if (floorDifference.length === 0) {
                        console.log(killZone.index, killZoneCenteroid, previousKillZone.index, previousKillZoneCenteroid);

                        this.createNewPath([{
                            lat: killZoneCenteroid.lat,
                            lng: killZoneCenteroid.lng
                        }, {
                            lat: previousKillZoneCenteroid.lat,
                            lng: previousKillZoneCenteroid.lng
                        }])
                    }
                }

                previousKillZone = killZone;
                previousKillZoneCenteroid = killZoneCenteroid;
                previousKillZoneFloorIds = killZoneFloorIds;
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

        let path = this._loadMapObject($.extend({}, {
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
class KillZonePathMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_KILLZONE_PATH, editable);

        this.fa_class = 'fa-route';

        /** @type {Array} Server-computed path segments, set on load and updated on killzone:changed */
        this._killZonePaths = [];
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
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not a KillZonePathMapObjectGroup', this);

        return new KillZonePath(this.manager.map, layer);
    }

    /**
     * @param {string} routeKey Public key of the dungeon route
     */
    fetchAndRefresh(routeKey) {
        $.ajax({
            type: 'GET',
            url: `/ajax/${routeKey}/killzone/paths`,
            success: (response) => {
                this.refresh(response.killzone_paths ?? null);
            },
        });
    }

    /**
     * @param {Array}  [killZonePaths] Updated path segments from server
     */
    refresh(killZonePaths = null) {
        console.warn(`KillZonePathMapObjectGroup::refresh:`, killZonePaths);

        // Set the new paths if provided
        if (killZonePaths !== null) {
            this._killZonePaths = killZonePaths;
        }

        this.clear();
        this.objects = [];
        this.currentId = 1;

        let currentFloorId = getState().getCurrentFloor().id;
        let totalSegments = this._killZonePaths.length;

        for (let i = 0; i < totalSegments; i++) {
            let segment = this._killZonePaths[i];

            // Keep only the points that belong to the current floor, in their original order.
            // Because pathfinding always transitions through floor-switch markers, consecutive
            // same-floor points will always be contiguous in the segment array.
            let floorPoints = segment.filter(p => p.floor_id === currentFloorId);

            if (floorPoints.length < 2) {
                continue;
            }

            let progress = totalSegments <= 1 ? 100 : (i / (totalSegments - 1)) * 100;
            let color = pickHexFromHandlers(c.map.killZonePath.defaultHandlers, progress);

            this.createNewPath(floorPoints, {polyline: {color: color}});
        }

        for (let key in this.objects) {
            this.setMapObjectVisibility(this.objects[key], true);
        }
    }

    load() {
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not a KillZonePathMapObjectGroup', this);

        this._killZonePaths = getState().getMapContext().getKillZonePaths();

        let killZoneMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        // let killZoneChangedFn = (event) => this.refresh(event.data.object.killzone_paths !== undefined ? event.data.object.killzone_paths : null)

        let self = this;
        let killZoneChangedFn = function (event) {
            console.warn(`KillZonePathMapObjectGroup received killzone event:`, event);
            self.refresh(event.data.object.killzone_paths !== undefined ? event.data.object.killzone_paths : null)
        };

        // Deleting killzones retrieves the raw json, handle it slightly differently
        let killZoneDeletedFn = function (event) {
            console.warn(`KillZonePathMapObjectGroup received killzone deleted event:`, event);
            self.refresh(event.data.json.killzone_paths !== undefined ? event.data.json.killzone_paths : null);
        }

        killZoneMapObjectGroup.register('save:success', this, killZoneChangedFn);
        killZoneMapObjectGroup.register('delete:success', this, killZoneDeletedFn);

        this.refresh();
        this._initialized = true;
    }

    update() {
        super.update();

        this.refresh();
    }

    /**
     * Creates a new Path based on some vertices and save it to the server.
     * @param vertices {Object}
     * @param options {Object}
     * @returns {Path}
     */
    createNewPath(vertices, options) {
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not a KillZonePathMapObjectGroup', this);

        let path = this.loadMapObject($.extend(true, {}, {
            id: this.currentId++,
            polyline: {
                color: c.map.polyline.killzonepath.color,
                color_animated: null,
                weight: c.map.polyline.killzonepath.weight,
                vertices_json: JSON.stringify(vertices),
            }
        }, options));

        this.signal('killzonepath:new', {newPath: path});
        return path;
    }
}

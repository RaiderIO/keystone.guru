class KillZonePathMapObjectGroup extends PolylineMapObjectGroup {
    constructor(manager, editable) {
        super(manager, MAP_OBJECT_GROUP_KILLZONE_PATH, editable);

        this.fa_class = 'fa-route';

        /** @type {Array} Server-computed path segments, set on load and updated on killzone:changed */
        this._killZonePaths = [];

        /** @type {?Array} The {points, color, path} descriptors currently drawn, or null before the first draw */
        this._renderedSegments = null;
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
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not a KillZonePathMapObjectGroup', this);

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
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not a KillZonePathMapObjectGroup', this);

        // Set the new paths if provided
        if (killZonePaths !== null) {
            this._killZonePaths = killZonePaths;
        }

        let segments = this._buildSegments();

        // Saving one pull returns the paths of the whole route, but only the segments touching that
        // pull moved; recreating the rest costs a removeLayer/addLayer pair plus a fresh set of
        // Leaflet handler registrations per polyline. A floor change or a path weight change comes
        // in without paths and does need every polyline rebuilt.
        if (killZonePaths !== null && this._renderedSegments !== null && this._renderedSegments.length === segments.length) {
            for (let i = 0; i < segments.length; i++) {
                if (this._areSegmentsEqual(this._renderedSegments[i], segments[i])) {
                    continue;
                }

                this._destroySegment(this._renderedSegments[i]);
                this._renderedSegments[i] = this._createSegment(segments[i]);
            }

            return;
        }

        this.clear();
        this.objects = [];
        this.currentId = 1;

        this._renderedSegments = segments.map((segment) => this._createSegment(segment));
    }

    /**
     * Builds the {points, color} descriptor of every path segment that is drawable on the current floor.
     *
     * @returns {Object[]}
     * @private
     */
    _buildSegments() {
        let currentFloorId = getState().getCurrentFloor().id;
        let totalSegments = this._killZonePaths.length;
        let segments = [];

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

            segments.push({
                points: floorPoints,
                color: pickHexFromHandlers(c.map.killZonePath.defaultHandlers, progress),
            });
        }

        return segments;
    }

    /**
     * @param {Object} segment
     * @returns {Object} The segment descriptor, carrying the path it was drawn as
     * @private
     */
    _createSegment(segment) {
        let path = this.createNewPath(segment.points, {polyline: {color: segment.color}});
        this.setMapObjectVisibility(path, true);

        return {points: segment.points, color: segment.color, path: path};
    }

    /**
     * @param {Object} renderedSegment
     * @private
     */
    _destroySegment(renderedSegment) {
        this.setLayerToMapObject(null, renderedSegment.path);
        renderedSegment.path.cleanup();
        // Removes it from this.objects through the group's own object:deleted handler.
        renderedSegment.path.localDelete();
    }

    /**
     * @param {Object} a
     * @param {Object} b
     * @returns {boolean}
     * @private
     */
    _areSegmentsEqual(a, b) {
        if (a.color !== b.color || a.points.length !== b.points.length) {
            return false;
        }

        for (let i = 0; i < a.points.length; i++) {
            if (a.points[i].floor_id !== b.points[i].floor_id ||
                a.points[i].lat !== b.points[i].lat ||
                a.points[i].lng !== b.points[i].lng) {
                return false;
            }
        }

        return true;
    }

    load() {
        console.assert(this instanceof KillZonePathMapObjectGroup, 'this is not a KillZonePathMapObjectGroup', this);

        this._killZonePaths = getState().getMapContext().getKillZonePaths();

        let killZoneMapObjectGroup = this.manager.getByName(MAP_OBJECT_GROUP_KILLZONE);
        // let killZoneChangedFn = (event) => this.refresh(event.data.object.killzone_paths !== undefined ? event.data.object.killzone_paths : null)

        let self = this;
        let killZoneChangedFn = function (event) {
            self.refresh(event.data.object.killzone_paths !== undefined ? event.data.object.killzone_paths : null)
        };

        // Deleting killzones retrieves the raw json, handle it slightly differently
        let killZoneDeletedFn = function (event) {
            self.refresh(event.data.json.killzone_paths !== undefined ? event.data.json.killzone_paths : null);
        }
1
        killZoneMapObjectGroup.register('save:success', this, killZoneChangedFn);
        killZoneMapObjectGroup.register('delete:success', this, killZoneDeletedFn);

        getState().register('killzonepathweight:changed', this, () => this.refresh());

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

        let weight = getState().getKillZonePathWeight();
        // Thicken the pull-connection lines by the multiplier passed from PHP (e.g. small thumbnail renders)
        // so the miniature still reads as a route shape. Null/absent keeps the normal width.
        let killZonePathWeightMultiplier = this.manager.map.options.killZonePathWeightMultiplier;
        if (killZonePathWeightMultiplier) {
            weight *= killZonePathWeightMultiplier;
        }

        let path = this.loadMapObject($.extend(true, {}, {
            id: this.currentId++,
            polyline: {
                color: c.map.polyline.killzonepath.color,
                color_animated: null,
                weight: weight,
                vertices_json: JSON.stringify(vertices),
            }
        }, options));

        this.signal('killzonepath:new', {newPath: path});
        return path;
    }
}

// Guarded export for the test runner (Vitest). This is a no-op in the browser,
// where `module` is undefined, so it does not affect the concatenated bundle.
if (typeof module !== 'undefined' && module.exports) {
    module.exports = {KillZonePathMapObjectGroup};
}
